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
    private static string $mode = 'live';     // 'live' | 'test'
    private static ?string $version = null;   // resolved API version (date string)

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

    // ── Mode (live / test sandbox) ─────────────────────────────────────────────

    public static function setMode(string $mode): void
    {
        self::$mode = $mode === 'test' ? 'test' : 'live';
    }

    public static function mode(): string
    {
        return self::$mode;
    }

    public static function isTest(): bool
    {
        return self::$mode === 'test';
    }

    // ── API version ────────────────────────────────────────────────────────────

    public static function setVersion(string $version): void
    {
        self::$version = $version;
    }

    public static function version(): ?string
    {
        return self::$version;
    }

    public static function clear(): void
    {
        self::$vendor = null;
        self::$mode = 'live';
        self::$version = null;
    }
}
