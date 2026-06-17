<?php

namespace Tests\Feature;

use App\Http\Middleware\UseAdminSession;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Tests\TestCase;

class UseAdminSessionTest extends TestCase
{
    public function test_it_uses_a_separate_cookie_for_admin_routes(): void
    {
        config(['session.cookie' => 'laravel_session']);

        (new UseAdminSession())->handle(
            Request::create('/admin'),
            fn () => response('ok'),
        );

        $this->assertSame('laravel_session_admin', config('session.cookie'));
    }

    public function test_it_uses_admin_cookie_for_admin_livewire_requests(): void
    {
        config(['app.url' => 'http://localhost']);
        config(['session.cookie' => 'laravel_session']);

        $request = Request::create(
            '/livewire-7420c050/update',
            'POST',
            server: ['HTTP_REFERER' => 'http://localhost/admin/bookings'],
        );

        (new UseAdminSession())->handle(
            $request,
            fn () => response('ok'),
        );

        $this->assertSame('laravel_session_admin', config('session.cookie'));
    }

    public function test_it_keeps_customer_session_cookie_for_non_admin_routes(): void
    {
        config(['session.cookie' => 'laravel_session']);

        (new UseAdminSession())->handle(
            Request::create('/login'),
            fn () => response('ok'),
        );

        $this->assertSame('laravel_session', config('session.cookie'));
    }

    public function test_it_runs_before_session_start_in_the_web_stack(): void
    {
        $webMiddleware = app('router')->getMiddlewareGroups()['web'];

        $adminSessionIndex = array_search(UseAdminSession::class, $webMiddleware, true);
        $startSessionIndex = array_search(StartSession::class, $webMiddleware, true);

        $this->assertNotFalse($adminSessionIndex);
        $this->assertNotFalse($startSessionIndex);
        $this->assertLessThan($startSessionIndex, $adminSessionIndex);
    }
}
