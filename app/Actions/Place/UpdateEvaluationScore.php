<?php

namespace App\Actions\Place;

use App\Helper\Evaluation;
use App\Models\Place;
use App\Models\PlaceEvaluation;

class UpdateEvaluationScore
{
    /**
     * Calculates and updates, via a formula, the evaluation score a Place.
     */
    public function __invoke(Place $place, PlaceEvaluation $evaluation): Place
    {
        $w = config('evaluation.alignment_w');
        $z = config('evaluation.alignment_z');

        $current = $place->evaluation_score;

        if (! $current) {
            return tap($place)->update([
                'evaluation_score' => $evaluation->evaluation->value,
            ]);
        }

        $new = $evaluation->evaluation->value;

        if (
            $new === Evaluation::Neutral->value
            && $current >= Evaluation::Neutral->value
        ) {
            $new = Evaluation::Neutral->value + $z;
        } elseif (
            $new === Evaluation::Neutral->value
            && $current < Evaluation::Neutral->value
        ) {
            $new = Evaluation::Neutral->value - $z;
        }

        $score = $w * $new + (1 - $w) * $current;

        return tap($place)->update([
            'evaluation_score' => round($score, 2),
        ]);
    }
}
