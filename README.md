# Laravel Suspicious Files

Get notified when suspicious files (PHP or other) appear in your file system.

This package monitors your Laravel application for suspicious files that are created in monitored directories. It's designed to detect potential security threats like uploaded malware or backdoor scripts.

## Requirements

- PHP 8.0 or higher
- Laravel 11.x, 12.x, or 13.x

## Installation

Install the package via Composer:

```bash
composer require accentinteractive/laravel-suspicious-files
```

The package will automatically register itself via Laravel's auto-discovery.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Accentinteractive\LaravelSuspiciousFiles\LaravelSuspiciousFilesServiceProvider" --tag="config"
```

This will create a `config/suspicious-files.php` file where you can customize:

- **Monitored directories** - Directories to scan for suspicious files
- **Excluded directories** - Directories to skip (e.g., vendor, storage)
- **Allowed files** - Files that are permitted (e.g., index.php)
- **File extensions** - Extensions to monitor (default: php)
- **Email notifications** - Configure email alerts

### Environment Variables

Add these to your `.env` file:

```env
# Email address to receive security alerts
SUSPICIOUS_FILES_EMAIL=security@example.com

# Whether to send email notifications (true/false)
SUSPICIOUS_FILES_SEND_EMAIL=true
```

## Usage

### Manual Scan

Run a manual scan to check for suspicious files created in the last 5 minutes:

```bash
php artisan suspicious-files:find
```

### Custom Time Window

Check for files created in the last 30 minutes:

```bash
php artisan suspicious-files:find --minutes=30
```

### Send Email Notification

Send an email alert if suspicious files are found:

```bash
php artisan suspicious-files:find --notify
```

### Scheduled Monitoring

Add to your `app/Console/Kernel.php` to run automatic scans:

```php
protected function schedule(Schedule $schedule)
{
    // Check every 5 minutes for files created in the last 10 minutes
    $schedule->command('suspicious-files:find --minutes=10 --notify')
             ->everyFiveMinutes();
}
```

## How It Works

1. The command scans configured directories for PHP files
2. Checks if files were created within the specified time window
3. Excludes configured directories (vendor, storage, etc.)
4. Skips allowed files (like index.php)
5. Reports findings via CLI output
6. Logs security events to Laravel logs
7. Optionally sends email notifications

## Security Alerts

When suspicious files are detected:

- **CLI Output**: Table showing file path, size, creation time, and age
- **Log Entry**: Critical log entry with file details
- **Email Alert**: Optional email with file information
- **Cache**: Results stored for 7 days for review

## Configuration Example

```php
return [
    'monitored_directories' => [
        'public/uploads',
        'storage/app/public',
    ],

    'excluded_directories' => [
        'vendor',
        'node_modules',
        'storage/framework',
        // ... more exclusions
    ],

    'allowed_files' => [
        'index.php',
    ],

    'suspicious_file_extensions' => [
        'php',
        'phtml',
        'php3',
        'php4',
        'php5',
    ],

    'email' => [
        'notification_email_address' => env('SUSPICIOUS_FILES_EMAIL', ''),
        'subject' => '⚠ SECURITY ALERT: suspicious files detected',
        'view' => 'suspicious::email.security-alert',
    ],
];
```

## Use Cases

- **Upload Directory Monitoring**: Detect malicious files uploaded through file upload forms
- **Web Shell Detection**: Identify backdoor scripts placed by attackers
- **Security Auditing**: Regular scans for unauthorized PHP files
- **Incident Response**: Quick detection of file-based attacks

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email joost@accentinteractive.nl instead of using the issue tracker.

## Credits

- [Joost van Veen](https://github.com/accentinteractive)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
