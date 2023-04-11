<?php

namespace App\Helper;

enum Evaluation: int
{
    case Accessible = 2;
    case Neutral = 1;
    case Inaccessible = 0;

    /**
     * Returns all values in a simple array.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(Evaluation::cases(), 'value');
    }
}
