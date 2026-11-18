<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            $businessId = $request->session()->get('managed_business_id');
            if (! $businessId) {
                return redirect()->route('superadmin.businesses.index')
                    ->with('info', 'Seleccione un negocio para administrarlo.');
            }

            $business = Business::query()->find($businessId);
            abort_unless($business, 404);
            $user->setAttribute('business_id', $business->id);
        } else {
            $business = Business::query()->find($user->business_id);
            abort_unless($business && $business->active, 403);
        }

        $brand = $user->role === 'superadmin'
            ? 'MichiSys · '.$business->name
            : $business->name;
        config([
            'adminlte.title' => $brand,
            'adminlte.logo' => e($brand),
            'adminlte.logo_img' => $business->logo
                ? Storage::disk('public')->url($business->logo)
                : 'images/michisys-store.svg',
            'adminlte.logo_img_alt' => $brand,
        ]);
        View::share('managedBusiness', $business);

        return $next($request);
    }
}
