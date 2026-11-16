<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            $businessId = $request->session()->get('managed_business_id');
            abort_unless($businessId, 403, 'Seleccione explícitamente un negocio para administrarlo.');

            $business = Business::query()->find($businessId);
            abort_unless($business, 404);
            $user->setAttribute('business_id', $business->id);
        } else {
            $business = Business::query()->find($user->business_id);
            abort_unless($business && $business->active, 403);
        }

        return $next($request);
    }
}
