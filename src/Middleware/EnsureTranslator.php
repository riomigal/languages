<?php

namespace Riomigal\Languages\Middleware;

use Closure;
use Illuminate\Http\Request;
use Riomigal\Languages\Models\Translator;
use Symfony\Component\HttpFoundation\Response;

class EnsureTranslator
{
    /**
     * Resolve the package translator and share the authorization context.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = Translator::find(auth(config('languages.translator_guard'))->user()?->id);

        if (!$authUser) {
            abort(403);
        }

        $request->attributes->set('authUser', $authUser);
        $request->attributes->set('isAdministrator', (bool) $authUser->admin);

        view()->share([
            'authUser' => $authUser,
            'isAdministrator' => (bool) $authUser->admin,
        ]);

        return $next($request);
    }
}
