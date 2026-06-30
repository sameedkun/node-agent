<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class NodeInstallCommand extends Command
{
    protected $signature = 'node:install';

    protected $description = 'Initialize the VPN node with a permanent identity';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('.env file not found. Copy .env.example to .env and try again.');

            return self::FAILURE;
        }

        if (! is_writable($envPath)) {
            $this->error('.env file is not writable. Check file permissions.');

            return self::FAILURE;
        }

        $uuid   = $this->resolveOrGenerate($envPath, 'NODE_UUID', fn () => (string) Str::uuid());
        $secret = $this->resolveOrGenerate($envPath, 'NODE_SECRET', fn () => bin2hex(random_bytes(32)));

        $this->info('Node installed successfully.');
        $this->newLine();
        $this->line('NODE_UUID='   . $uuid);
        $this->line('NODE_SECRET=' . $secret);

        return self::SUCCESS;
    }

    private function resolveOrGenerate(string $envPath, string $key, \Closure $generator): string
    {
        $existing = $this->readEnvValue($envPath, $key);

        if ($existing !== null) {
            return $existing;
        }

        $value = $generator();

        $this->writeEnvValue($envPath, $key, $value);

        return $value;
    }

    /**
     * Returns the value if the key is present and non-empty, null otherwise.
     */
    private function readEnvValue(string $envPath, string $key): ?string
    {
        $prefix = $key . '=';

        foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
            if (str_starts_with(ltrim($line), $prefix)) {
                $value = substr(ltrim($line), strlen($prefix));

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function writeEnvValue(string $envPath, string $key, string $value): void
    {
        $contents = file_get_contents($envPath);
        $pattern  = '/^(' . preg_quote($key, '/') . '=).*$/m';

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, '$1' . $value, $contents);
            file_put_contents($envPath, $contents);
        } else {
            file_put_contents($envPath, PHP_EOL . $key . '=' . $value, FILE_APPEND);
        }
    }
}
