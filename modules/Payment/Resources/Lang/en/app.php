<?php

return [
    'created' => 'Payment created successfully.',
    'updated' => 'Payment updated successfully.',
    'deleted' => 'Payment deleted successfully.',
    'not_found' => 'Payment not found.',
    'operation_failed' => 'Something went wrong! Please try again.',
    'list_success' => 'Payments retrieved successfully.',
    'completed' => 'Payment marked as completed successfully.',
    'refunded' => 'Payment refunded successfully.',
    'statistics_retrieved' => 'Statistics retrieved successfully.',
    'summary_retrieved' => 'Summary retrieved successfully.',

    'status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'cancelled' => 'Cancelled',
    ],

    'method' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'vodafone_cash' => 'Vodafone Cash',
        'orange_money' => 'Orange Money',
        'etisalat_cash' => 'Etisalat Cash',
        'easy_pay' => 'Easy Pay',
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'online' => 'Online Payment',
        'other' => 'Other',
    ],

    'invoice' => [
        'created' => 'Invoice created successfully.',
        'updated' => 'Invoice updated successfully.',
        'deleted' => 'Invoice deleted successfully.',
        'not_found' => 'Invoice not found.',
        'created_with_installments' => 'Invoice created with installments successfully.',
    ],

    'discount' => [
        'created' => 'Discount created successfully.',
        'updated' => 'Discount updated successfully.',
        'deleted' => 'Discount deleted successfully.',
        'not_found' => 'Discount not found.',
        'invalid' => 'Invalid or expired discount code.',
        'applied' => 'Discount applied successfully.',
        'code_exists' => 'This discount code already exists.',
        'percentage_max' => 'Percentage discount cannot exceed 100%.',
        'min_amount' => 'Minimum amount for this discount is :amount.',
    ],

    'validation' => [
        'reference_required' => 'Reference number is required for this payment method.',
    ],

    'period' => [
        'created' => 'Payment period created successfully.',
        'updated' => 'Payment period updated successfully.',
        'deleted' => 'Payment period deleted successfully.',
        'not_found' => 'Payment period not found.',
        'no_current_period' => 'No active payment period found for current date.',
        'month_year_required' => 'Month and year are required for monthly periods.',
        'monthly' => 'Monthly',
        'weekly' => 'Weekly',
        'daily' => 'Daily',
        'session' => 'Per Session',
        'custom' => 'Custom',
        'monthly_desc' => 'Monthly payment period',
        'weekly_desc' => 'Weekly payment period',
        'daily_desc' => 'Daily payment period',
        'session_desc' => 'Payment per lesson session',
        'custom_desc' => 'Custom payment period',
        'month_name' => ':month :year',
        'week_name' => 'Week :start - :end',
        'session_period' => 'Session Period',
        'custom_period' => 'Custom Period',
    ],
];

