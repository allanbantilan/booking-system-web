<?php

namespace Tests\Feature;

use App\Http\Middleware\UseAdminSession;
use Illuminate\Http\Request;
use Tests\TestCase;

class UseAdminSessionTest extends TestCase
{
    public function test_it_uses_a_separate_cookie_from_the_customer_session(): void
    {
        config(['session.cookie' => 'laravel_session']);

        (new UseAdminSession())->handle(
            Request::create('/admin'),
            fn () => response('ok'),
        );

        $this->assertSame('laravel_session_admin', config('session.cookie'));
    }
}
