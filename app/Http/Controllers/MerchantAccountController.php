<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantAccountRequest;
use App\Models\BackendUser;
use App\Models\MerchantRequest;
use Illuminate\Http\RedirectResponse;

class MerchantAccountController extends Controller
{
    public function store(MerchantAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'You must be logged in to request a merchant account.');
        }

        if (BackendUser::query()->where('email', $user->email)->exists()) {
            return back()->with('error', 'A backend account already exists for this email.');
        }

        if (MerchantRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists()
        ) {
            return back()->with('error', 'You already have a pending merchant request.');
        }

        MerchantRequest::query()->create([
            'user_id' => $user->id,
            'message' => $request->validated()['message'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Merchant request submitted. We will review it shortly.');
    }
}
