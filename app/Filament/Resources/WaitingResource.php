<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaitingResource\Pages;
use App\Filament\Resources\WaitingResource\RelationManagers;
use App\Models\Task;
use App\Models\Waiting;
use App\TaskStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WaitingResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $modelLabel = 'Waiting Task';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Task Management';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        //get task with waiting status
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('status', '=', TaskStatus::waiting)
            ->with(['assignedUsers', 'creator', 'organization']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3),

                Forms\Components\Select::make('status')
                    ->options([
                        TaskStatus::waiting->value => TaskStatus::waiting->getLabel(),
                    ])
                    ->default(TaskStatus::waiting)
                    ->required()
                    ->disabled(),

                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->default('medium')
                    ->required(),

                Forms\Components\Select::make('assignedUsers')
                    ->relationship('assignedUsers', 'name')
                    ->multiple()
                    ->preload()
                    ->visible(auth()->user()->isAdmin()),

                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->visible(auth()->user()->isSuperAdmin()),

                Forms\Components\DatePicker::make('due_date')
                    ->required(),
            ]);
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
                    ->visible(fn(Task $record) => $record->status === TaskStatus::waiting && $user->isAdmin())
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        $record->status = TaskStatus::Pending;
                        $record->save();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn(Task $record) => $record->status === TaskStatus::waiting && $user->isAdmin())
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        $record->status = TaskStatus::rejected;
                        $record->save();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(Task $record) => $record->canBeEditedBy(auth()->user())),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListWaitings::route('/'),
            'edit' => Pages\EditWaiting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = Task::query()->forUser($user)->where('status', TaskStatus::waiting)->count();
        return $count > 0 ? (string) $count : null;
    }
}
