<?php

namespace App\Filament\Widgets;

use App\Models\BrowserProfile;
use App\Models\CommentCampaign;
use App\Models\CommentLog;
use App\Models\FacebookGroup;
use App\Models\ScheduledPost;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Extension status
        $onlineProfiles = BrowserProfile::whereNotNull('extension_id')
            ->where('extension_id', '!=', '')
            ->where('last_active_at', '>=', now()->subMinutes(10))
            ->count();
        $totalProfiles = BrowserProfile::whereNotNull('extension_id')
            ->where('extension_id', '!=', '')
            ->count();

        // Groups
        $totalGroups = FacebookGroup::count();
        $avgMembers = FacebookGroup::whereNotNull('member_count')->avg('member_count');
        $avgMembersFormatted = $avgMembers ? ($avgMembers >= 1000 ? round($avgMembers / 1000, 1) . 'K' : number_format($avgMembers)) : '—';

        // Posts
        $pendingPosts = ScheduledPost::where('status', 'pending')->count();
        $completedPosts = ScheduledPost::where('status', 'completed')->count();

        // Campaigns
        $runningCampaigns = CommentCampaign::where('status', 'running')->count();
        $totalCampaigns = CommentCampaign::count();

        // Comments
        $todayComments = CommentLog::whereDate('commented_at', today())->count();
        $totalLogs = CommentLog::count();
        $successLogs = CommentLog::where('status', 'success')->count();
        $successRate = $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 1) : 0;

        return [
            Stat::make('Extension Online', "{$onlineProfiles}/{$totalProfiles}")
                ->description($onlineProfiles > 0 ? '🟢 Đang hoạt động' : '🔴 Offline')
                ->descriptionIcon($onlineProfiles > 0 ? 'heroicon-m-signal' : 'heroicon-m-signal-slash')
                ->color($onlineProfiles > 0 ? 'success' : 'danger')
                ->icon('heroicon-o-globe-alt'),

            Stat::make('Nhóm Facebook', $totalGroups)
                ->description("TB: {$avgMembersFormatted} thành viên/nhóm")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->icon('heroicon-o-user-group'),

            Stat::make('Bài đăng', $completedPosts)
                ->description("{$pendingPosts} đang chờ")
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPosts > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-document-text'),

            Stat::make('Chiến dịch', $totalCampaigns)
                ->description("{$runningCampaigns} đang chạy")
                ->descriptionIcon('heroicon-m-play-circle')
                ->color($runningCampaigns > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-rocket-launch'),

            Stat::make('Comments hôm nay', $todayComments)
                ->description("Tổng: {$totalLogs}")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info')
                ->icon('heroicon-o-chat-bubble-bottom-center-text'),

            Stat::make('Tỷ lệ thành công', "{$successRate}%")
                ->description("{$successLogs}/{$totalLogs} comments")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
