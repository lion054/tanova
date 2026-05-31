<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    /**
     * Generate main sitemap index
     */
    public function index()
    {
        $sitemaps = [
            'tours' => route('sitemap.tours'),
            'hotels' => route('sitemap.hotels'),
            'destinations' => route('sitemap.destinations'),
        ];

        $xml = view('sitemap.index', ['sitemaps' => $sitemaps])->render();
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate tours sitemap
     */
    public function tours()
    {
        $tours = \DB::table('bc_tours')
            ->where('status', '=', 'publish')
            ->select('id', 'updated_at', 'slug')
            ->orderBy('updated_at', 'desc')
            ->limit(50000)
            ->get();

        $xml = view('sitemap.tours', ['tours' => $tours])->render();
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate hotels sitemap
     */
    public function hotels()
    {
        $hotels = \DB::table('bc_hotels')
            ->where('status', '=', 'publish')
            ->select('id', 'updated_at', 'slug')
            ->orderBy('updated_at', 'desc')
            ->limit(50000)
            ->get();

        $xml = view('sitemap.hotels', ['hotels' => $hotels])->render();
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate destinations sitemap
     */
    public function destinations()
    {
        $destinations = \DB::table('locations')
            ->where('status', '=', 'publish')
            ->select('id', 'updated_at', 'slug')
            ->orderBy('updated_at', 'desc')
            ->limit(50000)
            ->get();

        $xml = view('sitemap.destinations', ['destinations' => $destinations])->render();
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
