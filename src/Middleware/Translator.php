<?php

namespace Riomigal\Languages\Middleware;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Translator
{
    /**
     * @param Request $request
     * @param \Closure $next
     * @return Response|RedirectResponse|null
     */
    public function handle(Request $request, \Closure $next): Response|RedirectResponse|null
    {
        if(!auth()->check()) {
            abort(403);
        }
        return $next($request);
    }
}
