<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="RateAnswer",
 *   description="Rate Answer model",
 *   title="Rate Answer Object",
 *   required={},
 *   @OA\Property(type="integer",description="Answers's ID",title="id",property="id",example="1",readOnly="true"),
 *   @OA\Property(type="integer",description="Answers's Question ID",title="rate_question_id",property="rate_question_id",example="1",readOnly="true"),
 *   @OA\Property(type="integer",description="Answer's placement order",title="order",property="order",example="1",readOnly="true"),
 *   @OA\Property(type="string",title="body",property="body",example="My Answer"),
 *   @OA\Property(type="string",title="slug",property="slug",example="my-answer"),
 *   @OA\Property(type="dateTime",title="created_at",property="created_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 *   @OA\Property(type="dateTime",title="updated_at",property="updated_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 * )
 */
class RateAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'order', 'body', 'slug', 'rate_question_id'
    ];
}
