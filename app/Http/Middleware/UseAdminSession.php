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
        config(['session.cookie' => config('session.cookie').'_admin']);

        return $next($request);
    }
}
