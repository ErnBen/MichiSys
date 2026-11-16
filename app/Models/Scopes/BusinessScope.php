<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        $businessId = $user?->business_id;

        if (! $businessId && $user?->role === 'superadmin') {
            $businessId = request()->session()->get('managed_business_id');
        }

        if ($businessId) {
            $builder->where($model->qualifyColumn('business_id'), $businessId);

            return;
        }

        if (! app()->runningInConsole()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
