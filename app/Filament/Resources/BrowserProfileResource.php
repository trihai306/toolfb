<?php

namespace App\Filament\Resources;

use App\Events\CampaignCommand;
use App\Models\BrowserProfile;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class BrowserProfileResource extends Resource
{
    protected static ?string $model = BrowserProfile::class;
    protected static string | UnitEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Browser Profiles';
    protected static ?string $modelLabel = 'Browser Profile';
    protected static string | UnitEnum | null $navigationGroup = '⚙️ Cấu hình';
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

            Section::make('👤 Facebook Profile chi tiết')
                ->description('Thông tin tài khoản Facebook — tự động lấy khi Sync')
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('facebook_name')
                            ->label('Tên Facebook')
                            ->disabled()
                            ->prefixIcon('heroicon-o-user')
                            ->placeholder('Extension tự lấy'),
                        Forms\Components\TextInput::make('facebook_uid')
                            ->label('Facebook UID')
                            ->disabled()
                            ->prefixIcon('heroicon-o-identification')
                            ->placeholder('Extension tự lấy'),
                        Forms\Components\TextInput::make('facebook_profile_url')
                            ->label('Profile URL')
                            ->disabled()
                            ->prefixIcon('heroicon-o-link')
                            ->placeholder('Tự lấy khi sync')
                            ->url()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openProfile')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($record) => $record?->facebook_profile_url)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => !empty($record?->facebook_profile_url))
                            ),
                    ]),
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('facebook_friends_count')
                            ->label('Số bạn bè')
                            ->disabled()
                            ->prefixIcon('heroicon-o-user-group')
                            ->placeholder('—')
                            ->numeric(),
                        Forms\Components\TextInput::make('facebook_join_date')
                            ->label('Tham gia từ')
                            ->disabled()
                            ->prefixIcon('heroicon-o-calendar')
                            ->placeholder('—'),
                        Forms\Components\TextInput::make('facebook_email')
                            ->label('Email')
                            ->disabled()
                            ->prefixIcon('heroicon-o-envelope')
                            ->placeholder('Nếu public'),
                        Forms\Components\TextInput::make('facebook_verified')
                            ->label('Xác minh')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? '✅ Đã xác minh' : ($state === false ? '❌ Chưa' : '—'))
                            ->placeholder('—'),
                    ]),
                    Forms\Components\Textarea::make('facebook_bio')
                        ->label('Tiểu sử')
                        ->disabled()
                        ->placeholder('Bio trên Facebook')
                        ->rows(2)
                        ->columnSpan('full'),
                ])
                ->collapsible()
                ->compact(),

            Section::make('📡 Thông tin trình duyệt (tự động)')
                ->description('Dữ liệu tự cập nhật từ extension khi kết nối.')
                ->icon('heroicon-o-arrow-path')
                ->schema([
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
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('extension_id')
                            ->label('Extension ID')
                            ->disabled()
                            ->prefixIcon('heroicon-o-puzzle-piece')
                            ->placeholder('Tự gán khi kết nối'),
                        Forms\Components\TextInput::make('screen_resolution')
                            ->label('Màn hình')
                            ->disabled()
                            ->placeholder('1920x1080'),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP')
                            ->disabled()
                            ->placeholder('Từ request'),
                    ]),
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('language')
                            ->label('Ngôn ngữ')
                            ->disabled()
                            ->placeholder('vi'),
                        Forms\Components\TextInput::make('timezone')
                            ->label('Múi giờ')
                            ->disabled()
                            ->placeholder('Asia/Ho_Chi_Minh'),
                        Forms\Components\TextInput::make('platform')
                            ->label('Platform')
                            ->disabled()
                            ->placeholder('MacIntel'),
                        Forms\Components\TextInput::make('connection_type')
                            ->label('Kết nối')
                            ->disabled()
                            ->placeholder('4g, wifi...'),
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

            Section::make('📱 Phần cứng & Fingerprint')
                ->description('Thông tin fingerprint trình duyệt')
                ->icon('heroicon-o-cpu-chip')
                ->schema([
                    Grid::make(4)->schema([
                        Forms\Components\TextInput::make('hardware_concurrency')
                            ->label('CPU Cores')
                            ->disabled()
                            ->prefixIcon('heroicon-o-cpu-chip')
                            ->placeholder('—')
                            ->numeric(),
                        Forms\Components\TextInput::make('device_memory')
                            ->label('RAM (GB)')
                            ->disabled()
                            ->placeholder('—')
                            ->numeric(),
                        Forms\Components\TextInput::make('cookies_count')
                            ->label('FB Cookies')
                            ->disabled()
                            ->placeholder('—')
                            ->numeric(),
                        Forms\Components\TextInput::make('do_not_track')
                            ->label('Do Not Track')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? '🔒 Bật' : ($state === false ? '🔓 Tắt' : '—'))
                            ->placeholder('—'),
                    ]),
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('webgl_renderer')
                            ->label('GPU (WebGL)')
                            ->disabled()
                            ->columnSpan(1)
                            ->placeholder('Tự lấy từ browser'),
                        Forms\Components\TextInput::make('touch_support')
                            ->label('Cảm ứng')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? '✅ Có touch' : ($state === false ? '❌ Không' : '—'))
                            ->placeholder('—'),
                    ]),
                ])
                ->collapsible()
                ->collapsed()
                ->compact(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('facebook_avatar')
                    ->label('')
                    ->circular()
                    ->size(36)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: false),
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
                    ->searchable()
                    ->description(fn ($record) => collect([
                        $record->facebook_uid ? "UID: {$record->facebook_uid}" : null,
                        $record->facebook_friends_count ? "👥 {$record->facebook_friends_count} bạn bè" : null,
                    ])->filter()->implode(' · ')),
                Tables\Columns\TextColumn::make('browser_name')
                    ->label('Trình duyệt')
                    ->formatStateUsing(fn ($record) => $record->browser_name
                        ? "{$record->browser_name} {$record->browser_version}"
                        : null
                    )
                    ->icon('heroicon-m-globe-alt')
                    ->placeholder('Chưa sync')
                    ->description(fn ($record) => $record->os_info),
                Tables\Columns\TextColumn::make('hardware_summary')
                    ->label('Phần cứng')
                    ->state(fn ($record) => collect([
                        $record->hardware_concurrency ? "{$record->hardware_concurrency} cores" : null,
                        $record->device_memory ? "{$record->device_memory}GB" : null,
                    ])->filter()->implode(' / ') ?: null)
                    ->icon('heroicon-m-cpu-chip')
                    ->placeholder('—')
                    ->description(fn ($record) => $record->webgl_renderer ? \Illuminate\Support\Str::limit($record->webgl_renderer, 30) : null)
                    ->toggleable(isToggledHiddenByDefault: false),
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
                Actions\Action::make('syncProfile')
                    ->label('📡 Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->tooltip('Gửi lệnh lấy thông tin trình duyệt + Facebook chi tiết từ extension (tự mở tab FB)')
                    ->hidden(fn (BrowserProfile $record) => empty($record->extension_id))
                    ->action(function (BrowserProfile $record) {
                        event(new CampaignCommand(
                            $record->extension_id,
                            'sync-profile',
                            ['profile_id' => $record->id]
                        ));
                        Notification::make()
                            ->title('📡 Đã gửi lệnh Sync')
                            ->body("Extension sẽ tự lấy thông tin trình duyệt + mở Facebook lấy profile chi tiết. Refresh trang sau vài giây.")
                            ->success()
                            ->duration(5000)
                            ->send();
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
