<?php

namespace Botble\Mollie\Services\Gateways;

use Botble\Mollie\Services\Abstracts\MolliePaymentAbstract;
use Illuminate\Http\Request;

class MolliePaymentService extends MolliePaymentAbstract
{
    public function makePayment(Request $request)
    {
    }

    public function afterMakePayment(Request $request)
    {
    }
}
