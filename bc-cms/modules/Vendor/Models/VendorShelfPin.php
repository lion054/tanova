<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorShelfPin extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_shelf_pins';

    public const SHELVES = ['trending' => 'Trending', 'bestseller' => 'Bestsellers'];

    protected $fillable = ['vendor_id', 'shelf', 'object_model', 'object_id'];
}
