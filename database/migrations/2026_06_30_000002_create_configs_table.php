<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configs', function (Blueprint $table): void {
            $table->id();
            $table->string('config_id')->unique();
            $table->string('protocol');
            $table->string('identifier');
            $table->string('user_id');
            $table->string('device_id');
            $table->json('driver_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};
