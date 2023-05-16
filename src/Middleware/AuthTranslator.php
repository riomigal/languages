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
     * @param \Closure $next
     * @return Response|RedirectResponse|JsonResponse|null
     */
    public function handle(Request $request, \Closure $next): Response|RedirectResponse|JsonResponse|null
    {

        if($request->wantsJson()) {
            if (!auth(config('languages.translator_guard'))->check()) {
                return response()->json(['message' => 'User is not authenticated!'], 403);
            }
        } else {
            if (!auth(config('languages.translator_guard'))->check()) {
                abort(302, '', ['Location' => route('languages.login')]);
            }
        }
        return $next($request);
    }
}
