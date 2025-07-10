<?php

namespace App\Filament\Resources\WaitingResource\Pages;

use App\Filament\Resources\WaitingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWaiting extends EditRecord
{
    protected static string $resource = WaitingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
