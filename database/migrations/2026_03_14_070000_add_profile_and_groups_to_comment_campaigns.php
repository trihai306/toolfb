<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comment_campaigns', function (Blueprint $table) {
            $table->foreignId('browser_profile_id')->nullable()->after('id')->constrained('browser_profiles')->nullOnDelete();
            $table->json('group_ids')->nullable()->after('groups');
        });
    }

    public function down(): void
    {
        Schema::table('comment_campaigns', function (Blueprint $table) {
            $table->dropForeign(['browser_profile_id']);
            $table->dropColumn(['browser_profile_id', 'group_ids']);
        });
    }
};
