
<?php

return [
    'dashboard' => 'Dashboard',
    'learners' => 'Learners',
    'event_transactions' => 'Event Transactions',
    'field_agents' => 'Field Agents',
    'field_agent_details' => 'Field Agent Details',
    'reports' => 'Reports',
    'profile' => 'Profile',
    'help_support' => 'Help & Support',

    'loading' => 'Loading...',
    'search' => 'Search',
    'refresh' => 'Refresh',
    'view_details' => 'View Details',
    'back' => 'Back',
    'save' => 'Save',
    'cancel' => 'Cancel',

     'account_settings' => 'Account Settings',
    'my_profile' => 'My Profile',
    'manage_profile_description' => 'Manage your personal information and account security.',
    'profile_settings' => 'Profile Settings',
    'personal_information' => 'Personal Information',
    'change_password' => 'Change Password',
    'set_login_mpin' => 'Set Login MPIN',
    'email_notifications' => 'Email Notifications',
    'field_agent' => 'Field Agent',

      // Profile section descriptions
    'personal_section_description' => 'View your registered Field Agent account details.',
    'password_section_description' => 'Update your password to keep your account secure.',
    'mpin_section_description' => 'Configure the MPIN used to access your account.',
    'notifications_section_description' => 'Choose which updates you want to receive by email.',

    // Personal information labels
    'agent_name' => 'Agent Name',
    'sakhi_id' => 'Sakhi ID',
    'contact_number' => 'Contact Number',
    'email_address' => 'Email Address',
    'partner' => 'Partner',
    'partner_division' => 'Partner Division',
    'csc_id' => 'CSC ID',
    'state' => 'State',
    'district' => 'District',
    'not_available' => 'Not available',
    'profile_correction_note' => 'Contact your administrator if your registered information needs to be corrected.',

    // Password form
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_new_password' => 'Confirm New Password',
    'update_password' => 'Update Password',
    'password_policy_help' => "Use at least 8 characters and follow your organisation's password policy.",
    'show_passwords' => 'Show passwords',
    'hide_passwords' => 'Hide passwords',

    // MPIN form
    'new_login_mpin' => 'New Login MPIN',
    'confirm_login_mpin' => 'Confirm Login MPIN',
    'mpin_security_note' => 'Choose a private numeric MPIN. Do not share it with anyone.',
    'mpin_policy_help' => 'Use 4 to 6 digits. Backend verification and security rules must be enforced when saving.',
    'show_mpin' => 'Show MPIN',
    'hide_mpin' => 'Hide MPIN',

    // Email notification preferences
    'learner_activity_updates' => 'Learner Activity Updates',
    'learner_activity_updates_description' => 'Receive updates about learner activity submissions.',
    'approval_notifications' => 'Approval Notifications',
    'approval_notifications_description' => 'Get notified when activities are approved.',
    'rejection_notifications' => 'Rejection Notifications',
    'rejection_notifications_description' => 'Receive notifications when activities need correction.',
    'important_announcements' => 'Important Announcements',
    'important_announcements_description' => 'Receive important programme and account announcements.',
    'save_preferences' => 'Save Preferences',

    // Form validation and feedback
    'complete_all_password_fields' => 'Please complete all password fields.',
    'password_minimum_length' => 'The new password must contain at least 8 characters.',
    'password_confirmation_mismatch' => 'The new passwords do not match.',
    'mpin_invalid_length' => 'MPIN must contain 4 to 6 digits.',
    'mpin_confirmation_mismatch' => 'The MPIN values do not match.',

    // Backend integration feedback
    'password_api_not_connected' => 'Password form validated. Connect your Laravel password-update API to save changes.',
    'mpin_api_not_connected' => 'MPIN form validated. Connect your secure Laravel MPIN API to save changes.',
    'notification_api_not_connected' => 'Preferences updated on this screen only. Connect your Laravel notification-preferences API to save them permanently.',

    'current_password_incorrect' => 'Your current password is incorrect.',
    'password_update_failed' => 'Unable to update your password. Please try again.',
    'password_updated_successfully' => 'Password updated successfully.',
    'updating_password' => 'Updating password...',
    'session_expired' => 'Your session has expired. Please log in again.',




    'mpin_must_be_six_digits' => 'MPIN must contain exactly 6 digits.',
    'mpin_confirmation_mismatch' => 'The MPIN confirmation does not match.',
    'mpin_setup_required' => 'Set an MPIN before enabling MPIN login.',
    'mpin_settings_updated' => 'MPIN settings updated successfully.',
    'mpin_settings_save_failed' => 'Unable to save MPIN settings. Please try again.',
    'mpin_settings_load_failed' => 'Unable to load MPIN settings. Please try again.',
    'loading_mpin_settings' => 'Loading MPIN settings...',
    'set_login_mpin' => 'Set Login MPIN',
    'change_mpin' => 'Change MPIN',
    'confirm_mpin' => 'Confirm MPIN',
    'mpin_setup_hint' => 'Choose a six-digit MPIN for quick login.',
    'mpin_change_hint' => 'Enter a new six-digit MPIN, or leave blank to keep the current one.',
    'enable_mpin_login' => 'Enable MPIN Login',
    'mpin_login_enabled_hint' => 'MPIN login is enabled for your account.',
    'mpin_login_disabled_hint' => 'Password login remains available. MPIN login is disabled.',
    'saving_mpin_settings' => 'Saving settings...',
    'save_mpin_settings' => 'Save MPIN Settings',

    'partner_code'=>'Partner Code'
];
