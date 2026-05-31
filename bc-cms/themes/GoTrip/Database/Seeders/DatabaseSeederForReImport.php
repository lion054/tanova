<?php

namespace Themes\GoTrip\Database\Seeders;

use Database\Seeders\BoatSeeder;
use Database\Seeders\FlightSeeder;
use Database\Seeders\MediaFileSeeder;
use Database\Seeders\CarSeeder;
use Database\Seeders\SocialSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Visa\Database\Seeders\VisaSeeder;

class DatabaseSeederForReImport extends Seeder
{
    public function run()
    {

        Artisan::call('cache:clear');
        $this->call(MediaFileSeeder::class);
        $this->call(General::class);
        $this->call(\Themes\GoTrip\Database\Seeders\News::class);
        $this->call(\Themes\GoTrip\Database\Seeders\Tour::class);
        $this->call(\Themes\GoTrip\Database\Seeders\SpaceSeeder::class);
        $this->call(\Themes\GoTrip\Database\Seeders\HotelSeeder::class);
        $this->call(CarSeeder::class);
        $this->call(\Themes\GoTrip\Database\Seeders\EventSeeder::class);
        $this->call(SocialSeeder::class);
        $this->call(FlightSeeder::class);
        $this->call(BoatSeeder::class);
        $this->call(VisaSeeder::class);

        $this->call(\Themes\GoTrip\Database\Seeders\CarSeeder::class);
    }
}
