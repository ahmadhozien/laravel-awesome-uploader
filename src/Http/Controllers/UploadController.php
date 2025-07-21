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
use Hozien\Uploader\Models\Upload;

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
        $allowGuests = Config::get('uploader.allow_guests', false);
        $guestTokenResolver = Config::get('uploader.guest_token_resolver');
        $guestToken = $guestTokenResolver();
        if (!$allowGuests && !auth()->check()) {
            return Response::json(['error' => 'Login required'], 403);
        }
        $multiple = $request->input('multiple', false) || $request->hasFile('files');
        $saveToDb = $request->boolean('saveToDb', false);
        if ($multiple) {
            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => 'file|mimes:' . implode(',', Config::get('uploader.allowed_file_types')) . '|max:' . Config::get('uploader.max_size'),
            ]);
            if ($validator->fails()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            $results = [];
            foreach ($request->file('files', []) as $file) {
                $result = $this->store($file);
                if ($result && Str::startsWith($result['type'], 'image') && Config::get('uploader.image_optimization')) {
                    $imageProcessor->process($result['path']);
                }
                if ($saveToDb && $result) {
                    $upload = Upload::create([
                        'path' => $result['path'],
                        'url' => $result['url'],
                        'type' => $result['type'],
                        'name' => $result['name'],
                        'size' => $result['size'],
                        'user_id' => auth()->id(),
                        'guest_token' => auth()->check() ? null : $guestToken,
                    ]);
                    $result['id'] = $upload->id;
                }
                $results[] = $result;
            }
            return Response::json($results);
        } else {
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
            if ($saveToDb && $result) {
                $upload = Upload::create([
                    'path' => $result['path'],
                    'url' => $result['url'],
                    'type' => $result['type'],
                    'name' => $result['name'],
                    'size' => $result['size'],
                    'user_id' => auth()->id(),
                    'guest_token' => auth()->check() ? null : $guestToken,
                ]);
                $result['id'] = $upload->id;
            }
            return $result
                ? Response::json($result)
                : Response::json(['error' => 'Could not save file.'], 500);
        }
    }

    public function index(Request $request)
    {
        $userResolver = Config::get('uploader.user_resolver');
        $adminResolver = Config::get('uploader.admin_resolver');
        $uploadsQuery = Config::get('uploader.uploads_query');
        $guestTokenResolver = Config::get('uploader.guest_token_resolver');
        $allowGuests = Config::get('uploader.allow_guests', false);
        $user = $userResolver();
        $isAdmin = $adminResolver($user);
        $guestToken = $guestTokenResolver();
        $query = Upload::query();
        if (!$user && $allowGuests) {
            $query = $query->where('guest_token', $guestToken);
        } else {
            $query = $uploadsQuery($query, $user, $isAdmin);
        }
        $uploads = $query->latest()->get();
        return Response::json($uploads);
    }
}
