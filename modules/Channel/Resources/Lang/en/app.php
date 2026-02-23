<?php

return [

    'channel' => [
        'created' => 'Channel created successfully.',
        'updated' => 'Channel updated successfully.',
        'deleted' => 'Channel deleted successfully.',
        'not_found' => 'Channel not found.',
        'already_exists' => 'A channel with this name already exists.',
    ],

    'user' => [
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
        'not_found' => 'User not found.',
        'unauthorized' => 'You are not authorized to perform this action.',
        'already_verified' => 'User is already verified.',
        'not_verified' => 'Email is not verified.',
        'cannot_delete_self' => 'You cannot delete your own account.',
    ],

    'student' => [
        'created' => 'Student added successfully.',
        'updated' => 'Student updated successfully.',
        'deleted' => 'Student removed successfully.',
        'not_found' => 'Student not found.',
    ],

    'role' => [
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'deleted' => 'Role removed successfully.',
        'not_found' => 'Role not found.',
        'cannot_modify_system_role' => 'System roles cannot be modified.',
        'cannot_delete_system_role' => 'System roles cannot be deleted.',
        'cannot_delete_assigned_role' => 'Cannot delete role. It is assigned to :count user(s).',
        'cannot_modify_general_role' => 'General roles (available to all channels) cannot be modified by channel users.',
        'cannot_delete_general_role' => 'General roles (available to all channels) cannot be deleted by channel users.',
        'cannot_create_general_role' => 'Channel users cannot create general roles. Only admins can create general roles.',
        'invalid_permission_format' => 'Invalid permission format. Permissions must be an array of strings.',
        'name_already_exists' => 'A role with this name already exists in this channel.',
    ],

    'settings' => [
        'updated' => 'Settings updated successfully.',
        'not_found' => 'Settings not found.',
    ],

    'common' => [
        'operation_failed' => 'Something went wrong! Please try again.',
        'not_found' => 'Resource not found.',
        'show_success' => 'Resource displayed successfully.',
        'list_success' => 'List displayed successfully.',   
    ],


    'mail' => [

        'verify_email_subject' => 'Verify Your Email Address',

        'verify_email_title' => 'Email Verification',

        'hello' => 'Hello :name,',

        'verify_email_text' =>
        'Thank you for registering. Please use the following verification code to confirm your email address.',

        'otp_expire' =>
        'This verification code will expire in a few minutes.',

        'ignore_if_not_you' =>
        'If you did not create an account, no further action is required.',

    ],
    'otp' => [
        'invalid' => 'The OTP you entered is invalid.',
        'expired' => 'The OTP has expired.',
        'validated' => 'OTP validated successfully.',
    ],

    'auth' => [
        'invalid_credentials' => 'Invalid credentials.',
        'login_success' => 'Login successful.',
        'blocked' => 'Account blocked.',
    ],
    'password' => [
        'reset_otp_sent' => 'Password reset OTP has been sent.',
        'reset_success' => 'Password reset successfully.',
    ],

    'validation' => [
        'model_not_belongs_to_channel' => 'The selected :model does not belong to the current channel.',
        'unique_in_channel' => 'This record already exists in this channel.',
        'role_not_found' => 'The selected role does not exist.',
    ],




];
