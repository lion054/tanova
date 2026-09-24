<?php

namespace Pro\Integrations\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorSyncLog extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_operator_sync_logs';

    protected $fillable = ['vendor_id', 'operator_id', 'status', 'records', 'message'];

    protected $casts = ['records' => 'integer'];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
}
