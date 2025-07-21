<?php

namespace Hozien\Uploader\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

trait InteractsWithStorage
{
    /**
     * Store the uploaded file.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array|false
     */
    public function store(UploadedFile $file)
    {
        $disk = Config::get('uploader.disk');
        $path = $file->store('uploads', $disk);

        if ($path) {
            return [
                'path' => $path,
                'url' => Storage::url($path),
                'type' => $file->getMimeType(),
            ];
        }

        return false;
    }
}
