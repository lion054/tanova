<?php

namespace App\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains tenant-owned models to the current vendor.
 *
 * Applied automatically by App\Traits\BelongsToVendor. The tenant id comes from
 * resolve_current_vendor_id() (API-key context first, then the authenticated user).
 *
 * When no tenant can be resolved (CLI, system jobs, seeders, unauthenticated, or a
 * super-admin context that opts out) the scope is a no-op — isolation for those
 * paths is the caller's responsibility via withoutVendorScope() / explicit filters.
 */
class VendorScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $vendorId = resolve_current_vendor_id();

        if ($vendorId === null) {
            return;
        }

        $builder->where(
            $model->getTable() . '.' . $model->getVendorColumn(),
            $vendorId
        );
    }

    public function extend(Builder $builder): void
    {
        // ->withoutVendorScope() — escape hatch for cross-vendor reads
        // (super-admin views, marketplace discovery, background jobs).
        $builder->macro('withoutVendorScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });
    }
}
