<?php

namespace App\Filament\Resources\PlaceEvaluationResource\Pages;

use App\Filament\Resources\PlaceEvaluationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPlaceEvaluation extends ViewRecord
{
    protected static string $resource = PlaceEvaluationResource::class;

    protected function getActions(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PlaceEvaluationResource\Widgets\QuestionsAnswers::class,
        ];
    }
}
