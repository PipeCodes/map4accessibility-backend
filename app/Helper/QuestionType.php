<?php

namespace App\Helper;

enum QuestionType: string
{
    case Mandatory = 'mandatory';
    case Optional = 'optional';

    /**
     * Returns all values in a simple array.
     */
    public static function values(): array
    {
        return array_column(QuestionType::cases(), 'value');
    }

    /**
     * Returns all names in a simple array.
     */
    public static function names(): array
    {
        return array_column(QuestionType::cases(), 'name');
    }

    /**
     * Returns all cases of this enum as an array
     * with translated labels.
     */
    public static function array(): array
    {
        return array_combine(
            keys: QuestionType::values(),
            values: QuestionType::names(),
        );
    }
}
