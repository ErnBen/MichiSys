<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model): void {
            if ($model->business_id) {
                return;
            }

            $businessId = Auth::user()?->business_id;

            if (! $businessId && app()->runningUnitTests()) {
                $businessId = DB::table('businesses')->orderBy('id')->value('id');
            }

            if (! $businessId && app()->runningInConsole()) {
                $businessId = DB::table('businesses')->orderBy('id')->value('id');
            }

            if (! $businessId) {
                if (app()->runningInConsole()) {
                    return;
                }

                throw new LogicException('A business context is required to create this record.');
            }

            $model->business_id = $businessId;
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
