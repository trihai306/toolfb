<?php

namespace App\Filament\Resources;

use App\Models\BrowserProfile;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;

class BrowserProfileResource extends Resource
{
    protected static ?string $model = BrowserProfile::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Browser Profiles';
    protected static ?string $modelLabel = 'Browser Profile';
    protected static string | \UnitEnum | null $navigationGroup = '⚙️ Cấu hình';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $online = BrowserProfile::where('status', 'online')->count();
        return $online > 0 ? "{$online} online" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin Profile')
                ->description('Cài đặt cơ bản')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tên Profile')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('VD: Chrome - Tài khoản chính')
                            ->prefixIcon('heroicon-o-tag'),
                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'online' => '🟢 Online',
                                'offline' => '⚫ Offline',
                                'banned' => '🔴 Bị khóa',
                            ])
                            ->default('offline')
                            ->prefixIcon('heroicon-o-signal'),
                    ]),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('proxy')
                            ->label('Proxy')
                            ->placeholder('ip:port hoặc ip:port:user:pass')
                            ->helperText('Để trống = dùng IP thật')
                            ->prefixIcon('heroicon-o-shield-check'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Ghi chú')
                            ->placeholder('Ghi chú thêm...')
                            ->rows(2),
                    ]),
                ])
                ->collapsible()
                ->compact(),

            Section::make('📡 Thông tin từ Extension (tự động)')
                ->description('Dữ liệu tự cập nhật từ extension khi kết nối. Không cần nhập tay.')
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('facebook_name')
                            ->label('Facebook Name')
                            ->disabled()
                            ->prefixIcon('heroicon-o-user')
                            ->placeholder('Extension tự lấy'),
                        Forms\Components\TextInput::make('facebook_uid')
                            ->label('Facebook UID')
                            ->disabled()
                            ->prefixIcon('heroicon-o-identification')
                            ->placeholder('Extension tự lấy'),
                        Forms\Components\TextInput::make('extension_id')
                            ->label('Extension ID')
                            ->disabled()
                            ->prefixIcon('heroicon-o-puzzle-piece')
                            ->placeholder('Tự gán khi kết nối'),
                    ]),
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('browser_name')
                            ->label('Trình duyệt')
                            ->disabled()
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->placeholder('Chrome, Edge...'),
                        Forms\Components\TextInput::make('browser_version')
                            ->label('Phiên bản')
                            ->disabled()
                            ->placeholder('134.0.6998'),
                        Forms\Components\TextInput::make('os_info')
                            ->label('Hệ điều hành')
                            ->disabled()
                            ->prefixIcon('heroicon-o-computer-desktop')
                            ->placeholder('macOS 15.2'),
                    ]),
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('screen_resolution')
                            ->label('Màn hình')
                            ->disabled()
                            ->placeholder('1920x1080'),
                        Forms\Components\TextInput::make('language')
                            ->label('Ngôn ngữ')
                            ->disabled()
                            ->placeholder('vi'),
                        Forms\Components\TextInput::make('timezone')
                            ->label('Múi giờ')
                            ->disabled()
                            ->placeholder('Asia/Ho_Chi_Minh'),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP')
                            ->disabled()
                            ->placeholder('Từ request'),
                    ]),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpan(2)
                            ->placeholder('Tự lấy từ browser'),
                    ]),
                ])
                ->collapsible()
                ->compact(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên Profile')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-globe-alt')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => '🟢 Online',
                        'offline' => '⚫ Offline',
                        'banned' => '🔴 Bị khóa',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'gray',
                        'banned' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('facebook_name')
                    ->label('Facebook')
                    ->icon('heroicon-m-user')
                    ->placeholder('Chưa liên kết')
                    ->searchable(),
                Tables\Columns\TextColumn::make('browser_name')
                    ->label('Trình duyệt')
                    ->formatStateUsing(fn ($record) => $record->browser_name
                        ? "{$record->browser_name} {$record->browser_version}"
                        : null
                    )
                    ->icon('heroicon-m-globe-alt')
                    ->placeholder('Chưa sync')
                    ->description(fn ($record) => $record->os_info),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->icon('heroicon-m-signal')
                    ->placeholder('Chưa sync')
                    ->description(fn ($record) => $record->screen_resolution),
                Tables\Columns\TextColumn::make('extension_id')
                    ->label('Extension')
                    ->limit(12)
                    ->tooltip(fn ($record) => $record->extension_id)
                    ->placeholder('Chưa kết nối')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_active_at')
                    ->label('Hoạt động')
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->last_active_at?->format('H:i d/m'))
                    ->placeholder('Chưa bao giờ'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'online' => '🟢 Online',
                        'offline' => '⚫ Offline',
                        'banned' => '🔴 Bị khóa',
                    ]),
            ])
            ->actions([
                Actions\Action::make('generateToken')
                    ->label('Token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('🔑 Tạo API Token mới?')
                    ->modalDescription('Token cũ sẽ bị vô hiệu hóa. Token mới chỉ hiện 1 lần.')
                    ->action(function (BrowserProfile $record) {
                        $token = BrowserProfile::generateToken($record);
                        Notification::make()
                            ->title('🔑 Token mới đã tạo')
                            ->body("Sao chép ngay - chỉ hiện 1 lần!\n\n`{$token}`")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Actions\Action::make('toggleStatus')
                    ->label(fn (BrowserProfile $record) => $record->status === 'banned' ? 'Mở khóa' : 'Khóa')
                    ->icon(fn (BrowserProfile $record) => $record->status === 'banned' ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (BrowserProfile $record) => $record->status === 'banned' ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(function (BrowserProfile $record) {
                        if ($record->status === 'banned') {
                            $record->update(['status' => 'offline']);
                            Notification::make()->title('✅ Đã mở khóa profile')->success()->send();
                        } else {
                            $record->update(['status' => 'banned']);
                            Notification::make()->title('🔒 Đã khóa profile')->warning()->send();
                        }
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_active_at', 'desc')
            ->emptyStateHeading('Chưa có browser profile')
            ->emptyStateDescription('Đăng nhập từ extension để tự động tạo profile.')
            ->emptyStateIcon('heroicon-o-globe-alt');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\BrowserProfileResource\Pages\ListBrowserProfiles::route('/'),
            'create' => \App\Filament\Resources\BrowserProfileResource\Pages\CreateBrowserProfile::route('/create'),
            'edit' => \App\Filament\Resources\BrowserProfileResource\Pages\EditBrowserProfile::route('/{record}/edit'),
        ];
    }
}
