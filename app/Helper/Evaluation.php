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

    /**
     * Returns all cases of this enum as an array
     * with translated labels.
     *
     * @return array
     */
    public static function array(): array
    {
        $keys = Evaluation::values();

        return array_combine(
            keys: $keys,
            values: array_map(function (int $key) {
                return static::nameOf($key);
            }, $keys)
        );
    }

    public static function nameOf(int $value)
    {
        return static::tryFrom($value)->name;
    }
}
