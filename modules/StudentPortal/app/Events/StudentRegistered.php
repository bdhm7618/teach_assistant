<?php

namespace Modules\StudentPortal\App\Events;

use Modules\Student\App\Models\Student;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class StudentRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Student $student) {}
}
