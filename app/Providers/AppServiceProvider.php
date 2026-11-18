<?php

namespace App\Providers;

use App\Models\User;
use App\Http\Responses\RoleBasedLoginResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, RoleBasedLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config([
            'adminlte.plugins' => array_merge((array) config('adminlte.plugins', []), [
                'michisys-touch' => [
                    'active' => true,
                    'files' => [
                        ['type' => 'css', 'asset' => true, 'location' => 'css/michisys-touch.css'],
                    ],
                ],
            ]),
        ]);

        Gate::define('manage-platform', fn (User $user): bool => $user->role === 'superadmin');
        Gate::define('manage-business', fn (User $user): bool =>
            (in_array($user->role, ['dueno', 'admin'], true) && (bool) $user->business_id)
            || ($user->role === 'superadmin' && (bool) session('managed_business_id'))
        );
        Gate::define('use-sales', fn (User $user): bool =>
            (in_array($user->role, ['dueno', 'admin', 'cajero'], true) && (bool) $user->business_id)
            || ($user->role === 'superadmin' && (bool) session('managed_business_id'))
        );
        Gate::define('use-receivables', fn (User $user): bool =>
            (in_array($user->role, ['dueno', 'admin', 'cajero'], true) && (bool) $user->business_id)
            || ($user->role === 'superadmin' && (bool) session('managed_business_id'))
        );
    }
}
