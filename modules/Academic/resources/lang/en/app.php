<?php
return [
    'class' => [
        'created' => 'Class created successfully.',
        'updated' => 'Class updated successfully.',
        'deleted' => 'Class deleted successfully.',
        'not_found' => 'Class not found.',
    ],

    'academic_year' => [
        'created' => 'Academic year created successfully.',
        'updated' => 'Academic year updated successfully.',
        'deleted' => 'Academic year deleted successfully.',
        'not_found' => 'Academic year not found.',
        'already_active' => 'This academic year is already active.',
        'list' => 'Academic year list retrieved successfully.',
    ],
    'group' => [
        'created' => 'Group created successfully.',
        'updated' => 'Group updated successfully.',
        'deleted' => 'Group deleted successfully.',
        'not_found' => 'Group not found.',
        'list_success' => 'Groups list retrieved successfully.',
        'show_success' => 'Group retrieved successfully.',
    ],
    'subject' => [
        'created' => 'Subject created successfully.',
        'updated' => 'Subject updated successfully.',
        'deleted' => 'Subject deleted successfully.',
        'not_found' => 'Subject not found.',
        'list_success' => 'Subjects list retrieved successfully.',
        'show_success' => 'Subject retrieved successfully.',
    ],
    'validation' => [
        'class_grade_duplicate' => 'Class grade (Level :grade_level - Stage :stage) already exists in this channel.',
        'academic_year_not_belongs_to_channel' => 'The selected academic year does not belong to the current channel.',
        'group_duplicate' => 'Group with name ":name" already exists in this class and subject.',
        'session_time_conflict' => 'Session time conflicts with another session on :day at :time.',
        'session_time_conflict_existing' => 'Session time conflicts with existing session on :day at :time in group ":group".',
        'end_time_after_start_time' => 'End time must be after start time.',
        'subject_duplicate' => 'Subject with code ":code" already exists.',
        'subject_code_duplicate' => 'Subject code ":code" already exists in this channel or as a general subject.',
    ],
];
