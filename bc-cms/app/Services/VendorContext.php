<?php

namespace App\Services;

use App\User;

/**
 * Request-scoped vendor identity resolved from an API key.
 * Set once by ResolveVendorApiKey middleware; read by model scopes and controllers.
 */
class VendorContext
{
    private static ?User $vendor = null;

    public static function set(User $vendor): void
    {
        self::$vendor = $vendor;
    }

    public static function get(): ?User
    {
        return self::$vendor;
    }

    public static function id(): ?int
    {
        return self::$vendor?->id;
    }

    public static function active(): bool
    {
        return self::$vendor !== null;
    }

    public static function clear(): void
    {
        self::$vendor = null;
    }
}
