<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('browser_profile_id')->constrained()->cascadeOnDelete();
            $table->string('group_id');          // Facebook group ID or slug
            $table->string('name');
            $table->string('category')->default('general');
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->unique(['browser_profile_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_groups');
    }
};
