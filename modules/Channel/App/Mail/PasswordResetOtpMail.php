<?php

namespace Modules\Channel\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class PasswordResetOtpMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Model $user,
        public string $otp
    ) {}

    public function build()
    {
        return $this
            ->subject(trans('channel::app.mail.reset_password_subject'))
            ->view('channel::emails.password-reset')
            ->with([
                'user' => $this->user,
                'otp'  => $this->otp,
            ]);
    }
}
