<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Organizations';

    // Only Super Admin can see this resource
    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Organization Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $context, $state, callable $set) {
                                if ($context === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash'])
                            ->helperText('URL-friendly version of the organization name'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('organizations/logos')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->circleCropper(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive organizations cannot be used'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Organization Settings')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Custom Settings')
                            ->keyLabel('Setting Name')
                            ->valueLabel('Setting Value')
                            ->default([
                                'timezone' => 'UTC',
                                'currency' => 'USD',
                                'working_hours' => '09:00-17:00',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Total Users')
                    ->getStateUsing(fn(Organization $record) => $record->users()->count())
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('admins_count')
                    ->label('Admins')
                    ->getStateUsing(fn(Organization $record) => $record->admins()->count())
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('regular_users_count')
                    ->label('Regular Users')
                    ->getStateUsing(fn(Organization $record) => $record->regularUsers()->count())
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->getStateUsing(fn(Organization $record) => $record->tasks()->count())
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All organizations')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('has_users')
                    ->label('Has Users')
                    ->query(fn(Builder $query): Builder => $query->has('users'))
                    ->toggle(),

                Tables\Filters\Filter::make('has_tasks')
                    ->label('Has Tasks')
                    ->query(fn(Builder $query): Builder => $query->has('tasks'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('view_users')
                        ->label('View Users')
                        ->icon('heroicon-o-users')
                        ->color('primary')
                        ->url(fn(Organization $record): string => UserResource::getUrl('index', ['tableFilters[organization][value]' => $record->id])),

                    Tables\Actions\Action::make('view_tasks')
                        ->label('View Tasks')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->url(fn(Organization $record): string => TaskResource::getUrl('index', ['tableFilters[organization][value]' => $record->id])),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('This will permanently delete the organization and all associated users and tasks. This action cannot be undone.'),
                ])
                    ->label('Actions')
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Organizations')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Organizations')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('This will permanently delete the selected organizations and all associated data. This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Organization::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = Organization::count();

        if ($count === 0) {
            return 'gray';
        } elseif ($count < 5) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Slug' => $record->slug,
            'Users' => $record->users()->count() . ' users',
            'Tasks' => $record->tasks()->count() . ' tasks',
            'Status' => $record->is_active ? 'Active' : 'Inactive',
        ];
    }
}
