<?php

namespace App\Models;

use CloudinaryLabs\CloudinaryLaravel\MediaAlly;
use CloudinaryLabs\CloudinaryLaravel\Model\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *  @OA\Schema(
 *      schema="Place",
 *      type="object",
 *
 *      @OA\Property(
 *          readOnly=1,
 *          property="id",
 *          format="int64",
 *          description="Place ID",
 *          title="ID",
 *      ),
 *      @OA\Property(
 *          property="google_place_id",
 *          description="Google Place id",
 *          title="Google Place id",
 *          example="",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="name",
 *          description="Name Place",
 *          title="Name"
 *      ),
 *      @OA\Property(
 *          property="place_type",
 *          description="Place Type",
 *          title="Place Type"
 *      ),
 *      @OA\Property(
 *          property="country_code",
 *          description="Country Code",
 *          title="country_code"
 *       ),
 *      @OA\Property(
 *          property="city",
 *          description="Place City",
 *          title="Place City",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="address",
 *          description="Place Address",
 *          title="Place Address",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="phone",
 *          description="Place Phone",
 *          title="Place Phone",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="email",
 *          description="Place Email",
 *          title="Place Email",
 *          format="email",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="website",
 *          description="Place Website",
 *          title="Place Website",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="schedule",
 *          description="Place Schedule",
 *          title="Place Schedule",
 *          type="string",
 *      ),
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
 *           property="evaluation_score",
 *           format="decimal",
 *           description="Current evaluation score calculation",
 *           title="Current evaluation score calculation",
 *           example="2.5"
 *       ),
 *      @OA\Property(
 *          property="disabilities",
 *          description="Disabilities",
 *          title="Disabilities",
 *          type="array",
 *
 *          @OA\Items(type="string"),
 *          example={"visual", "motor"}
 *      ),
 *  )
 */
class Place extends Model
{
    use HasFactory, MediaAlly, SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['media'];

    protected $fillable = [
        'latitude',
        'longitude',
        'google_place_id',
        'name',
        'country_code',
        'place_type',
        'city',
        'address',
        'phone',
        'email',
        'website',
        'schedule',
        'evaluation_score',
        'disabilities',
    ];

    protected $casts = [
        'ratio_up_down' => 'float',
        'ratio_down_up' => 'float',
        'disabilities' => 'array',
    ];

    public function placeEvaluations()
    {
        return $this->hasMany(PlaceEvaluation::class);
    }

    public function placeDeletion()
    {
        return $this->hasMany(PlaceDeletion::class);
    }

    /**
     * Get the media from cloud.
     */
    protected function media(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fetchLastMedia() ? $this->fetchLastMedia()->getSecurePath() : null,
        );
    }

    public function mediaEvaluations()
    {
        return $this->hasManyThrough(
            Media::class,
            PlaceEvaluation::class,
            'place_id',
            'medially_id',
            'id',
            'id'
        );
    }

    public function creator()
    {
        return $this
            ->belongsToMany(AppUser::class, 'place_evaluations')
            ->oldest()
            ->take(1);
    }
}
