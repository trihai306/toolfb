<?php

namespace App\Filament\Resources\ScheduledPostResource\Pages;

use App\Events\CampaignCommand;
use App\Filament\Resources\ScheduledPostResource;
use App\Models\BrowserProfile;
use App\Models\FacebookGroup;
use App\Models\ScheduledPost;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListScheduledPosts extends ListRecords
{
    protected static string $resource = ScheduledPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quickPost')
                ->label('⚡ Đăng ngay')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->form([
                    Select::make('browser_profile_id')
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
                        ->helperText('Chỉ hiện profile đã kết nối extension'),

                    Textarea::make('content')
                        ->label('Nội dung bài đăng')
                        ->placeholder('Nhập nội dung bài đăng...')
                        ->rows(4)
                        ->required()
                        ->helperText('💡 Dùng {spin|text1|text2} để xoay vòng nội dung'),

                    FileUpload::make('images')
                        ->label('Hình ảnh')
                        ->multiple()
                        ->image()
                        ->directory('post-images')
                        ->helperText('Tùy chọn: upload ảnh kèm bài'),

                    CheckboxList::make('group_ids')
                        ->label('Chọn nhóm đăng')
                        ->options(function (callable $get) {
                            $profileId = $get('browser_profile_id');
                            if (!$profileId) return [];
                            return FacebookGroup::where('browser_profile_id', $profileId)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($g) => [
                                    $g->group_id => $g->name . ($g->member_count ? " ({$g->member_count} TV)" : ''),
                                ]);
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(1)
                        ->required()
                        ->helperText(function (callable $get) {
                            $profileId = $get('browser_profile_id');
                            if (!$profileId) return '⚠️ Chọn Profile trước';
                            $count = FacebookGroup::where('browser_profile_id', $profileId)->count();
                            return $count > 0
                                ? "📌 {$count} nhóm khả dụng"
                                : '⚠️ Chưa sync nhóm. Vào Nhóm Facebook → Đồng bộ';
                        }),
                ])
                ->action(function (array $data) {
                    $profile = BrowserProfile::find($data['browser_profile_id']);
                    if (!$profile || !$profile->extension_id) {
                        Notification::make()
                            ->title('❌ Profile chưa kết nối extension')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Create a scheduled post with scheduled_at = now
                    $post = ScheduledPost::create([
                        'browser_profile_id' => $profile->id,
                        'content' => $data['content'],
                        'images' => $data['images'] ?? [],
                        'group_ids' => $data['group_ids'],
                        'settings' => ['delay_min' => 30, 'delay_max' => 60],
                        'status' => 'pending',
                        'scheduled_at' => now(),
                    ]);

                    Notification::make()
                        ->title('⚡ Đã tạo bài đăng ngay')
                        ->body("Bài đăng tới " . count($data['group_ids']) . " nhóm sẽ được xử lý trong vòng 1 phút")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('📅 Lên lịch')
                ->icon('heroicon-o-calendar'),
        ];
    }
}
