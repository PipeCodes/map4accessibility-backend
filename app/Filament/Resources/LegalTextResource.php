<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalTextResource\Pages;
use App\Models\LegalText;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use App\Helper\AppLocales;

class LegalTextResource extends Resource
{
    protected static ?string $model = LegalText::class;

    protected static ?string $navigationGroup = 'App';

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    //protected static ?string $navigationLabel = 'Custom Navigation Label';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'terms' => 'terms',
                        'privacy' => 'privacy',
                    ]),
                Forms\Components\MarkdownEditor::make('description')
                    ->required()
                    ->maxLength(65535),
                AppLocales::SelectLocales(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('locale'),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLegalTexts::route('/'),
        ];
    }
}
