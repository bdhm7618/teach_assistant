<?php

namespace Modules\Assignment\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assignment\App\Models\Assignment;

class AssignmentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Assignment $assignment) {}
}
