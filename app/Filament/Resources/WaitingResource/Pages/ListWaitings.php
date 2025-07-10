<?php

namespace App\Filament\Resources\WaitingResource\Pages;

use App\Filament\Resources\WaitingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaitings extends ListRecords
{
    protected static string $resource = WaitingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
