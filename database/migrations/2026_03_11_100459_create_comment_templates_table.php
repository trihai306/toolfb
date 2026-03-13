<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content'); // Comment text with spin syntax
            $table->json('images')->nullable(); // Array of image URLs
            $table->json('tags')->nullable(); // Categorization tags
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_templates');
    }
};
