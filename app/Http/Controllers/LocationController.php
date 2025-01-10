<?php

namespace App\Http\Controllers;

use App\Services\GeoLocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $geoLocationService;

    public function __construct(GeoLocationService $geoLocationService)
    {
        $this->geoLocationService = $geoLocationService;
    }

    public function getLocation(Request $request, $ip = null)
    {
        // Use provided IP or fallback to client's IP
        $ip = $ip ?? $request->ip();

        // If we're on localhost, use a public IP for testing
        if ($ip == '127.0.0.1' || $ip == '::1') {
            $ip = '8.8.8.8'; // Example IP (Google's DNS server) for testing
        }

        $locationData = $this->geoLocationService->getLocation($ip);

        return response()->json($locationData);
    }
}
