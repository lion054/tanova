<?php

namespace Pro\Integrations\Models;

use App\BaseModel;

class IntegrationRoomChannel extends BaseModel
{
    protected $table = 'bc_integration_room_channels';

    protected $fillable = [
        'integration_slug', 'nobeds_room_id', 'nobeds_hotel_id',
        'channel_room_id', 'channel_rate_id', 'enabled',
        'last_synced_at', 'last_error',
    ];

    protected $casts = [
        'enabled'        => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}
