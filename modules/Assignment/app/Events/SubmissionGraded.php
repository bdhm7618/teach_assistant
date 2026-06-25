<?php

namespace Modules\Assignment\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assignment\App\Models\AssignmentSubmission;

class SubmissionGraded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly AssignmentSubmission $submission) {}
}
