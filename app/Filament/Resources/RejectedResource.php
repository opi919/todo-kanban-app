<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RejectedResource\Pages;
use App\Filament\Resources\RejectedResource\RelationManagers;
use App\Models\Rejected;
use App\Models\Task;
use App\TaskStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RejectedResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $modelLabel = 'Rejected Task';
    protected static ?string $navigationIcon = 'heroicon-o-x-circle';
    protected static ?string $navigationGroup = 'Task Management';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        //get task with waiting status
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('status', '=', TaskStatus::rejected)
            ->with(['assignedUsers', 'creator', 'organization']);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn(TaskStatus $state): string => $state->getLabel())
                    ->colors([
                        'secondary' => TaskStatus::Pending,
                        'warning' => TaskStatus::InProgress,
                        'success' => TaskStatus::Completed,
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'secondary' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                        'danger' => 'urgent',
                    ]),

                Tables\Columns\TextColumn::make('assignedUsers.name')
                    ->label('Assigned To')
                    ->badge()
                    ->separator(',')
                    ->visible($user->isAdmin()),

                Tables\Columns\TextColumn::make('assigned_users_names')
                    ->label('Assigned To')
                    ->visible(!$user->isAdmin())
                    ->getStateUsing(fn(Task $record) => $record->assignedUsers->pluck('name')->join(', ')),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->visible($user->isSuperAdmin()),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->visible($user->isAdmin()),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TaskStatus::class),

                Tables\Filters\SelectFilter::make('assignedUsers')
                    ->label('Assigned To')
                    ->relationship('assignedUsers', 'name')
                    ->options($user->getAssignableUsersQuery()->pluck('name', 'id'))
                    ->visible($user->isAdmin()),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),

                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->visible($user->isSuperAdmin()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn(Task $record) => $record->status === TaskStatus::rejected && $record->canBeEditedBy(auth()->user()))
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        $record->status = TaskStatus::Pending;
                        $record->save();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $user->isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => $user->isAdmin()),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRejecteds::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = Task::query()->forUser($user)->where('status', TaskStatus::rejected)->count();
        return $count > 0 ? (string) $count : null;
    }
}
