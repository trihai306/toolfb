<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['draft', 'running', 'paused', 'completed', 'failed'])->default('draft');
            $table->text('content'); // Comment content with spin syntax support
            $table->json('images')->nullable(); // Array of image URLs/paths
            $table->json('groups'); // Array of {groupId, name}
            $table->json('settings')->nullable(); // {commentsPerGroup, minDelay, maxDelay, scrollDepth}
            $table->json('stats')->nullable(); // {total, success, failed, skipped}
            $table->string('extension_id')->nullable(); // Target extension UUID
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_campaigns');
    }
};
