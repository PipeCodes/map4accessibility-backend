<?php

namespace App\Filament\Resources;

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
                Forms\Components\Select::make('app_user_id')
                    ->relationship('appUser', 'email'),
                Forms\Components\Select::make('place_id')
                ->relationship('place', 'name'),
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
                Tables\Columns\TextColumn::make('appUser.email')->searchable()
                    ->url(fn ($record) => "app-users/{$record->appUser->id}/edit"),
                Tables\Columns\TextColumn::make('place.name')->searchable(),
                Tables\Columns\BooleanColumn::make('thumb_direction'),
                Tables\Columns\TextColumn::make('comment')->searchable(),
                Tables\Columns\TextColumn::make('questions_answers')->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('appUser')->relationship('appUser', 'email'),
                SelectFilter::make('place')->relationship('place', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
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
