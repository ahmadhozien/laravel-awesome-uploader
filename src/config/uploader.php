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
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
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
    | Image Optimization
    |--------------------------------------------------------------------------
    |
    | When enabled, uploaded images will be automatically optimized to reduce
    | their file size. This requires the `intervention/image` package.
    |
    */
    'image_optimization' => true,

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | Enable or disable soft deletes for uploads. If true, deleted uploads are
    | only marked as deleted and can be restored. If false, uploads are permanently deleted.
    |
    */
    'soft_deletes' => true,

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
];
