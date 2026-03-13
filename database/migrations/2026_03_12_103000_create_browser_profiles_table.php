<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('browser_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Profile display name
            $table->string('api_token', 64)->unique();       // SHA256 hashed token for API auth
            $table->string('extension_id')->nullable();      // Chrome extension UUID (auto-set on connect)
            $table->string('facebook_name')->nullable();     // FB account name
            $table->string('facebook_uid')->nullable();      // FB user ID
            $table->string('user_agent')->nullable();        // Browser user-agent
            $table->string('proxy')->nullable();             // Proxy config (ip:port:user:pass)
            $table->enum('status', ['online', 'offline', 'banned'])->default('offline');
            $table->text('notes')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_profiles');
    }
};
