<?php

namespace App\Filament\Resources\PostLogResource\Pages;

use App\Filament\Resources\PostLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPostLogs extends ListRecords
{
    protected static string $resource = PostLogResource::class;
}
