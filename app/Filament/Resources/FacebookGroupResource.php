<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookGroupResource\Pages;
use App\Models\BrowserProfile;
use App\Models\FacebookGroup;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class FacebookGroupResource extends Resource
{
    protected static ?string $model = FacebookGroup::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Nhóm Facebook';
    protected static ?string $modelLabel = 'Nhóm Facebook';
    protected static ?string $pluralModelLabel = 'Nhóm Facebook';
    protected static string | UnitEnum | null $navigationGroup = '🤖 Tự động hóa';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) FacebookGroup::count();
    }

    public static function canCreate(): bool
    {
        return false; // Groups are synced from extension only
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên nhóm')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->name)
                    ->description(fn ($record) => $record->category ? "📂 {$record->category}" : null)
                    ->url(fn ($record) => $record->url ?? "https://facebook.com/groups/{$record->group_id}")
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('group_id')
                    ->label('Group ID')
                    ->searchable()
                    ->copyable()
                    ->size('sm')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('browserProfile.name')
                    ->label('Profile')
                    ->description(fn ($record) => $record->browserProfile?->facebook_name)
                    ->icon('heroicon-o-globe-alt')
                    ->sortable(),

                Tables\Columns\TextColumn::make('member_count')
                    ->label('Thành viên')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        if ($state >= 1000000) return round($state / 1000000, 1) . 'M';
                        if ($state >= 1000) return round($state / 1000, 1) . 'K';
                        return number_format($state);
                    })
                    ->color('success')
                    ->icon('heroicon-o-users'),

                Tables\Columns\TextColumn::make('privacy')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'public' => '🌐 Công khai',
                        'private' => '🔒 Riêng tư',
                        default => $state ?? '—',
                    })
                    ->color(fn ($state) => match($state) {
                        'public' => 'success',
                        'private' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sync lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans()),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->filters([
                Tables\Filters\SelectFilter::make('browser_profile_id')
                    ->label('Profile')
                    ->options(BrowserProfile::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Phân loại')
                    ->options(
                        FacebookGroup::whereNotNull('category')
                            ->distinct()
                            ->pluck('category', 'category')
                            ->toArray()
                    ),
            ])
            ->actions([
                Actions\Action::make('openGroup')
                    ->label('Mở nhóm')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->url ?? "https://facebook.com/groups/{$record->group_id}")
                    ->openUrlInNewTab()
                    ->color('info'),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có nhóm nào')
            ->emptyStateDescription('Mở extension trên Chrome → vào Facebook → nhóm sẽ tự động sync')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacebookGroups::route('/'),
        ];
    }
}
