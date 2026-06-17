<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseAdminSession
{
    /**
     * Give the Filament admin panel its own session cookie.
     *
     * The admin panel ('backend' guard) and the customer app ('web' guard) would
     * otherwise share one cookie. In a single browser, logging into admin and
     * Filament's session regenerate clobbered the customer's session. A distinct
     * cookie lets both sessions coexist. Must run before StartSession.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldUseAdminSession($request)) {
            $cookie = (string) config('session.cookie');

            config([
                'session.cookie' => str_ends_with($cookie, '_admin')
                    ? $cookie
                    : $cookie.'_admin',
            ]);
        }

        return $next($request);
    }

    private function shouldUseAdminSession(Request $request): bool
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return true;
        }

        if (! $this->isLivewireUpdate($request)) {
            return false;
        }

        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);

        return is_string($refererPath)
            && ($refererPath === '/admin' || str_starts_with($refererPath, '/admin/'));
    }

    private function isLivewireUpdate(Request $request): bool
    {
        return str_contains($request->path(), 'livewire')
            && str_ends_with($request->path(), '/update');
    }
}
