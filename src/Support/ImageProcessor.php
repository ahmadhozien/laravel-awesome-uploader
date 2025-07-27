<?php

namespace Hozien\Uploader\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ImageProcessor
{
    protected $available = false;
    protected $manager;
    protected $isV3 = false;

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
                // Check if we're using Intervention Image v3+
                if (class_exists('\Intervention\Image\Drivers\Gd\Driver')) {
                    // v3+ API
                    $this->manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                    $this->isV3 = true;
                } else {
                    // v2 API
                    $this->manager = new \Intervention\Image\ImageManager(['driver' => 'gd']);
                    $this->isV3 = false;
                }
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
     * Create an image instance from data.
     *
     * @param string $data
     * @return mixed
     */
    protected function createImage($data)
    {
        if ($this->isV3) {
            // v3 API: read() method instead of make()
            return $this->manager->read($data);
        } else {
            // v2 API: make() method
            return $this->manager->make($data);
        }
    }

    /**
     * Orientate image (fix EXIF orientation).
     *
     * @param mixed $image
     * @return mixed
     */
    protected function orientateImage($image)
    {
        // Try v3 API first - in v3, auto-orientation might be done automatically
        // or might need a different method
        if (method_exists($image, 'orient')) {
            return $image->orient();
        } elseif (method_exists($image, 'orientate')) {
            return $image->orientate();
        }

        // If no orientation method exists, return the image as-is
        // In v3, orientation might be handled automatically
        return $image;
    }

    /**
     * Resize image with constraints.
     *
     * @param mixed $image
     * @param int $width
     * @param int $height
     * @return mixed
     */
    protected function resizeImage($image, $width, $height)
    {
        // Ensure dimensions are integers
        $width = (int) $width;
        $height = (int) $height;

        // Try v3 API first (cover method with aspect ratio)
        if (method_exists($image, 'cover')) {
            try {
                return $image->cover($width, $height);
            } catch (\Exception $e) {
                // Try basic resize in v3
                try {
                    return $image->resize($width, $height);
                } catch (\Exception $e2) {
                    // Continue to v2 API
                }
            }
        }

        // Try v2 syntax (with constraints)
        try {
            return $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } catch (\Exception $e) {
            // Last resort: simple resize without constraints
            return $image->resize($width, $height);
        }
    }

    /**
     * Encode image with quality.
     *
     * @param mixed $image
     * @param int $quality
     * @return string
     */
    protected function encodeImage($image, $quality)
    {
        // Ensure quality is an integer
        $quality = (int) $quality;

        // Try v3 API methods first
        if (method_exists($image, 'toJpeg')) {
            try {
                // v3 API: Use specific encoders
                return $image->toJpeg($quality)->toString();
            } catch (\Exception $e) {
                try {
                    // Fallback to PNG (no quality parameter for PNG in v3)
                    return $image->toPng()->toString();
                } catch (\Exception $e2) {
                    // Continue to v2 API
                }
            }
        }

        // Try v2 API methods
        try {
            return $image->encode(null, $quality);
        } catch (\Exception $e) {
            // Last resort: try toString if available
            if (method_exists($image, 'toString')) {
                return $image->toString();
            }

            // If all else fails, throw the original exception
            throw $e;
        }
    }

    /**
     * Get image dimensions.
     *
     * @param mixed $image
     * @return array
     */
    protected function getImageDimensions($image)
    {
        if ($this->isV3) {
            // v3 API: width() and height() methods
            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'aspect_ratio' => round($image->width() / $image->height(), 2)
            ];
        } else {
            // v2 API: width() and height() methods
            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'aspect_ratio' => round($image->width() / $image->height(), 2)
            ];
        }
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

            $image = $this->createImage($disk->get($path));
            $processed = false;

            // Auto-orient image
            if ($options['auto_orient'] ?? Config::get('uploader.auto_orient', true)) {
                try {
                    $image = $this->orientateImage($image);
                    $processed = true;
                } catch (\Exception $e) {
                    Log::warning('Image orientation failed: ' . $e->getMessage());
                    // Continue without orientation
                }
            }

            // Optimize quality
            $quality = (int) ($options['quality'] ?? Config::get('uploader.image_quality', 85));
            if ($quality < 100) {
                $processed = true;
            }

            // Apply processing if any changes were made
            if ($processed) {
                try {
                    $optimized = $this->encodeImage($image, $quality);
                    $disk->put($path, $optimized);
                } catch (\Exception $e) {
                    Log::warning('Image encoding failed: ' . $e->getMessage());
                    // Continue without optimization
                    $processed = false;
                }
            }

            // Generate thumbnails if enabled
            $thumbnails = [];
            $shouldGenerateThumbnails = $options['generate_thumbnails'] ?? Config::get('uploader.generate_thumbnails', true);
            if ($shouldGenerateThumbnails) {
                Log::info('Generating thumbnails for image', ['path' => $path]);
                $thumbnails = $this->generateThumbnails($path, $options);
                Log::info('Thumbnail generation result', ['count' => count($thumbnails), 'thumbnails' => $thumbnails]);
            } else {
                Log::info('Thumbnail generation disabled', ['config_value' => $shouldGenerateThumbnails]);
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
            Log::warning('Thumbnail generation skipped - Intervention Image not available');
            return [];
        }

        $disk = Storage::disk(Config::get('uploader.disk'));
        $sizes = $options['sizes'] ?? Config::get('uploader.thumbnail_sizes', [150, 300, 600]);
        $quality = $options['thumbnail_quality'] ?? Config::get('uploader.thumbnail_quality', 80);
        $thumbnails = [];

        Log::info('Starting thumbnail generation', [
            'image_path' => $imagePath,
            'sizes' => $sizes,
            'quality' => $quality
        ]);

        try {
            $image = $this->createImage($disk->get($imagePath));

            foreach ($sizes as $size) {
                $thumbnailPath = $this->getThumbnailPath($imagePath, $size);

                $thumbnail = clone $image;
                $thumbnail = $this->resizeImage($thumbnail, $size, $size);
                $encoded = $this->encodeImage($thumbnail, $quality);
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
            $image = $this->createImage($disk->get($path));

            return $this->getImageDimensions($image);
        } catch (\Exception $e) {
            Log::error('Failed to get image dimensions', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
