<?php

namespace App\Helper;

/**
 * TODO:
 * To Be Decided
 */
enum PlaceType: string
{
    case Google = 'google';

    /**
     * Returns all values in a simple array.
     */
    public static function values(): array
    {
        return array_column(PlaceType::cases(), 'value');
    }

    /**
     * Returns all names in a simple array.
     */
    public static function names(): array
    {
        return array_column(PlaceType::cases(), 'name');
    }

    /**
     * Returns all cases of this enum as an array
     * with translated labels.
     */
    public static function array(): array
    {
        return array_combine(
            keys: PlaceType::values(),
            values: PlaceType::names(),
        );
    }
}
