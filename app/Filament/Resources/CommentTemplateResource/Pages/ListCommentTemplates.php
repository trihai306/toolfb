<?php

namespace App\Filament\Resources\CommentTemplateResource\Pages;

use App\Filament\Resources\CommentTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommentTemplates extends ListRecords
{
    protected static string $resource = CommentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
