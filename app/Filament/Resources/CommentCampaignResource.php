<?php

namespace App\Filament\Resources;

use App\Events\CampaignCommand;
use App\Filament\Resources\CommentCampaignResource\Pages;
use App\Models\BrowserProfile;
use App\Models\CommentCampaign;
use App\Models\FacebookGroup;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CommentCampaignResource extends Resource
{
    protected static ?string $model = CommentCampaign::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Chiến dịch';
    protected static ?string $modelLabel = 'Chiến dịch Comment';
    protected static string | \UnitEnum | null $navigationGroup = '💬 Comment dạo';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $running = static::getModel()::where('status', 'running')->count();
        return $running > 0 ? "{$running} đang chạy" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Schemas\Components\Section::make('📋 Thông tin chiến dịch')
                ->description('Thiết lập thông tin cơ bản cho chiến dịch comment dạo')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Schemas\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tên chiến dịch')
                            ->placeholder('VD: Comment dạo - Review sản phẩm tháng 3')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag'),
                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'draft' => '📝 Nháp',
                                'running' => '🚀 Đang chạy',
                                'paused' => '⏸️ Tạm dừng',
                                'completed' => '✅ Hoàn thành',
                                'failed' => '❌ Thất bại',
                            ])
                            ->default('draft')
                            ->prefixIcon('heroicon-o-signal'),
                    ]),
                    Forms\Components\Select::make('browser_profile_id')
                        ->label('Profile trình duyệt')
                        ->options(
                            BrowserProfile::whereNotNull('extension_id')
                                ->where('extension_id', '!=', '')
                                ->get()
                                ->mapWithKeys(fn ($p) => [
                                    $p->id => ($p->facebook_name ?: $p->name) . ($p->facebook_uid ? " (UID: {$p->facebook_uid})" : ''),
                                ])
                        )
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            if ($state) {
                                $profile = BrowserProfile::find($state);
                                $set('extension_id', $profile?->extension_id);
                            }
                        })
                        ->prefixIcon('heroicon-o-globe-alt')
                        ->helperText('Chỉ hiện profile đã kết nối extension'),
                    Forms\Components\Textarea::make('content')
                        ->label('Nội dung comment')
                        ->placeholder("VD: Sản phẩm chất lượng quá! {spin|Mình đã dùng|Mình đã thử|Mình rất thích} rồi, recommend cho mọi người 👍")
                        ->helperText('💡 Dùng {spin|text1|text2|text3} để xoay vòng nội dung, tránh trùng lặp bị Facebook phát hiện')
                        ->rows(5)
                        ->required(),
                    Forms\Components\FileUpload::make('images')
                        ->label('Hình ảnh đính kèm')
                        ->multiple()
                        ->image()
                        ->directory('comment-images')
                        ->helperText('Upload ảnh kèm comment (khuyến nghị nhiều ảnh để xoay vòng)'),
                ]),

            Schemas\Components\Section::make('👥 Chọn nhóm comment')
                ->description('Chọn nhóm Facebook để comment dạo')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\CheckboxList::make('group_ids')
                        ->label('')
                        ->options(function (callable $get) {
                            $profileId = $get('browser_profile_id');
                            if (!$profileId) return [];
                            return FacebookGroup::where('browser_profile_id', $profileId)
                                ->orderBy('name')
                                ->pluck('name', 'group_id');
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(1)
                        ->helperText(function (callable $get) {
                            $profileId = $get('browser_profile_id');
                            if (!$profileId) return '⚠️ Vui lòng chọn Profile trước';
                            $count = FacebookGroup::where('browser_profile_id', $profileId)->count();
                            return $count > 0
                                ? "📌 {$count} nhóm khả dụng — tick chọn nhóm muốn comment"
                                : '⚠️ Profile này chưa sync nhóm. Vào Admin → Nhóm Facebook → Đồng bộ';
                        })
                        ->required(),
                    Forms\Components\Hidden::make('extension_id'),
                ]),

            Schemas\Components\Section::make('⏱️ Thời gian delay')
                ->description('Khoảng cách giữa các lần comment — delay tự nhiên tránh bot')
                ->icon('heroicon-o-clock')
                ->schema([
                    Schemas\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('settings.commentMinDelay')
                            ->label('Delay tối thiểu (giây)')
                            ->numeric()
                            ->default(15)
                            ->minValue(5)
                            ->suffix('giây')
                            ->helperText('Thời gian nghỉ tối thiểu giữa các comment'),
                        Forms\Components\TextInput::make('settings.commentMaxDelay')
                            ->label('Delay tối đa (giây)')
                            ->numeric()
                            ->default(45)
                            ->minValue(10)
                            ->suffix('giây')
                            ->helperText('Thời gian nghỉ tối đa (phân phối Gaussian)'),
                        Forms\Components\TextInput::make('settings.commentsPerGroup')
                            ->label('Số comment/nhóm')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('Bao nhiêu bài viết sẽ được comment trong mỗi nhóm'),
                    ]),
                    Schemas\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('settings.scrollDepth')
                            ->label('Độ sâu cuộn feed')
                            ->numeric()
                            ->default(5)
                            ->minValue(2)
                            ->maxValue(15)
                            ->helperText('Số lần cuộn feed để tìm bài — cuộn nhiều = nhiều bài hơn'),
                        Forms\Components\Toggle::make('settings.spinEnabled')
                            ->label('Bật spin nội dung')
                            ->helperText('Xoay vòng nội dung dùng {spin|text1|text2}')
                            ->default(true),
                    ]),
                ])
                ->collapsible(),

            Schemas\Components\Section::make('🛡️ Chống phát hiện Bot')
                ->description('Hành vi giống người thật để Facebook không phát hiện')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Schemas\Components\Grid::make(2)->schema([
                        Forms\Components\Toggle::make('settings.skipSponsored')
                            ->label('Bỏ qua bài quảng cáo/sponsored')
                            ->helperText('Tự động skip bài Sponsored — tự nhiên hơn')
                            ->default(true),
                        Forms\Components\Toggle::make('settings.skipOwn')
                            ->label('Bỏ qua bài của mình')
                            ->helperText('Không comment vào bài mình đã đăng')
                            ->default(true),
                        Forms\Components\Toggle::make('settings.skipCommented')
                            ->label('Bỏ qua bài đã comment')
                            ->helperText('Không comment lại vào bài đã bình luận')
                            ->default(true),
                    ]),
                    Schemas\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('settings.autoLikeChance')
                            ->label('Xác suất auto-like (%)')
                            ->numeric()
                            ->default(30)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('% cơ hội like bài trước khi comment'),
                        Forms\Components\TextInput::make('settings.maxPerHour')
                            ->label('Giới hạn comment/giờ')
                            ->numeric()
                            ->default(30)
                            ->minValue(5)
                            ->maxValue(60)
                            ->helperText('Tự dừng nghỉ khi đạt giới hạn'),
                        Forms\Components\TextInput::make('settings.maxPerDay')
                            ->label('Giới hạn comment/ngày')
                            ->numeric()
                            ->default(150)
                            ->minValue(10)
                            ->maxValue(500)
                            ->helperText('Không vượt quá giới hạn này trong 1 ngày'),
                    ]),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên chiến dịch')
                    ->searchable()
                    ->limit(35)
                    ->weight('bold')
                    ->icon('heroicon-m-rocket-launch')
                    ->tooltip(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '📝 Nháp',
                        'running' => '🚀 Chạy',
                        'paused' => '⏸️ Dừng',
                        'completed' => '✅ Xong',
                        'failed' => '❌ Lỗi',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'running' => 'success',
                        'paused' => 'warning',
                        'completed' => 'primary',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('logs_count')
                    ->label('💬 Comments')
                    ->counts('logs')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->content)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->format('d/m/Y H:i')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Lọc trạng thái')
                    ->options([
                        'draft' => '📝 Nháp',
                        'running' => '🚀 Đang chạy',
                        'paused' => '⏸️ Tạm dừng',
                        'completed' => '✅ Hoàn thành',
                        'failed' => '❌ Thất bại',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square'),
                Actions\Action::make('run')
                    ->label('Chạy')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('🚀 Bắt đầu chiến dịch?')
                    ->modalDescription('Chiến dịch sẽ bắt đầu gửi lệnh comment tới extension. Đảm bảo extension đang hoạt động.')
                    ->visible(fn (CommentCampaign $record) => in_array($record->status, ['draft', 'paused']))
                    ->action(function (CommentCampaign $record) {
                        // Get extension_id from profile or direct
                        $extensionId = $record->extension_id;
                        if (!$extensionId && $record->browser_profile_id) {
                            $extensionId = BrowserProfile::find($record->browser_profile_id)?->extension_id;
                        }

                        if (!$extensionId) {
                            Notification::make()
                                ->title('❌ Không tìm thấy Extension')
                                ->body('Profile chưa kết nối extension. Mở trình duyệt và bật extension trước.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update(['status' => 'running', 'started_at' => now()]);

                        // Build groups array with URLs for the extension
                        $groupIds = $record->group_ids ?? $record->groups ?? [];
                        $groups = FacebookGroup::whereIn('group_id', $groupIds)
                            ->get()
                            ->map(fn ($g) => [
                                'groupId' => $g->group_id,
                                'name' => $g->name,
                                'url' => 'https://www.facebook.com/groups/' . $g->group_id,
                            ])
                            ->values()
                            ->toArray();

                        // Broadcast on 'extension.command' — the channel the extension listens on
                        event(new CampaignCommand($extensionId, 'extension.command', [
                            'command' => 'START_COMMENTING',
                            'data' => [
                                'campaign_id' => $record->id,
                                'content' => $record->content,
                                'groups' => $groups,
                                'images' => $record->images ?? [],
                                'settings' => $record->settings ?? [],
                            ],
                            'timestamp' => now()->toISOString(),
                        ]));

                        Notification::make()
                            ->title('🚀 Chiến dịch đã bắt đầu')
                            ->body("Đã gửi lệnh comment tới extension — " . count($groups) . " nhóm")
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('stop')
                    ->label('Dừng')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('⏹ Dừng chiến dịch?')
                    ->modalDescription('Chiến dịch sẽ tạm dừng ngay lập tức.')
                    ->visible(fn (CommentCampaign $record) => $record->status === 'running')
                    ->action(function (CommentCampaign $record) {
                        $record->update(['status' => 'paused']);

                        $extensionId = $record->extension_id;
                        if (!$extensionId && $record->browser_profile_id) {
                            $extensionId = BrowserProfile::find($record->browser_profile_id)?->extension_id;
                        }

                        if ($extensionId) {
                            event(new CampaignCommand($extensionId, 'extension.command', [
                                'command' => 'STOP_COMMENTING',
                                'data' => ['campaign_id' => $record->id],
                                'timestamp' => now()->toISOString(),
                            ]));
                        }

                        Notification::make()
                            ->title('⏹ Chiến dịch đã dừng')
                            ->body('Đã gửi lệnh dừng tới extension')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có chiến dịch nào')
            ->emptyStateDescription('Tạo chiến dịch comment dạo đầu tiên để bắt đầu ')
            ->emptyStateIcon('heroicon-o-rocket-launch');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommentCampaigns::route('/'),
            'create' => Pages\CreateCommentCampaign::route('/create'),
            'edit' => Pages\EditCommentCampaign::route('/{record}/edit'),
        ];
    }
}
