<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="LegalText",
 *   description="Legal Text model",
 *   title="Legal Text Object",
 *   required={},
 *
 *   @OA\Property(type="integer",description="id of Legal Text",title="id",property="id",example="1",readOnly="true"),
 *   @OA\Property(type="string",title="type",property="type",example="terms"),
 *   @OA\Property(type="string",title="description",property="description",example="This is a Legal Text!"),
 *   @OA\Property(type="string",title="locale",property="locale",example="en"),
 *   @OA\Property(type="dateTime",title="created_at",property="created_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 *   @OA\Property(type="dateTime",title="updated_at",property="updated_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 * )
 *
 * @OA\Schema(
 *   schema="LegalTexts",
 *   title="Legal Text Response",
 *
 *   @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
 *   @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
 *   @OA\Property(title="result",property="result",type="object",
 *      @OA\Property(title="data",property="data",type="array",
 *
 *          @OA\Items(type="object",ref="#/components/schemas/LegalText"),
 *      )
 *   )
 * )
 *
 * @OA\Parameter(
 *      parameter="LegalText--type",
 *      in="path",
 *      name="type",
 *      required=true,
 *      description="Type of Legal Text",
 *
 *      @OA\Schema(
 *          type="string",
 *          example="terms",
 *      )
 * ),
 */
class LegalText extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'locale',
    ];

    public static function terms(?string $locale = 'en')
    {
        return static::where('type', 'terms')
            ->where('locale', $locale ? strtolower($locale) : 'en')
            ->firstOrFail();
    }

    public static function privacy(?string $locale = 'en')
    {
        return static::where('type', 'privacy')
            ->where('locale', $locale ? strtolower($locale) : 'en')
            ->firstOrFail();
    }
}
