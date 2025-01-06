<?php

// namespace Botble\Ecommerce\Forms;

// use Botble\Base\Forms\FormAbstract;
// use Botble\Ecommerce\Forms\Concerns\HasSubmitButton;
// use Botble\Ecommerce\Http\Requests\ShipmentRequest;
// use Botble\Ecommerce\Models\Shipment;

// class ShipmentInfoForm extends FormAbstract
// {
//     use HasSubmitButton;

//     public function setup(): void
//     {
//         $this
//             ->setupModel(new Shipment())
//             ->setValidatorClass(ShipmentRequest::class)
//             ->contentOnly()
//             ->add('shipping_company_name', 'text', [
//                 'label' => trans('plugins/ecommerce::shipping.shipping_company_name'),
//                 'attr' => [
//                     'placeholder' => 'Ex: DHL, AliExpress...',
//                 ],
//             ])
//             ->add('tracking_id', 'text', [
//                 'label' => trans('plugins/ecommerce::shipping.tracking_id'),
//                 'attr' => [
//                     'placeholder' => 'Ex: JJD0099999999',
//                 ],
//             ])
//             ->add('tracking_link', 'text', [
//                 'label' => trans('plugins/ecommerce::shipping.tracking_link'),
//                 'attr' => [
//                     'placeholder' => 'Ex: https://mydhl.express.dhl/us/en/tracking.html#/track-by-reference',
//                 ],
//             ])
//             ->add('estimate_date_shipped', 'datePicker', [
//                 'label' => trans('plugins/ecommerce::shipping.estimate_date_shipped'),
//             ])
//             ->add('note', 'textarea', [
//                 'label' => trans('plugins/ecommerce::shipping.note'),
//                 'attr' => [
//                     'rows' => 3,
//                     'placeholder' => trans('plugins/ecommerce::shipping.add_note'),
//                 ],
//             ])
//             ->addSubmitButton(trans('core/base::forms.save_and_continue'), 'ti ti-circle-check');
//     }
// }


// namespace Botble\Ecommerce\Forms;

// use Botble\Base\Forms\FormAbstract;
// use Botble\Ecommerce\Forms\Concerns\HasSubmitButton;
// use Botble\Ecommerce\Http\Requests\ShipmentRequest;
// use Botble\Ecommerce\Models\Shipment;

// class ShipmentInfoForm extends FormAbstract
// {
//     use HasSubmitButton;

//     public function setup(): void
//     {
//         $this
//             ->setupModel(new Shipment())
//             ->setValidatorClass(ShipmentRequest::class)
//             ->contentOnly()
//             ->add('shipping_company_name', 'text', [
//                 'label' => trans('plugins/ecommerce::shipping.shipping_company_name'),
//                 'attr' => [
//                     'placeholder' => 'Ex: DHL, AliExpress...',
//                 ],
//             ])
//             ->add('tracking_id', 'text', [
//                 'label' => trans('plugins/ecommerce::shipping.tracking_id'),
//                 'attr' => [
//                     'placeholder' => 'Ex: JJD0099999999',
//                 ],
//             ])
//             ->add('tracking_link', 'text', [
//                 'label' => trans('plugins/ecommerce::shipping.tracking_link'),
//                 'attr' => [
//                     'placeholder' => 'Ex: https://mydhl.express.dhl/us/en/tracking.html#/track-by-reference',
//                 ],
//             ])
//             ->add('estimate_date_shipped', 'datePicker', [
//                 'label' => trans('plugins/ecommerce::shipping.estimate_date_shipped'),
//             ])
//             ->add('note', 'textarea', [
//                 'label' => trans('plugins/ecommerce::shipping.note'),
//                 'attr' => [
//                     'rows' => 3,
//                     'placeholder' => trans('plugins/ecommerce::shipping.add_note'),
//                 ],
//             ])
//             ->addSubmitButton(trans('core/base::forms.save_and_continue'), 'ti ti-circle-check');
//     }
// }
// namespace Botble\Ecommerce\Forms;

// use Botble\Base\Forms\FormAbstract;
// use Botble\Ecommerce\Forms\Concerns\HasSubmitButton;
// use Botble\Ecommerce\Http\Requests\ShipmentRequest;
// use Botble\Ecommerce\Models\Shipment;

// class ShipmentInfoForm extends FormAbstract
// {
//     use HasSubmitButton;

//     public function setup(): void
//     {

        // $this
        //     ->setupModel(new Shipment())
        //     ->setValidatorClass(ShipmentRequest::class)
        //     ->contentOnly()
        //     ->add('shipping_company_name', 'text', [
        //         'label' => trans('plugins/ecommerce::shipping.shipping_company_name'),
        //         'attr' => [
        //             'placeholder' => 'Ex: DHL, AliExpress...',
        //         ],
        //     ])
        //     ->add('tracking_id', 'text', [
        //         'label' => trans('plugins/ecommerce::shipping.tracking_id'),
        //         'attr' => [
        //             'placeholder' => 'Ex: JJD0099999999',
        //         ],
        //     ])
        //     ->add('tracking_link', 'text', [
        //         'label' => trans('plugins/ecommerce::shipping.tracking_link'),
        //         'attr' => [
        //             'placeholder' => 'Ex: https://mydhl.express.dhl/us/en/tracking.html#/track-by-reference',
        //         ],
        //     ])
        //     ->add('estimate_date_shipped', 'datePicker', [
        //         'label' => trans('plugins/ecommerce::shipping.estimate_date_shipped'),
        //     ])
        //     ->add('note', 'textarea', [
        //         'label' => trans('plugins/ecommerce::shipping.note'),
        //         'attr' => [
        //             'rows' => 3,
        //             'placeholder' => trans('plugins/ecommerce::shipping.add_note'),
        //         ],
        //     ])
        //     ->addSubmitButton(trans('core/base::forms.save_and_continue'), 'ti ti-circle-check')
        //     ->add('generate_shipping_label', 'button', [
        //         'label' => 'Generate Shipping Label',
        //         'attr' => [
        //             'class' => 'btn btn-primary',
        //             'id' => 'generate-shipping-label',
        //         ],
        //     ])
        //     ->add('track_awb', 'button', [
        //         'label' => 'Track AWB',
        //         'attr' => [
        //             'class' => 'btn btn-secondary',
        //             'id' => 'track-awb',
        //         ],
        //     ])
        //     ->add('cancel_shipment', 'button', [
        //         'label' => 'Cancel Shipment',
        //         'attr' => [
        //             'class' => 'btn btn-danger',
        //             'id' => 'cancel-shipment',
        //         ],
        //     ]);
// }
// File: app/Forms/ShipmentInfoForm.php

namespace Botble\Ecommerce\Forms;

use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Models\EliteShipment;
use Botble\Ecommerce\Forms\Concerns\HasSubmitButton;

class ShipmentInfoForm extends FormAbstract
{
    use HasSubmitButton;

    public function setup(): void
    {
        $this
            ->setupModel(new EliteShipment())
            ->contentOnly();

        // Add the buttons directly below the form content
        $this->addButtonsBelowContent();
    }

    /**
     * Method to add buttons directly below the form content.
     */
    protected function addButtonsBelowContent(): void
    {
        if ($this->getModel()->eliteShipment) {
            $trackShipmentRoute = $this->getModel()->eliteShipment->tracking_url;
            $generateAwbRoute = $this->getModel()->eliteShipment->awb_label_url;
            $cancelShipmentRoute = "/admin/ecommerce/cancel-shipment";

            $this->add('button_section', 'html', [
                'html' => sprintf(
                    '
                    <div class="form-buttons mt-4">
                        <a href="%s" class="btn btn-secondary" target="_blank">Track Shipment</a>
                        <a href="%s" class="btn btn-info" target="_blank">Generate AWB</a>
                        <a href="%s" class="btn btn-info">Cancel Shipment</a>
                    </div>
                    ',
                    $trackShipmentRoute, $generateAwbRoute, $cancelShipmentRoute
                ),
            ]);
        } else {
            $createShipmentRoute = "/admin/ecommerce/create-shipment?id=" . $this->getModel()->id;

            $this->add('button_section', 'html', [
                'html' => sprintf(
                    '
                    <div class="form-buttons mt-4">
                        <a href="%s" class="btn btn-primary">Create Shipment</a>
                    </div>
                    ',
                    $createShipmentRoute
                ),
            ]);
        }
    }
}


// namespace Botble\Ecommerce\Forms;

// use Botble\Base\Forms\FormAbstract;
// use Botble\Ecommerce\Models\EliteShipment;
// use Botble\Ecommerce\Forms\Concerns\HasSubmitButton;

// class ShipmentInfoForm extends FormAbstract
// {
//     use HasSubmitButton;

//     public function setup(): void
//     {
//         $this
//             ->setupModel(new EliteShipment()) // Update this to use EliteShipment
//             ->contentOnly();

        //   ->add('shipper_name', 'text', [
        //         'label' => 'Shipper Name:',
        //         'attr' => [
        //             'placeholder' => 'Enter shipper name',
        //         ],
        //     ])
        //     ->add('shipper_address', 'text', [
        //         'label' => 'Shipper Address:',
        //         'attr' => [
        //             'placeholder' => 'Enter shipper address',
        //         ],
        //     ])
        //     ->add('shipper_area', 'text', [
        //         'label' => 'Shipper Area:',
        //         'attr' => [
        //             'placeholder' => 'Enter shipper area',
        //         ],
        //     ])
        //     ->add('shipper_city', 'text', [
        //         'label' => 'Shipper City:',
        //         'attr' => [
        //             'placeholder' => 'Enter shipper city',
        //         ],
        //     ])
        //     ->add('shipper_telephone', 'text', [
        //         'label' => 'Shipper Telephone:',
        //         'attr' => [
        //             'placeholder' => 'Enter shipper telephone',
        //         ],
        //     ])
        //     ->add('receiver_name', 'text', [
        //         'label' => 'Receiver Name:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver name',
        //         ],
        //     ])
        //     ->add('receiver_address', 'text', [
        //         'label' => 'Receiver Address:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver address',
        //         ],
        //     ])
        //     ->add('receiver_address2', 'text', [
        //         'label' => 'Receiver Address 2:',
        //         'attr' => [
        //             'placeholder' => 'Enter additional receiver address (optional)',
        //         ],
        //         'default_value' => '',
        //     ])
        //     ->add('receiver_area', 'text', [
        //         'label' => 'Receiver Area:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver area',
        //         ],
        //     ])
        //     ->add('receiver_city', 'text', [
        //         'label' => 'Receiver City:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver city',
        //         ],
        //     ])
        //     ->add('receiver_telephone', 'text', [
        //         'label' => 'Receiver Telephone:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver telephone',
        //         ],
        //     ])
        //     ->add('receiver_mobile', 'text', [
        //         'label' => 'Receiver Mobile:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver mobile',
        //         ],
        //     ])
        //     ->add('receiver_email', 'email', [
        //         'label' => 'Receiver Email:',
        //         'attr' => [
        //             'placeholder' => 'Enter receiver email',
        //         ],
        //     ])
        //     ->add('shipping_reference', 'text', [
        //         'label' => 'Shipping Reference:',
        //         'attr' => [
        //             'placeholder' => 'Enter shipping reference',
        //         ],
        //     ])
        //     ->add('orders', 'text', [
        //         'label' => 'Orders:',
        //         'attr' => [
        //             'placeholder' => 'Enter order details',
        //         ],
        //     ])
        //     ->add('item_type', 'text', [
        //         'label' => 'Item Type:',
        //         'attr' => [
        //             'placeholder' => 'Enter item type',
        //         ],
        //     ])
        //     ->add('item_description', 'text', [
        //         'label' => 'Item Description:',
        //         'attr' => [
        //             'placeholder' => 'Enter item description',
        //         ],
        //     ])
        //     ->add('item_value', 'number', [
        //         'label' => 'Item Value:',
        //         'attr' => [
        //             'placeholder' => 'Enter item value',
        //         ],
        //     ])
        //     ->add('dangerousGoodsType', 'text', [
        //         'label' => 'Dangerous Goods Type:',
        //         'attr' => [
        //             'placeholder' => 'Enter dangerous goods type',
        //         ],
        //         'default_value' => '',
        //     ])
        //     ->add('weight_kg', 'number', [
        //         'label' => 'Weight (kg):',
        //         'attr' => [
        //             'placeholder' => 'Enter weight in kg',
        //         ],
        //     ])
        //     ->add('no_of_pieces', 'number', [
        //         'label' => 'No of Pieces:',
        //         'attr' => [
        //             'placeholder' => 'Enter number of pieces',
        //         ],
        //     ])
        //     ->add('service_type', 'text', [
        //         'label' => 'Service Type:',
        //         'attr' => [
        //             'placeholder' => 'Enter service type',
        //         ],
        //     ])
        //     ->add('cod_value', 'number', [
        //         'label' => 'COD Value:',
        //         'attr' => [
        //             'placeholder' => 'Enter COD value',
        //         ],
        //         'default_value' => '',
        //     ])
        //     ->add('service_date', 'date', [
        //         'label' => 'Service Date:',
        //         'attr' => [
        //             'placeholder' => 'Enter service date (YYYY-MM-DD)',
        //         ],
        //     ])
        //     ->add('service_time', 'time', [
        //         'label' => 'Service Time:',
        //         'attr' => [
        //             'placeholder' => 'Enter service time (e.g., 10:00-18:00)',
        //         ],
        //     ])
        //     ->add('created_by', 'text', [
        //         'label' => 'Created By:',
        //         'attr' => [
        //             'placeholder' => 'Enter creator name',
        //         ],
        //     ])
        //     ->add('special', 'text', [
        //         'label' => 'Special Instructions:',
        //         'attr' => [
        //             'placeholder' => 'Enter any special instructions (optional)',
        //         ],
        //         'default_value' => '',
        //     ])
        //     ->add('order_type', 'text', [
        //         'label' => 'Order Type:',
        //         'attr' => [
        //             'placeholder' => 'Enter order type',
        //         ],
        //     ])
        //     ->add('ship_region', 'text', [
        //         'label' => 'Ship Region:',
        //         'attr' => [
        //             'placeholder' => 'Enter ship region (e.g., AE)',
        //         ],
        //         'default_value' => 'AE',
        //     ]);


            // ->addSubmitButton(trans('core/base::forms.save_and_continue'), 'ti ti-circle-check');
    // }
// }
