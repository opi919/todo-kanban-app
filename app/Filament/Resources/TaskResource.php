<?php

namespace App\Filament\Resources;

use App\TaskStatus;
use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\User;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Task Management';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forUser(auth()->user())
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('status', '!=', TaskStatus::rejected)
            ->where('status', '!=', TaskStatus::waiting)
            ->with(['assignedUsers', 'creator', 'organization']);
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3),

                Forms\Components\Select::make('status')
                    ->options(
                        $user->isAdmin() ? collect(TaskStatus::cases())
                            ->reject(fn($status) => $status === TaskStatus::waiting)
                            ->reject(fn($status) => $status === TaskStatus::rejected)
                            ->mapWithKeys(fn($status) => [$status->value => $status->getLabel()])
                            ->toArray() : TaskStatus::class
                    )
                    ->default($user->isAdmin() ? TaskStatus::Pending : TaskStatus::waiting)
                    ->required(),

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
                    ->label('Assigned To')
                    ->relationship('assignedUsers', 'name')
                    ->options(function () use ($user) {
                        return $user->getAssignableUsersQuery()->pluck('name', 'id');
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(),

                $user->isSuperAdmin()
                    ? Forms\Components\Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->options(\App\Models\Organization::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    : Forms\Components\Hidden::make('organization_id')
                    ->default($user->organization_id),
                    
                Forms\Components\DatePicker::make('due_date'),

                Forms\Components\Hidden::make('created_by')
                    ->default($user->id),

                Forms\Components\Textarea::make('comment')
                    ->rows(2),
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
                        'danger' => TaskStatus::rejected,
                        'blue' => TaskStatus::waiting,
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

                Tables\Columns\TextColumn::make('comment')
                    ->label('Comment')
                    ->searchable()
                    ->limit(50),

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(Task $record) => $record->canBeEditedBy(auth()->user()) && $user->isAdmin()),
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

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission('assign_tasks') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = Task::query()->forUser($user)
            ->where('status', '!=', TaskStatus::rejected)
            ->where('status', '!=', TaskStatus::waiting)
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return auth()->user()->isAdmin() ? 'warning' : 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
