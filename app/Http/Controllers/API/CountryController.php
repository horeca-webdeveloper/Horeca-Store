<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    // Get all countries
    public function index()
    {
        $countries = Country::all();
        return response()->json($countries);
    }

    // Get a specific country by ID
    public function show($id)
    {
        $country = Country::find($id);

        if (!$country) {
            return response()->json(['message' => 'Country not found'], 404);
        }

        return response()->json($country);
    }
}
