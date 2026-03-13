<?php

namespace App\Filament\Resources\CommentCampaignResource\Pages;

use App\Filament\Resources\CommentCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommentCampaigns extends ListRecords
{
    protected static string $resource = CommentCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
