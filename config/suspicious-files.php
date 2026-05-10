<?php

return [
    /**
     * Directories to monitor for suspicious files.
     */
    'monitored_directories' => [
        '', // relative to the base path
    ],

    /**
     * Directories to exclude from monitoring, by name.
     */
    'excluded_directories' => [
        'app',
        'bootstrap',
        'config',
        'database',
        'lang',
        'resources/lang',
        'resources/views',
        'routes',
        'storage/app/livewire-tmp',
        'storage/framework',
        'storage/logs',
        'storage/tmp',
        'tests',
        'vendor',
    ],

    /**
     * Files to allow in the monitored directories.
     */
    'allowed_files' => [
        'index.php',
    ],

    /**
     * Extensions of files to monitor for suspicious activity.
     */
    'suspicious_file_extensions' => [
        'php',
        'phtml',
        'php3',
        'php4',
        'php5',
    ],

    /**
     * Email settings
     */
    'email' => [
        'notification_email_address' => env('SUSPICIOUS_FILES_EMAIL', ''),
        'subject' => '⚠ SECURITY ALERT: suspicious files detected on ' . config('app.name'),
        'view' => 'suspicious::email.security-alert',
    ],
];
