<?php

declare(strict_types=1);

namespace App\Drivers\WireGuard;

use App\Drivers\WireGuard\Contracts\WireGuardConfigRepositoryInterface;
use App\Drivers\WireGuard\Exceptions\WireGuardException;

final class WireGuardConfigRepository implements WireGuardConfigRepositoryInterface
{
    /**
     * Prefix used to tag every managed [Peer] block.
     * Written as a comment on the line immediately before [Peer].
     */
    private const CONFIG_ID_COMMENT = '# ConfigID: ';

    public function __construct(
        private readonly string $configPath = '/etc/wireguard/wg0.conf',
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public function exists(): bool
    {
        return is_file($this->configPath);
    }

    /**
     * Returns the raw contents of wg0.conf, or an empty string when the file
     * does not exist yet.
     *
     * @throws WireGuardException  On read failure.
     */
    public function read(): string
    {
        if (! $this->exists()) {
            return '';
        }

        $contents = file_get_contents($this->configPath);

        if ($contents === false) {
            throw new WireGuardException(
                "Failed to read WireGuard config: {$this->configPath}"
            );
        }

        return $contents;
    }

    /**
     * Appends a new tagged [Peer] block to the configuration.
     *
     * The block is tagged with a ConfigID comment so it can be removed later:
     *
     *   # ConfigID: <configId>
     *   [Peer]
     *   PublicKey = ...
     *   ...
     *
     * @throws WireGuardException  On lock or write failure.
     */
    public function appendPeer(string $configId, string $peerBlock): void
    {
        $this->withLock(function () use ($configId, $peerBlock): void {
            $current  = $this->read();
            $rtrimmed = rtrim($current);

            $separator = $rtrimmed !== '' ? "\n\n" : '';
            $tagged    = self::CONFIG_ID_COMMENT . $configId . "\n" . $peerBlock;

            $this->writeAtomically($rtrimmed . $separator . $tagged);
        });
    }

    /**
     * Removes the [Peer] block tagged with the given configId.
     *
     * Returns true when the peer was found and removed, false when no peer
     * with that configId exists. Does not throw when the peer is absent —
     * the caller decides whether that is an error.
     *
     * @throws WireGuardException  On lock or write failure.
     */
    public function removePeer(string $configId): bool
    {
        return (bool) $this->withLock(function () use ($configId): bool {
            $current = $this->read();

            if ($current === '') {
                return false;
            }

            $updated = $this->excisePeer($current, $configId);

            if ($updated === $current) {
                return false;
            }

            $this->writeAtomically($updated);

            return true;
        });
    }

    // -------------------------------------------------------------------------
    // Peer removal logic
    // -------------------------------------------------------------------------

    /**
     * Returns a copy of $contents with the [Peer] block tagged by $configId
     * removed, including any blank lines that preceded the block.
     *
     * Returns $contents unchanged when the configId is not found.
     */
    private function excisePeer(string $contents, string $configId): string
    {
        $targetComment = self::CONFIG_ID_COMMENT . $configId;
        $lines         = explode("\n", $contents);
        $commentIndex  = null;

        foreach ($lines as $i => $line) {
            if (trim($line) === $targetComment) {
                $commentIndex = $i;
                break;
            }
        }

        if ($commentIndex === null) {
            return $contents;
        }

        // Walk back past blank lines that precede the comment so the
        // surrounding blocks keep clean separation after removal.
        $startIndex = $commentIndex;
        while ($startIndex > 0 && trim($lines[$startIndex - 1]) === '') {
            $startIndex--;
        }

        // Walk forward past the [Peer] block until a blank line or end of file.
        // WireGuard sections contain no internal blank lines, so the first blank
        // line after the comment marks the boundary of this peer block.
        $endIndex = $commentIndex + 1;
        $total    = count($lines);

        while ($endIndex < $total) {
            if (trim($lines[$endIndex]) === '') {
                break;
            }
            $endIndex++;
        }

        array_splice($lines, $startIndex, $endIndex - $startIndex);

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // File I/O
    // -------------------------------------------------------------------------

    /**
     * Writes $contents atomically by creating a temporary file in the same
     * directory and renaming it over the target.
     *
     * The rename(2) syscall is atomic on POSIX filesystems when both paths are
     * on the same mount point, which tempnam() in the same directory guarantees.
     *
     * Permissions are set to 0600 before the rename because wg0.conf contains
     * private keys and must never be world-readable.
     *
     * @throws WireGuardException
     */
    private function writeAtomically(string $contents): void
    {
        $dir = dirname($this->configPath);
        $tmp = tempnam($dir, 'wg_');

        if ($tmp === false) {
            throw new WireGuardException(
                "Cannot create temporary file in: {$dir}"
            );
        }

        try {
            if (file_put_contents($tmp, $contents) === false) {
                throw new WireGuardException(
                    "Failed to write temporary config file: {$tmp}"
                );
            }

            chmod($tmp, 0600);

            if (! rename($tmp, $this->configPath)) {
                throw new WireGuardException(
                    "Failed to atomically replace: {$this->configPath}"
                );
            }
        } catch (\Throwable $e) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }

            throw $e instanceof WireGuardException
                ? $e
                : new WireGuardException(
                    "Unexpected error while writing config: {$e->getMessage()}",
                    previous: $e,
                );
        }
    }

    /**
     * Acquires an exclusive flock on a dedicated lock file for the duration of
     * $operation, then releases it regardless of outcome.
     *
     * A separate lock file (not wg0.conf itself) is used because rename()
     * replaces the inode of wg0.conf — a lock held on the old file descriptor
     * would not transfer to the newly written file.
     *
     * @throws WireGuardException  If the lock cannot be acquired.
     */
    private function withLock(\Closure $operation): mixed
    {
        $lockPath = $this->configPath . '.lock';
        $handle   = fopen($lockPath, 'c');

        if ($handle === false) {
            throw new WireGuardException(
                "Cannot open lock file: {$lockPath}"
            );
        }

        if (! flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new WireGuardException(
                "Cannot acquire exclusive lock on: {$lockPath}"
            );
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
