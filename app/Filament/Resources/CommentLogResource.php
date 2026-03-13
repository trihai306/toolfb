<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentLogResource\Pages;
use App\Models\CommentLog;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommentLogResource extends Resource
{
    protected static ?string $model = CommentLog::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Lịch sử';
    protected static ?string $modelLabel = 'Log Comment';
    protected static string | \UnitEnum | null $navigationGroup = '💬 Comment dạo';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $today = static::getModel()::whereDate('commented_at', today())->count();
        return $today > 0 ? "{$today} hôm nay" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Chiến dịch')
                    ->searchable()
                    ->icon('heroicon-m-rocket-launch')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->campaign?->name),
                Tables\Columns\TextColumn::make('group_name')
                    ->label('Nhóm')
                    ->icon('heroicon-m-user-group')
                    ->limit(22)
                    ->tooltip(fn ($record) => $record->group_name)
                    ->searchable(),
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
                    ->tooltip(fn ($record) => $record->comment_content)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('post_url')
                    ->label('Bài viết')
                    ->url(fn ($record) => $record->post_url, true)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commented_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->commented_at?->format('H:i d/m'))
                    ->tooltip(fn ($record) => $record->commented_at?->format('d/m/Y H:i:s')),
            ])
            ->defaultSort('commented_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'success' => '✅ Thành công',
                        'failed' => '❌ Thất bại',
                        'skipped' => '⏭️ Bỏ qua',
                    ]),
                Tables\Filters\SelectFilter::make('campaign')
                    ->label('Chiến dịch')
                    ->relationship('campaign', 'name'),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->icon('heroicon-m-eye'),
            ])
            ->emptyStateHeading('Chưa có log comment nào')
            ->emptyStateDescription('Khi chiến dịch chạy, log sẽ tự động xuất hiện ở đây')
            ->emptyStateIcon('heroicon-o-clock');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommentLogs::route('/'),
        ];
    }
}
