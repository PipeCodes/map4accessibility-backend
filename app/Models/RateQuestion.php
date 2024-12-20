<?php

namespace App\Models;

use App\Helper\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="RateQuestion",
 *   description="Rate Question model",
 *   title="Rate Question Object",
 *   required={},
 *
 *   @OA\Property(type="integer",description="Question's ID",title="id",property="id",example="1",readOnly="true"),
 *   @OA\Property(type="string",title="title",property="title",example="My Question"),
 *   @OA\Property(type="string",title="slug",property="slug",example="my-question"),
 *   @OA\Property(type="string",title="place_type",property="place_type",example="type3"),
 *   @OA\Property(type="boolean",title="is_mandatory",property="is_mandatory",example="true"),
 *   @OA\Property(type="dateTime",title="created_at",property="created_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 *   @OA\Property(type="dateTime",title="updated_at",property="updated_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 *   @OA\Property(title="answers",property="answers",type="array",
 *
 *      @OA\Items(type="object",ref="#/components/schemas/RateAnswer")
 *   ),
 * )
 */
class RateQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'slug', 'title', 'place_type', 'question_type', 'locale',
    ];

    protected $casts = [
        'question_type' => QuestionType::class,
    ];

    /**
     * Answers relation.
     */
    public function answers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RateAnswer::class)->orderBy('order');
    }
}
