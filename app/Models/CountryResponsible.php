<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="CountryResponsible",
 *   description="Country Responsible model",
 *   title="Country Responsible Object",
 *   required={},
 *
 *   @OA\Property(type="integer",description="Country responsible's ID",title="id",property="id",example="1",readOnly="true"),
 *   @OA\Property(type="string",title="country_iso",property="country_iso",example="PT"),
 *   @OA\Property(type="string",title="email",property="email",example="example@example.com"),
 *   @OA\Property(type="dateTime",title="created_at",property="created_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 *   @OA\Property(type="dateTime",title="updated_at",property="updated_at",example="2022-07-04T02:41:42.336Z",readOnly="true"),
 * )
 */
class CountryResponsible extends Model
{
    use HasFactory;
}
