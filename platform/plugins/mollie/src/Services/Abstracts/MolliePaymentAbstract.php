<?php

namespace Botble\Mollie\Services\Abstracts;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Laravel\Facades\Mollie;

abstract class MolliePaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected string $paymentCurrency;

    protected MollieApiClient $client;

    protected bool $supportRefundOnline;

    protected float $totalAmount;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->totalAmount = 0;

        $this->setClient();

        $this->supportRefundOnline = true;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function setClient(): self
    {
        $this->client = Mollie::api();

        return $this;
    }

    public function getClient(): MollieApiClient
    {
        return $this->client;
    }

    public function setCurrency(string $currency): self
    {
        $this->paymentCurrency = $currency;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->paymentCurrency;
    }

    public function getPaymentDetails(string $paymentId): bool|Payment
    {
        try {
            $response  = $this->client->payments->get($paymentId); // Returns a particular payment
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return $response;
    }

    public function refundOrder($paymentId, $amount, array $options = []): array
    {
        try {
            $payment = $this->client->payments->get($paymentId);

            if ($payment->canBeRefunded() &&
                $payment->amountRemaining->currency == $this->paymentCurrency &&
                (float) $payment->amountRemaining->value >= (float) $amount) {
                /*
                 * https://docs.mollie.com/reference/v2/refunds-api/create-refund
                 */
                $description = Arr::get($options, 'refund_note') ?: get_order_code(Arr::get($options, 'order_id'));
                $refund = $payment->refund([
                    'amount' => [
                        'currency' => $this->paymentCurrency,
                        'value' => number_format((float) $amount, 2, '.', ''), // You must send the correct number of decimals, thus we enforce the use of strings
                    ],
                    'description' => Str::limit($description, 140),
                    'metadata' => $options,
                ]);

                return [
                    'error' => false,
                    'message' => "{$refund->amount->currency} {$refund->amount->value} of payment {$paymentId} refunded.",
                    'data' => (array) $refund,
                ];
            }

            return [
                'error' => true,
                'message' => "Payment {$paymentId} can not be refunded.",
            ];
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function execute(Request $request): bool
    {
        try {
            return $this->makePayment($request);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);
}
