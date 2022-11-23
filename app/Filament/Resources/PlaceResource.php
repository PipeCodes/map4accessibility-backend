<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Helper\Country;
use App\Models\Place;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $navigationIcon = 'heroicon-o-location-marker';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('google_place_id'),
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('place_type'),
                Forms\Components\TextInput::make('country_code')->required(),
                Forms\Components\TextInput::make('latitude')->required(),
                Forms\Components\TextInput::make('longitude')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $countryCases = Country::cases();
        $countries = array_combine(
            keys: array_column($countryCases, 'value'),
            values: array_map(fn ($c) => $c->getLabel(), $countryCases)
        );

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('google_place_id'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('country_code')
                    ->formatStateUsing(
                        fn (string $state): ?string => 
                            Country::tryFrom($state)?->getLabel() ?? null
                    ),
                Tables\Columns\TextColumn::make('place_type'),
                Tables\Columns\TextColumn::make('latitude'),
                Tables\Columns\TextColumn::make('longitude'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('country_code')->options($countries),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaces::route('/'),
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
