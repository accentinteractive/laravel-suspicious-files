<?php

namespace Accentinteractive\LaravelSuspiciousFiles\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SuspiciousFilesFind extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suspicious-files:find
                            {--minutes=5 : Check for files created in the last N minutes}
                            {--notify : Send email notification yes or no}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor for new suspicious files in public directories and alert on suspicious activity.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = $this->option('minutes');
        $this->info("Scanning for suspicious files created in the last {$minutes} minutes...");

        $suspiciousFiles = $this->scanForNewSuspiciousFiles($minutes);

        if (empty($suspiciousFiles)) {
            $this->info('✓ No suspicious files detected.');

            return self::SUCCESS;
        }

        // Alert: Suspicious files found!
        $this->error('⚠ ALERT: Suspicious files detected!');
        $this->newLine();

        $this->table(
            ['File', 'Size', 'Created', 'Age'],
            collect($suspiciousFiles)->map(function ($file) {
                return [
                    $file['path'],
                    $this->formatBytes($file['size']),
                    $file['created']->format('Y-m-d H:i:s'),
                    $file['created']->diffForHumans(),
                ];
            })->toArray()
        );

        // Log to security log
        $messageTitle = 'SECURITY ALERT: Suspicious files detected on ' . config('app.name');
        $messages = $messageTitle . PHP_EOL;
        foreach ($suspiciousFiles as $file) {
            $messages .= '- ' . $file['full_path'] . ' | created at ' . $file['created']->format('Y-m-d H:i:s') . PHP_EOL;
        }

        Log::critical($messageTitle, [
            'files' => $messages,
        ]);

        // Send notifications
        if ($this->option('notify')) {
            $this->sendEmailNotification($suspiciousFiles);
        }

        // Store in cache for dashboard display
        Cache::put('security:suspicious_files', $suspiciousFiles, now()->addDays(7));

        return self::FAILURE; // Exit with error code to trigger monitoring alerts
    }

    protected function scanForNewSuspiciousFiles(int $minutes): array
    {
        $suspiciousFiles = [];
        $cutoffTime = Carbon::now()->subMinutes($minutes);

        foreach (config('suspicious-files.monitored_directories') as $directory) {
            $fullPath = base_path($directory);

            if ( ! File::exists($fullPath)) {
                continue;
            }

            $files = File::allFiles($fullPath);

            foreach ($files as $file) {
                // Skip if file does not have a suspicious extension
                if ( ! in_array($file->getExtension(), config('suspicious-files.suspicious_file_extensions'))) {
                    continue;
                }

                // Skip excluded directories
                if ($this->isExcludedPath($file->getPathname())) {
                    continue;
                }

                // Skip allowed files
                if ($this->isAllowedFile($file->getFilename())) {
                    continue;
                }

                // Check if file was created recently
                $createdAt = Carbon::createFromTimestamp($file->getCTime());

                if ($createdAt->greaterThan($cutoffTime)) {
                    $suspiciousFiles[] = [
                        'path' => $file->getRelativePathname(),
                        'full_path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'created' => $createdAt,
                        'content_preview' => $this->getFilePreview($file->getPathname()),
                    ];
                }
            }
        }

        return $suspiciousFiles;
    }

    /**
     * Check if path should be excluded
     */
    protected function isExcludedPath(string $path): bool
    {
        foreach (config('suspicious-files.excluded_directories') as $excluded) {
            if (str_contains($path, DIRECTORY_SEPARATOR . $excluded . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file is in the allowed list
     */
    protected function isAllowedFile(string $filename): bool
    {
        return in_array($filename, config('suspicious-files.allowed_files'));
    }

    /**
     * Get first 500 characters of file for preview
     */
    protected function getFilePreview(string $path): string
    {
        try {
            $content = file_get_contents($path);

            return substr($content, 0, 500);
        } catch (\Exception $e) {
            return '[Unable to read file]';
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[ $pow ];
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(array $files): void
    {
        if ( ! config('suspicious-files.send_email_notification')) {
            $this->error('✗ Notification email not configured.');
            Log::error('Notification email for suspicious files not configured.');
        }

        try {
            Mail::send(config('suspicious-files.email.view'), ['files' => $files], function ($message) use ($files) {
                $message->to(config('suspicious-files.email.notification_email_address'))
                        ->subject(config('suspicious-files.email.subject'));
            });

            $this->info('✓ Email notification sent');
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email: ' . $e->getMessage());
        }
    }

}
