<?php

namespace Hozien\Uploader\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Hozien\Uploader\UploaderServiceProvider;
use Hozien\Uploader\Models\Upload;
use Hozien\Uploader\Support\ImageProcessor;

class UploaderStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploader:status 
                            {--detailed : Show detailed configuration and statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Laravel Awesome Uploader package status and version information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();
        $this->displayVersion();
        $this->displayConfiguration();

        if (Schema::hasTable('uploads')) {
            $this->displayStatistics();
        } else {
            $this->warn('Database table "uploads" not found. Run: php artisan migrate');
        }

        $this->displayDependencies();

        if ($this->option('detailed')) {
            $this->displayDetailedConfiguration();
        }

        $this->displayFooter();
    }

    /**
     * Display the header.
     */
    protected function displayHeader()
    {
        $this->line('');
        $this->line('<fg=blue>╔══════════════════════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=blue>║</> <fg=yellow;options=bold>                     Laravel Awesome Uploader Status                        </> <fg=blue>║</>');
        $this->line('<fg=blue>╚══════════════════════════════════════════════════════════════════════════════╝</>');
        $this->line('');
    }

    /**
     * Display version information.
     */
    protected function displayVersion()
    {
        $version = UploaderServiceProvider::version();
        $this->info("📦 Package Version: {$version}");
        $this->line('');
    }

    /**
     * Display configuration status.
     */
    protected function displayConfiguration()
    {
        $this->line('<fg=cyan>Configuration Status:</>');
        $this->line('────────────────────');

        $disk = Config::get('uploader.disk', 'public');
        $saveToDb = Config::get('uploader.save_to_db', false);
        $allowGuests = Config::get('uploader.allow_guests', true);
        $checkDuplicates = Config::get('uploader.check_duplicates', true);
        $imageOptimization = Config::get('uploader.image_optimization', true);
        $generateThumbnails = Config::get('uploader.generate_thumbnails', true);

        $this->line("Storage Disk: <fg=yellow>{$disk}</>");
        $this->line("Database Integration: " . ($saveToDb ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line("Guest Uploads: " . ($allowGuests ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line("File Deduplication: " . ($checkDuplicates ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line("Image Optimization: " . ($imageOptimization ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line("Thumbnail Generation: " . ($generateThumbnails ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line('');
    }

    /**
     * Display upload statistics.
     */
    protected function displayStatistics()
    {
        try {
            $totalUploads = Upload::count();
            $totalSize = Upload::sum('size');
            $imageCount = Upload::where('type', 'like', 'image%')->count();
            $todayUploads = Upload::whereDate('created_at', today())->count();
            $avgFileSize = $totalUploads > 0 ? $totalSize / $totalUploads : 0;

            $this->line('<fg=cyan>Upload Statistics:</>');
            $this->line('─────────────────');
            $this->line("Total Files: <fg=yellow>{$totalUploads}</>");
            $this->line("Total Size: <fg=yellow>" . $this->formatBytes($totalSize) . "</>");
            $this->line("Images: <fg=yellow>{$imageCount}</>");
            $this->line("Documents: <fg=yellow>" . ($totalUploads - $imageCount) . "</>");
            $this->line("Today's Uploads: <fg=yellow>{$todayUploads}</>");
            $this->line("Average File Size: <fg=yellow>" . $this->formatBytes($avgFileSize) . "</>");
            $this->line('');
        } catch (\Exception $e) {
            $this->error("Could not retrieve statistics: " . $e->getMessage());
            $this->line('');
        }
    }

    /**
     * Display dependency status.
     */
    protected function displayDependencies()
    {
        $this->line('<fg=cyan>Dependencies:</>');
        $this->line('─────────────');

        // Check Intervention Image
        $imageProcessor = new ImageProcessor();
        $interventionAvailable = $imageProcessor->isAvailable();
        $this->line("Intervention Image: " . ($interventionAvailable ? '<fg=green>Available</>' : '<fg=yellow>Not Installed (Optional)</>'));

        // Check GD/Imagick
        $gdAvailable = extension_loaded('gd');
        $imagickAvailable = extension_loaded('imagick');
        $this->line("GD Extension: " . ($gdAvailable ? '<fg=green>Available</>' : '<fg=red>Not Available</>'));
        $this->line("Imagick Extension: " . ($imagickAvailable ? '<fg=green>Available</>' : '<fg=yellow>Not Available</>'));

        // Check storage disk
        try {
            $disk = Config::get('uploader.disk');
            \Storage::disk($disk)->exists('test');
            $this->line("Storage Disk ({$disk}): <fg=green>Accessible</>");
        } catch (\Exception $e) {
            $this->line("Storage Disk ({$disk}): <fg=red>Error - " . $e->getMessage() . "</>");
        }

        $this->line('');
    }

    /**
     * Display detailed configuration.
     */
    protected function displayDetailedConfiguration()
    {
        $this->line('<fg=cyan>Detailed Configuration:</>');
        $this->line('─────────────────────');

        $config = Config::get('uploader');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Max File Size', $config['max_size'] . ' KB'],
                ['Allowed Types', implode(', ', array_slice($config['allowed_file_types'], 0, 10)) . (count($config['allowed_file_types']) > 10 ? '...' : '')],
                ['Strict MIME Validation', $config['strict_mime_validation'] ?? false ? 'Yes' : 'No'],
                ['Soft Deletes', $config['soft_deletes'] ?? true ? 'Yes' : 'No'],
                ['Guest Upload Limit', $config['guest_upload_limit'] ?? 10],
                ['Pagination Limit', $config['pagination_limit'] ?? 20],
                ['Image Quality', $config['image_quality'] ?? 85],
                ['Thumbnail Quality', $config['thumbnail_quality'] ?? 80],
                ['Thumbnail Sizes', implode(', ', $config['thumbnail_sizes'] ?? [150, 300, 600])],
                ['Auto Cleanup', $config['auto_cleanup_orphaned'] ?? false ? 'Yes' : 'No'],
                ['Cleanup Days', $config['cleanup_after_days'] ?? 30],
                ['Logging Enabled', $config['enable_logging'] ?? true ? 'Yes' : 'No'],
            ]
        );

        $this->line('');
    }

    /**
     * Display footer with helpful commands.
     */
    protected function displayFooter()
    {
        $this->line('<fg=cyan>Helpful Commands:</>');
        $this->line('────────────────');
        $this->line('<fg=yellow>php artisan uploader:cleanup</> - Clean orphaned files');
        $this->line('<fg=yellow>php artisan uploader:thumbnails</> - Generate missing thumbnails');
        $this->line('<fg=yellow>php artisan vendor:publish --tag=uploader</> - Publish all assets');
        $this->line('<fg=yellow>php artisan test</> - Run package tests');
        $this->line('');
        $this->line('<fg=green>✅ Laravel Awesome Uploader is ready!</>');
        $this->line('');
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
