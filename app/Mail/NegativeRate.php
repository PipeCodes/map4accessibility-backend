<?php

namespace App\Mail;

use App\Models\PlaceEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NegativeRate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The instance.
     *
     * @var \App\Models\PlaceEvaluation
     */
    public $placeEvaluation;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\PlaceEvaluation  $user
     * @return void
     */
    public function __construct(PlaceEvaluation $placeEvaluation)
    {
        $this->placeEvaluation = $placeEvaluation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $google_place_id = $this->placeEvaluation->place->google_place_id ?? 'null';
        $url = config('app.APP_FRONTEND_URL').
        "/place-details/{$this->placeEvaluation->place->id}/{$google_place_id}";

        return $this->markdown('emails.placeRates.negative-rate')
            ->subject(__('mail.negative_place_rate'))
            ->with(
                [
                    'placeEvaluation' => $this->placeEvaluation,
                    'url' => $url,
                ]
            );
    }
}
