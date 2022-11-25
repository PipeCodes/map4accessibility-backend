<?php

namespace App\Models;

use CloudinaryLabs\CloudinaryLaravel\Model\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *  @OA\Schema(
 *      schema="Place",
 *      type="object",
 *      @OA\Property(
 *          readOnly=1,
 *          property="id",
 *          format="int64",
 *          description="Place ID",
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
 *          property="place_type",
 *          description="Place Type",
 *          title="Place Type"
 *      ),
 *      @OA\Property(
 *          property="country_code",
 *          description="Country Code",
 *          title="country_code"
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
 *  )
 */
class Place extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'latitude', 'longitude', 'google_place_id',
        'name', 'country_code', 'place_type'
    ];

    public function placeEvaluations()
    {
        return $this->hasMany(PlaceEvaluation::class);
    }

    public function medias()
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
}
