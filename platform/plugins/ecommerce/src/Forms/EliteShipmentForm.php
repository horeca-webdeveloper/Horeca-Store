<?php


namespace Botble\Ecommerce\Forms;

use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Models\EliteShipment;
use Botble\Ecommerce\Forms\Concerns\HasSubmitButton;

class EliteShipmentForm extends FormAbstract
{
	use HasSubmitButton;

	public function setup(): void
	{
		$this
		->setupModel($this->getModel() ?? new EliteShipment())
		->contentOnly()

		->add('shipment_id', 'hidden', [
			'value' => $this->getModel()->id ?? '',
		])
		->add('shipper_name', 'text', [
			'label' => 'Shipper Name:',
			'attr' => [
				'placeholder' => 'Enter shipper name',
				'required' => true,
			],
		])
		->add('shipper_address', 'text', [
			'label' => 'Shipper Address:',
			'attr' => [
				'placeholder' => 'Enter shipper address',
				'required' => true,
			],
		])
		->add('shipper_area', 'text', [
			'label' => 'Shipper Area:',
			'attr' => [
				'placeholder' => 'Enter shipper area',
				'required' => true,
			],
		])
		->add('shipper_city', 'text', [
			'label' => 'Shipper City:',
			'attr' => [
				'placeholder' => 'Enter shipper city',
				'required' => true,
			],
		])
		->add('shipper_telephone', 'text', [
			'label' => 'Shipper Telephone:',
			'attr' => [
				'placeholder' => 'Enter shipper telephone',
				'required' => true,
			],
		])
		->add('receiver_name', 'text', [
			'label' => 'Receiver Name:',
			'attr' => [
				'placeholder' => 'Enter receiver name',
				'required' => true,
			],
			'value' => $this->getModel()->order->shippingAddress->name,
		])
		->add('receiver_address', 'text', [
			'label' => 'Receiver Address:',
			'attr' => [
				'placeholder' => 'Enter receiver address',
				'required' => true,
			],
			'value' => $this->getModel()->order->shippingAddress->address,
		])
		->add('receiver_address2', 'text', [
			'label' => 'Receiver Address 2:',
			'attr' => [
				'placeholder' => 'Enter additional receiver address (optional)',
			],
			'default_value' => '',
		])
		->add('receiver_area', 'text', [
			'label' => 'Receiver Area:',
			'attr' => [
				'placeholder' => 'Enter receiver area',
				'required' => true,
			],
			'value' => $this->getModel()->order->shippingAddress->state,
		])
		->add('receiver_city', 'text', [
			'label' => 'Receiver City:',
			'attr' => [
				'placeholder' => 'Enter receiver city',
				'required' => true,
			],
			'value' => strtoupper($this->getModel()->order->shippingAddress->city),
		])
		->add('receiver_telephone', 'text', [
			'label' => 'Receiver Telephone:',
			'attr' => [
				'placeholder' => 'Enter receiver telephone',
				'required' => true,
			],
			'value' => $this->getModel()->order->shippingAddress->phone,
		])
		->add('receiver_mobile', 'text', [
			'label' => 'Receiver Mobile:',
			'attr' => [
				'placeholder' => 'Enter receiver mobile',
				'required' => true,
			],
			'value' => $this->getModel()->order->shippingAddress->phone,
		])
		->add('receiver_email', 'email', [
			'label' => 'Receiver Email:',
			'attr' => [
				'placeholder' => 'Enter receiver email',
				'required' => true,
			],
			'value' => $this->getModel()->order->shippingAddress->email,
		])
		->add('shipping_reference', 'text', [
			'label' => 'Shipping Reference:',
			'attr' => [
				'placeholder' => 'Enter shipping reference',
				'required' => true,
			],
			'value' => str_pad(mt_rand(1,99999999),8,'0',STR_PAD_LEFT),
		])
		->add('orders', 'text', [
			'label' => 'Orders:',
			'attr' => [
				'placeholder' => 'Enter order details',
				'readonly' => true,
				'required' => true,
			],
			'value' => $this->getModel()->order->id,
		])
		->add('item_type', 'text', [
			'label' => 'Item Type:',
			'attr' => [
				'placeholder' => 'Enter item type',
				'readonly' => true,
				'required' => true,
			],
			'value' => 'X',
		])
		->add('item_description', 'text', [
			'label' => 'Item Description:',
			'attr' => [
				'placeholder' => 'Enter item description',
				'readonly' => true,
				'required' => true,
			],
			'value' => 'HD',
		])
		->add('item_value', 'number', [
			'label' => 'Item Value:',
			'attr' => [
				'placeholder' => 'Enter item value',
				'required' => true,
			],
			'value' => $this->getModel()->order->amount,
		])
		->add('dangerousGoodsType', 'text', [
			'label' => 'Dangerous Goods Type:',
			'attr' => [
				'placeholder' => 'Enter dangerous goods type',
				'required' => true,
			],
		])
		->add('weight_kg', 'number', [
			'label' => 'Weight (kg):',
			'attr' => [
				'placeholder' => 'Enter weight in kg',
				'required' => true,
			],
			'value' => $this->getModel()->order->products->sum('qty'),
		])
		->add('no_of_pieces', 'number', [
			'label' => 'No of Pieces:',
			'attr' => [
				'placeholder' => 'Enter number of pieces',
				'required' => true,
			],
			'value' => $this->getModel()->order->products->sum('qty'),
		])
		->add('service_type', 'text', [
			'label' => 'Service Type:',
			'attr' => [
				'placeholder' => 'Enter service type',
				'readonly' => true
			],
			'value' => 'N',
		])
		->add('cod_value', 'number', [
			'label' => 'COD Value:',
			'attr' => [
				'placeholder' => 'Enter COD value',
				'required' => true,
			],
			'value' => $this->getModel()->order->shipping_amount,
		])
		->add('service_date', 'date', [
			'label' => 'Service Date:',
			'attr' => [
				'placeholder' => 'Enter service date (YYYY-MM-DD)',
				'required' => true,
			],
		])
		->add('service_time', 'text', [
			'label' => 'Service Time:',
			'attr' => [
				'placeholder' => 'Enter service time (e.g., 10:00-18:00)',
				'required' => true,
			],
		])
		->add('created_by', 'text', [
			'label' => 'Created By:',
			'attr' => [
				'placeholder' => 'Enter creator name',
				'required' => true,
			],
		])
		->add('special', 'text', [
			'label' => 'Special Instructions:',
			'attr' => [
				'placeholder' => 'Enter any special instructions (optional)',
				'required' => true,
			],
			'default_value' => '',
		])
		->add('order_type', 'text', [
			'label' => 'Order Type:',
			'attr' => [
				'placeholder' => 'Enter order type',
				'readonly' => true,
				'required' => true,
			],
			'value' => 'D'
		])
		->add('ship_region', 'text', [
			'label' => 'Ship Region:',
			'attr' => [
				'placeholder' => 'Enter ship region (e.g., AE)',
				'readonly' => true,
				'required' => true,
			],
			'value' => 'AE',
		]);
	}
}
