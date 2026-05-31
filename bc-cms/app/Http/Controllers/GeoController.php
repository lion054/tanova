<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeoController extends Controller
{
    /**
     * Get user's geolocation and nearby services
     */
    public function nearby(Request $request)
    {
        $latitude = $request->query('lat');
        $longitude = $request->query('lng');
        $radius = $request->query('radius', 50); // km
        $type = $request->query('type', 'all'); // tours, hotels, destinations

        if (!$latitude || !$longitude) {
            return response()->json(['error' => 'Coordinates required'], 400);
        }

        $results = [];

        // Search nearby tours
        if ($type === 'all' || $type === 'tours') {
            $tours = $this->nearbyTours($latitude, $longitude, $radius);
            $results['tours'] = $tours;
        }

        // Search nearby hotels
        if ($type === 'all' || $type === 'hotels') {
            $hotels = $this->nearbyHotels($latitude, $longitude, $radius);
            $results['hotels'] = $hotels;
        }

        // Search nearby destinations
        if ($type === 'all' || $type === 'destinations') {
            $destinations = $this->nearbyDestinations($latitude, $longitude, $radius);
            $results['destinations'] = $destinations;
        }

        return response()->json($results);
    }

    /**
     * Find tours within radius
     */
    private function nearbyTours($lat, $lng, $radius)
    {
        return \DB::table('tours')
            ->select('id', 'title', 'slug', 'location_id')
            ->selectRaw("
                ( 6371 * acos(
                    cos(radians({$lat}))
                    * cos(radians(locations.latitude))
                    * cos(radians(locations.longitude) - radians({$lng}))
                    + sin(radians({$lat}))
                    * sin(radians(locations.latitude))
                )) AS distance
            ")
            ->join('locations', 'tours.location_id', '=', 'locations.id')
            ->where('tours.status', 'publish')
            ->havingRaw("distance <= ?", [$radius])
            ->orderBy('distance')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Find hotels within radius
     */
    private function nearbyHotels($lat, $lng, $radius)
    {
        return \DB::table('hotels')
            ->select('id', 'title', 'slug', 'location_id')
            ->selectRaw("
                ( 6371 * acos(
                    cos(radians({$lat}))
                    * cos(radians(locations.latitude))
                    * cos(radians(locations.longitude) - radians({$lng}))
                    + sin(radians({$lat}))
                    * sin(radians(locations.latitude))
                )) AS distance
            ")
            ->join('locations', 'hotels.location_id', '=', 'locations.id')
            ->where('hotels.status', 'publish')
            ->havingRaw("distance <= ?", [$radius])
            ->orderBy('distance')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Find destinations within radius
     */
    private function nearbyDestinations($lat, $lng, $radius)
    {
        return \DB::table('locations')
            ->select('id', 'name', 'slug')
            ->selectRaw("
                ( 6371 * acos(
                    cos(radians({$lat}))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians({$lng}))
                    + sin(radians({$lat}))
                    * sin(radians(latitude))
                )) AS distance
            ")
            ->where('status', 'publish')
            ->havingRaw("distance <= ?", [$radius])
            ->orderBy('distance')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Detect user location from IP
     */
    public function detect(Request $request)
    {
        $ip = $request->ip();
        $geoData = $this->getGeoFromIP($ip);

        return response()->json([
            'ip' => $ip,
            'country' => $geoData['country'] ?? null,
            'city' => $geoData['city'] ?? null,
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
        ]);
    }

    /**
     * Get geolocation from IP (using free API)
     */
    private function getGeoFromIP($ip)
    {
        try {
            $response = \Http::timeout(5)->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                return [
                    'country' => $response['country_code'],
                    'city' => $response['city'],
                    'latitude' => $response['latitude'],
                    'longitude' => $response['longitude'],
                ];
            }
        } catch (\Exception $e) {
            \Log::warning("Geolocation lookup failed for IP {$ip}", ['error' => $e->getMessage()]);
        }

        return [];
    }
}
