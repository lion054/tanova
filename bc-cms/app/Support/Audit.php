<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The audit trail: one line per thing that changed money, status or settings, saying who did it and from where.
 * Never lets a failure to write the line break the thing being done, and never records secrets (only which fields changed).
 */
class Audit
{
    /**
     * @param  string  $action  e.g. "invoice.voided", "payment.recorded"
     * @param  object|null  $subject  a model with an id (and vendor_id)
     */
    public static function log(string $action, ?object $subject = null, array $meta = [], ?int $vendorId = null, ?string $summary = null): void
    {
        try {
            $req = app()->runningInConsole() ? null : request();
            $key = $req?->attributes->get('resolved_api_key');
            $user = auth()->user();
            DB::table('bc_audit_log')->insert([
                'vendor_id'    => $vendorId ?? ($subject->vendor_id ?? null) ?? (function_exists('resolve_current_vendor_id') ? resolve_current_vendor_id() : null),
                'actor_id'     => $key ? $key->id : ($user->id ?? null),
                'actor_type'   => $key ? 'api_key' : ($user ? 'user' : ($req && !app()->runningInConsole() ? 'guest' : 'system')),
                'action'       => substr($action, 0, 60),
                'subject_type' => $subject ? strtolower(class_basename($subject)) : null,
                'subject_id'   => $subject->id ?? null,
                'summary'      => $summary ? substr($summary, 0, 255) : null,
                'meta'         => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'ip'           => $req?->ip(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('audit_write_failed: ' . $e->getMessage());
        }
    }
}
