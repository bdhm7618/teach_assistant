<?php

namespace Modules\Channel\App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Channel\App\Mail\EmailVerificationOtpMail;

class SendEmailVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Model $user,
        public string $otp
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new EmailVerificationOtpMail(
                $this->user,
                $this->otp
            ));
    }
}
