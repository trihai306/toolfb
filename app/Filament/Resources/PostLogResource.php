<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostLogResource\Pages;
use App\Models\BrowserProfile;
use App\Models\PostLog;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PostLogResource extends Resource
{
    protected static ?string $model = PostLog::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Lịch sử đăng bài';
    protected static ?string $modelLabel = 'Log đăng bài';
    protected static ?string $pluralModelLabel = 'Lịch sử đăng bài';
    protected static string | UnitEnum | null $navigationGroup = '📊 Báo cáo';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) PostLog::whereDate('posted_at', today())->count();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group_name')
                    ->label('Nhóm')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->group_name)
                    ->url(fn ($record) => "https://facebook.com/groups/{$record->group_id}")
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('browserProfile.name')
                    ->label('Profile')
                    ->icon('heroicon-o-globe-alt')
                    ->sortable(),

                Tables\Columns\TextColumn::make('content_preview')
                    ->label('Nội dung')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->content_preview)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Kết quả')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'success' => '✅ Thành công',
                        'failed' => '❌ Thất bại',
                        'skipped' => '⏭ Bỏ qua',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('error')
                    ->label('Lỗi')
                    ->limit(50)
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->posted_at?->diffForHumans()),
            ])
            ->defaultSort('posted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Kết quả')
                    ->options([
                        'success' => '✅ Thành công',
                        'failed' => '❌ Thất bại',
                        'skipped' => '⏭ Bỏ qua',
                    ]),

                Tables\Filters\SelectFilter::make('browser_profile_id')
                    ->label('Profile')
                    ->options(BrowserProfile::pluck('name', 'id')),
            ])
            ->actions([
                Actions\Action::make('openGroup')
                    ->label('Mở nhóm')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => "https://facebook.com/groups/{$record->group_id}")
                    ->openUrlInNewTab()
                    ->color('info'),
            ])
            ->emptyStateHeading('Chưa có log đăng bài')
            ->emptyStateDescription('Log sẽ được tạo tự động khi extension đăng bài thành công hoặc thất bại')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostLogs::route('/'),
        ];
    }
}
