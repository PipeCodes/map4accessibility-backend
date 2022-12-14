<?php

namespace App\Helper;

enum PlaceEvaluationStatus: string 
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Pending = 'pending';
}