<?php

namespace Dev3bdulrahman\Pos\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Dev3bdulrahman\Pos\Models\PosTerminal;
use Dev3bdulrahman\Pos\Services\PosService;
use Exception;

class PosApiController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * Get active terminals for the company.
     */
    public function getTerminals(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id') ?? 1;

        $terminals = PosTerminal::where('company_id', $companyId)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $terminals,
        ]);
    }

    /**
     * Store a new POS sale transaction from API.
     */
    public function storeSale(Request $request): JsonResponse
    {
        $request->validate([
            'terminal_id'             => 'required|integer',
            'created_by'              => 'required|integer',
            'subtotal'                => 'required|numeric',
            'discount'                => 'nullable|numeric',
            'tax'                     => 'nullable|numeric',
            'total'                   => 'required|numeric',
            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer',
            'items.*.quantity'        => 'required|numeric|min:0.01',
            'items.*.unit_price'      => 'required|numeric',
            'items.*.discount'        => 'nullable|numeric',
            'items.*.tax'             => 'nullable|numeric',
            'payments'                => 'required|array|min:1',
            'payments.*.payment_method'=> 'required|string',
            'payments.*.amount'       => 'required|numeric',
            'payments.*.reference_number' => 'nullable|string',
        ]);

        $companyId = $request->header('X-Company-Id') ?? 1;
        $data = $request->all();
        $data['company_id'] = $companyId;

        try {
            $sale = $this->posService->processSale($data);

            return response()->json([
                'success' => true,
                'message' => 'Sale processed successfully.',
                'data'    => [
                    'id'   => $sale->id,
                    'code' => $sale->code,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
