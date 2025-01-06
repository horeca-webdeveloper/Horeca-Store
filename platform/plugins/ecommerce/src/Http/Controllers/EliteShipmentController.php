<?php
// File: app/Http/Controllers/EliteShipmentController.php
namespace Botble\Ecommerce\Http\Controllers;
use Kris\LaravelFormBuilder\Facades\FormBuilder; // Import the FormBuilder facade

use Botble\Ecommerce\Models\EliteShipment;
use Botble\Ecommerce\Models\Shipment;
use Botble\Ecommerce\Forms\EliteShipmentForm;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Http;
use Carbon\Carbon;

class EliteShipmentController extends BaseController
{

    public function setup(): void
    {
        $this
            ->setupModel(new EliteShipment()) // Update this to use EliteShipment
            ->contentOnly();
    }

    public function create(Request $request)
    {
        $id = $request->id ?? null;
        $shipment = $id ? Shipment::find($id) : new EliteShipment();
        $form = FormBuilder::create(EliteShipmentForm::class, [
            'method' => 'POST',
            'url' => route('eliteshipment.store'),
            'model' => $shipment, // Pass the model to the form
        ]);

        return view('eliteshipment.create', compact('form'));
    }


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'shipper_name' => 'required|string',
    //         'shipper_address' => 'required|string',
    //         'shipper_area' => 'required|string',
    //         'shipper_city' => 'required|string',
    //         'shipper_telephone' => 'required|string',
    //         'receiver_name' => 'required|string',
    //         'receiver_address' => 'required|string',
    //         'receiver_area' => 'required|string',
    //         'receiver_city' => 'required|string',
    //         'receiver_telephone' => 'required|string',
    //         'receiver_mobile' => 'required|string',
    //         'receiver_email' => 'required|email',
    //         'shipping_reference' => 'required|string',
    //         'orders' => 'required|string',
    //         'item_type' => 'required|string',
    //         'item_description' => 'required|string',
    //         'item_value' => 'required|numeric',
    //         'dangerousGoodsType' => 'nullable|string',
    //         'weight_kg' => 'required|numeric',
    //         'no_of_pieces' => 'required|numeric',
    //         'service_type' => 'required|string',
    //         'cod_value' => 'nullable|numeric',
    //         'service_date' => 'required|date',
    //         'service_time' => 'required|date_format:H:i', // Assuming H:i format for time
    //         'created_by' => 'required|string',
    //         'special' => 'nullable|string',
    //         'order_type' => 'required|string',
    //         'ship_region' => 'required|string',
    //     ]);


    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     // Create a new shipment record
    //     $shipment = new EliteShipment();
    //     $shipment->shipper_name = $request->shipper_name;
    //     $shipment->shipper_address = $request->shipper_address;
    //     $shipment->shipper_area = $request->shipper_area;
    //     $shipment->shipper_city = $request->shipper_city;
    //     $shipment->shipper_telephone = $request->shipper_telephone;
    //     $shipment->receiver_name = $request->receiver_name;
    //     $shipment->receiver_address = $request->receiver_address;
    //     $shipment->receiver_address2 = $request->receiver_address2;
    //     $shipment->receiver_area = $request->receiver_area;
    //     $shipment->receiver_city = $request->receiver_city;
    //     $shipment->receiver_telephone = $request->receiver_telephone;
    //     $shipment->receiver_mobile = $request->receiver_mobile;
    //     $shipment->receiver_email = $request->receiver_email;
    //     $shipment->shipping_reference = $request->shipping_reference;
    //     $shipment->orders = $request->orders;
    //     $shipment->item_type = $request->item_type;
    //     $shipment->item_description = $request->item_description;
    //     $shipment->item_value = $request->item_value;
    //     $shipment->dangerousGoodsType = $request->dangerousGoodsType;
    //     $shipment->weight_kg = $request->weight_kg;
    //     $shipment->no_of_pieces = $request->no_of_pieces;
    //     $shipment->service_type = $request->service_type;
    //     $shipment->cod_value = $request->cod_value;
    //     $shipment->service_date = $request->service_date;
    //     $shipment->service_time = $request->service_time;
    //     $shipment->created_by = $request->created_by;
    //     $shipment->special = $request->special;
    //     $shipment->order_type = $request->order_type;
    //     $shipment->ship_region = $request->ship_region;

    //     // Save the shipment record
    //     $shipment->save();

    //     // Redirect with success message
    //     return redirect()->route('eliteshipment.create')->with('success', 'Shipment created successfully!');
    // }


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'shipper_name' => 'required|string',
    //         'shipper_address' => 'required|string',
    //         'shipper_area' => 'required|string',
    //         'shipper_city' => 'required|string',
    //         'shipper_telephone' => 'required|string',
    //         'receiver_name' => 'required|string',
    //         'receiver_address' => 'required|string',
    //         'receiver_area' => 'required|string',
    //         'receiver_city' => 'required|string',
    //         'receiver_telephone' => 'required|string',
    //         'receiver_mobile' => 'required|string',
    //         'receiver_email' => 'required|email',
    //         'shipping_reference' => 'required|string',
    //         'orders' => 'required|string',
    //         'item_type' => 'required|string',
    //         'item_description' => 'required|string',
    //         'item_value' => 'required|numeric',
    //         'dangerousGoodsType' => 'nullable|string',
    //         'weight_kg' => 'required|numeric',
    //         'no_of_pieces' => 'required|numeric',
    //         'service_type' => 'required|string',
    //         'cod_value' => 'nullable|numeric',
    //         'service_date' => 'required|date',
    //         'service_time' => 'required|date_format:H:i', // Assuming H:i format for time
    //         'created_by' => 'required|string',
    //         'special' => 'nullable|string',
    //         'order_type' => 'required|string',
    //         'ship_region' => 'required|string',
    //     ]);


    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     // Create a new shipment record
    //     $shipment = new EliteShipment();
    //     $shipment->shipper_name = $request->shipper_name;
    //     $shipment->shipper_address = $request->shipper_address;
    //     $shipment->shipper_area = $request->shipper_area;
    //     $shipment->shipper_city = $request->shipper_city;
    //     $shipment->shipper_telephone = $request->shipper_telephone;
    //     $shipment->receiver_name = $request->receiver_name;
    //     $shipment->receiver_address = $request->receiver_address;
    //     $shipment->receiver_address2 = $request->receiver_address2;
    //     $shipment->receiver_area = $request->receiver_area;
    //     $shipment->receiver_city = $request->receiver_city;
    //     $shipment->receiver_telephone = $request->receiver_telephone;
    //     $shipment->receiver_mobile = $request->receiver_mobile;
    //     $shipment->receiver_email = $request->receiver_email;
    //     $shipment->shipping_reference = $request->shipping_reference;
    //     $shipment->orders = $request->orders;
    //     $shipment->item_type = $request->item_type;
    //     $shipment->item_description = $request->item_description;
    //     $shipment->item_value = $request->item_value;
    //     $shipment->dangerousGoodsType = $request->dangerousGoodsType;
    //     $shipment->weight_kg = $request->weight_kg;
    //     $shipment->no_of_pieces = $request->no_of_pieces;
    //     $shipment->service_type = $request->service_type;
    //     $shipment->cod_value = $request->cod_value;
    //     $shipment->service_date = $request->service_date;
    //     $shipment->service_time = $request->service_time;
    //     $shipment->created_by = $request->created_by;
    //     $shipment->special = $request->special;
    //     $shipment->order_type = $request->order_type;
    //     $shipment->ship_region = $request->ship_region;

    //     // Save the shipment record
    //     $shipment->save();



    //     // Define the shipment details
    //     $orderData = [
    //         "order_id" => "ORD123456",
    //         "customer_name" => "Jane Doe",
    //         "customer_email" => "janedoe@mail.com",
    //         "customer_phone" => "+1 (555) 123-4567",
    //         "order_date" => "2024-11-14",
    //         "shipping_address" => "123 Elm St, Springfield, IL, 62701",
    //         "shipping_method" => "Standard Shipping",
    //         "payment_method" => "Credit Card",
    //         "payment_status" => "Paid",
    //         "order_status" => "Processing",
    //         "items" =>'X',
    //         "subtotal" => 209.97,
    //         "tax" => 15.74,
    //         "shipping_cost" => 5.99,
    //         "order_total" => 231.70,
    //         "discount_code" => "SAVE10",
    //         "discount_amount" => 10.00,
    //         "final_amount" => 221.70,
    //         "tracking_number" => "1Z12345E0205271688",
    //         "estimated_delivery" => "2024-11-18",
    //         "created_at" => "2024-11-14 08:45:00",
    //         "updated_at" => "2024-11-14 09:30:00"
    //     ];


    //     // Retrieve authentication details from .env
    //     $authDetails = [
    //         'Username' => env('ELITE_API_USERNAME'),
    //         'Password' => env('ELITE_API_PASSWORD')
    //     ];

    //     // Combine shipment data with authentication details
    //     $postFields = array_merge($authDetails, $orderData);

    //     // Send the POST request using Laravel's Http facade
    //     $response = Http::asForm()->post('https://www.elite-co.com/api/GlobalAWB.php', $postFields);
    //     // $shipmentUpdate = EliteShipment::find($shipment->id);
    //     // $shipmentUpdate->AWB = $responseAWB;

    //     // $shipmentUpdate->update();
    //     // Handle the response
    //   // Handle the response
    //     if ($response->successful()) {
    //         return response()->json(['message' => 'Shipment sent successfully', 'data' => $response->json()]);
    //     } else {
    //         return response()->json(['error' => 'Failed to send shipment', 'details' => $response->body()], $response->status());
    //     }
    // }


//   public function store(Request $request)
// {
//     // Validate the incoming request data
//     \Log::info('Validation started.');
//     $validator = Validator::make($request->all(), [
//         'shipper_name' => 'required|string',
//         'shipper_address' => 'required|string',
//         'shipper_area' => 'required|string',
//         'shipper_city' => 'required|string',
//         'shipper_telephone' => 'required|string',
//         'receiver_name' => 'required|string',
//         'receiver_address' => 'required|string',
//         'receiver_area' => 'required|string',
//         'receiver_city' => 'required|string',
//         'receiver_telephone' => 'required|string',
//         'receiver_mobile' => 'required|string',
//         'receiver_email' => 'required|email',
//         'shipping_reference' => 'required|string',
//         'orders' => 'required|string',
//         'item_type' => 'required|string',
//         'item_description' => 'required|string',
//         'item_value' => 'required|numeric',
//         'dangerousGoodsType' => 'nullable|string',
//         'weight_kg' => 'required|numeric',
//         'no_of_pieces' => 'required|numeric',
//         'service_type' => 'required|string',
//         'cod_value' => 'nullable|numeric',
//         'service_date' => 'required|date',
//         'service_time' => 'required|date_format:H:i', // Assuming H:i format for time
//         'created_by' => 'required|string',
//         'special' => 'nullable|string',
//         'order_type' => 'required|string',
//         'ship_region' => 'required|string',
//     ]);

//     if ($validator->fails()) {
//         \Log::warning('Validation failed.', $validator->errors()->toArray());
//         return redirect()->back()->withErrors($validator)->withInput();
//     }
//     \Log::info('Validation passed.');

//     // Create a new shipment record
//     \Log::info('Creating new shipment record.');
//     $shipment = new EliteShipment();
//     $shipment->shipper_name = $request->shipper_name;
//     $shipment->shipper_address = $request->shipper_address;
//     $shipment->shipper_area = $request->shipper_area;
//     $shipment->shipper_city = $request->shipper_city;
//     $shipment->shipper_telephone = $request->shipper_telephone;
//     $shipment->receiver_name = $request->receiver_name;
//     $shipment->receiver_address = $request->receiver_address;
//     $shipment->receiver_address2 = $request->receiver_address2;
//     $shipment->receiver_area = $request->receiver_area;
//     $shipment->receiver_city = $request->receiver_city;
//     $shipment->receiver_telephone = $request->receiver_telephone;
//     $shipment->receiver_mobile = $request->receiver_mobile;
//     $shipment->receiver_email = $request->receiver_email;
//     $shipment->shipping_reference = $request->shipping_reference;
//     $shipment->orders = $request->orders;
//     $shipment->item_type = $request->item_type;
//     $shipment->item_description = $request->item_description;
//     $shipment->item_value = $request->item_value;
//     $shipment->dangerousGoodsType = $request->dangerousGoodsType;
//     $shipment->weight_kg = $request->weight_kg;
//     $shipment->no_of_pieces = $request->no_of_pieces;
//     $shipment->service_type = $request->service_type;
//     $shipment->cod_value = $request->cod_value;
//     $shipment->service_date = $request->service_date;
//     $shipment->service_time = $request->service_time;
//     $shipment->created_by = $request->created_by;
//     $shipment->special = $request->special;
//     $shipment->order_type = $request->order_type;
//     $shipment->ship_region = $request->ship_region;

//     \Log::info('Shipment record created.');

//     // Define authentication details and order data for the API request
//     $authDetails = [
//         'Username' => env('ELITE_API_USERNAME'),
//         'Password' => env('ELITE_API_PASSWORD')
//     ];

//     $orderData = [
//         'Username' => '1112190',
//         'Password' => 'W%i1aI4gz)b0Wh',
//         'shipper_name' => 'balas',
//         'shipper_address' => 'streeteast',
//         'shipper_area' => 'Alquoz',
//         'shipper_city' => 'Dubai',
//         'shipper_telephone' => '052205007',
//         'Receiver_name' => 'Mr.Alex',
//         'Receiver_address' => 'Street55',
//         'Receiver_address2' => 'IndArea1',
//         'Receiver_Area' => 'alquoz',
//         'Receiver_city' => 'DUBAI',
//         'Receiver_telephone' => '042205007',
//         'Receiver_mobile' => '0500000005',
//         'Receiver_email' => 'aa@aa.com',
//         'shipping_reference' => '63458724',
//         'orders' => '1345678,1231',
//         'item_type' => 'X',
//         'item_description' => 'HD',
//         'item_value' => 20,
//         'dangerousGoodsType' => 'ID8000',
//         'weight_kg' => 20,
//         'no_of_pieces' => 2,
//         'service_type' => 'N',
//         'COD_value' => 50.75,
//         'service_date' => '2024-11-14',
//         'Service_time' => '10:00-18:00',
//         'created_by' => 'IT',
//         'Special' => 'Del',
//         'Order_Type' => 'D',
//         'ship_region' => 'AE',



//     ];



//     \Log::info('Prepared order data for API request.');

//     // Combine authentication details with order data
//     $postFields = array_merge($authDetails, $orderData);

//     // Send the POST request to the API
//     \Log::info('Sending API request to Elite.');
//     $response = Http::asForm()->post('https://www.elite-co.com/api/GlobalAWB.php', $postFields);

//     // Check if the API call was successful
//     if ($response->successful()) {
//         \Log::info('API request successful.');

//         // Log the raw response body for debugging
//         $rawResponse = trim($response->body()); // Remove any unwanted characters
//         \Log::info('Raw API response: ' . $rawResponse);

//         // Try to parse the XML response
//         $xml = simplexml_load_string($rawResponse);

//         if ($xml === false) {
//             \Log::error('Failed to parse XML response.');
//             return response()->json([
//                 'error' => 'Failed to parse XML response.',
//                 'details' => $rawResponse // Log raw response for inspection
//             ]);
//         }

//         \Log::info('XML parsed successfully.');

//         // Extract AWB and other details from the parsed XML
//         if (isset($xml->item0)) {
//             $awb = (string) $xml->item0->awb;
//             $trackingUrl = (string) $xml->item0->awb_track;
//             $awbLabelUrl = (string) $xml->item0->awb_label;

//             // Store the AWB in the shipment record
//             $shipment->awb = $awb;
//             $shipment->tracking_url = $trackingUrl;
//             $shipment->awb_label_url = $awbLabelUrl;
//             $shipment->save(); // Save the shipment with the AWB

//             \Log::info('AWB saved in the shipment record: ' . $awb);

//             // Return a success message with the AWB and other details
//             return response()->json([
//                 'message' => 'Shipment sent successfully',
//                 'data' => [
//                     'awb' => $awb,
//                     'tracking_url' => $trackingUrl,
//                     'awb_label_url' => $awbLabelUrl
//                 ]
//             ]);
//         } else {
//             \Log::warning('AWB data not found in the response.');
//             return response()->json([
//                 'error' => 'AWB data not found in the response.',
//                 'details' => $rawResponse // Log raw response for inspection
//             ]);
//         }
//     } else {
//         \Log::error('API request failed.', [
//             'status' => $response->status(),
//             'response' => $response->body()
//         ]);
//         return response()->json([
//             'error' => 'Failed to send shipment',
//             'details' => $response->body()
//         ], $response->status());
//     }
// }


public function store(Request $request)
{
    // Validate the incoming request data shipment_id
    \Log::info('Validation started.');
    $validator = Validator::make($request->all(), [
        'shipper_name' => 'required|string',
        'shipper_address' => 'required|string',
        'shipper_area' => 'required|string',
        'shipper_city' => 'required|string',
        'shipper_telephone' => 'required|string',
        'receiver_name' => 'required|string',
        'receiver_address' => 'required|string',
        'receiver_area' => 'required|string',
        'receiver_city' => 'required|string',
        'receiver_telephone' => 'required|string',
        'receiver_mobile' => 'required|string',
        'receiver_email' => 'required|email',
        'shipping_reference' => 'required|string',
        'orders' => 'required|string',
        'item_type' => 'required|string',
        'item_description' => 'required|string',
        'item_value' => 'required|numeric',
        'dangerousGoodsType' => 'required|string',
        'weight_kg' => 'required|numeric',
        'no_of_pieces' => 'required|numeric',
        'service_type' => 'required|string',
        'cod_value' => 'nullable|numeric',
        'service_date' => 'required|date',
        // 'service_time' => 'required|date_format:H:i',
        'service_time' => 'required|string',
        'created_by' => 'required|string',
        'special' => 'nullable|string',
        'order_type' => 'required|string',
        'ship_region' => 'required|string',
    ]);

    if ($validator->fails()) {
        \Log::warning('Validation failed.', $validator->errors()->toArray());
        return redirect()->back()->withErrors($validator)->withInput();
    }
    \Log::info('Validation passed.');

    // Create a new shipment record
    \Log::info('Creating new shipment record.');
    $shipment = new EliteShipment();
    $shipment->shipper_name = $request->shipper_name;
    $shipment->shipper_address = $request->shipper_address;
    $shipment->shipper_area = $request->shipper_area;
    $shipment->shipper_city = $request->shipper_city;
    $shipment->shipper_telephone = $request->shipper_telephone;
    $shipment->receiver_name = $request->receiver_name;
    $shipment->receiver_address = $request->receiver_address;
    $shipment->receiver_address2 = $request->receiver_address2;
    $shipment->receiver_area = $request->receiver_area;
    $shipment->receiver_city = $request->receiver_city;
    $shipment->receiver_telephone = $request->receiver_telephone;
    $shipment->receiver_mobile = $request->receiver_mobile;
    $shipment->receiver_email = $request->receiver_email;
    $shipment->shipping_reference = $request->shipping_reference;
    $shipment->orders = $request->orders;
    $shipment->item_type = $request->item_type;
    $shipment->item_description = $request->item_description;
    $shipment->item_value = $request->item_value;
    $shipment->dangerousGoodsType = $request->dangerousGoodsType;
    $shipment->weight_kg = $request->weight_kg;
    $shipment->no_of_pieces = $request->no_of_pieces;
    $shipment->service_type = $request->service_type;
    $shipment->cod_value = $request->cod_value;
    $shipment->service_date = $request->service_date;
    $shipment->service_time = $request->service_time;
    $shipment->created_by = $request->created_by;
    $shipment->special = $request->special;
    $shipment->order_type = $request->order_type;
    $shipment->ship_region = $request->ship_region;

    \Log::info('Shipment record created.');



    // Define authentication details and order data for the API request
    $authDetails = [
        'Username' => env('ELITE_API_USERNAME'),
        'Password' => env('ELITE_API_PASSWORD')
    ];
// Service Date Parsing
$serviceDate = isset($request->service_date) && !empty($request->service_date)
    ? Carbon::parse(trim($request->service_date))->format('Y-m-d')
    : null;

// Service Time Parsing
$serviceTime = isset($request->service_time) && !empty($request->service_time)
    ? Carbon::parse(trim($request->service_time))->format('H:i')
    : null;

// Log the date and time for debugging purposes
\Log::info("Service Date: " . $serviceDate);
\Log::info("Service Time: " . $serviceTime);
    $orderData = [
        'Username' => 1112190,
        'Password' => 'W%i1aI4gz)b0Wh',
        'shipper_name' => $request->shipper_name,
        'shipper_address' => $request->shipper_address,
        'shipper_area' => $request->shipper_area,
        'shipper_city' => $request->shipper_city,
        'shipper_telephone' => $request->shipper_telephone,
        'Receiver_name' => $request->receiver_name,
        'Receiver_address' => $request->receiver_address,
        'Receiver_address2' => $request->receiver_address2,
        'Receiver_Area' => $request->receiver_area,
        'Receiver_city' => $request->receiver_city,
        'Receiver_telephone' => $request->receiver_telephone,
        'Receiver_mobile' => $request->receiver_mobile,
        'Receiver_email' => $request->receiver_email,
        'shipping_reference' => $request->shipping_reference,
        'orders' => $request->orders,
        'item_type' => $request->item_type,
        'item_description' => $request->item_description,
        'item_value' => $request->item_value,
        'dangerousGoodsType' => 'ID8000',
        'weight_kg' => $request->weight_kg,
        'no_of_pieces' => $request->no_of_pieces,
        'service_type' => $request->service_type,
        'COD_value' => $request->cod_value,
        'service_date' => $serviceDate,
         'Service_time' => $serviceTime,
        'created_by' => $request->created_by,
        'Special' => $request->special,
        'Order_Type' => $request->order_type,
        'ship_region' => $request->ship_region,
    ];
\Log::info('Service Date:', ['service_date' => $request->service_date]);

    \Log::info('Prepared order data for API request.');

    // Combine authentication details with order data
    $postFields = array_merge($authDetails, $orderData);

    // Send the POST request to the API
    \Log::info('Sending API request to Elite.');
    $response = Http::asForm()->post('https://www.elite-co.com/api/GlobalAWB.php', $postFields);

    // Check if the API call was successful
    if ($response->successful()) {
        \Log::info('API request successful.');

        // Log the raw response body for debugging
        $rawResponse = trim($response->body()); // Remove any unwanted characters
        \Log::info('Raw API response: ' . $rawResponse);

        // Try to parse the XML response
        $xml = simplexml_load_string($rawResponse);

        if ($xml === false) {
            \Log::error('Failed to parse XML response.');
            return response()->json([
                'error' => 'Failed to parse XML response.',
                'details' => $rawResponse // Log raw response for inspection
            ]);
        }

        \Log::info('XML parsed successfully.');

        // Extract AWB and other details from the parsed XML
        if (isset($xml->item0)) {
            $awb = (string) $xml->item0->awb;
            $trackingUrl = (string) $xml->item0->awb_track;
            $awbLabelUrl = (string) $xml->item0->awb_label;

            // Store the AWB in the shipment record
            $shipment->awb = $awb;
            $shipment->tracking_url = $trackingUrl;
            $shipment->awb_label_url = $awbLabelUrl;
            $shipment->shipment_id = $request->shipment_id;
            $shipment->save(); // Save the shipment with the AWB

            \Log::info('AWB saved in the shipment record: ' . $awb);

            // Return a success message with the AWB and other details
            if ($request->shipment_id) {
                session()->put('success', 'Shipment sent successfully.');
                return redirect()
                ->to('admin/ecommerce/shipments/edit/' . $request->shipment_id);

            }
            return response()->json([
                'message' => 'Shipment sent successfully',
                'data' => [
                    'awb' => $awb,
                    'tracking_url' => $trackingUrl,
                    'awb_label_url' => $awbLabelUrl
                ]
            ]);
        } else {
            \Log::warning('AWB data not found in the response.');
            return response()->json([
                'error' => 'AWB data not found in the response.',
                'details' => $rawResponse // Log raw response for inspection
            ]);
        }
    } else {
        \Log::error('API request failed.', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        return response()->json([
            'error' => 'Failed to send shipment',
            'details' => $response->body()
        ], $response->status());
    }
}


}
