<?php

namespace Modules\Notification\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $studentName,
        public readonly string  $channelName,
        public readonly string  $subjectTitle,   // exam or assignment title
        public readonly string  $subjectType,    // 'exam' | 'assignment'
        public readonly float   $marksObtained,
        public readonly float   $totalMarks,
        public readonly bool    $isPass,
        public readonly ?string $feedback = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel  = ucfirst($this->subjectType);
        $percentage = $this->totalMarks > 0
            ? round(($this->marksObtained / $this->totalMarks) * 100, 1)
            : 0;
        $result     = $this->isPass ? 'PASS ✓' : 'FAIL ✗';

        $mail = (new MailMessage)
            ->subject("{$typeLabel} Graded — {$this->subjectTitle}")
            ->greeting("Hello, {$this->studentName}!")
            ->line("Your {$typeLabel} **{$this->subjectTitle}** has been graded at **{$this->channelName}**.")
            ->line("**Score:** {$this->marksObtained} / {$this->totalMarks} ({$percentage}%)")
            ->line("**Result:** {$result}");

        if ($this->feedback) {
            $mail->line("**Teacher feedback:** {$this->feedback}");
        }

        return $mail->salutation("— {$this->channelName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'submission_graded',
            'subject_title'  => $this->subjectTitle,
            'subject_type'   => $this->subjectType,
            'marks_obtained' => $this->marksObtained,
            'total_marks'    => $this->totalMarks,
            'is_pass'        => $this->isPass,
        ];
    }
}
