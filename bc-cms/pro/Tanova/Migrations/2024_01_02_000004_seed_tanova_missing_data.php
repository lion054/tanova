<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds curated accommodations for destinations that have thin data in
 * tsokanew. Rows have tsokanew_id = NULL (manually curated) so the import
 * command never overwrites them.
 *
 * Location IDs (bc_locations):
 *   7  = Nyanga / Inyanga
 *   9  = Cape Town
 *   10 = Dubai
 *   11 = Zanzibar
 *   12 = Singapore
 */
class SeedTanovaMissingData extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // ── Accommodations ──────────────────────────────────────────────────

        $accos = [
            // Cape Town (location_id=9)
            ['location_id' => 9, 'name' => 'The Silo Hotel',                   'stay_type' => 'room',      'cost_per_night' => 280.00, 'description' => 'Six-star luxury atop the V&A Waterfront grain silo with panoramic harbour views.', 'address' => 'Silo Square, V&A Waterfront, Cape Town'],
            ['location_id' => 9, 'name' => 'Taj Cape Town',                    'stay_type' => 'room',      'cost_per_night' => 180.00, 'description' => 'Heritage hotel blending colonial architecture with modern luxury in the CBD.', 'address' => 'Wale Street, Cape Town CBD'],
            ['location_id' => 9, 'name' => 'Cape Royale Luxury Hotel',         'stay_type' => 'room',      'cost_per_night' => 120.00, 'description' => 'All-suite hotel in Green Point, walking distance from V&A Waterfront.', 'address' => 'Green Point, Cape Town'],
            ['location_id' => 9, 'name' => 'POD Camps Bay Boutique Hotel',     'stay_type' => 'apartment', 'cost_per_night' =>  90.00, 'description' => 'Sleek self-catering suites with Atlantic Ocean views in trendy Camps Bay.', 'address' => 'Victoria Road, Camps Bay, Cape Town'],
            ['location_id' => 9, 'name' => 'The Peninsula All Suite Hotel',    'stay_type' => 'apartment', 'cost_per_night' => 140.00, 'description' => 'Spacious self-catering suites overlooking Sea Point promenade.', 'address' => 'Sea Point, Cape Town'],
            ['location_id' => 9, 'name' => 'Hippo Boutique Hotel',             'stay_type' => 'room',      'cost_per_night' =>  65.00, 'description' => 'Affordable boutique hotel in De Waterkant with colourful Victorian style.', 'address' => 'De Waterkant, Cape Town'],

            // Dubai (location_id=10)
            ['location_id' => 10, 'name' => 'Atlantis The Palm',               'stay_type' => 'room',      'cost_per_night' => 350.00, 'description' => 'Iconic resort on Palm Jumeirah with private beach, waterpark, and marine habitat.', 'address' => 'Crescent Road, Palm Jumeirah, Dubai'],
            ['location_id' => 10, 'name' => 'JW Marriott Marquis Dubai',       'stay_type' => 'room',      'cost_per_night' => 200.00, 'description' => 'World\'s tallest hotel offering panoramic city views and multiple restaurants.', 'address' => 'Sheikh Zayed Road, Business Bay, Dubai'],
            ['location_id' => 10, 'name' => 'Rove Downtown Dubai',             'stay_type' => 'room',      'cost_per_night' =>  80.00, 'description' => 'Modern, design-led hotel steps from Dubai Mall and Burj Khalifa.', 'address' => 'Financial Centre Road, Downtown Dubai'],
            ['location_id' => 10, 'name' => 'La Mer Beach Residences',         'stay_type' => 'apartment', 'cost_per_night' => 120.00, 'description' => 'Beachfront serviced apartments with private beach access on Jumeirah coastline.', 'address' => 'La Mer, Jumeirah 1, Dubai'],
            ['location_id' => 10, 'name' => 'The First Collection Business Bay','stay_type' => 'room',      'cost_per_night' => 100.00, 'description' => 'Stylish city hotel with rooftop pool and Dubai Canal views.', 'address' => 'Business Bay, Dubai'],
            ['location_id' => 10, 'name' => 'Dubai Creek Harbour Residence',   'stay_type' => 'apartment', 'cost_per_night' => 150.00, 'description' => 'Modern apartments with Burj Khalifa views in the new Dubai Creek Harbour.', 'address' => 'Dubai Creek Harbour, Dubai'],

            // Zanzibar (location_id=11)
            ['location_id' => 11, 'name' => 'Zanzibar Serena Hotel',           'stay_type' => 'room',      'cost_per_night' => 120.00, 'description' => 'Colonial-era hotel in Stone Town with ocean-facing terrace and swahili heritage.', 'address' => 'Kelele Square, Stone Town, Zanzibar'],
            ['location_id' => 11, 'name' => 'Baraza Resort and Spa',           'stay_type' => 'room',      'cost_per_night' => 250.00, 'description' => 'Luxury beachfront villas with private pool on Bwejuu beach, east coast Zanzibar.', 'address' => 'Bwejuu Beach, Zanzibar'],
            ['location_id' => 11, 'name' => 'Jafferji House & Spa',            'stay_type' => 'room',      'cost_per_night' =>  90.00, 'description' => 'Intimate boutique riad in the heart of Stone Town with rooftop views.', 'address' => 'Stone Town, Zanzibar'],
            ['location_id' => 11, 'name' => 'The Zala Zanzibar',               'stay_type' => 'apartment', 'cost_per_night' =>  75.00, 'description' => 'Garden bungalows with self-catering facilities in quiet Matemwe, north Zanzibar.', 'address' => 'Matemwe Beach, Zanzibar'],
            ['location_id' => 11, 'name' => 'Essque Zalu Zanzibar',            'stay_type' => 'room',      'cost_per_night' => 180.00, 'description' => 'Boutique villa resort on Nungwi lagoon with sunset infinity pool.', 'address' => 'Nungwi, Zanzibar'],
            ['location_id' => 11, 'name' => 'Kisiwa on the Beach',             'stay_type' => 'room',      'cost_per_night' =>  60.00, 'description' => 'Budget-friendly beachfront bandas with Swahili charm on Paje beach.', 'address' => 'Paje Beach, Zanzibar'],

            // Singapore (location_id=12)
            ['location_id' => 12, 'name' => 'Raffles Hotel Singapore',         'stay_type' => 'room',      'cost_per_night' => 500.00, 'description' => 'Legendary colonial grand dame hotel, home of the Singapore Sling, reopened after full restoration.', 'address' => '1 Beach Road, City Hall, Singapore'],
            ['location_id' => 12, 'name' => 'The Fullerton Hotel Singapore',   'stay_type' => 'room',      'cost_per_night' => 250.00, 'description' => 'Heritage landmark in a 1928 Palladian building at the mouth of the Singapore River.', 'address' => '1 Fullerton Square, Singapore'],
            ['location_id' => 12, 'name' => 'PARKROYAL COLLECTION Marina Bay', 'stay_type' => 'room',      'cost_per_night' => 180.00, 'description' => 'Garden-in-the-sky hotel with sky pools and rainforest terraces overlooking Marina Bay.', 'address' => '6 Raffles Boulevard, Marina Bay, Singapore'],
            ['location_id' => 12, 'name' => 'Amara Sanctuary Resort Sentosa',  'stay_type' => 'room',      'cost_per_night' => 130.00, 'description' => 'Colonial bungalow resort on Sentosa island surrounded by lush tropical gardens.', 'address' => '1 Lovedale Road, Sentosa, Singapore'],
            ['location_id' => 12, 'name' => 'Lyf Funan Singapore',             'stay_type' => 'apartment', 'cost_per_night' =>  85.00, 'description' => 'Social co-living hotel in the heart of the Civic District with co-working spaces.', 'address' => '67 Hill Street, City Hall, Singapore'],
            ['location_id' => 12, 'name' => 'Hotel Mono',                      'stay_type' => 'room',      'cost_per_night' =>  70.00, 'description' => 'Minimalist boutique hotel in Chinatown with striking black-and-white design.', 'address' => '18 Mosque Street, Chinatown, Singapore'],
        ];

        foreach ($accos as $a) {
            $exists = DB::table('bc_tanova_accommodations')
                ->where('location_id', $a['location_id'])
                ->where('name', $a['name'])
                ->exists();

            if (!$exists) {
                DB::table('bc_tanova_accommodations')->insert([
                    'location_id'    => $a['location_id'],
                    'tsokanew_id'    => null,
                    'name'           => $a['name'],
                    'stay_type'      => $a['stay_type'],
                    'description'    => $a['description'],
                    'address'        => $a['address'],
                    'cost_per_night' => $a['cost_per_night'],
                    'status'         => 'publish',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

    }

    public function down(): void
    {
        DB::table('bc_tanova_accommodations')
            ->whereNull('tsokanew_id')
            ->whereIn('location_id', [9, 10, 11, 12])
            ->delete();
    }
}
