<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Services\RecordPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        private readonly RecordPaymentService $paymentService
    ) {
    }

    /**
     * Record a payment against an invoice.
     *
     * POST /api/v1/invoices/{invoice}/payments
     */
    public function store(RecordPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('update', $invoice);

        $data                = $request->validated();
        $data['recorded_by'] = $request->user()->id;

        $payment = $this->paymentService->recordPayment($invoice->id, $data);

        return $this->respondSuccess(new PaymentResource($payment), 'Payment recorded successfully.');
    }
}
