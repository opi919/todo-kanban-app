<?php

namespace App\Filament\Resources;

use App\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        return $user->getManageableUsersQuery();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->dehydrated(fn($state): bool => filled($state))
                    ->dehydrateStateUsing(fn($state): string => Hash::make($state)),

                Forms\Components\Select::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::User)
                    ->required()
                    ->disabled(fn() => !$user->isSuperAdmin()), // Only super admin can change roles

                Forms\Components\Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->default($user->organization_id) // Default to current user's organization
                    ->disabled(fn() => !$user->isSuperAdmin()) // Only super admin can change organizations
                    ->visible(fn() => $user->isSuperAdmin()),

                Forms\Components\Select::make('admin_id')
                    ->label('Admin')
                    ->options(function () use ($user) {
                        if ($user->isSuperAdmin()) {
                            return User::where('role', 'admin')->pluck('name', 'id');
                        }
                        return [$user->id => $user->name];
                    })
                    ->default($user->isOrganizationAdmin() ? $user->id : null)
                    ->disabled(fn() => $user->isOrganizationAdmin()) // Org admin can't change admin assignment
                    ->visible(fn(string $context) => $context === 'create' || $user->isSuperAdmin()),

                Forms\Components\Hidden::make('admin_id')
                    ->default($user->id)
                    ->visible(fn() => $user->isOrganizationAdmin()),

                Forms\Components\Hidden::make('organization_id')
                    ->default($user->organization_id)
                    ->visible(fn() => $user->isOrganizationAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->formatStateUsing(fn(UserRole $state): string => $state->getLabel())
                    ->colors([
                        'primary' => UserRole::Admin,
                        'secondary' => UserRole::User,
                    ]),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->visible($user->isSuperAdmin()),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Admin')
                    ->visible($user->isSuperAdmin()),

                Tables\Columns\TextColumn::make('assigned_tasks_count')
                    ->label('Assigned Tasks')
                    ->getStateUsing(fn(User $record) => $record->assignedTasks()->count()),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(UserRole::class),

                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->visible($user->isSuperAdmin()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
