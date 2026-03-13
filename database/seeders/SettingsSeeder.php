<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // === Posting ===
            [
                'group' => 'posting',
                'key' => 'min_delay',
                'value' => '30',
                'type' => 'integer',
                'label' => 'Delay tối thiểu (giây)',
                'description' => 'Thời gian chờ tối thiểu giữa các bài đăng',
            ],
            [
                'group' => 'posting',
                'key' => 'max_delay',
                'value' => '120',
                'type' => 'integer',
                'label' => 'Delay tối đa (giây)',
                'description' => 'Thời gian chờ tối đa giữa các bài đăng',
            ],
            [
                'group' => 'posting',
                'key' => 'spin_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Spin nội dung',
                'description' => 'Bật/tắt tính năng xoay vòng nội dung bài viết',
            ],
            [
                'group' => 'posting',
                'key' => 'notify_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Thông báo',
                'description' => 'Bật/tắt thông báo khi hoàn thành đăng bài',
            ],
            [
                'group' => 'posting',
                'key' => 'max_posts_per_day',
                'value' => '50',
                'type' => 'integer',
                'label' => 'Giới hạn bài/ngày',
                'description' => 'Số bài đăng tối đa mỗi ngày để tránh bị khóa tài khoản',
            ],

            // === Commenting ===
            [
                'group' => 'commenting',
                'key' => 'comments_per_group',
                'value' => '3',
                'type' => 'integer',
                'label' => 'Số comment/nhóm',
                'description' => 'Số lượng comment cho mỗi nhóm',
            ],
            [
                'group' => 'commenting',
                'key' => 'scroll_depth',
                'value' => '5',
                'type' => 'integer',
                'label' => 'Cuộn sâu (bài)',
                'description' => 'Số bài cần cuộn sâu để tìm bài viết cần comment',
            ],
            [
                'group' => 'commenting',
                'key' => 'comment_min_delay',
                'value' => '15',
                'type' => 'integer',
                'label' => 'Delay comment tối thiểu (giây)',
                'description' => 'Thời gian chờ tối thiểu giữa các comment',
            ],
            [
                'group' => 'commenting',
                'key' => 'comment_max_delay',
                'value' => '45',
                'type' => 'integer',
                'label' => 'Delay comment tối đa (giây)',
                'description' => 'Thời gian chờ tối đa giữa các comment',
            ],

            // === AI ===
            [
                'group' => 'ai',
                'key' => 'ai_api_key',
                'value' => '',
                'type' => 'string',
                'label' => 'Gemini API Key',
                'description' => 'API Key để sử dụng Google Gemini AI',
            ],
            [
                'group' => 'ai',
                'key' => 'ai_model',
                'value' => 'gemini-2.0-flash',
                'type' => 'string',
                'label' => 'Model AI',
                'description' => 'Tên model Gemini sử dụng',
            ],
            [
                'group' => 'ai',
                'key' => 'ai_tone',
                'value' => 'thân thiện',
                'type' => 'string',
                'label' => 'Giọng văn AI',
                'description' => 'Tone viết khi AI sinh nội dung',
            ],
            [
                'group' => 'ai',
                'key' => 'ai_auto_image',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Tự tạo ảnh',
                'description' => 'Tự động generate ảnh kèm theo bài viết',
            ],

            // === System ===
            [
                'group' => 'system',
                'key' => 'default_extension_id',
                'value' => '',
                'type' => 'string',
                'label' => 'Extension ID mặc định',
                'description' => 'UUID của extension Chrome mặc định',
            ],
            [
                'group' => 'system',
                'key' => 'auto_sync_settings',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Tự đồng bộ',
                'description' => 'Tự động đồng bộ settings khi extension kết nối',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
