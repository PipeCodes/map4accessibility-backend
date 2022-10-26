<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceEvaluationResource\Pages;
use App\Filament\Resources\PlaceEvaluationResource\RelationManagers;
use App\Models\PlaceEvaluation;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
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
                Forms\Components\TextInput::make('place_id'),
                Forms\Components\TextInput::make('google_place_id'),
                Forms\Components\Toggle::make('thumb_direction'),
                Forms\Components\Textarea::make('comment')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('questions_answers'),
                Forms\Components\FileUpload::make('attachment')
                    ->disk('cloudinary')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('place_id'),
                Tables\Columns\TextColumn::make('google_place_id'),
                Tables\Columns\BooleanColumn::make('thumb_direction'),
                Tables\Columns\TextColumn::make('comment'),
                Tables\Columns\TextColumn::make('questions_answers'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
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
