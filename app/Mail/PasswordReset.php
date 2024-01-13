<?php

namespace App\Mail;

use App\Models\AppUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The App User instance.
     *
     * @var \App\Models\AppUser
     */
    public $user;

    /**
     * Reset Password token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(AppUser $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $url = config('app.APP_FRONTEND_URL').
            "/change-password/?email={$this->user->email}&token={$this->token}";

        return $this->markdown('emails.appUsers.password-reset')
            ->subject(__('mail.subject_password_reset'))
            ->with([
                'email' => $this->user->email,
                'url' => $url,
            ]);
    }
}
