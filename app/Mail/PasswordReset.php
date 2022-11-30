<?php

namespace App\Mail;

use App\Models\AppUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

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
     * @param  \App\Models\AppUser  $user
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
        $url = env('APP_FRONTEND_URL') . 
            "/change-password/?email={$this->user->email}&token={$this->token}";

        return $this->markdown('emails.appUsers.password-reset')
            ->subject(__('mail.subject_password_reset'))
            ->with([
                'email' => $this->user->email,
                'url' => $url,
            ]);
    }
}