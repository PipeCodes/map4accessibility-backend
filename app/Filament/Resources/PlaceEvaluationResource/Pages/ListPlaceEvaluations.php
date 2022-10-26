<?php

namespace App\Filament\Resources\PlaceEvaluationResource\Pages;

use App\Filament\Resources\PlaceEvaluationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlaceEvaluations extends ListRecords
{
    protected static string $resource = PlaceEvaluationResource::class;

    protected function getActions(): array
    {
        return [];
    }
}
