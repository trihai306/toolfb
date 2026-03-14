<?php

namespace App\Filament\Resources\PostTemplateResource\Pages;

use App\Filament\Resources\PostTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPostTemplate extends EditRecord
{
    protected static string $resource = PostTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
