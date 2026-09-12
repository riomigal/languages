<?php

namespace Riomigal\Languages\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApi
{
    /**
     * @param Request $request
     * @param \Closure(Request): (JsonResponse|null) $next
     * @return JsonResponse|null
     */
    public function handle(Request $request, \Closure $next): JsonResponse|null
    {
        $request->headers->set('Accept', 'application/json');

        /** @var array{api_key: string} $data Guaranteed by the required|string validation rule. */
        $data = $request->validate([
            'api_key' => 'required|string',
        ]);

        $sharedKey = config('languages.api_shared_api_key');

        // Fail closed: with no key configured the inter-host API is unavailable
        // rather than falling back to a value that ships with the package.
        if (!is_string($sharedKey) || $sharedKey === '') {
            return response()->json(
                ['invalid_key' => 'Api key is not configured!'],
                503
            );
        }

        if (!hash_equals($sharedKey, $data['api_key'])) {
            return response()->json(['invalid_key' => 'Api key is invalid!'], 401);
        }

        return $next($request);
    }
}
