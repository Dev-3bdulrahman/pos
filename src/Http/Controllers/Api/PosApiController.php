<?php

namespace Dev3bdulrahman\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HasApiResponse;
use Dev3bdulrahman\Pos\Http\Requests\Api\StorePosSaleApiRequest;
use Dev3bdulrahman\Pos\Events\PosSaleCompleted;
use Dev3bdulrahman\Pos\Models\PosCashMovement;
use Dev3bdulrahman\Pos\Models\PosSale;
use Dev3bdulrahman\Pos\Models\PosSession;
use Dev3bdulrahman\Pos\Models\PosShift;
use Dev3bdulrahman\Pos\Models\PosTerminal;
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

    // ── Sessions ──────────────────────────────────────────────────────────────

    /**
     * List POS sessions paginated.
     */
    public function sessionsIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = (int) $request->get('per_page', 15);

        $sessions = PosSession::where('company_id', $companyId)
            ->with(['shift', 'user'])
            ->latest()
            ->paginate($perPage);

        return $this->success(
            $sessions->items(),
            __('POS sessions retrieved successfully'),
            200,
            [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ]
        );
    }

    /**
     * Open a new POS session (shift).
     */
    public function openSession(Request $request, PosService $service): JsonResponse
    {
        $validated = $request->validate([
            'terminal_id' => 'required|integer|exists:pos_terminals,id',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();

        $shift = $service->openShift([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'terminal_id' => $validated['terminal_id'],
            'opening_balance' => $validated['opening_balance'] ?? 0.00,
        ]);

        return $this->success(
            $shift->load('sessions'),
            __('POS session opened successfully'),
            201
        );
    }

    /**
     * Close a POS session.
     */
    public function closeSession(Request $request, PosSession $posSession, PosService $service): JsonResponse
    {
        $validated = $request->validate([
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shift = $service->closeShift(
            $posSession->shift_id,
            (float) $validated['actual_balance'],
            $validated['notes'] ?? ''
        );

        return $this->success(
            $shift,
            __('POS session closed successfully')
        );
    }

    // ── Shifts ────────────────────────────────────────────────────────────────

    /**
     * List POS shifts paginated.
     */
    public function shiftsIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = (int) $request->get('per_page', 15);

        $shifts = PosShift::where('company_id', $companyId)
            ->with(['user', 'terminal'])
            ->latest()
            ->paginate($perPage);

        return $this->success(
            $shifts->items(),
            __('POS shifts retrieved successfully'),
            200,
            [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
            ]
        );
    }

    // ── Terminals ─────────────────────────────────────────────────────────────

    /**
     * List POS terminals paginated.
     */
    public function terminalsIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = (int) $request->get('per_page', 15);

        $terminals = PosTerminal::where('company_id', $companyId)
            ->with('warehouse')
            ->latest()
            ->paginate($perPage);

        return $this->success(
            $terminals->items(),
            __('POS terminals retrieved successfully'),
            200,
            [
                'current_page' => $terminals->currentPage(),
                'last_page' => $terminals->lastPage(),
                'per_page' => $terminals->perPage(),
                'total' => $terminals->total(),
            ]
        );
    }

    // ── Cash Movements ────────────────────────────────────────────────────────

    /**
     * List cash movements paginated.
     */
    public function cashMovementsIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = (int) $request->get('per_page', 15);

        $movements = PosCashMovement::whereHas('shift', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
            ->with(['shift', 'creator'])
            ->latest()
            ->paginate($perPage);

        return $this->success(
            $movements->items(),
            __('Cash movements retrieved successfully'),
            200,
            [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ]
        );
    }

    /**
     * Store a new cash movement.
     */
    public function storeCashMovement(Request $request, PosService $service): JsonResponse
    {
        $validated = $request->validate([
            'shift_id' => 'required|integer|exists:pos_shifts,id',
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $movement = $service->logCashMovement([
            'shift_id' => $validated['shift_id'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return $this->success(
            $movement,
            __('Cash movement recorded successfully'),
            201
        );
    }
}
