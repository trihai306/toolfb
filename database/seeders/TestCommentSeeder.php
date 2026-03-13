<?php

namespace Database\Seeders;

use App\Models\CommentCampaign;
use App\Models\CommentTemplate;
use App\Models\CommentLog;
use Illuminate\Database\Seeder;

class TestCommentSeeder extends Seeder
{
    public function run(): void
    {
        // === Comment Templates ===
        $templates = [
            [
                'name' => 'Chúc mừng',
                'content' => '{spin|Chúc mừng bạn nhé!|Wow, tuyệt vời quá!|Quá đỉnh luôn!|Chúc mừng chúc mừng 🎉}',
                'tags' => ['chúc mừng', 'tích cực'],
                'usage_count' => 15,
            ],
            [
                'name' => 'Hỏi giá',
                'content' => '{spin|Cho em hỏi giá với ạ|Giá bao nhiêu vậy bạn?|Inbox giá giúp em với|Bạn ơi cho em xin giá ạ}',
                'tags' => ['mua bán', 'hỏi giá'],
                'usage_count' => 42,
            ],
            [
                'name' => 'Cảm ơn chia sẻ',
                'content' => '{spin|Cảm ơn bạn đã chia sẻ!|Bài viết hay quá, cảm ơn bạn|Thông tin hữu ích, thanks bạn!|Hay quá, cảm ơn bạn nhé 👍}',
                'tags' => ['cảm ơn', 'chia sẻ'],
                'usage_count' => 28,
            ],
            [
                'name' => 'Quan tâm sản phẩm',
                'content' => '{spin|Em quan tâm ạ, inbox em với|Mình rất thích sản phẩm này|Cho mình xin thêm thông tin với|Sản phẩm đẹp quá, mình muốn mua}',
                'tags' => ['mua bán', 'quan tâm'],
                'usage_count' => 35,
            ],
            [
                'name' => 'Review tích cực',
                'content' => '{spin|Mình đã dùng rồi, rất tốt!|Sản phẩm chất lượng, recommend!|Dùng thử rồi, 10 điểm không có nhưng|Quá ok luôn, mọi người nên thử}',
                'tags' => ['review', 'tích cực'],
                'usage_count' => 20,
            ],
        ];

        foreach ($templates as $template) {
            CommentTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        // === Comment Campaigns ===
        $groups = [
            ['groupId' => 'hoibantre', 'name' => 'Hội bạn trẻ Hà Nội'],
            ['groupId' => 'muabannhanh', 'name' => 'Mua bán nhanh HN'],
            ['groupId' => 'reviewhanoi', 'name' => 'Review Hà Nội'],
            ['groupId' => 'congdongkhoinghiep', 'name' => 'Cộng đồng khởi nghiệp VN'],
            ['groupId' => 'techvietnam', 'name' => 'Tech Vietnam'],
        ];

        // Campaign 1: Đang chạy
        $campaign1 = CommentCampaign::updateOrCreate(
            ['name' => '[Test] Comment dạo - Chúc mừng'],
            [
                'status' => 'running',
                'content' => '{spin|Chúc mừng bạn nhé!|Wow, tuyệt vời quá!|Quá đỉnh luôn!}',
                'groups' => array_slice($groups, 0, 3),
                'settings' => [
                    'commentsPerGroup' => '3',
                    'minDelay' => '15',
                    'maxDelay' => '45',
                    'scrollDepth' => '5',
                ],
                'started_at' => now()->subMinutes(30),
            ]
        );

        // Campaign 2: Nháp
        $campaign2 = CommentCampaign::updateOrCreate(
            ['name' => '[Test] Comment dạo - Hỏi giá'],
            [
                'status' => 'draft',
                'content' => '{spin|Cho em hỏi giá với ạ|Giá bao nhiêu vậy bạn?|Inbox giá giúp em với}',
                'groups' => array_slice($groups, 1, 2),
                'settings' => [
                    'commentsPerGroup' => '5',
                    'minDelay' => '20',
                    'maxDelay' => '60',
                    'scrollDepth' => '10',
                ],
            ]
        );

        // Campaign 3: Hoàn thành
        $campaign3 = CommentCampaign::updateOrCreate(
            ['name' => '[Test] Comment dạo - Review sản phẩm'],
            [
                'status' => 'completed',
                'content' => '{spin|Mình đã dùng rồi, rất tốt!|Sản phẩm chất lượng!|10 điểm không có nhưng}',
                'groups' => $groups,
                'settings' => [
                    'commentsPerGroup' => '2',
                    'minDelay' => '10',
                    'maxDelay' => '30',
                    'scrollDepth' => '3',
                ],
                'stats' => ['total' => 10, 'success' => 8, 'failed' => 1, 'skipped' => 1],
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHour(),
            ]
        );

        // === Comment Logs (for campaign 3) ===
        $logStatuses = ['success', 'success', 'success', 'failed', 'success', 'success', 'skipped', 'success', 'success', 'success'];
        $errorMessages = [
            'failed' => 'Không tìm thấy nút comment',
            'skipped' => 'Bài viết đã comment trước đó',
        ];

        foreach ($logStatuses as $i => $status) {
            CommentLog::create([
                'campaign_id' => $campaign3->id,
                'group_id' => $groups[$i % count($groups)]['groupId'],
                'group_name' => $groups[$i % count($groups)]['name'],
                'post_url' => "https://facebook.com/groups/{$groups[$i % count($groups)]['groupId']}/posts/" . (1000 + $i),
                'comment_content' => $status === 'success'
                    ? ['Mình đã dùng rồi, rất tốt!', 'Sản phẩm chất lượng!', '10 điểm không có nhưng'][array_rand(['Mình đã dùng rồi, rất tốt!', 'Sản phẩm chất lượng!', '10 điểm không có nhưng'])]
                    : '',
                'status' => $status,
                'error_message' => $errorMessages[$status] ?? null,
                'commented_at' => now()->subMinutes(120 - ($i * 10)),
            ]);
        }

        // Also add some logs for campaign 1 (currently running)
        for ($i = 0; $i < 4; $i++) {
            CommentLog::create([
                'campaign_id' => $campaign1->id,
                'group_id' => $groups[$i % 3]['groupId'],
                'group_name' => $groups[$i % 3]['name'],
                'post_url' => "https://facebook.com/groups/{$groups[$i % 3]['groupId']}/posts/" . (2000 + $i),
                'comment_content' => ['Chúc mừng bạn nhé!', 'Wow, tuyệt vời quá!', 'Quá đỉnh luôn!'][$i % 3],
                'status' => 'success',
                'commented_at' => now()->subMinutes(25 - ($i * 5)),
            ]);
        }
    }
}
