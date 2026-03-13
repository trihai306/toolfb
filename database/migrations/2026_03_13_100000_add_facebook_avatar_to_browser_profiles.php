<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('browser_profiles', function (Blueprint $table) {
            $table->string('facebook_avatar')->nullable()->after('facebook_uid');
        });
    }

    public function down(): void
    {
        Schema::table('browser_profiles', function (Blueprint $table) {
            $table->dropColumn('facebook_avatar');
        });
    }
};
