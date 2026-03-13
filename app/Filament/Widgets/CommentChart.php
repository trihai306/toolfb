<?php

namespace App\Filament\Widgets;

use App\Models\CommentLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CommentChart extends ChartWidget
{
    protected ?string $heading = 'Thống kê comment 7 ngày';
    protected ?string $description = 'Số lượng comment thành công vs thất bại theo ngày';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $successData = $days->map(fn ($day) =>
            CommentLog::where('status', 'success')
                ->whereDate('commented_at', $day)
                ->count()
        )->toArray();

        $failedData = $days->map(fn ($day) =>
            CommentLog::whereIn('status', ['failed', 'skipped'])
                ->whereDate('commented_at', $day)
                ->count()
        )->toArray();

        $labels = $days->map(fn ($day) => $day->format('d/m'))->toArray();

        return [
            'datasets' => [
                [
                    'label' => '✅ Thành công',
                    'data' => $successData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => '❌ Thất bại/Bỏ qua',
                    'data' => $failedData,
                    'borderColor' => '#f43f5e',
                    'backgroundColor' => 'rgba(244, 63, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
