<?php

namespace Accentinteractive\LaravelSuspiciousFiles\Tests;

use Accentinteractive\LaravelSuspiciousFiles\Commands\SuspiciousFilesFind;
use Accentinteractive\LaravelSuspiciousFiles\LaravelSuspiciousFilesServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class SuspiciousFilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::forget('security:suspicious_files');

        // Create a test directory
        $this->testDir = base_path('test-suspicious-files');
        if (!File::exists($this->testDir)) {
            File::makeDirectory($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelSuspiciousFilesServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Set up test configuration
        Config::set('suspicious-files.monitored_directories', ['test-suspicious-files']);
        Config::set('suspicious-files.excluded_directories', []);
        Config::set('suspicious-files.allowed_files', ['index.php']);
        Config::set('suspicious-files.suspicious_file_extensions', ['php']);
        Config::set('suspicious-files.send_email_notification', false);
    }

    public function test_command_is_registered(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('suspicious-files:find', $commands);
    }

    public function test_command_returns_success_when_no_suspicious_files(): void
    {
        $this->artisan('suspicious-files:find')
            ->assertExitCode(0)
            ->expectsOutput('Scanning for suspicious files created in the last 5 minutes...')
            ->expectsOutput('✓ No suspicious files detected.');
    }

    public function test_command_detects_newly_created_php_file(): void
    {
        // Create a suspicious PHP file
        $suspiciousFile = $this->testDir . '/malicious.php';
        File::put($suspiciousFile, '<?php echo "malicious code"; ?>');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(1)
            ->expectsOutput('⚠ ALERT: Suspicious files detected!');
    }

    public function test_command_ignores_allowed_files(): void
    {
        // Create an allowed file
        $allowedFile = $this->testDir . '/index.php';
        File::put($allowedFile, '<?php // index file ?>');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(0)
            ->expectsOutput('✓ No suspicious files detected.');
    }

    public function test_command_respects_time_window(): void
    {
        // Create a file
        $file = $this->testDir . '/test.php';
        File::put($file, '<?php echo "test"; ?>');

        // Scan with a very short time window (0 minutes)
        // This should not detect the file as it takes some time to create
        sleep(1);
        $this->artisan('suspicious-files:find --minutes=0')
            ->assertExitCode(0);
    }

    public function test_command_logs_security_event(): void
    {
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'SECURITY ALERT');
            });

        // Create a suspicious PHP file
        $suspiciousFile = $this->testDir . '/malicious.php';
        File::put($suspiciousFile, '<?php echo "malicious code"; ?>');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(1);
    }

    public function test_command_caches_results(): void
    {
        // Create a suspicious PHP file
        $suspiciousFile = $this->testDir . '/malicious.php';
        File::put($suspiciousFile, '<?php echo "malicious code"; ?>');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(1);

        // Check that results are cached
        $this->assertTrue(Cache::has('security:suspicious_files'));
        $cached = Cache::get('security:suspicious_files');
        $this->assertIsArray($cached);
        $this->assertNotEmpty($cached);
    }

    public function test_command_handles_non_existent_directory(): void
    {
        Config::set('suspicious-files.monitored_directories', ['non-existent-directory']);

        $this->artisan('suspicious-files:find')
            ->assertExitCode(0)
            ->expectsOutput('✓ No suspicious files detected.');
    }

    public function test_command_ignores_excluded_directories(): void
    {
        // Create subdirectory
        $excludedDir = $this->testDir . '/vendor';
        File::makeDirectory($excludedDir, 0755, true);

        Config::set('suspicious-files.excluded_directories', ['vendor']);

        // Create a PHP file in excluded directory
        $file = $excludedDir . '/excluded.php';
        File::put($file, '<?php echo "excluded"; ?>');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(0)
            ->expectsOutput('✓ No suspicious files detected.');
    }

    public function test_command_only_monitors_configured_extensions(): void
    {
        Config::set('suspicious-files.suspicious_file_extensions', ['php']);

        // Create a .txt file (should be ignored)
        $txtFile = $this->testDir . '/test.txt';
        File::put($txtFile, 'some text content');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(0)
            ->expectsOutput('✓ No suspicious files detected.');

        // Create a .php file (should be detected)
        $phpFile = $this->testDir . '/test.php';
        File::put($phpFile, '<?php echo "test"; ?>');

        $this->artisan('suspicious-files:find --minutes=1')
            ->assertExitCode(1);
    }

    public function test_command_accepts_custom_minutes_option(): void
    {
        $this->artisan('suspicious-files:find --minutes=30')
            ->assertExitCode(0)
            ->expectsOutput('Scanning for suspicious files created in the last 30 minutes...');
    }

    public function test_service_provider_publishes_config(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'Accentinteractive\LaravelSuspiciousFiles\LaravelSuspiciousFilesServiceProvider',
            '--tag' => 'config',
        ])->assertExitCode(0);
    }
}
