<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppUserResource\Pages\EditAppUser;
use App\Filament\Resources\PlaceEvaluationResource\Pages;
use App\Models\PlaceEvaluation;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlaceEvaluationResource extends Resource
{
    protected static ?string $model = PlaceEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('google_place_id'),
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('country')->required(),
                Forms\Components\TextInput::make('latitude')->required(),
                Forms\Components\TextInput::make('longitude')->required(),
                Forms\Components\Toggle::make('thumb_direction'),
                Forms\Components\Textarea::make('comment')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('questions_answers'),
                Forms\Components\TextInput::make('media_url'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('appUser.email')
                    ->url(fn ($record) => 
                        "app-users/{$record->appUser->id}/edit"),
                Tables\Columns\TextColumn::make('google_place_id'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('latitude'),
                Tables\Columns\TextColumn::make('longitude'),
                Tables\Columns\BooleanColumn::make('thumb_direction'),
                Tables\Columns\TextColumn::make('comment'),
                Tables\Columns\TextColumn::make('questions_answers'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('appUser')->relationship('appUser', 'email'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListPlaceEvaluations::route('/'),
            'view' => Pages\ViewPlaceEvaluation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
