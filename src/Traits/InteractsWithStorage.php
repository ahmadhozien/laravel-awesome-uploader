<?php

namespace Hozien\Uploader\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Hozien\Uploader\Models\Upload;

trait InteractsWithStorage
{
    /**
     * Store the uploaded file with comprehensive error handling.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  array  $options
     * @return array
     */
    public function store(UploadedFile $file, array $options = []): array
    {
        try {
            // Validate file first
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check for duplicates if enabled
            if ($options['check_duplicates'] ?? Config::get('uploader.check_duplicates', true)) {
                $duplicate = $this->findDuplicate($file);
                if ($duplicate && ($options['return_existing'] ?? true)) {
                    return [
                        'success' => true,
                        'path' => $duplicate->path,
                        'url' => $duplicate->url,
                        'type' => $duplicate->type,
                        'name' => $duplicate->name,
                        'size' => $duplicate->size,
                        'file_hash' => $duplicate->file_hash,
                        'is_duplicate' => true,
                        'existing_id' => $duplicate->id
                    ];
                }
            }

            $disk = Config::get('uploader.disk');
            $directory = $options['directory'] ?? 'uploads';

            // Generate unique filename to prevent conflicts
            $filename = $this->generateUniqueFilename($file, $directory);

            // Store the file
            $path = $file->storeAs($directory, $filename, $disk);

            if (!$path) {
                Log::error('Failed to store uploaded file', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'disk' => $disk
                ]);

                return [
                    'success' => false,
                    'errors' => ['Failed to store file on disk']
                ];
            }

            // Verify file was actually stored
            if (!Storage::disk($disk)->exists($path)) {
                Log::error('File upload completed but file not found on disk', [
                    'path' => $path,
                    'disk' => $disk
                ]);

                return [
                    'success' => false,
                    'errors' => ['File storage verification failed']
                ];
            }

            // Generate file hash for deduplication
            $fileHash = $this->generateFileHash($file);

            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::disk($disk)->url($path),
                'type' => $file->getMimeType(),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'file_hash' => $fileHash,
                'is_duplicate' => false
            ];
        } catch (\Exception $e) {
            Log::error('Exception during file upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $file->getClientOriginalName() ?? 'unknown'
            ]);

            return [
                'success' => false,
                'errors' => ['An unexpected error occurred during file upload']
            ];
        }
    }

    /**
     * Generate a unique filename to prevent conflicts.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $directory
     * @return string
     */
    protected function generateUniqueFilename(UploadedFile $file, string $directory): string
    {
        $disk = Storage::disk(Config::get('uploader.disk'));
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $basename);
        $basename = substr($basename, 0, 50); // Limit length

        $filename = $basename . '.' . $extension;
        $counter = 1;

        // Check if file exists and generate unique name
        while ($disk->exists($directory . '/' . $filename)) {
            $filename = $basename . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $filename;
    }

    /**
     * Generate file hash for deduplication.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     */
    protected function generateFileHash(UploadedFile $file): string
    {
        return md5_file($file->getRealPath());
    }

    /**
     * Validate if file meets upload requirements.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array Returns ['valid' => bool, 'errors' => array]
     */
    protected function validateFile(UploadedFile $file): array
    {
        $errors = [];
        $allowedTypes = Config::get('uploader.allowed_file_types', []);
        $maxSize = Config::get('uploader.max_size', 2048) * 1024; // Convert KB to bytes

        // Check if file is valid
        if (!$file->isValid()) {
            $errors[] = "File upload failed: " . $file->getErrorMessage();
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, array_map('strtolower', $allowedTypes))) {
            $errors[] = "File type '.{$extension}' is not allowed. Allowed types: " . implode(', ', $allowedTypes);
        }

        // Check file size
        if ($file->getSize() > $maxSize) {
            $errors[] = "File size (" . $this->formatBytes($file->getSize()) . ") exceeds maximum allowed size of " . $this->formatBytes($maxSize);
        }

        // Additional MIME type validation
        $detectedMime = $file->getMimeType();
        if (!$this->isAllowedMimeType($detectedMime, $extension)) {
            $errors[] = "File MIME type '{$detectedMime}' does not match extension '.{$extension}'";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if MIME type is allowed for the given extension.
     *
     * @param  string  $mimeType
     * @param  string  $extension
     * @return bool
     */
    protected function isAllowedMimeType(string $mimeType, string $extension): bool
    {
        $allowedMimes = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        $extension = strtolower($extension);

        if (!isset($allowedMimes[$extension])) {
            return true; // Allow if not specifically restricted
        }

        return in_array($mimeType, $allowedMimes[$extension]);
    }

    /**
     * Find duplicate file based on hash and size.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return \Hozien\Uploader\Models\Upload|null
     */
    protected function findDuplicate(UploadedFile $file): ?Upload
    {
        $hash = $this->generateFileHash($file);

        return Upload::query()
            ->where('file_hash', $hash)
            ->where('size', $file->getSize())
            ->first();
    }

    /**
     * Format bytes to human readable format.
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Delete uploaded file from storage.
     *
     * @param  string  $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        try {
            $disk = Storage::disk(Config::get('uploader.disk'));

            if (!$disk->exists($path)) {
                Log::warning('Attempted to delete non-existent file', ['path' => $path]);
                return false;
            }

            return $disk->delete($path);
        } catch (\Exception $e) {
            Log::error('Failed to delete file', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
