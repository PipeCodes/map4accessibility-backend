<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use App\Helper\AppLocales;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationGroup = 'App';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    //protected static ?string $navigationLabel = 'Custom Navigation Label';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('question')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('answer')
                    ->required()
                    ->maxLength(65535),
                Forms\Components\TextInput::make('order')
                    ->required(),
                AppLocales::SelectLocales(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question'),
                Tables\Columns\TextColumn::make('answer'),
                Tables\Columns\TextColumn::make('order'),
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
            'index' => Pages\ManageFaqs::route('/'),
        ];
    }
}
