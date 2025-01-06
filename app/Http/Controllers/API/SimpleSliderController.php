<?php

namespace App\Http\Controllers\API;

use Botble\SimpleSlider\Http\Requests\SimpleSliderRequest;
use Botble\SimpleSlider\Models\SimpleSlider;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class SimpleSliderController extends Controller
{
    public function index()
    {
        // Eager load slider items
        $sliders = SimpleSlider::with('sliderItems')->get();
        return response()->json($sliders);
    }

    public function store(SimpleSliderRequest $request)
    {
        $slider = SimpleSlider::create($request->all());
        return response()->json($slider, 201);
    }

    public function show($id)
    {
        // Eager load slider items
        $slider = SimpleSlider::with('sliderItems')->findOrFail($id);
        return response()->json($slider);
    }

    public function update(SimpleSliderRequest $request, $id)
    {
        $slider = SimpleSlider::findOrFail($id);
        $slider->update($request->all());
        return response()->json($slider);
    }

    public function destroy($id)
    {
        SimpleSlider::destroy($id);
        return response()->json(null, 204);
    }
}
