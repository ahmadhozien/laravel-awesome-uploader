<?php

namespace Hozien\Uploader\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ImageProcessor
{
    protected $available = false;
    protected $manager;

    public function __construct()
    {
        $this->checkAvailability();
    }

    /**
     * Check if Intervention Image is available.
     *
     * @return void
     */
    protected function checkAvailability(): void
    {
        if (class_exists('\Intervention\Image\ImageManager')) {
            try {
                $this->manager = new \Intervention\Image\ImageManager(['driver' => 'gd']);
                $this->available = true;
            } catch (\Exception $e) {
                Log::warning('Intervention Image available but failed to initialize: ' . $e->getMessage());
                $this->available = false;
            }
        } else {
            Log::info('Intervention Image package not installed - image processing disabled');
            $this->available = false;
        }
    }

    /**
     * Check if image processing is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Process an uploaded image with optimization.
     *
     * @param string $path
     * @param array $options
     * @return array
     */
    public function process(string $path, array $options = []): array
    {
        if (!$this->available) {
            return [
                'success' => false,
                'message' => 'Image processing not available - Intervention Image package not installed'
            ];
        }

        try {
            $disk = Storage::disk(Config::get('uploader.disk'));

            if (!$disk->exists($path)) {
                return [
                    'success' => false,
                    'message' => 'Image file not found'
                ];
            }

            $image = $this->manager->make($disk->get($path));
            $processed = false;

            // Auto-orient image
            if ($options['auto_orient'] ?? Config::get('uploader.auto_orient', true)) {
                $image->orientate();
                $processed = true;
            }

            // Optimize quality
            $quality = $options['quality'] ?? Config::get('uploader.image_quality', 85);
            if ($quality < 100) {
                $processed = true;
            }

            // Apply processing if any changes were made
            if ($processed) {
                $optimized = $image->encode(null, $quality);
                $disk->put($path, $optimized);
            }

            // Generate thumbnails if enabled
            $thumbnails = [];
            if ($options['generate_thumbnails'] ?? Config::get('uploader.generate_thumbnails', true)) {
                $thumbnails = $this->generateThumbnails($path, $options);
            }

            return [
                'success' => true,
                'processed' => $processed,
                'thumbnails' => $thumbnails,
                'original_size' => strlen($disk->get($path)),
            ];
        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Image processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate thumbnails for an image.
     *
     * @param string $imagePath
     * @param array $options
     * @return array
     */
    public function generateThumbnails(string $imagePath, array $options = []): array
    {
        if (!$this->available) {
            return [];
        }

        $disk = Storage::disk(Config::get('uploader.disk'));
        $sizes = $options['sizes'] ?? Config::get('uploader.thumbnail_sizes', [150, 300, 600]);
        $quality = $options['thumbnail_quality'] ?? Config::get('uploader.thumbnail_quality', 80);
        $thumbnails = [];

        try {
            $image = $this->manager->make($disk->get($imagePath));

            foreach ($sizes as $size) {
                $thumbnailPath = $this->getThumbnailPath($imagePath, $size);

                $thumbnail = clone $image;
                $thumbnail->resize($size, $size, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $encoded = $thumbnail->encode(null, $quality);
                $disk->put($thumbnailPath, $encoded);

                $thumbnails[$size] = [
                    'path' => $thumbnailPath,
                    'url' => $disk->url($thumbnailPath),
                    'size' => strlen($encoded),
                ];
            }

            return $thumbnails;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get thumbnail path for a given size.
     *
     * @param string $originalPath
     * @param int $size
     * @return string
     */
    protected function getThumbnailPath(string $originalPath, int $size): string
    {
        $pathInfo = pathinfo($originalPath);

        return $pathInfo['dirname'] . '/' .
            $pathInfo['filename'] . '_thumb_' . $size . '.' .
            $pathInfo['extension'];
    }

    /**
     * Delete thumbnails for an image.
     *
     * @param string $imagePath
     * @return bool
     */
    public function deleteThumbnails(string $imagePath): bool
    {
        $disk = Storage::disk(Config::get('uploader.disk'));
        $sizes = Config::get('uploader.thumbnail_sizes', [150, 300, 600]);
        $deleted = true;

        foreach ($sizes as $size) {
            $thumbnailPath = $this->getThumbnailPath($imagePath, $size);

            if ($disk->exists($thumbnailPath)) {
                if (!$disk->delete($thumbnailPath)) {
                    $deleted = false;
                    Log::warning('Failed to delete thumbnail', ['path' => $thumbnailPath]);
                }
            }
        }

        return $deleted;
    }

    /**
     * Get image dimensions.
     *
     * @param string $path
     * @return array|null
     */
    public function getDimensions(string $path): ?array
    {
        if (!$this->available) {
            return null;
        }

        try {
            $disk = Storage::disk(Config::get('uploader.disk'));
            $image = $this->manager->make($disk->get($path));

            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'aspect_ratio' => round($image->width() / $image->height(), 2)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get image dimensions', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
