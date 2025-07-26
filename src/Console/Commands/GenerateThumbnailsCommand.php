<?php

namespace Hozien\Uploader\Console\Commands;

use Illuminate\Console\Command;
use Hozien\Uploader\Models\Upload;
use Hozien\Uploader\Support\ImageProcessor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class GenerateThumbnailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploader:thumbnails 
                            {--missing-only : Only generate thumbnails for images that don\'t have them}
                            {--regenerate : Regenerate all thumbnails, overwriting existing ones}
                            {--batch-size=50 : Number of images to process in each batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate thumbnails for uploaded images';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $imageProcessor = new ImageProcessor();

        if (!$imageProcessor->isAvailable()) {
            $this->error('❌ Image processing not available. Please install intervention/image package:');
            $this->line('   composer require intervention/image');
            return 1;
        }

        $this->info('📸 Laravel Awesome Uploader - Thumbnail Generator');
        $this->line('');

        $missingOnly = $this->option('missing-only');
        $regenerate = $this->option('regenerate');
        $batchSize = (int) $this->option('batch-size');

        if (!$missingOnly && !$regenerate) {
            $missingOnly = true; // Default behavior
        }

        // Get image uploads
        $query = Upload::where('type', 'like', 'image%');
        $totalImages = $query->count();

        if ($totalImages === 0) {
            $this->info('No image uploads found.');
            return;
        }

        $this->info("Found {$totalImages} image upload(s)");

        if ($missingOnly) {
            $this->info('Generating thumbnails for images that don\'t have them...');
        } elseif ($regenerate) {
            $this->info('Regenerating all thumbnails...');
        }

        $this->line('');

        $progressBar = $this->output->createProgressBar($totalImages);
        $progressBar->start();

        $processed = 0;
        $generated = 0;
        $errors = 0;

        $query->chunk($batchSize, function ($uploads) use ($imageProcessor, $missingOnly, &$processed, &$generated, &$errors, $progressBar) {
            foreach ($uploads as $upload) {
                $processed++;

                try {
                    $result = $this->processImage($upload, $imageProcessor, $missingOnly);
                    if ($result) {
                        $generated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    // Log error but continue processing
                    \Log::error("Failed to generate thumbnails for {$upload->path}: " . $e->getMessage());
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->line('');
        $this->line('');

        // Display results
        $this->info("✅ Processing complete!");
        $this->line("   Processed: {$processed} images");
        $this->line("   Generated: {$generated} thumbnail sets");

        if ($errors > 0) {
            $this->warn("   Errors: {$errors} images failed");
            $this->line('   Check logs for details');
        }

        $this->line('');
    }

    /**
     * Process a single image upload.
     */
    protected function processImage(Upload $upload, ImageProcessor $imageProcessor, bool $missingOnly): bool
    {
        $disk = Storage::disk(Config::get('uploader.disk'));

        // Check if file exists
        if (!$disk->exists($upload->path)) {
            return false;
        }

        // Check if thumbnails already exist (for missing-only mode)
        if ($missingOnly && $this->thumbnailsExist($upload->path, $imageProcessor, $disk)) {
            return false;
        }

        // Generate thumbnails
        $result = $imageProcessor->generateThumbnails($upload->path);

        return !empty($result);
    }

    /**
     * Check if thumbnails already exist for an image.
     */
    protected function thumbnailsExist(string $imagePath, ImageProcessor $imageProcessor, $disk): bool
    {
        $sizes = Config::get('uploader.thumbnail_sizes', [150, 300, 600]);

        foreach ($sizes as $size) {
            $thumbnailPath = $this->getThumbnailPath($imagePath, $size);
            if (!$disk->exists($thumbnailPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get thumbnail path for a given size.
     */
    protected function getThumbnailPath(string $originalPath, int $size): string
    {
        $pathInfo = pathinfo($originalPath);

        return $pathInfo['dirname'] . '/' .
            $pathInfo['filename'] . '_thumb_' . $size . '.' .
            $pathInfo['extension'];
    }
}
