<?php

namespace App\Filament\Resources\PlaceEvaluationResource\Widgets;

use App\Models\PlaceEvaluation;
use Filament\Widgets\Widget;

class QuestionsAnswers extends Widget
{
    protected static string $view = 'filament.resources.place-evaluation-resource.widgets.questions-answers';

    protected int|string|array $columnSpan = 'full';

    public ?PlaceEvaluation $record = null;
}
