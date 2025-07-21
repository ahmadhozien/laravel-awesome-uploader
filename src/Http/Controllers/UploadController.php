<?php

namespace Hozien\Uploader\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Hozien\Uploader\Traits\InteractsWithStorage;
use Hozien\Uploader\Support\ImageProcessor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class UploadController extends Controller
{
    use InteractsWithStorage;

    /**
     * Show the uploader view.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return View::make('uploader::popup');
    }

    /**
     * Handle the file upload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Hozien\Uploader\Support\ImageProcessor  $imageProcessor
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request, ImageProcessor $imageProcessor)
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimes:' . implode(',', Config::get('uploader.allowed_file_types')),
                'max:' . Config::get('uploader.max_size'),
            ],
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $result = $this->store($file);

        if ($result && Str::startsWith($result['type'], 'image') && Config::get('uploader.image_optimization')) {
            $imageProcessor->process($result['path']);
        }

        return $result
            ? Response::json($result)
            : Response::json(['error' => 'Could not save file.'], 500);
    }
}
