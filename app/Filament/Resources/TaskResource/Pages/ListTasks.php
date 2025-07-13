<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        if (!auth()->user()?->isAdmin()) {
            return [];
        }
        return [
            Actions\CreateAction::make(),
        ];
    }
}
