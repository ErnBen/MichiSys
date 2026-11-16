<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse;

class RoleBasedLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse
    {
        return match ($request->user()->role) {
            'superadmin' => redirect()->route('superadmin.businesses.index'),
            'cajero' => redirect()->route('sales.create'),
            default => redirect()->intended(route('dashboard')),
        };
    }
}
