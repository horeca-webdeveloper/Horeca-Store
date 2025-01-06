<?php

namespace App\Http\Controllers\API;

use Botble\SimpleSlider\Http\Requests\SimpleSliderItemRequest;
use Botble\SimpleSlider\Models\SimpleSliderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SimpleSliderItemController extends Controller
{
    public function store(SimpleSliderItemRequest $request)
    {
        $item = SimpleSliderItem::create($request->all());
        return response()->json($item, 201);
    }
}
