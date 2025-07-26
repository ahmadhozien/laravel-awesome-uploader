<?php

namespace Hozien\Uploader\Console\Commands;

use Illuminate\Console\Command;
use Hozien\Uploader\Uploader;

class CleanupOrphanedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploader:cleanup 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned files (files on disk without database records)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uploader = app(Uploader::class);
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🧹 Laravel Awesome Uploader - File Cleanup');
        $this->line('');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            $this->line('');
        }

        // Get orphaned files
        $this->info('Scanning for orphaned files...');

        if ($dryRun) {
            // For dry run, we need to simulate the cleanup
            $result = $this->simulateCleanup($uploader);
        } else {
            $result = $uploader->cleanupOrphanedFiles();
        }

        if (empty($result['files'])) {
            $this->info('✅ No orphaned files found!');
            return;
        }

        $this->line('');
        $this->warn("Found {$result['cleaned']} orphaned file(s):");

        foreach ($result['files'] as $file) {
            $this->line("  - {$file}");
        }

        if ($dryRun) {
            $this->line('');
            $this->info('Run without --dry-run to actually delete these files.');
            return;
        }

        if (!$force) {
            $this->line('');
            if (!$this->confirm('Do you want to delete these orphaned files?')) {
                $this->info('Cleanup cancelled.');
                return;
            }
        }

        $this->line('');
        $this->info("🗑️  Cleaned up {$result['cleaned']} orphaned file(s)!");
    }

    /**
     * Simulate cleanup for dry-run mode.
     */
    protected function simulateCleanup(Uploader $uploader): array
    {
        // This is a simplified version that shows what would be deleted
        // without actually deleting anything
        $disk = \Storage::disk(\Config::get('uploader.disk'));
        $uploadPath = 'uploads';
        $orphaned = [];

        if (!$disk->exists($uploadPath)) {
            return ['cleaned' => 0, 'files' => []];
        }

        $diskFiles = $disk->allFiles($uploadPath);
        $dbPaths = \Hozien\Uploader\Models\Upload::pluck('path')->toArray();

        foreach ($diskFiles as $filePath) {
            if (!in_array($filePath, $dbPaths)) {
                $orphaned[] = $filePath;
            }
        }

        return [
            'cleaned' => count($orphaned),
            'files' => $orphaned
        ];
    }
}
