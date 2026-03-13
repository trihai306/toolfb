<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('browser_profiles', function (Blueprint $table) {
            // Extended browser info
            $table->string('platform')->nullable()->after('cookies_count');
            $table->integer('hardware_concurrency')->nullable()->after('platform');
            $table->float('device_memory')->nullable()->after('hardware_concurrency');
            $table->string('webgl_renderer')->nullable()->after('device_memory');
            $table->boolean('do_not_track')->nullable()->after('webgl_renderer');
            $table->string('connection_type')->nullable()->after('do_not_track');
            $table->boolean('touch_support')->nullable()->after('connection_type');

            // Extended Facebook profile info
            $table->string('facebook_profile_url')->nullable()->after('facebook_avatar');
            $table->integer('facebook_friends_count')->nullable()->after('facebook_profile_url');
            $table->string('facebook_cover_photo', 1000)->nullable()->after('facebook_friends_count');
            $table->boolean('facebook_verified')->nullable()->after('facebook_cover_photo');
            $table->string('facebook_email')->nullable()->after('facebook_verified');
            $table->string('facebook_join_date')->nullable()->after('facebook_email');
            $table->text('facebook_bio')->nullable()->after('facebook_join_date');
        });
    }

    public function down(): void
    {
        Schema::table('browser_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'platform',
                'hardware_concurrency',
                'device_memory',
                'webgl_renderer',
                'do_not_track',
                'connection_type',
                'touch_support',
                'facebook_profile_url',
                'facebook_friends_count',
                'facebook_cover_photo',
                'facebook_verified',
                'facebook_email',
                'facebook_join_date',
                'facebook_bio',
            ]);
        });
    }
};
