<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('browser_profiles', function (Blueprint $table) {
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_info')->nullable();
            $table->string('screen_resolution')->nullable();
            $table->string('language')->nullable();
            $table->string('timezone')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('cookies_count')->nullable();
            $table->json('browser_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('browser_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'browser_name',
                'browser_version',
                'os_info',
                'screen_resolution',
                'language',
                'timezone',
                'ip_address',
                'cookies_count',
                'browser_config',
            ]);
        });
    }
};
