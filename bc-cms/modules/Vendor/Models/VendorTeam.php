<?php

namespace Modules\Vendor\Models;

use App\BaseModel;
use App\User;

class VendorTeam extends BaseModel
{

   const STATUS_PUBLISH = 'publish';
   const STATUS_PENDING = 'pending';

   protected $table = 'vendor_team';

   protected $casts = [
       'permissions'=>'array'
   ];

   protected static function booted(): void
   {
       // The person's company link (users.vendor_id, which the portal reads to know whose things they see) follows the team record.
       static::saved(function (VendorTeam $t) {
           if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'vendor_id')) {
               \Illuminate\Support\Facades\DB::table('users')->where('id', $t->member_id)->update(['vendor_id' => $t->status === self::STATUS_PUBLISH ? $t->vendor_id : null]);
           }
       });
       static::deleted(function (VendorTeam $t) {
           \Illuminate\Support\Facades\DB::table('users')->where('id', $t->member_id)->where('vendor_id', $t->vendor_id)->update(['vendor_id' => null]);
       });
   }

   public function getStatusBadgeAttribute(): string
   {
       return $this->status === self::STATUS_PUBLISH ? 'success' : 'warning';
   }

   public function getStatusTextAttribute(): string
   {
       return $this->status === self::STATUS_PUBLISH ? __('Active') : __('Invited');
   }

   public function vendor(){
       return $this->belongsTo(User::class,'vendor_id');
   }
   public function member(){
       return $this->belongsTo(User::class,'member_id');
   }
}
