<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="FAQ",
 *   description="FAQ model",
 *   title="FAQ Object",
 *   required={},
 *   @OA\Property(type="integer",description="id of Book",title="id",property="id",example="1",readOnly="true"),
 *   @OA\Property(type="string",title="question",property="question",example="Is this a question?"),
 *   @OA\Property(type="string",title="answer",property="answer",example="This is an answer!"),
 *   @OA\Property(type="integer",title="order",property="order",example="1"),
 *   @OA\Property(type="string",title="locale",property="locale",example="en"),
 *   @OA\Property(type="dateTime",title="created_at",property="created_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 *   @OA\Property(type="dateTime",title="updated_at",property="updated_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 * )
 *
 * @OA\Schema(
 *   schema="FAQs",
 *   title="FAQ Response",
 *   @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
 *   @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
 *   @OA\Property(title="result",property="result",type="object",
 *      @OA\Property(title="data",property="data",type="array",
 *          @OA\Items(type="object",ref="#/components/schemas/FAQ"),
 *      )
 *   )
 * )
 */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'order',
        'locale',
    ];
}
