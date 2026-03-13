<?php

namespace App\Filament\Resources\BrowserProfileResource\Pages;

use App\Filament\Resources\BrowserProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListBrowserProfiles extends ListRecords
{
    protected static string $resource = BrowserProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Tạo Profile')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
