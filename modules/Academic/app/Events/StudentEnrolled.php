<?php

namespace Modules\Academic\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Academic\App\Models\StudentEnrollment;

class StudentEnrolled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StudentEnrollment $enrollment,
        public readonly float $firstInvoiceAmount,
    ) {}
}
