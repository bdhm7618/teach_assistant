<?php

namespace Modules\Exam\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Exam\App\Models\ExamSubmission;

class SubmissionGraded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ExamSubmission $submission) {}
}
