<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceEvaluationResource\Pages;
use App\Helper\Country;
use App\Helper\Evaluation;
use App\Models\PlaceEvaluation;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

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
                Forms\Components\Select::make('evaluation')
                    ->options(Evaluation::array()),
                Forms\Components\TextInput::make('media_url'),
                Forms\Components\Textarea::make('comment')
                    ->columnSpan(2)
                    ->maxLength(65535),
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
                Tables\Columns\TextColumn::make('appUser.email')->searchable()
                    ->url(fn ($record) => "app-users/{$record->appUser->id}/edit"),
                Tables\Columns\TextColumn::make('place.name')->searchable(),
                Tables\Columns\TextColumn::make('place.latitude'),
                Tables\Columns\TextColumn::make('place.longitude'),
                Tables\Columns\TextColumn::make('evaluation')
                    ->getStateUsing(function ($record) {
                        return $record->evaluation->name;
                    }),
                Tables\Columns\TextColumn::make('comment')->searchable(),
                Tables\Columns\TextColumn::make('questions_answers')
                    ->getStateUsing(function ($record) {
                        return count($record->questions_answers ?? []).' questions answered';
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('appUser')->relationship('appUser', 'email'),
                SelectFilter::make('place')->relationship('place', 'name'),
                SelectFilter::make('country_code')->options($countries)->query(function (Builder $query, array $data): Builder {
                    if (isset($data['value'])) {
                        return $query->whereHas('place', function ($query) use ($data) {
                            $query->where('country_code', '=', $data['value']);
                        });
                    }

                    return $query;
                }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
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

    public static function getWidgets(): array
    {
        return [
            AppUserResource\Widgets\AppUserComments::class,
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
