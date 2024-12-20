<?php

namespace App\Models;

use App\Helper\PlaceDeletionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Place Deletion model.
 *
 *  *  @OA\Schema(
 *      schema="PlaceDeletion",
 *      type="object",
 *
 *      @OA\Property(
 *          property="place_id",
 *          description="Place id",
 *          title="Place id",
 *          example="",
 *          type="integer"
 *      ),
 *      @OA\Property(
 *          property="app_user_id",
 *          description="App User id",
 *          title="App User id",
 *          example="",
 *          type="integer"
 *      ),
 *      @OA\Property(
 *          property="status",
 *          description="Current status",
 *          title="Current status",
 *          example="",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="created_at",
 *          description="Deletion creation timestamp",
 *          title="Deletion creation timestamp",
 *          example="",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="updated_at",
 *          description="Last update timestamp",
 *          title="Last update timestamp",
 *          example="",
 *          type="string"
 *      ),
 *  )
 *
 * @property int $id
 * @property-read \App\Models\Place $place
 * @property int $place_id
 * @property-read \App\Models\AppUser $appUser
 * @property int $app_user_id
 * @property \App\Helper\PlaceDeletionStatus $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PlaceDeletion extends Model
{
    use HasFactory;

    /**
     * PlaceDeletion model casts.
     *
     * @var array
     */
    protected $casts = [
        'status' => PlaceDeletionStatus::class,
    ];

    /**
     * Model's fillable property.
     *
     * @var array
     */
    protected $fillable = [
        'place_id',
        'app_user_id',
        'status',
    ];

    /**
     * Closes the Place Deletion.
     * Returns false if the action of closing the deletion
     * is not at the proper stage, i.e., the status is not Pending.
     */
    public function close(): PlaceDeletion|bool
    {
        if ($this->status !== PlaceDeletionStatus::Pending) {
            return false;
        }

        $this->status = PlaceDeletionStatus::Closed;

        $this->save();

        return $this;
    }

    /**
     * Returns the Place for which this deletion belongs to.
     */
    public function place(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Returns the AppUser for which this deletion belongs to.
     */
    public function appUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
