<?php

namespace App\Filament\Resources\AppUserResource\Pages;

use App\Filament\Resources\AppUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailConfirmation;
use Filament\Pages\Actions;

class CreateAppUser extends CreateRecord
{
    protected static string $resource = AppUserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;
        Mail::to($user->email)->send(new EmailConfirmation($user->record));
    }
}
