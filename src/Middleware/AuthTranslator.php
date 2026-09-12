<?php

namespace Riomigal\Languages\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthTranslator
{
    /**
     * @param Request $request
     * @param \Closure(Request): (Response|RedirectResponse|JsonResponse|null) $next
     * @return Response|RedirectResponse|JsonResponse|null
     */
    public function handle(Request $request, \Closure $next): Response|RedirectResponse|JsonResponse|null
    {
        /** @var string $guard Configured translator guard name. */
        $guard = config('languages.translator_guard');

        if($request->wantsJson()) {
            if (!auth($guard)->check()) {
                return response()->json(['message' => 'User is not authenticated!'], 403);
            }
        } else {
            if (!auth($guard)->check()) {
                abort(302, '', ['Location' => route('languages.login')]);
            }
        }
        return $next($request);
    }
}
