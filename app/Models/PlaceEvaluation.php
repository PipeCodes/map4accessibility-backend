<?php

namespace App\Models;

use CloudinaryLabs\CloudinaryLaravel\Model\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CloudinaryLabs\CloudinaryLaravel\MediaAlly;


/**
 * Class AppUser.
 *
 * @author  Miguel Fortes <miguel.fortes@pipecodes.com>
 *
 *
 * @OA\Schema(
 *      schema="PlaceEvaluation",
 *      type="object",
 *      @OA\Property(
 *          readOnly=1,
 *          property="id",
 *          format="int64",
 *          description="placeEvaluationId",
 *          title="ID",
 *      ),
 *      @OA\Property(
 *          property="google_place_id",
 *          format="int64",
 *          description="Google Place id",
 *          title="Google Place id",
 *          example=""
 *      ),
 *      @OA\Property(
 *          property="name",
 *          description="Name Place",
 *          title="Name"
 *      ),
 *      @OA\Property(
 *          property="country",
 *          description="country",
 *          title="country"
 *       ),
 *       @OA\Property(
 *           property="latitude",
 *           format="decimal",
 *           description="Coord. Latitude",
 *           title="Coord. Latitude",
 *           example=""
 *       ),
 *       @OA\Property(
 *           property="longitude",
 *           format="decimal",
 *           description="Coord. Longitude",
 *           title="Coord. Longitude",
 *           example=""
 *       ),
 *      @OA\Property(
 *          property="thumb_direction",
 *          type="integer",
 *          minimum=0,
 *          maximum=1,
 *          description="Thumb Direction boolean (0- thumb_down, 1- thumb_up)",
 *          title="Thumb Direction"
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
 *      )
 *)
 *
 */
class PlaceEvaluation extends Model
{
    use HasFactory, MediaAlly;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'google_place_id',
        'name',
        'country',
        'latitude',
        'longitude',
        'thumb_direction',
        'comment',
        'questions_answers',
        'app_user_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'thumb_direction' => 'boolean',
        'questions_answers' => 'array'
    ];

    protected $hidden = [];

    public function appUser()
    {
        return $this->belongsTo(AppUser::class);
    }

    public function toArray()
    {
        $parentArrayData = parent::toArray();

        /**
         * @var Media|null $mediaRecord
         */
        if ($mediaRecord = $this->fetchLastMedia()) {
            $parentArrayData['media_url'] = $mediaRecord->getSecurePath();
        }

        return $parentArrayData;
    }
}
