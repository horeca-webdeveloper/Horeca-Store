<?php

namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Botble\Base\Models\BaseModel;

class EliteShipment extends BaseModel
{
    use HasFactory;

    protected $table = 'eliteshipment';

    protected $fillable = [
        'shipment_id', 'shipper_name', 'shipper_address', 'shipper_area', 'shipper_city', 'shipper_telephone',
        'receiver_name', 'receiver_address', 'receiver_address2', 'receiver_area', 'receiver_city',
        'receiver_telephone', 'receiver_mobile', 'receiver_email', 'shipping_reference', 'orders',
        'item_type', 'item_description', 'item_value', 'dangerousGoodsType', 'weight_kg',
        'no_of_pieces', 'service_type', 'cod_value', 'service_date', 'service_time', 'created_by',
        'special', 'order_type', 'ship_region', 'AWB', 'tracking_url' ,'awb_label_url', 'status'
    ];
}
