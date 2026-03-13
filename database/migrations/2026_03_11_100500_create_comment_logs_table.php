<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('comment_campaigns')->cascadeOnDelete();
            $table->string('group_id');
            $table->string('group_name');
            $table->string('post_url')->nullable();
            $table->text('comment_content');
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('commented_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_logs');
    }
};
