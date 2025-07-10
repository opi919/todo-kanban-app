<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Organization Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('logo')
                                    ->circular()
                                    ->size(80),
                                
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->size('lg')
                                            ->weight('bold'),
                                        
                                        TextEntry::make('slug')
                                            ->fontFamily('mono')
                                            ->color('gray'),
                                        
                                        TextEntry::make('is_active')
                                            ->badge()
                                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),
                                    ]),
                            ]),

                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('users_count')
                                    ->label('Total Users')
                                    ->getStateUsing(fn ($record) => $record->users()->count())
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('admins_count')
                                    ->label('Admins')
                                    ->getStateUsing(fn ($record) => $record->admins()->count())
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('regular_users_count')
                                    ->label('Regular Users')
                                    ->getStateUsing(fn ($record) => $record->regularUsers()->count())
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('tasks_count')
                                    ->label('Tasks')
                                    ->getStateUsing(fn ($record) => $record->tasks()->count())
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),

                Section::make('Settings')
                    ->schema([
                        KeyValueEntry::make('settings')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime(),
                                
                                TextEntry::make('updated_at')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}