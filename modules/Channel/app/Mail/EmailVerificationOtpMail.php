<?php

namespace Modules\Channel\App\Mail;



use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class EmailVerificationOtpMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Model $user,
        public string $otp
    ) {}

    public function build()
    {
        return $this
            ->subject(trans('channel::app.mail.verify_email_subject'))
            ->view('channel::emails.verify-email')
            ->with([
                'user' => $this->user,
                'otp'  => $this->otp,
            ]);
    }
}
