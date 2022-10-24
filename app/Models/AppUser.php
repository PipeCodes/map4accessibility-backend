<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\AppUserResetPasswordNotification;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class AppUser.
 *
 * @author  Miguel Fortes <miguel.fortes@pipecodes.com>
 *
 *
 * @OA\Schema(
 *  schema="AppUser",
 * type="object",
 *   @OA\Property(
 *      property="id",
 *     format="int64",
 *     description="ID",
 *     title="ID",
 * ),
 * @OA\Property(
 *      property="email",
 *     format="email",
 *     description="Email",
 *     title="Email",
 *      example="user@example.com"
 * ),
 * @OA\Property(
 *      property="password",
 *     description="Password",
 *     title="Password"
 * ),
 * @OA\Property(
 *      property="name",
 *     description="Name",
 *     title="Name"
 * ),
 * @OA\Property(
 *      property="surname",
 *     description="Surname",
 *     title="Surname"
 * ),
 * @OA\Property(
 *       property="birthdate",
 *     description="Birthdate",
 *     title="Birthdate",
 *     format="date"
 * ),
 * @OA\Property(
 *      property="disabilities",
 *     description="Disabilities",
 *     title="Disabilities",
 *     type="array",
 *      @OA\Items(type="string"),
 *     example={"visual", "motor"}
 * ),
 * @OA\Property(
 *      property="terms_accepted",
 *     type="bool",
 *     description="Terms Accepted",
 *     title="Terms Accepted"
 * ),
 * @OA\Property(
 *      property="auth_providers",
 *     type="object",
 *     additionalProperties={"type":"string"},
 *     example={"facebook": 123, "gmail":456456546},
 *     description="Auth Providers",
 *     title="Auth Providers"
 * )
 * )
 *
 */
class AppUser extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'birthdate',
        'disabilities',
        'avatar',
        'password',
        'terms_accepted',
        'auth_providers',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'terms_accepted' => 'boolean',
        'auth_providers' => 'array',
        'disabilities' => 'array',
    ];

    protected $hidden = [
        'password'
    ];

    public function accountStatus()
    {
        return $this->belongsTo(AccountStatus::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new AppUserResetPasswordNotification($token));
    }
}
