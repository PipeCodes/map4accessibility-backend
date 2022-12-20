<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppUserResource\Pages;
use App\Models\AppUser;
use Closure;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;

class AppUserResource extends Resource
{
    protected static ?string $model = AppUser::class;

    protected static ?string $navigationGroup = 'App';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('surname')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()->label('New password')->reactive()->rules(['confirmed'])
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\TextInput::make('password_confirmation')
                    ->password()->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->hidden(fn (Closure $get) => $get('password') == null),
                Forms\Components\Select::make('disabilities')
                    ->multiple()->searchable()->disablePlaceholderSelection()
                    ->options(__('validation.disabilities')),
                Forms\Components\DatePicker::make('birthdate')
                    ->required(),
                Forms\Components\FileUpload::make('avatar')
                    ->image(),
                Forms\Components\Toggle::make('terms_accepted')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_status_id'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\BooleanColumn::make('terms_accepted'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AppUserResource\Widgets\AppUserComments::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppUsers::route('/'),
            'create' => Pages\CreateAppUser::route('/create'),
            'edit' => Pages\EditAppUser::route('/{record}/edit'),
        ];
    }
}
