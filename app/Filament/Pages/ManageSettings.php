<?php

namespace App\Filament\Pages;

use App\Events\CampaignCommand;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Schema;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $navigationLabel = 'Cài đặt';
    protected static ?string $title = 'Cài đặt hệ thống';
    protected static string | \UnitEnum | null $navigationGroup = '⚙️ Cấu hình';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::getFormData());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === POSTING SETTINGS ===
                Schemas\Components\Section::make('Đăng bài tự động')
                    ->description('Cấu hình thời gian chờ và giới hạn đăng bài. Điều chỉnh delay phù hợp để tránh bị Facebook phát hiện.')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Schemas\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('min_delay')
                                ->label('Delay tối thiểu')
                                ->numeric()
                                ->minValue(5)
                                ->maxValue(600)
                                ->placeholder('30')
                                ->helperText('Giây - Khuyến nghị ≥ 30s')
                                ->suffixIcon('heroicon-o-clock')
                                ->suffix('giây'),
                            Forms\Components\TextInput::make('max_delay')
                                ->label('Delay tối đa')
                                ->numeric()
                                ->minValue(10)
                                ->maxValue(3600)
                                ->placeholder('120')
                                ->helperText('Giây - Khuyến nghị ≥ 60s')
                                ->suffixIcon('heroicon-o-clock')
                                ->suffix('giây'),
                            Forms\Components\TextInput::make('max_posts_per_day')
                                ->label('Giới hạn bài/ngày')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(500)
                                ->placeholder('50')
                                ->helperText('Bảo vệ tài khoản')
                                ->suffixIcon('heroicon-o-shield-check')
                                ->suffix('bài'),
                        ]),
                        Schemas\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('spin_enabled')
                                ->label('Spin nội dung')
                                ->helperText('Xoay vòng {spin|text1|text2} tự động')
                                ->onIcon('heroicon-o-arrow-path')
                                ->onColor('success'),
                            Forms\Components\Toggle::make('notify_enabled')
                                ->label('Thông báo khi xong')
                                ->helperText('Nhận thông báo khi hoàn thành đăng bài')
                                ->onIcon('heroicon-o-bell')
                                ->onColor('success'),
                        ]),
                    ])
                    ->collapsible()
                    ->compact(),

                // === COMMENTING SETTINGS ===
                Schemas\Components\Section::make('Comment dạo')
                    ->description('Cấu hình số lượng, tốc độ comment. Delay ngẫu nhiên giữa min-max giúp tránh spam detection.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Schemas\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make('comments_per_group')
                                ->label('Comment/nhóm')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(50)
                                ->placeholder('3')
                                ->suffix('comment')
                                ->helperText('Số comment mỗi nhóm'),
                            Forms\Components\TextInput::make('scroll_depth')
                                ->label('Cuộn sâu')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->placeholder('5')
                                ->suffix('bài')
                                ->helperText('Số bài tìm kiếm'),
                            Forms\Components\TextInput::make('comment_min_delay')
                                ->label('Delay min')
                                ->numeric()
                                ->minValue(5)
                                ->maxValue(300)
                                ->placeholder('15')
                                ->suffix('giây')
                                ->suffixIcon('heroicon-o-clock'),
                            Forms\Components\TextInput::make('comment_max_delay')
                                ->label('Delay max')
                                ->numeric()
                                ->minValue(10)
                                ->maxValue(600)
                                ->placeholder('45')
                                ->suffix('giây')
                                ->suffixIcon('heroicon-o-clock'),
                        ]),
                    ])
                    ->collapsible()
                    ->compact(),

                // === AI SETTINGS ===
                Schemas\Components\Section::make('AI Agent - Gemini')
                    ->description('Cấu hình Google Gemini AI để tự sinh nội dung comment thông minh, đa dạng, phù hợp ngữ cảnh.')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\TextInput::make('ai_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('AIzaSy...')
                            ->helperText('Lấy tại [aistudio.google.com/apikey](https://aistudio.google.com/apikey)')
                            ->prefixIcon('heroicon-o-key'),
                        Schemas\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('ai_model')
                                ->label('Model AI')
                                ->options([
                                    'gemini-2.0-flash' => '⚡ 2.0 Flash (Nhanh)',
                                    'gemini-2.0-flash-lite' => '💨 2.0 Flash Lite (Nhẹ nhất)',
                                    'gemini-2.5-pro-preview-05-06' => '🧠 2.5 Pro (Thông minh nhất)',
                                    'gemini-2.5-flash-preview-04-17' => '⚖️ 2.5 Flash (Cân bằng)',
                                ])
                                ->helperText('Pro = chất lượng cao, Flash = tốc độ nhanh')
                                ->prefixIcon('heroicon-o-cpu-chip'),
                            Forms\Components\Select::make('ai_tone')
                                ->label('Giọng văn')
                                ->options([
                                    'thân thiện' => '😊 Thân thiện',
                                    'chuyên nghiệp' => '💼 Chuyên nghiệp',
                                    'hài hước' => '😂 Hài hước',
                                    'nghiêm túc' => '📋 Nghiêm túc',
                                    'sáng tạo' => '✨ Sáng tạo',
                                    'bán hàng' => '🛒 Bán hàng',
                                ])
                                ->helperText('Phong cách viết comment')
                                ->prefixIcon('heroicon-o-language'),
                            Forms\Components\Toggle::make('ai_auto_image')
                                ->label('Auto generate ảnh')
                                ->helperText('AI tự tạo ảnh minh họa')
                                ->onIcon('heroicon-o-photo')
                                ->onColor('success'),
                        ]),
                    ])
                    ->collapsible()
                    ->compact(),

                // === SYSTEM SETTINGS ===
                Schemas\Components\Section::make('Hệ thống & Extension')
                    ->description('Kết nối và đồng bộ với Chrome Extension. Extension ID sẽ tự động cập nhật khi extension kết nối.')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Schemas\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('default_extension_id')
                                ->label('Extension ID mặc định')
                                ->placeholder('Tự động lấy khi extension kết nối')
                                ->helperText('UUID Chrome Extension - để trống nếu chưa kết nối')
                                ->prefixIcon('heroicon-o-puzzle-piece'),
                            Forms\Components\Toggle::make('auto_sync_settings')
                                ->label('Tự đồng bộ khi kết nối')
                                ->helperText('Auto push settings khi extension online')
                                ->onIcon('heroicon-o-arrow-path')
                                ->onColor('success'),
                        ]),
                    ])
                    ->collapsible()
                    ->compact(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Validate delay ranges
        if (isset($data['min_delay'], $data['max_delay'])) {
            if ((int) $data['min_delay'] >= (int) $data['max_delay']) {
                Notification::make()
                    ->title('⚠️ Lỗi cấu hình')
                    ->body('Delay tối thiểu phải nhỏ hơn delay tối đa (Đăng bài)')
                    ->danger()
                    ->send();
                return;
            }
        }

        if (isset($data['comment_min_delay'], $data['comment_max_delay'])) {
            if ((int) $data['comment_min_delay'] >= (int) $data['comment_max_delay']) {
                Notification::make()
                    ->title('⚠️ Lỗi cấu hình')
                    ->body('Delay tối thiểu phải nhỏ hơn delay tối đa (Comment)')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Convert toggles to string for storage
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $data[$key] = $value ? '1' : '0';
            }
        }

        Setting::bulkUpdate($data);

        Notification::make()
            ->title('✅ Đã lưu cài đặt')
            ->body('Tất cả thông số đã được cập nhật thành công')
            ->success()
            ->duration(3000)
            ->send();
    }

    public function syncToExtension(): void
    {
        $extensionId = Setting::getValue('default_extension_id');

        if (empty($extensionId)) {
            Notification::make()
                ->title('⚠️ Chưa có Extension ID')
                ->body('Vui lòng nhập Extension ID hoặc chờ extension kết nối tự động')
                ->warning()
                ->send();
            return;
        }

        $settings = Setting::getAllGrouped();

        event(new CampaignCommand($extensionId, 'config.update', [
            'settings' => $settings,
        ]));

        Notification::make()
            ->title('🔄 Đã đồng bộ')
            ->body("Đã gửi cài đặt tới extension {$extensionId}")
            ->success()
            ->duration(3000)
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu cài đặt')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->action('save'),
            Action::make('sync')
                ->label('Đồng bộ Extension')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('syncToExtension')
                ->requiresConfirmation()
                ->modalHeading('🔄 Đồng bộ cài đặt?')
                ->modalDescription('Gửi tất cả cài đặt hiện tại tới Chrome Extension. Đảm bảo extension đang hoạt động.'),
        ];
    }
}
