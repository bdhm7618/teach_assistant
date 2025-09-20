<?php

namespace App\Rules;

use App\Models\Attendance;
use App\Models\SessionTime;
use Closure;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;

class StudentIdsRule implements ValidationRule
{
    public function __construct(private $session_time_id) {}
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group_id = SessionTime::find($this->session_time_id)?->group_id;

        $student = Student::find($value);

        if ($student?->group_id != $group_id) {
            $fail("This student ID [{$student?->id}] is not a member of the specified group number [$group_id].");
            return;
        }

        if (Attendance::where([
            ["session_time_id", "=", $this->session_time_id],
            ["student_id", "=", $value],
            ["date", "=", date("Y-m-d")]
        ])->exists()) {
            $fail("This student with ID $value is already assigned to the selected session time today.");
            return;
        }
    }
}
