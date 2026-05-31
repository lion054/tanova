<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all Dare2Travel activities (author_id = 9) with:
        // - time_slot = 1 (Morning 07:00-12:00)
        // - zone assignment based on location

        DB::table('bc_tours')->where('author_id', 9)->update([
            'time_slot' => 1, // Morning (07:00-12:00)
        ]);

        // Assign zones based on geographic location
        $zoneMapping = [
            'Arusha' => 1,
            'Moshi' => 1,
            'West Kilimanjaro' => 1,
            'Mount Kilimanjaro' => 1,
            'Tarangire National Park' => 2,
            'Lake Manyara National Park' => 2,
            'Lake Eyasi' => 2,
            'Serengeti National Park' => 3,
            'Ngorongoro' => 3,
            'Lake Natron' => 3,
            'Lake Victoria' => 4,
            'Mkomazi National Park' => 5,
            'Lushoto (Usambara)' => 5,
            'Pangani' => 6,
            'Saadani National Park' => 6,
            'Dar es Salaam' => 6,
        ];

        foreach ($zoneMapping as $address => $zone) {
            DB::table('bc_tours')
                ->where('author_id', 9)
                ->where('address', $address)
                ->update(['zone' => $zone]);
        }

        echo "Updated all Dare2Travel activities:\n";
        echo "  - time_slot = 1 (Morning)\n";
        echo "  - zone assignments by location\n";
    }

    public function down(): void
    {
        DB::table('bc_tours')
            ->where('author_id', 9)
            ->update(['time_slot' => 0, 'zone' => null]);
    }
};
