<?php

namespace Dev3bdulrahman\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HasApiResponse;
use Dev3bdulrahman\Pos\Http\Requests\Api\StorePosSaleApiRequest;
use Dev3bdulrahman\Pos\Events\PosSaleCompleted;
use Dev3bdulrahman\Pos\Models\PosSale;
use Dev3bdulrahman\Pos\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosApiController extends Controller
{
    use HasApiResponse;

    /**
     * List all POS sales.
     */
    public function index(Request $request, PosService $service): JsonResponse
    {
        $this->authorize('viewAny', PosSale::class);

        $companyId = $request->user()->company_id;
        $perPage = (int) $request->get('per_page', 15);

        $sales = PosSale::where('company_id', $companyId)
            ->with(['items', 'payments', 'customer'])
            ->latest()
            ->paginate($perPage);

        return $this->success(
            $sales->items(),
            __('POS sales retrieved successfully'),
            200,
            [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ]
        );
    }

    /**
     * Store a new POS sale.
     */
    public function store(StorePosSaleApiRequest $request, PosService $service): JsonResponse
    {
        $this->authorize('create', PosSale::class);

        $validated = $request->validated();
        $user = $request->user();

        $data = [
            'company_id' => $user->company_id,
            'terminal_id' => $validated['terminal_id'],
            'created_by' => $user->id,
            'subtotal' => collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']),
            'discount' => 0,
            'tax' => 0,
            'total' => collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']),
            'items' => $validated['items'],
            'payments' => [
                [
                    'payment_method' => $validated['payment_method'],
                    'amount' => collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']),
                ],
            ],
        ];

        $sale = $service->processSale($data);

        PosSaleCompleted::dispatch($sale, $user->id, $user->company_id);

        return $this->success(
            [
                'id' => $sale->id,
                'code' => $sale->code,
                'total' => $sale->total,
            ],
            __('POS sale created successfully'),
            201
        );
    }

    /**
     * Show a single POS sale.
     */
    public function show(PosSale $posSale): JsonResponse
    {
        $this->authorize('view', $posSale);

        $posSale->load(['items', 'payments', 'customer']);

        return $this->success(
            $posSale,
            __('POS sale details retrieved')
        );
    }
}
