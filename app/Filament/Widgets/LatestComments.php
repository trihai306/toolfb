<?php

namespace App\Filament\Widgets;

use App\Models\CommentLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestComments extends TableWidget
{
    protected static ?string $heading = 'Comment gần nhất';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CommentLog::query()
                    ->with('campaign')
                    ->latest('commented_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Chiến dịch')
                    ->icon('heroicon-m-rocket-launch')
                    ->limit(25)
                    ->searchable(),
                Tables\Columns\TextColumn::make('group_name')
                    ->label('Nhóm')
                    ->icon('heroicon-m-user-group')
                    ->limit(20),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => '✅ Thành công',
                        'failed' => '❌ Thất bại',
                        'skipped' => '⏭️ Bỏ qua',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('comment_content')
                    ->label('Nội dung')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->comment_content),
                Tables\Columns\TextColumn::make('commented_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('commented_at', 'desc');
    }
}
