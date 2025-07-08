<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TodoResource\Pages;
use App\Filament\Resources\TodoResource\RelationManagers;
use App\Models\Todo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\{TextInput, Select, Toggle, DatePicker};
use Filament\Tables\Columns\{TextColumn, BadgeColumn, ToggleColumn};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TodoResource extends Resource
{
    protected static ?string $model = Todo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required(),

            Select::make('user_id')
                ->relationship('user', 'name', fn($query) => $query->where('id', '!=', auth()->id()))
                ->label('Assign To')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                ])
                ->default('pending'),

            Select::make('priority')
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                ])
                ->default('medium'),

            DatePicker::make('due_date'),

            Toggle::make('is_completed')->label('Mark as Completed'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = Auth::user()->is_admin;

        return $table
            ->reorderable('order_column')
            ->defaultSort('order_column')
            ->columns([
                TextColumn::make('title')->sortable(),

                // 👇 Conditionally show Assigned To column
                ...($isAdmin ? [
                    TextColumn::make('user.name')->label('Assigned To'),
                ] : []),

                BadgeColumn::make('status')->colors([
                    'gray' => 'pending',
                    'warning' => 'in_progress',
                    'success' => 'completed',
                ]),
                BadgeColumn::make('priority')->colors([
                    'success' => 'low',
                    'warning' => 'medium',
                    'danger' => 'high',
                ]),
                TextColumn::make('due_date')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTodos::route('/'),
            'create' => Pages\CreateTodo::route('/create'),
            'edit' => Pages\EditTodo::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->is_admin) {
            // Admin sees all tasks
            return $query;
        }

        // Regular users see only their own tasks
        return $query->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return auth()->user()->is_admin;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->is_admin || $record->user_id === auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->is_admin || $record->user_id === auth()->id();
    }
}
