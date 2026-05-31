<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fills in offered_by, includes, and image on the manually-seeded
 * bc_tanova_accommodations rows from migration 000004.
 * Keyed by (location_id, name) — same unique pair used in 000004.
 */
class FillTanovaMissingFields extends Migration
{
    public function up(): void
    {
        // ── Accommodations ───────────────────────────────────────────────────

        $accos = [
            // Cape Town (location_id=9)
            [9,  'The Silo Hotel',                    'Royal Portfolio',            'Breakfast, Wi-Fi, Rooftop pool, Concierge, Minibar',         'the_silo_cape_town.jpg'],
            [9,  'Taj Cape Town',                     'Taj Hotels & Resorts',       'Wi-Fi, Gym, Room service, Concierge, Valet parking',         'taj_cape_town.jpg'],
            [9,  'Cape Royale Luxury Hotel',          'Cape Royale Hotels',         'Wi-Fi, Rooftop pool, Gym, Secure parking',                   'cape_royale_hotel.jpg'],
            [9,  'POD Camps Bay Boutique Hotel',      'POD Hotels',                 'Wi-Fi, Kitchenette, Beach towels, Complimentary breakfast',  'pod_camps_bay.jpg'],
            [9,  'The Peninsula All Suite Hotel',     'Peninsula Hotels SA',        'Wi-Fi, Full kitchen, Laundry, Pool, Gym',                    'peninsula_suite_cape_town.jpg'],
            [9,  'Hippo Boutique Hotel',              'Hippo Hospitality',          'Breakfast, Wi-Fi, Secure parking',                           'hippo_boutique_hotel.jpg'],

            // Dubai (location_id=10)
            [10, 'Atlantis The Palm',                 'Kerzner International',      'Beach access, Aquaventure Waterpark, Wi-Fi, Multiple pools',  'atlantis_palm_dubai.jpg'],
            [10, 'JW Marriott Marquis Dubai',         'Marriott International',     'Wi-Fi, Rooftop pool, Gym, Spa, Multiple restaurants',        'jw_marriott_marquis_dubai.jpg'],
            [10, 'Rove Downtown Dubai',               'Emaar Hospitality',          'Wi-Fi, Pool, Gym, Complimentary bicycle hire',               'rove_downtown_dubai.jpg'],
            [10, 'La Mer Beach Residences',           'La Mer Properties',          'Private beach, Wi-Fi, Full kitchen, Pool, Beach club access', 'la_mer_beach_dubai.jpg'],
            [10, 'The First Collection Business Bay', 'The First Collection Hotels','Wi-Fi, Rooftop pool, Gym, Complimentary shuttle',            'first_collection_business_bay.jpg'],
            [10, 'Dubai Creek Harbour Residence',     'Emaar Properties',           'Wi-Fi, Full kitchen, Pool, Gym, Parking',                    'dubai_creek_harbour_residence.jpg'],

            // Zanzibar (location_id=11)
            [11, 'Zanzibar Serena Hotel',             'Serena Hotels & Resorts',    'Breakfast, Wi-Fi, Pool, Concierge, Water sports',            'zanzibar_serena_hotel.jpg'],
            [11, 'Baraza Resort and Spa',             'Baraza Resort Zanzibar',     'Full board, Wi-Fi, Private plunge pool, Spa, Beach access',  'baraza_resort_zanzibar.jpg'],
            [11, 'Jafferji House & Spa',              'Jafferji Houses & Spas',     'Breakfast, Wi-Fi, Rooftop pool, Spa treatments',             'jafferji_house_zanzibar.jpg'],
            [11, 'The Zala Zanzibar',                 'The Zala',                   'Wi-Fi, Self-catering kitchen, Garden, Free bicycles',        'the_zala_zanzibar.jpg'],
            [11, 'Essque Zalu Zanzibar',              'Essque Hotels',              'Breakfast, Wi-Fi, Infinity pool, Kayaking, Snorkelling gear', 'essque_zalu_zanzibar.jpg'],
            [11, 'Kisiwa on the Beach',               'Kisiwa Beach Resort',        'Breakfast, Wi-Fi, Beach access, Snorkelling gear',           'kisiwa_beach_zanzibar.jpg'],

            // Singapore (location_id=12)
            [12, 'Raffles Hotel Singapore',           'Raffles Hotels & Resorts',   'Butler service, Wi-Fi, Pool, Concierge, Dining credit',      'raffles_hotel_singapore.jpg'],
            [12, 'The Fullerton Hotel Singapore',     'The Fullerton Hotels',       'Wi-Fi, Pool, Gym, Spa, Breakfast on weekends',               'fullerton_hotel_singapore.jpg'],
            [12, 'PARKROYAL COLLECTION Marina Bay',  'Pan Pacific Hotels Group',   'Breakfast, Wi-Fi, Sky pool, Gym, Garden terrace',            'parkroyal_marina_bay.jpg'],
            [12, 'Amara Sanctuary Resort Sentosa',    'Amara Hotels & Resorts',     'Breakfast, Wi-Fi, Pool, Gym, Beach shuttle',                 'amara_sanctuary_sentosa.jpg'],
            [12, 'Lyf Funan Singapore',              'The Ascott Limited',         'Wi-Fi, Co-working space, Gym, Shared kitchen, Social areas', 'lyf_funan_singapore.jpg'],
            [12, 'Hotel Mono',                        'Hotel Mono Singapore',       'Breakfast, Wi-Fi, Gym, Complimentary minibar',               'hotel_mono_singapore.jpg'],
        ];

        foreach ($accos as [$locationId, $name, $offeredBy, $includes, $image]) {
            DB::table('bc_tanova_accommodations')
                ->where('location_id', $locationId)
                ->where('name', $name)
                ->whereNull('tsokanew_id')
                ->update([
                    'offered_by' => $offeredBy,
                    'includes'   => $includes,
                    'image'      => $image,
                    'updated_at' => now()->toDateTimeString(),
                ]);
        }

    }

    public function down(): void
    {
        DB::table('bc_tanova_accommodations')
            ->whereNull('tsokanew_id')
            ->whereIn('location_id', [9, 10, 11, 12])
            ->update(['offered_by' => null, 'includes' => null, 'image' => null]);
    }
}
