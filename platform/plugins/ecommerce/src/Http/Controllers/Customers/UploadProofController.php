<?php

namespace Botble\Ecommerce\Http\Controllers\Customers;

use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Http\Requests\Fronts\UploadProofRequest;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Illuminate\Support\Facades\Storage;

class UploadProofController extends BaseController
{
    public function upload(int|string $id, UploadProofRequest $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $order = Order::query()
            ->where('user_id', $customer->getKey())
            ->findOrFail($id);

        $storage = Storage::disk('local');

        if ($order->proof_file) {
            $storage->delete($order->proof_file);
        }

        if (! $storage->exists('proofs')) {
            $storage->makeDirectory('proofs');
        }

        $file = $request->file('file');

        $proofFilePath = $storage->putFileAs('proofs', $file, sprintf('%s-%s', $order->getKey(), $file->getClientOriginalName()));

        $order->update([
            'proof_file' => $proofFilePath,
        ]);

        $emailVariables = [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'order_id' => get_order_code($order->getKey()),
            'order_link' => route('orders.edit', $order),
        ];

        if (is_plugin_active('payment') && $order->payment) {
            $emailVariables['payment_link'] = route('payment.show', $order->payment);
        }

        EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME)
            ->setVariableValues($emailVariables)
            ->sendUsingTemplate('payment-proof-upload-notification', args: [
                'attachments' => [$storage->path($proofFilePath)],
            ]);

        return $this
            ->httpResponse()
            ->setMessage(__('Uploaded proof successfully'));
    }

    public function download(int|string $id)
    {
        $order = Order::query()
            ->where('user_id', auth('customer')->id())
            ->findOrFail($id);

        $storage = Storage::disk('local');

        if (! $storage->exists($order->proof_file)) {
            abort(404);
        }

        return $storage->download($order->proof_file);
    }
}
