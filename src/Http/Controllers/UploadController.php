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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    use InteractsWithStorage;

    protected $imageProcessor;

    public function __construct(ImageProcessor $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;
    }

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
     * Handle the file upload with enhanced features.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            // Check guest upload permissions
            $allowGuests = Config::get('uploader.allow_guests', false);
            if (!$allowGuests && !Auth::check()) {
                return Response::json(['errors' => ['Login required']], 403);
            }

            // Get upload options
            $multiple = $request->boolean('multiple', false) || $request->hasFile('files');
            $saveToDb = $request->boolean('saveToDb', Config::get('uploader.save_to_db', false));
            $guestToken = $request->input('guest_token');

            // Check guest upload limits
            if (!Auth::check() && $allowGuests) {
                $guestLimit = Config::get('uploader.guest_upload_limit', 10);
                $currentCount = Upload::where('guest_token', $guestToken)->count();

                if ($currentCount >= $guestLimit) {
                    return Response::json(['errors' => ['Guest upload limit exceeded']], 429);
                }
            }

            if ($multiple) {
                return $this->handleMultipleUpload($request, $saveToDb, $guestToken);
            } else {
                return $this->handleSingleUpload($request, $saveToDb, $guestToken);
            }
        } catch (\Exception $e) {
            \Log::error('Upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return Response::json([
                'errors' => ['An unexpected error occurred during upload'],
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle single file upload.
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $saveToDb
     * @param string|null $guestToken
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSingleUpload(Request $request, bool $saveToDb, ?string $guestToken)
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
        $result = $this->store($file, [
            'check_duplicates' => Config::get('uploader.check_duplicates', true),
            'return_existing' => Config::get('uploader.return_existing_on_duplicate', true)
        ]);

        if (!$result['success']) {
            return Response::json(['errors' => $result['errors']], 400);
        }

        // Process image if it's an image and processing is enabled
        if (Str::startsWith($result['type'], 'image') && Config::get('uploader.image_optimization')) {
            $processResult = $this->imageProcessor->process($result['path']);
            if ($processResult['success'] && isset($processResult['thumbnails'])) {
                $result['thumbnails'] = $processResult['thumbnails'];
            }
        }

        // Save to database if enabled
        if ($saveToDb) {
            $uploadData = [
                'path' => $result['path'],
                'url' => $result['url'],
                'type' => $result['type'],
                'name' => $result['name'],
                'size' => $result['size'],
                'file_hash' => $result['file_hash'],
                'user_id' => Auth::id(),
                'guest_token' => Auth::check() ? null : $guestToken,
            ];

            if ($result['is_duplicate'] && isset($result['existing_id'])) {
                $upload = Upload::find($result['existing_id']);
                $result['id'] = $upload->id;
            } else {
                $upload = Upload::create($uploadData);
                $result['id'] = $upload->id;
            }
        }

        return Response::json($result);
    }

    /**
     * Handle multiple file uploads.
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $saveToDb
     * @param string|null $guestToken
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleMultipleUpload(Request $request, bool $saveToDb, ?string $guestToken)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'file|mimes:' . implode(',', Config::get('uploader.allowed_file_types')) . '|max:' . Config::get('uploader.max_size'),
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $results = [];
        $errors = [];

        foreach ($request->file('files', []) as $index => $file) {
            $result = $this->store($file, [
                'check_duplicates' => Config::get('uploader.check_duplicates', true),
                'return_existing' => Config::get('uploader.return_existing_on_duplicate', true)
            ]);

            if (!$result['success']) {
                $errors["files.{$index}"] = $result['errors'];
                continue;
            }

            // Process image if needed
            if (Str::startsWith($result['type'], 'image') && Config::get('uploader.image_optimization')) {
                $processResult = $this->imageProcessor->process($result['path']);
                if ($processResult['success'] && isset($processResult['thumbnails'])) {
                    $result['thumbnails'] = $processResult['thumbnails'];
                }
            }

            // Save to database if enabled
            if ($saveToDb) {
                $uploadData = [
                    'path' => $result['path'],
                    'url' => $result['url'],
                    'type' => $result['type'],
                    'name' => $result['name'],
                    'size' => $result['size'],
                    'file_hash' => $result['file_hash'],
                    'user_id' => Auth::id(),
                    'guest_token' => Auth::check() ? null : $guestToken,
                ];

                if ($result['is_duplicate'] && isset($result['existing_id'])) {
                    $upload = Upload::find($result['existing_id']);
                    $result['id'] = $upload->id;
                } else {
                    $upload = Upload::create($uploadData);
                    $result['id'] = $upload->id;
                }
            }

            $results[] = $result;
        }

        if (!empty($errors)) {
            return Response::json(['errors' => $errors, 'results' => $results], 422);
        }

        return Response::json($results);
    }

    /**
     * Get uploads with pagination and filtering.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $userResolver = Config::get('uploader.user_resolver');
            $adminResolver = Config::get('uploader.admin_resolver');
            $uploadsQuery = Config::get('uploader.uploads_query');
            $allowGuests = Config::get('uploader.allow_guests', false);

            $user = $userResolver();
            $isAdmin = $adminResolver($user);
            $guestToken = $request->input('guest_token');

            $query = Upload::query()->with(['user' => function ($query) {
                $query->select('id', 'name', 'email'); // Only select needed fields
            }]);

            // Apply user/admin filtering
            if (!$user && $allowGuests) {
                $query->where('guest_token', $guestToken);
            } else {
                $query = $uploadsQuery($query, $user, $isAdmin);
            }

            // Apply filters
            if ($request->has('type')) {
                $type = $request->input('type');
                if ($type === 'images') {
                    $query->where('type', 'like', 'image%');
                } elseif ($type === 'documents') {
                    $query->where('type', 'not like', 'image%');
                }
            }

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('name', 'like', "%{$search}%");
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = min($request->input('per_page', Config::get('uploader.pagination_limit', 20)), 100);
            $uploads = $query->paginate($perPage);

            // Add permissions to each upload
            $uploads->getCollection()->transform(function ($upload) use ($guestToken, $user) {
                $uploadArray = $upload->toArray();

                // Simple permission logic for guest users
                $viewPermission = false;
                $deletePermission = false;

                if ($user) {
                    // Authenticated user - use policy
                    $viewPermission = Gate::allows('view', [$upload, $guestToken]);
                    $deletePermission = Gate::allows('delete', [$upload, $guestToken]);
                } else {
                    // Guest user - check guest token match
                    if ($guestToken && $upload->guest_token === $guestToken) {
                        $viewPermission = true;
                        $deletePermission = true;
                    }
                }

                $uploadArray['permissions'] = [
                    'view' => $viewPermission,
                    'delete' => $deletePermission,
                    'download' => $viewPermission,
                ];
                $uploadArray['formatted_size'] = $upload->formatted_size;
                $uploadArray['is_image'] = $upload->is_image;

                // Add debug info
                $uploadArray['debug'] = [
                    'guest_token_match' => $upload->guest_token === $guestToken,
                    'upload_guest_token' => $upload->guest_token,
                    'request_guest_token' => $guestToken,
                    'view_permission' => $viewPermission,
                    'delete_permission' => $deletePermission,
                    'user_authenticated' => $user ? true : false,
                    'upload_guest_token_type' => gettype($upload->guest_token),
                    'request_guest_token_type' => gettype($guestToken),
                    'tokens_identical' => $upload->guest_token === $guestToken,
                    'tokens_equal' => $upload->guest_token == $guestToken,
                ];

                return $uploadArray;
            });

            return Response::json($uploads);
        } catch (\Exception $e) {
            Log::error('Error in uploads index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return Response::json([
                'error' => 'An error occurred while fetching uploads',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete an upload.
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Request $request)
    {
        $upload = Upload::find($id);

        if (!$upload) {
            return Response::json(['error' => 'File not found'], 404);
        }

        // Check permissions
        $guestToken = $request->input('guest_token');
        $user = Auth::user();

        $canDelete = false;

        if ($user) {
            // Authenticated user - use policy
            $canDelete = Gate::allows('delete', [$upload, $guestToken]);
        } else {
            // Guest user - check guest token match
            $canDelete = $guestToken && $upload->guest_token === $guestToken;
        }

        if (!$canDelete) {
            return Response::json(['error' => 'Unauthorized'], 403);
        }

        $softDeletes = Config::get('uploader.soft_deletes', true);

        // Delete thumbnails if it's an image
        if ($upload->is_image) {
            $this->imageProcessor->deleteThumbnails($upload->path);
        }

        if ($softDeletes) {
            $upload->delete();
        } else {
            // Delete actual file from storage
            $this->deleteFile($upload->path);
            $upload->forceDelete();
        }

        return Response::json(['success' => true]);
    }

    /**
     * Get upload statistics.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        $userResolver = Config::get('uploader.user_resolver');
        $user = $userResolver();

        if (!$user) {
            return Response::json(['error' => 'Authentication required'], 403);
        }

        $uploader = app('uploader');
        $stats = $uploader->getUploadStats($user->id);

        return Response::json($stats);
    }

    /**
     * Clean up orphaned files.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanup()
    {
        $userResolver = Config::get('uploader.user_resolver');
        $adminResolver = Config::get('uploader.admin_resolver');

        $user = $userResolver();
        $isAdmin = $adminResolver($user);

        if (!$isAdmin) {
            return Response::json(['error' => 'Admin access required'], 403);
        }

        $uploader = app('uploader');
        $result = $uploader->cleanupOrphanedFiles();

        return Response::json($result);
    }
}
