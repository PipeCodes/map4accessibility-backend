<?php

namespace App\Mail;

use App\Models\appUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        $emailConfirmationResendUrl =
            route('email-confirmation.resend', ['email' => $this->user->email]);

        return $this->markdown('emails.appUsers.confirmation')
            ->subject(__('mail.subject_email_confirmation'))
            ->with(
                [
                    'url' => $url,
                    'resendUrl' => $emailConfirmationResendUrl,
                ]
            );
    }
}
