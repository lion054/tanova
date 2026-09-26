<?php
namespace Modules\Vendor\Models;

use App\BaseModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\SEO;

class VendorPlan extends BaseModel
{
    use SoftDeletes;
    protected $table = 'core_vendor_plans';
    protected $fillable = [
        'name',
        'base_commission',
        'price',
        'price_annual',
        'status',
        'tagline',
        'os_limit',
        'max_staff',
        'addon_price',
        'addon_price_annual',
        'highlight',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'price'        => 'float',
        'price_annual' => 'float',
        'base_commission' => 'integer',
        'os_limit'     => 'integer',
        'max_staff'    => 'integer',
        'addon_price'  => 'float',
        'addon_price_annual' => 'float',
        'highlight'    => 'boolean',
        'is_public'    => 'boolean',
    ];

    /** The plans offered to companies, in the order they are shown. */
    public static function offered()
    {
        return static::where('status', 'publish')->where('is_public', 1)->orderBy('sort_order')->orderBy('price')->get();
    }

    public function coversAllOs(): bool
    {
        return (int) $this->os_limit === 0;
    }

    /** "1 OS", "Any 3 OS", "Every OS" */
    public function osSummary(): string
    {
        $n = (int) $this->os_limit;

        return $n === 0 ? __('Every OS') : ($n === 1 ? __('1 OS of your choice') : __('Any :n OS', ['n' => $n]));
    }

    public static function getModelName()
    {
        return __("Vendor Plans");
    }

    public static function getAsMenuItem($id)
    {
        return parent::select('id', 'name')->find($id);
    }

    public static function searchForMenu($q = false)
    {
        $query = static::select('id', 'name');
        if (strlen($q)) {

            $query->where('name', 'like', "%" . $q . "%");
        }
        $a = $query->orderBy('id', 'desc')->limit(10)->get();
        return $a;
    }
    public function meta(){
        return $this->hasMany(VendorPlanMeta::class);
    }

    public function getEditUrlAttribute()
    {
        return null;
    }
}
