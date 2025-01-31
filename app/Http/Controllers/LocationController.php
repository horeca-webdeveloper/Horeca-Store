<?php

namespace App\Http\Controllers;

use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http; // HTTP client
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


    public function getCoordinates(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
        ]);

        $address = urlencode($request->address);
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

        $client = new Client();
        $response = $client->get($url);
        $data = json_decode($response->getBody(), true);

        if (!empty($data['results'])) {
            return response()->json([
                'location' => $data['results'][0]['geometry']['location']
            ]);
        }

        return response()->json(['message' => 'Location not found'], 404);
    }

    public function getRealTimeLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
    
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $apiKey = env('GOOGLE_MAPS_API_KEY');
    
        // URL for Google Maps Geocoding API
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
    
        // Use Guzzle to fetch the data from Google Maps API
        $client = new Client();
        $response = $client->get($url);
        $data = json_decode($response->getBody(), true);
    
        \Log::info('Google Maps API Response:', $data);  // Log the response from Google Maps API
    
        // If results are found, return the formatted address
        if (!empty($data['results'])) {
            return response()->json([
                'address' => $data['results'][0]['formatted_address']
            ]);
        }
    
        // Return an error if no address is found
        return response()->json(['message' => 'Address not found'], 404);
    }
    
  // HERE API incoming parameters


    public function getAddress(Request $request)
    {
        // Validate incoming latitude and longitude
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        // Get latitude and longitude from the request
        $latitude = $request->input('lat');
        $longitude = $request->input('lon');

        // Get your HERE API key from .env
        $apiKey = env('HERE_API_KEY');

        // Send request to HERE API's reverse geocoding endpoint
        $response = Http::get("https://geocode.search.hereapi.com/v1/reverse", [
            'apiKey' => $apiKey,
            'at' => "$latitude,$longitude", // Latitude and longitude as a string
            'lang' => 'en', // Optional: specify language for the result
        ]);

        // Check if the response was successful
        if ($response->successful()) {
            $data = $response->json();

            // Check if the response contains address information
            if (isset($data['items']) && count($data['items']) > 0) {
                return response()->json([
                    'status' => 'success',
                    'address' => $data['items'][0]['address'],
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Address not found',
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch data from HERE API',
            ]);
        }
    }
    
}
