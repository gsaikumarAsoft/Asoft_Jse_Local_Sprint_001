<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SettlementAccountUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $account;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $account)
    {
        $this->user = $user;
        $this->account = $account;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.settlement_account_details_update');
    }
}
