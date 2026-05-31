<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\User;

class VendorAllowedOrigin extends Model
{
    protected $table = 'bc_vendor_allowed_origins';

    protected $fillable = ['vendor_id', 'origin'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public static function isAllowed(int $vendorId, string $origin): bool
    {
        return static::where('vendor_id', $vendorId)
            ->where('origin', $origin)
            ->exists();
    }
}
