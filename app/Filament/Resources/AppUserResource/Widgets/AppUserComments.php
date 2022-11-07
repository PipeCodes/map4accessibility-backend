<?php

namespace App\Filament\Resources\AppUserResource\Widgets;

use App\Models\AppUser;
use Filament\Widgets\Widget;

class AppUserComments extends Widget
{
    protected static string $view = 'filament.resources.app-user-resource.widgets.app-user-comments';

    protected int | string | array $columnSpan = 'full';

    public ?AppUser $record = null;
}
