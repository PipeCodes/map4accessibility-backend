<?php

namespace App\Helper;

use Filament\Forms;

class AppLocales
{
    public static function locales()
    {
        return [
            'en' => 'English',
            'pt' => 'Portuguese',
            'de' => 'German',
            'it' => 'Italian',
            'bg' => 'Bulgarian',
        ];
    }

    public static function SelectLocales()
    {
        return Forms\Components\Select::make('locale')
            ->label('Locale')
            ->options(AppLocales::locales())
            ->required();
    }
}
