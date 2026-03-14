<?php

namespace App\Filament\Resources\FacebookGroupResource\Pages;

use App\Events\CampaignCommand;
use App\Filament\Resources\FacebookGroupResource;
use App\Models\BrowserProfile;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFacebookGroups extends ListRecords
{
    protected static string $resource = FacebookGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncGroups')
                ->label('🔄 Đồng bộ nhóm')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->form([
                    Select::make('browser_profile_id')
                        ->label('Chọn tài khoản')
                        ->options(
                            BrowserProfile::whereNotNull('extension_id')
                                ->where('extension_id', '!=', '')
                                ->get()
                                ->mapWithKeys(fn ($profile) => [
                                    $profile->id => ($profile->facebook_name ?? $profile->name) . ' — ' . ($profile->facebook_uid ? "FB:{$profile->facebook_uid}" : 'Chưa sync FB')
                                ])
                        )
                        ->required()
                        ->searchable()
                        ->placeholder('Chọn profile trình duyệt...')
                        ->helperText('Chỉ hiện profile đã kết nối extension'),
                ])
                ->action(function (array $data) {
                    $profile = BrowserProfile::find($data['browser_profile_id']);

                    if (!$profile || empty($profile->extension_id)) {
                        Notification::make()
                            ->title('❌ Lỗi')
                            ->body('Profile không có extension_id. Extension chưa kết nối.')
                            ->danger()
                            ->send();
                        return;
                    }

                    event(new CampaignCommand(
                        $profile->extension_id,
                        'extension.command',
                        [
                            'command' => 'FETCH_GROUPS',
                            'data' => ['profile_id' => $profile->id],
                            'timestamp' => now()->toISOString(),
                        ]
                    ));

                    Notification::make()
                        ->title('📡 Đã gửi lệnh đồng bộ nhóm')
                        ->body("Extension trên \"{$profile->name}\" sẽ mở Facebook → quét nhóm → sync về server.\nĐợi 30-60 giây rồi refresh trang.")
                        ->success()
                        ->duration(6000)
                        ->send();
                }),
        ];
    }
}
