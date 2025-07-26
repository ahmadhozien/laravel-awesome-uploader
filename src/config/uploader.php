<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage disk that will be used by the
    | uploader. You can set this to any of the disks defined in your
    | `config/filesystems.php` file.
    |
    */
    'disk' => env('UPLOADER_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | Here you can specify the file types that are allowed to be uploaded.
    | You can add or remove any file extensions as needed.
    |
    */
    'allowed_file_types' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'txt',
        'csv',
        'zip',
        'rar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max File Size (in KB)
    |--------------------------------------------------------------------------
    |
    | This value specifies the maximum file size allowed for uploads in
    | kilobytes. Any file larger than this value will be rejected.
    |
    */
    'max_size' => 2048, // 2MB

    /*
    |--------------------------------------------------------------------------
    | File Deduplication
    |--------------------------------------------------------------------------
    |
    | When enabled, the uploader will check for duplicate files based on their
    | hash and size before uploading. If a duplicate is found, it can either
    | return the existing file or upload a new copy.
    |
    */
    'check_duplicates' => env('UPLOADER_CHECK_DUPLICATES', true),
    'return_existing_on_duplicate' => env('UPLOADER_RETURN_EXISTING', true),

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    |
    | When enabled, uploaded images will be automatically optimized to reduce
    | their file size. This requires the `intervention/image` package.
    | If the package is not installed, this feature will be gracefully skipped.
    |
    */
    'image_optimization' => env('UPLOADER_IMAGE_OPTIMIZATION', true),
    'image_quality' => env('UPLOADER_IMAGE_QUALITY', 85), // 1-100
    'auto_orient' => env('UPLOADER_AUTO_ORIENT', true), // Fix image orientation

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Generation
    |--------------------------------------------------------------------------
    |
    | Automatically generate thumbnails for uploaded images.
    | Requires intervention/image package.
    |
    */
    'generate_thumbnails' => env('UPLOADER_GENERATE_THUMBNAILS', true),
    'thumbnail_sizes' => [150, 300, 600], // Width in pixels
    'thumbnail_quality' => env('UPLOADER_THUMBNAIL_QUALITY', 80),

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Additional security measures for file uploads.
    |
    */
    'strict_mime_validation' => env('UPLOADER_STRICT_MIME', true),
    'scan_uploaded_files' => env('UPLOADER_SCAN_FILES', false), // Future: virus scanning
    'max_filename_length' => env('UPLOADER_MAX_FILENAME_LENGTH', 100),

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | Enable or disable soft deletes for uploads. If true, deleted uploads are
    | only marked as deleted and can be restored. If false, uploads are permanently deleted.
    |
    */
    'soft_deletes' => env('UPLOADER_SOFT_DELETES', true),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Settings for cleaning up orphaned files and old uploads.
    |
    */
    'auto_cleanup_orphaned' => env('UPLOADER_AUTO_CLEANUP', false),
    'cleanup_after_days' => env('UPLOADER_CLEANUP_DAYS', 30), // Delete soft-deleted files after X days

    /*
    |--------------------------------------------------------------------------
    | Guest Uploads
    |--------------------------------------------------------------------------
    |
    | Allow non-authenticated users to upload files. If true, guests can upload
    | and their uploads are tracked by a guest token (session ID by default).
    |
    */
    'allow_guests' => env('UPLOADER_ALLOW_GUESTS', true),
    'guest_token_resolver' => function () {
        return session()->getId();
    },
    'guest_upload_limit' => env('UPLOADER_GUEST_LIMIT', 10), // Max files per guest session

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Settings for database integration and querying.
    |
    */
    'save_to_db' => env('UPLOADER_SAVE_TO_DB', true),
    'pagination_limit' => env('UPLOADER_PAGINATION_LIMIT', 20),

    /*
    |--------------------------------------------------------------------------
    | User/Admin/Query Resolvers
    |--------------------------------------------------------------------------
    |
    | These closures allow you to customize how the uploader determines the
    | current user, admin status, and which uploads to fetch. You can override
    | these in your app's config/uploader.php for full flexibility.
    |
    */
    'user_resolver' => function () {
        return auth()->user();
    },
    'admin_resolver' => function ($user) {
        return $user && property_exists($user, 'is_admin') && $user->is_admin;
    },
    'uploads_query' => function ($query, $user, $isAdmin) {
        if ($isAdmin) {
            return $query;
        }
        return $query->where('user_id', $user ? $user->id : null);
    },

    /*
    |--------------------------------------------------------------------------
    | UI Modules
    |--------------------------------------------------------------------------
    |
    | These options allow you to enable or disable the different frontend
    | UI modules provided by this package.
    |
    */
    'ui' => [
        'blade' => true,
        'react' => true,
        'vue' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for debugging upload issues.
    |
    */
    'enable_logging' => env('UPLOADER_ENABLE_LOGGING', false),
    'log_channel' => env('UPLOADER_LOG_CHANNEL', 'daily'), // Laravel log channel
];
