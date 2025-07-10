<?php

namespace App\Filament\Resources\RejectedResource\Pages;

use App\Filament\Resources\RejectedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRejecteds extends ListRecords
{
    protected static string $resource = RejectedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
