<?php

namespace App\Filament\Resources\LegalTextResource\Pages;

use App\Filament\Resources\LegalTextResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLegalTexts extends ManageRecords
{
    protected static string $resource = LegalTextResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
