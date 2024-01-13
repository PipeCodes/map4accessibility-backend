<?php

namespace App\Http\Resources;

use App\Helper\QuestionType;
use App\Models\RateQuestion;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceRateSettingsResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'questions' => $this->resource->get('questions', [])
                ->map(function (RateQuestion $question) {
                    $question->is_mandatory =
                        $question->question_type === QuestionType::Mandatory;

                    unset($question->question_type);

                    return $question;
                }),
            'country_responsibles' => $this->resource->get(
                'country_responsibles',
                []
            ),
        ];
    }
}
