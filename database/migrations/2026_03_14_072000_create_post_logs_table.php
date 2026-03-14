<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('browser_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('group_id')->index();
            $table->string('group_name')->nullable();
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->text('content_preview')->nullable();
            $table->text('error')->nullable();
            $table->string('post_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_logs');
    }
};
