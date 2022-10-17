<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class AppUser.
 *
 * @author  Miguel Fortes <miguel.fortes@pipecodes.com>
 *
 *
 * @OA\Schema(schema="AppUser")
 *
 */
class AppUser extends Model
{
    use HasFactory, HasApiTokens;

    /**
     * @OA\Property(
     *     format="int64",
     *     description="ID",
     *     title="ID",
     * )
     *
     * @var int
     */
    private $id;

    /**
     * @OA\Property(
     *     format="email",
     *     description="Email",
     *     title="Email",
     * )
     *
     * @var string
     */
    private $email;
    /**
     * @OA\Property(
     *     description="Name",
     *     title="Name"
     * )
     *
     * @var string
     */
    private $name;
    /**
     * @OA\Property(
     *     description="Surname",
     *     title="Surname"
     * )
     *
     * @var string
     */
    private $surname;
    /**
     * @OA\Property(
     *     description="Surname",
     *     title="Surname",
     *     format="date"
     * )
     *
     * @var string
     */
    private $birthdate;


    /**
     * @OA\Property(
     *     description="Disabilities",
     *     title="Disabilities"
     * )
     *
     * @var string
     */
    private $disabilities;

    /**
     * @OA\Property(
     *     description="password",
     *     title="password"
     * )
     *
     * @var string
     */
    private $password;

    /**
     * @OA\Property(
     *     type="bool",
     *     description="Terms Accepted",
     *     title="Terms Accepted"
     * )
     *
     * @var bool
     */
    private $terms_accepted;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'terms_accepted' => 'boolean',
    ];

    public function accountStatus()
    {
        return $this->belongsTo(AccountStatus::class);
    }
}
