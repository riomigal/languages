<?php

namespace Riomigal\Languages\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApi
{
    /**
     * @param Request $request
     * @param \Closure $next
     * @return JsonResponse|null
     */
    public function handle(Request $request, \Closure $next): JsonResponse|null
    {
        $request->headers->set('Accept', 'application/json');

        $data = $request->validate([
            'api_key' => 'required|string',
        ]);

        if($data['api_key'] !== config('languages.api_shared_api_key')) {
            return response()->json(['invalid_key' => 'Api key is invalid!', 401]);
        }

        return $next($request);
    }
}
