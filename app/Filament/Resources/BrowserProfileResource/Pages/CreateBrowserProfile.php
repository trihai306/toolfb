<?php

namespace App\Filament\Resources\BrowserProfileResource\Pages;

use App\Filament\Resources\BrowserProfileResource;
use App\Models\BrowserProfile;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBrowserProfile extends CreateRecord
{
    protected static string $resource = BrowserProfileResource::class;

    protected function afterCreate(): void
    {
        // Auto-generate API token on create
        $token = BrowserProfile::generateToken($this->record);

        Notification::make()
            ->title('🔑 API Token đã tạo')
            ->body("Sao chép ngay - chỉ hiện 1 lần!\n\n`{$token}`")
            ->success()
            ->persistent()
            ->send();
    }
}
