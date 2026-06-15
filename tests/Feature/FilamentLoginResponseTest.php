<?php

namespace Tests\Feature;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class FilamentLoginResponseTest extends TestCase
{
    public function test_admin_login_ignores_a_customer_intended_url(): void
    {
        $this->withSession([
            'url.intended' => route('dashboard'),
        ]);

        $request = Request::create('/admin/login', 'POST');
        $request->setLaravelSession(app('session.store'));

        $response = app(LoginResponse::class)->toResponse($request);

        $this->assertSame(url('/admin'), $response->getTargetUrl());
        $this->assertFalse(session()->has('url.intended'));
    }
}
