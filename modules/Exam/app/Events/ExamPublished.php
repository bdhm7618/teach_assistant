<?php

namespace Modules\Exam\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Exam\App\Models\Exam;

class ExamPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Exam $exam) {}
}
