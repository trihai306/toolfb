<?php

namespace App\Filament\Resources\CommentCampaignResource\Pages;

use App\Filament\Resources\CommentCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommentCampaign extends EditRecord
{
    protected static string $resource = CommentCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
