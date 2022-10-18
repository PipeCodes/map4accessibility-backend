<?php

namespace App\Helper;

use Filament\Forms;

class AppLocales
{
    public static function SelectLocales()
    {
        return Forms\Components\Select::make('locale')
            ->label('Locale')
            ->options([
                'en' => 'English',
                'pt' => 'Portuguese',
                'de' => 'German',
                'it' => 'Italian',
                'bg' => 'Bulgarian',
            ])
            ->required();
    }
}
