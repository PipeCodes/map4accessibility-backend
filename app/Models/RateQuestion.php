<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'slug', 'title', 'place_type'
    ];

    public function answers()
    {
        return $this->hasMany(RateAnswer::class);
    }
}
