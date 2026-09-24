<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** One confirmed movement of money. Append-only: see the migration. */
class LedgerEntry extends Model
{
    use BelongsToVendor;

    public const UPDATED_AT = null;

    protected $table = 'bc_money_ledger';
    protected $guarded = [];
    protected $casts = ['amount' => 'decimal:2', 'base_amount' => 'decimal:2', 'occurred_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('The money ledger is append-only: post a reversing entry instead.'));
        static::deleting(fn () => throw new \LogicException('The money ledger is append-only: post a reversing entry instead.'));
    }

    public function isReversal(): bool
    {
        return $this->reverses_id !== null;
    }

    public function heldByPlatform(): bool
    {
        return $this->held_by === 'platform';
    }
}
