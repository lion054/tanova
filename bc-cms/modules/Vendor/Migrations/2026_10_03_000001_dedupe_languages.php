<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The language list held every language twice (English, Japanese and Egyptian each appeared two times), so pickers showed duplicates.
 * Keeps the first of each and drops the rest. Translations belong to a language by its code, so none are lost.
 */
return new class extends Migration {
    public function up(): void
    {
        $keep = DB::table('core_languages')->whereNull('deleted_at')->selectRaw('MIN(id) as id')->groupBy('locale')->pluck('id')->all();
        if ($keep) {
            DB::table('core_languages')->whereNull('deleted_at')->whereNotIn('id', $keep)->delete();
        }
        Cache::forget('locale_active_0');
        Cache::forget('locale_active_1');
    }

    public function down(): void
    {
    }
};
