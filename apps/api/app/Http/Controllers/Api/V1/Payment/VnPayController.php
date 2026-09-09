<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Crypt;

final class VnPayController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $payments,
    ) {}

    #[Response(200, 'VNPay IPN acknowledgement.')]
    public function ipn(Request $request): HttpResponse
    {
        try {
            $payload = $request->query();
            $result = $this->gateway->verify($payload);
            $isNew = $this->payments->recordWebhook('vnpay', 'ipn', $payload, $result->orderRef, $result->responseCode);
            if ($isNew) {
                $txn = $this->payments->findTxnByOrderNumber($result->orderRef);
                if ($txn !== null) {
                    if ($result->ok) {
                        $this->payments->settleSuccess($txn, $result->providerTxnId, 'payment:vnpay');
                    } else {
                        $this->payments->markFailed($txn, 'payment:vnpay:'.$result->responseCode);
                    }
                }
            }

            return response('{"RspCode":"00","Message":"Confirm Success"}', 200)
                ->header('Content-Type', 'application/json');
        } catch (CommerceException $e) {
            if ($e->errorCode === ErrorCode::PAYMENT_INVALID_SIGNATURE) {
                return response('{"RspCode":"97","Message":"Invalid Signature"}', 200)
                    ->header('Content-Type', 'application/json');
            }

            return response('{"RspCode":"99","Message":"Unknown error"}', 200)
                ->header('Content-Type', 'application/json');
        }
    }

    public function returnUrl(Request $request): RedirectResponse
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $status = 'failed';
        $number = (string) $request->query('vnp_TxnRef', '');
        $token = null;

        try {
            $payload = $request->query();
            $result = $this->gateway->verify($payload);
            $number = $result->orderRef;
            $isNew = $this->payments->recordWebhook('vnpay', 'return', $payload, $result->orderRef, $result->responseCode);
            $txn = $this->payments->findTxnByOrderNumber($result->orderRef);
            if ($isNew && $txn !== null) {
                if ($result->ok) {
                    $this->payments->settleSuccess($txn, $result->providerTxnId, 'payment:vnpay');
                    $status = 'paid';
                } else {
                    $this->payments->markFailed($txn, 'payment:vnpay:'.$result->responseCode);
                }
            } elseif ($txn !== null && $txn->status === 'succeeded') {
                $status = 'paid';
            }
            if ($txn?->order?->status === 'cancelled') {
                $status = 'cancelled';
            }

            $order = Order::query()->where('number', $result->orderRef)->first();
            $cipher = $order?->guest_lookup_token_cipher;
            $expiresAt = $order?->guest_lookup_token_expires_at;
            if (is_string($cipher) && $cipher !== '' && $expiresAt !== null && $expiresAt->isFuture()) {
                $token = Crypt::decryptString($cipher);
            }
        } catch (CommerceException) {
            $status = 'failed';
        } catch (DecryptException) {
            $token = null;
        }

        $query = 'number='.urlencode($number).'&status='.urlencode($status);
        if (is_string($token) && $token !== '') {
            $query .= '&token='.urlencode($token);
        }

        return redirect()->away($frontend.'/checkout/result?'.$query);
    }
}
