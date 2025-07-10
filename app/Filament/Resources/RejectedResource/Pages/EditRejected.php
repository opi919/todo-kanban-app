<?php

namespace App\Filament\Resources\RejectedResource\Pages;

use App\Filament\Resources\RejectedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRejected extends EditRecord
{
    protected static string $resource = RejectedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
