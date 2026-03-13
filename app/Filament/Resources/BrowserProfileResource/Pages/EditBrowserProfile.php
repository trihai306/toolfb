<?php

namespace App\Filament\Resources\BrowserProfileResource\Pages;

use App\Filament\Resources\BrowserProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrowserProfile extends EditRecord
{
    protected static string $resource = BrowserProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
