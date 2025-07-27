<?php

namespace Hozien\Uploader\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // If response is not already JSON, convert it
        if (!$response instanceof JsonResponse) {
            // Log the non-JSON response for debugging
            Log::warning('Non-JSON response detected in uploader API', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'response_type' => get_class($response),
                'status' => $response->getStatusCode()
            ]);

            // Convert to JSON response
            $content = $response->getContent();

            // Try to decode if it's already JSON
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return response()->json($data, $response->getStatusCode());
            }

            // If it's not JSON, wrap it in an error response
            return response()->json([
                'error' => 'Invalid response format',
                'message' => 'Server returned non-JSON response',
                'debug' => config('app.debug') ? $content : null
            ], 500);
        }

        return $response;
    }
}
