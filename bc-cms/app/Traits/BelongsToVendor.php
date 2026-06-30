<?php

namespace App\Traits;

use App\Scope\VendorScope;
use App\User;

/**
 * Makes a model tenant-aware in the multi-tenant (vendor) SaaS.
 *
 * Reading: a global VendorScope auto-filters every query to the current vendor.
 * Writing: the owning `vendor_id` is auto-stamped on create from the current
 *          tenant, so callers can never forget it (and can never set the wrong one).
 *
 * Override the column name with a `VENDOR_COLUMN` constant if it is not `vendor_id`.
 *
 * Escape hatch: Model::withoutVendorScope() bypasses the read filter for legitimate
 * cross-vendor reads (super-admin, marketplace discovery, jobs).
 */
trait BelongsToVendor
{
    public static function bootBelongsToVendor(): void
    {
        static::addGlobalScope(new VendorScope());

        static::creating(function ($model) {
            $column = $model->getVendorColumn();

            if (empty($model->{$column})) {
                $vendorId = resolve_current_vendor_id();

                if ($vendorId !== null) {
                    $model->{$column} = $vendorId;
                }
            }
        });
    }

    public function getVendorColumn(): string
    {
        return defined(static::class . '::VENDOR_COLUMN') ? static::VENDOR_COLUMN : 'vendor_id';
    }

    public function vendorOwner()
    {
        return $this->belongsTo(User::class, $this->getVendorColumn());
    }
}
