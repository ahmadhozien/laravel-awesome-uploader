<?php

namespace Hozien\Uploader;

use Hozien\Uploader\Traits\InteractsWithStorage;
use Hozien\Uploader\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Uploader
{
    use InteractsWithStorage;

    /**
     * Get file information without storing it.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function getFileInfo(UploadedFile $file): array
    {
        return [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'is_image' => Str::startsWith($file->getMimeType(), 'image'),
            'human_size' => $this->formatBytes($file->getSize()),
        ];
    }

    /**
     * Validate if file meets upload requirements.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array Returns ['valid' => bool, 'errors' => array]
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];
        $allowedTypes = Config::get('uploader.allowed_file_types', []);
        $maxSize = Config::get('uploader.max_size', 2048) * 1024; // Convert KB to bytes

        // Check file extension
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedTypes)) {
            $errors[] = "File type '{$file->getClientOriginalExtension()}' is not allowed.";
        }

        // Check file size
        if ($file->getSize() > $maxSize) {
            $errors[] = "File size exceeds maximum allowed size of " . $this->formatBytes($maxSize) . ".";
        }

        // Check if file is actually uploaded
        if (!$file->isValid()) {
            $errors[] = "File upload failed: " . $file->getErrorMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

        /**
     * Check if a file already exists based on hash.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return \Hozien\Uploader\Models\Upload|null
     */
    public function findDuplicate(UploadedFile $file): ?Upload
    {
        $hash = md5_file($file->getRealPath());
        
        return Upload::query()->where('file_hash', $hash)
            ->where('size', $file->getSize())
            ->first();
    }

    /**
     * Get upload statistics.
     *
     * @param int|null $userId
     * @return array
     */
    public function getUploadStats(?int $userId = null): array
    {
        $query = Upload::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $totalFiles = $query->count();
        $totalSize = $query->sum('size');
        $imageCount = $query->where('type', 'like', 'image%')->count();

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'image_count' => $imageCount,
            'document_count' => $totalFiles - $imageCount,
        ];
    }

    /**
     * Clean up orphaned files (files on disk without database records).
     *
     * @return array
     */
    public function cleanupOrphanedFiles(): array
    {
        $disk = Storage::disk(Config::get('uploader.disk'));
        $uploadPath = 'uploads';
        $cleaned = [];

        if (!$disk->exists($uploadPath)) {
            return ['cleaned' => 0, 'files' => []];
        }

        $diskFiles = $disk->allFiles($uploadPath);
        $dbPaths = Upload::query()->pluck('path')->toArray();

        foreach ($diskFiles as $filePath) {
            if (!in_array($filePath, $dbPaths)) {
                $disk->delete($filePath);
                $cleaned[] = $filePath;
            }
        }

        return [
            'cleaned' => count($cleaned),
            'files' => $cleaned
        ];
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Generate thumbnail for image uploads.
     *
     * @param string $imagePath
     * @param array $sizes
     * @return array
     */
    public function generateThumbnails(string $imagePath, array $sizes = [150, 300, 600]): array
    {
        if (!class_exists('\Intervention\Image\ImageManager')) {
            return ['error' => 'Intervention Image package not installed'];
        }

        $disk = Storage::disk(Config::get('uploader.disk'));
        $thumbnails = [];

        try {
            $manager = new \Intervention\Image\ImageManager(['driver' => 'gd']);
            $image = $manager->make($disk->get($imagePath));

            foreach ($sizes as $size) {
                $thumbnailPath = str_replace(
                    '.' . pathinfo($imagePath, PATHINFO_EXTENSION),
                    "_thumb_{$size}." . pathinfo($imagePath, PATHINFO_EXTENSION),
                    $imagePath
                );

                $thumbnail = clone $image;
                $thumbnail->resize($size, $size, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $disk->put($thumbnailPath, $thumbnail->encode());
                $thumbnails[$size] = [
                    'path' => $thumbnailPath,
                    'url' => $disk->url($thumbnailPath),
                ];
            }

            return ['thumbnails' => $thumbnails];
        } catch (\Exception $e) {
            return ['error' => 'Failed to generate thumbnails: ' . $e->getMessage()];
        }
    }
}
