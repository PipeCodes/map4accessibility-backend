<?php

namespace App\Models;

use App\Helper\Evaluation;
use CloudinaryLabs\CloudinaryLaravel\MediaAlly;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AppUser.
 *
 * @author  Miguel Fortes <miguel.fortes@pipecodes.com>
 *
 * @OA\Schema(
 *      schema="PlaceEvaluation",
 *      type="object",
 *
 *      @OA\Property(
 *          readOnly=1,
 *          property="id",
 *          format="int64",
 *          description="placeEvaluationId",
 *          title="ID",
 *      ),
 *      @OA\Property(
 *          property="evaluation",
 *          type="integer",
 *          minimum=0,
 *          maximum=2,
 *          description="Evaluation (0 = Inaccessible, 1 = Neutral, 2 = Accessible)",
 *          title="Evaluation"
 *      ),
 *      @OA\Property(
 *          property="comment",
 *          description="Comment",
 *          title="Comment"
 *      ),
 *      @OA\Property(
 *          property="questions_answers",
 *          type="object",
 *          description="Questions Answers JSON",
 *          title="Questions Answers JSON",
 *          example={}
 *      ),
 *      @OA\Property(
 *          readOnly=1,
 *          property="media_url",
 *          description="Media Url Cloudinary",
 *          title="Media Url Cloudinary"
 *      ),
 *      @OA\Property(
 *          readOnly=1,
 *          property="place",
 *          description="Place",
 *          title="Place",
 *          type="object",
 *          ref="#/components/schemas/Place"
 *      ),
 *      @OA\Property(
 *          readOnly=1,
 *          property="app_user",
 *          description="App User",
 *          title="App User",
 *          type="object",
 *          ref="#/components/schemas/AppUser"
 *      )
 *)
 */
class PlaceEvaluation extends Model
{
    use HasFactory, MediaAlly, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'evaluation',
        'comment',
        'questions_answers',
        'app_user_id',
        'place_id',
        'status',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['media_url'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'evaluation' => Evaluation::class,
        'questions_answers' => 'array',
    ];

    protected $hidden = [
        'app_user_id',
        'place_id',
    ];

    public function appUser()
    {
        return $this->belongsTo(AppUser::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Get the media from cloud.
     */
    protected function mediaUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fetchLastMedia() ? $this->fetchLastMedia()->getSecurePath() : null,
        );
    }
}
