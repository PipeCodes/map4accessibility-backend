<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'latitude', 'longitude', 'google_place_id',
        'name', 'country_code', 'place_type'
    ];

    public function placeEvaluations()
    {
        return $this->hasMany(PlaceEvaluation::class);
    }
}
