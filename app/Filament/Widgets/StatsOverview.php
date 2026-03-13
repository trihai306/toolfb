<?php

namespace App\Filament\Widgets;

use App\Models\CommentCampaign;
use App\Models\CommentLog;
use App\Models\CommentTemplate;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalCampaigns = CommentCampaign::count();
        $runningCampaigns = CommentCampaign::where('status', 'running')->count();
        $todayComments = CommentLog::whereDate('commented_at', today())->count();
        $totalLogs = CommentLog::count();
        $successLogs = CommentLog::where('status', 'success')->count();
        $successRate = $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 1) : 0;
        $totalTemplates = CommentTemplate::count();

        return [
            Stat::make('Chiến dịch', $totalCampaigns)
                ->description("{$runningCampaigns} đang chạy")
                ->descriptionIcon('heroicon-m-play-circle')
                ->chart([3, 5, 2, 4, 6, $totalCampaigns])
                ->color($runningCampaigns > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-rocket-launch'),

            Stat::make('Comments hôm nay', $todayComments)
                ->description("Tổng: {$totalLogs}")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->chart([2, 4, 6, 3, 5, 7, $todayComments])
                ->color('info')
                ->icon('heroicon-o-chat-bubble-bottom-center-text'),

            Stat::make('Tỷ lệ thành công', "{$successRate}%")
                ->description("{$successLogs}/{$totalLogs} comments")
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([85, 90, 88, 92, 95, $successRate])
                ->color($successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Mẫu comment', $totalTemplates)
                ->description('Templates sẵn dùng')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('warning')
                ->icon('heroicon-o-document-text'),
        ];
    }
}
