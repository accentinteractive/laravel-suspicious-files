# Changelog

All notable changes to `laravel-suspicious-files` will be documented in this file.

## [1.0.0] - 2026-05-10

### Changed
- Fixed view folder issue

## [0.0.2] - 2026-05-10

### Removed
- Removed send_email_notification in config because it is already a flag

## [0.0.1] - 2026-05-10

### Added
- Initial release of Laravel Suspicious Files package
- Command `suspicious-files:find` to scan for suspicious PHP files
- Email notification system for security alerts
- Configurable monitored directories
- Configurable excluded directories and allowed files
- Customizable file extension monitoring
- CLI output with detailed file information (path, size, creation time, age)
- Security event logging to Laravel logs
- Time window configuration for scanning (default: 5 minutes)
- Support for Laravel 11.x, 12.x, and 13.x
- Configuration publishing via artisan command

### Security
- Detects suspicious PHP files in monitored directories
- Provides email alerts for security teams
- Logs critical security events
- Supports scheduled automated scanning
