<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Visit;
use App\Services\CancelInvoiceService;
use App\Services\GenerateInvoiceService;
use App\Services\Support\BranchScopeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly GenerateInvoiceService $generateService,
        private readonly CancelInvoiceService   $cancelService,
    ) {
    }

    /**
     * List invoices (branch-scoped).
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $baseQuery = BranchScopeFilter::apply(Invoice::query(), $request->user());

        $perPage = min($request->integer('per_page', 20), 100);

        $invoices = QueryBuilder::for($baseQuery)
            ->allowedFilters(['status', 'patient_id', 'visit_id'])
            ->allowedSorts(['created_at', 'total'])
            ->allowedIncludes(['items', 'payments', 'patient'])
            ->paginate($perPage);

        return $this->respondPaginated(
            InvoiceResource::collection($invoices),
            'Invoices retrieved successfully.'
        );
    }

    /**
     * Show a single invoice.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice);

        $invoice = QueryBuilder::for(Invoice::class)
            ->allowedIncludes(['items', 'payments', 'patient'])
            ->whereKey($invoice->id)
            ->firstOrFail();

        return $this->respondSuccess(new InvoiceResource($invoice), 'Invoice retrieved successfully.');
    }

    /**
     * Generate an invoice from a completed visit.
     *
     * POST /api/v1/visits/{visit}/invoice
     */
    public function store(Request $request, Visit $visit): JsonResponse
    {
        Gate::authorize('create', Invoice::class);

        $invoice = $this->generateService->generate($visit, $request->user()->id);

        return $this->respondSuccess(new InvoiceResource($invoice), 'Invoice generated successfully.');
    }

    /**
     * Cancel an invoice.
     *
     * DELETE /api/v1/invoices/{invoice}
     * Note: per Article I, this performs a status transition, not a physical delete.
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('delete', $invoice);

        $request->validate(['cancellation_reason' => ['required', 'string']]);

        $cancelled = $this->cancelService->cancel($invoice->id, $request->input('cancellation_reason'));

        return $this->respondSuccess(new InvoiceResource($cancelled), 'Invoice cancelled successfully.');
    }
}
