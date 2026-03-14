<?php

namespace App\Filament\Resources\PostTemplateResource\Pages;

use App\Filament\Resources\PostTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPostTemplates extends ListRecords
{
    protected static string $resource = PostTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('✍️ Tạo mẫu mới')
                ->icon('heroicon-o-plus'),
        ];
    }
}
