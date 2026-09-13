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
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var string $guard Configured translator guard name. */
        $guard = config('languages.translator_guard');
        $authUser = Translator::find(auth($guard)->user()?->id);

        if (!$authUser) {
            abort(403);
        }

        // Background JSON requests must not consume a form redirect's flash data.
        if ($request->expectsJson()) {
            $request->session()->reflash();
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
