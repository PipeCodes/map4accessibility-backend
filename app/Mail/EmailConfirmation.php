<?php

namespace App\Mail;

use App\Models\appUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\appUser
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\appUser  $user
     * @return void
     */
    public function __construct(appUser $user)
    {
        $this->user = $user;
    }

    /**
    * Build the message.
    *
    * @return $this
    */
    public function build()
    {
        $token = $this->user->createToken($this->user->email, ['email-confirmation'])->plainTextToken;
        $url = route('emailConfirmation', ['tokenEmailConfirmation' => $token]);

        return $this->markdown('emails.appUsers.confirmation')
            ->subject(__('mail.subject_email_confirmation'))
            ->with(
                [
                    'url' => $url
                ]
            );
    }
}
