<?php

return [
    'created'           => 'Exam created successfully.',
    'updated'           => 'Exam updated successfully.',
    'deleted'           => 'Exam deleted successfully.',
    'retrieved'         => 'Exam retrieved successfully.',
    'published'         => 'Exam published. Students can now attempt it.',
    'closed'            => 'Exam closed. No more submissions accepted.',
    'results_retrieved' => 'Exam results retrieved successfully.',
    'not_found'         => 'Exam not found.',
    'operation_failed'  => 'Something went wrong! Please try again.',

    'cannot_edit_published'           => 'Cannot edit a published exam that already has submissions.',
    'cannot_delete_with_submissions'  => 'Cannot delete an exam that already has submissions.',
    'cannot_modify_closed'            => 'Cannot add or modify questions on a closed exam.',
    'no_questions'                    => 'Cannot publish an exam with no questions.',
    'already_published'               => 'Exam is already published.',

    'not_published'        => 'This exam is not published yet.',
    'not_started'          => 'This exam has not started yet.',
    'ended'                => 'This exam has ended.',
    'max_attempts_reached' => 'You have reached the maximum number of attempts for this exam.',
    'already_in_progress'  => 'You already have an in-progress attempt for this exam.',

    'question' => [
        'created'             => 'Question added successfully.',
        'updated'             => 'Question updated successfully.',
        'deleted'             => 'Question deleted successfully.',
        'retrieved'           => 'Questions retrieved successfully.',
        'not_found'           => 'Question not found.',
        'exactly_one_correct' => 'Exactly one option must be marked as correct.',
    ],

    'submission' => [
        'started'           => 'Exam started. Good luck!',
        'submitted'         => 'Answers submitted and graded.',
        'graded'            => 'Submission graded successfully.',
        'retrieved'         => 'Submission retrieved successfully.',
        'not_found'         => 'Submission not found.',
        'already_submitted' => 'This submission has already been submitted.',
        'not_submitted_yet' => 'Cannot grade a submission that is still in progress.',
        'student_mismatch'  => 'Student ID does not match this submission.',
    ],
];
