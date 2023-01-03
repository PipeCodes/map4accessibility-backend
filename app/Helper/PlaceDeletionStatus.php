<?php

namespace App\Helper;

enum PlaceDeletionStatus: string
{
    case Pending = 'pending';
    case Closed = 'closed';
}
