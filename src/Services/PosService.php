<?php

namespace Dev3bdulrahman\Pos\Services;

use Dev3bdulrahman\Pos\Models\PosTerminal;
use Dev3bdulrahman\Pos\Models\PosShift;
use Dev3bdulrahman\Pos\Models\PosSession;
use Dev3bdulrahman\Pos\Models\PosSale;
use Dev3bdulrahman\Pos\Models\PosSaleItem;
use Dev3bdulrahman\Pos\Models\PosPayment;
use Dev3bdulrahman\Pos\Models\PosCashMovement;
use Dev3bdulrahman\Inventory\Services\StockMoveService;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PosService
{
    protected StockMoveService $stockMoveService;

    public function __construct()
    {
        $this->stockMoveService = new StockMoveService();
    }

    /**
     * Open a new cashier shift and register session.
     */
    public function openShift(array $data): PosShift
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? 1;
            $userId = $data['user_id'];
            $terminalId = $data['terminal_id'];
            $openingBalance = $data['opening_balance'] ?? 0.00;

            // Check if there is already an open shift for this terminal or user
            $existingShift = PosShift::where('company_id', $companyId)
                ->where('terminal_id', $terminalId)
                ->where('status', 'open')
                ->first();

            if ($existingShift) {
                throw new Exception('There is already an open shift for this terminal.');
            }

            $shift = PosShift::create([
                'company_id'               => $companyId,
                'user_id'                  => $userId,
                'terminal_id'              => $terminalId,
                'opened_at'                => Carbon::now(),
                'opening_balance'          => $openingBalance,
                'expected_closing_balance' => $openingBalance,
                'actual_closing_balance'   => 0.00,
                'difference'               => 0.00,
                'status'                   => 'open',
            ]);

            // Create accompanying session
            PosSession::create([
                'company_id' => $companyId,
                'shift_id'   => $shift->id,
                'user_id'    => $userId,
                'status'     => 'active',
                'opened_at'  => Carbon::now(),
            ]);

            return $shift;
        });
    }

    /**
     * Close an active shift, calculating balances and differences.
     */
    public function closeShift(int $shiftId, float $actualBalance, string $notes = ''): PosShift
    {
        return DB::transaction(function () use ($shiftId, $actualBalance, $notes) {
            $shift = PosShift::with(['sales.payments', 'cashMovements'])->findOrFail($shiftId);

            if ($shift->status === 'closed') {
                return $shift;
            }

            // Calculate expected closing balance:
            // opening_balance + cash sales + cash_in movements - cash_out movements
            $cashPaymentsSum = 0.00;
            foreach ($shift->sales as $sale) {
                if ($sale->status !== 'returned') {
                    $cashPaymentsSum += $sale->payments()
                        ->where('payment_method', 'cash')
                        ->sum('amount');
                }
            }

            $cashInSum = $shift->cashMovements->where('type', 'cash_in')->sum('amount');
            $cashOutSum = $shift->cashMovements->where('type', 'cash_out')->sum('amount');

            $expectedClosing = $shift->opening_balance + $cashPaymentsSum + $cashInSum - $cashOutSum;
            $difference = $actualBalance - $expectedClosing;

            $shift->update([
                'closed_at'                => Carbon::now(),
                'expected_closing_balance' => $expectedClosing,
                'actual_closing_balance'   => $actualBalance,
                'difference'               => $difference,
                'notes'                    => $notes,
                'status'                   => 'closed',
            ]);

            // Close all associated sessions
            PosSession::where('shift_id', $shiftId)
                ->where('status', 'active')
                ->update([
                    'status'    => 'closed',
                    'closed_at' => Carbon::now(),
                ]);

            return $shift;
        });
    }

    /**
     * Log a drawer cash movement.
     */
    public function logCashMovement(array $data): PosCashMovement
    {
        return DB::transaction(function () use ($data) {
            $shift = PosShift::findOrFail($data['shift_id']);
            if ($shift->status !== 'open') {
                throw new Exception('Cannot log cash movement for a closed shift.');
            }

            return PosCashMovement::create([
                'shift_id'   => $data['shift_id'],
                'type'       => $data['type'], // cash_in / cash_out
                'amount'     => $data['amount'],
                'reason'     => $data['reason'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
        });
    }

    /**
     * Process a new POS Sale transaction, applying inventory changes.
     */
    public function processSale(array $data): PosSale
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? 1;
            $userId = $data['created_by'];
            $terminalId = $data['terminal_id'];

            // Retrieve active shift
            $shift = PosShift::where('company_id', $companyId)
                ->where('terminal_id', $terminalId)
                ->where('status', 'open')
                ->first();

            if (!$shift) {
                throw new Exception('No open shift found for this terminal. Please open a shift first.');
            }

            // Retrieve terminal configuration to get target warehouse
            $terminal = PosTerminal::findOrFail($terminalId);
            $warehouseId = $terminal->warehouse_id;

            // Generate POS sale code e.g. POS-YYYYMMDD-XXXX
            $datePrefix = 'POS-' . date('Ymd');
            $latestSale = PosSale::where('company_id', $companyId)
                ->where('code', 'like', $datePrefix . '-%')
                ->latest('id')
                ->first();

            $sequence = 1;
            if ($latestSale) {
                $parts = explode('-', $latestSale->code);
                $lastSeq = (int) end($parts);
                $sequence = $lastSeq + 1;
            }
            $code = $datePrefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Create POS Sale record
            $sale = PosSale::create([
                'company_id'  => $companyId,
                'shift_id'    => $shift->id,
                'session_id'  => PosSession::where('shift_id', $shift->id)->where('status', 'active')->first()?->id,
                'customer_id' => $data['customer_id'] ?? null,
                'code'        => $code,
                'subtotal'    => $data['subtotal'],
                'discount'    => $data['discount'] ?? 0.00,
                'tax'         => $data['tax'] ?? 0.00,
                'total'       => $data['total'],
                'status'      => 'completed',
                'created_by'  => $userId,
            ]);

            // Add Sale Items and execute stock deductions
            foreach ($data['items'] as $item) {
                $productId = $item['product_id'];
                $qty = $item['quantity'];
                $price = $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0.00;
                $itemTax = $item['tax'] ?? 0.00;
                $itemSubtotal = ($qty * $price) - $itemDiscount;
                $itemTotal = $itemSubtotal + $itemTax;

                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'product_id'  => $productId,
                    'quantity'    => $qty,
                    'unit_price'  => $price,
                    'discount'    => $itemDiscount,
                    'tax'         => $itemTax,
                    'subtotal'    => $itemSubtotal,
                    'total'       => $itemTotal,
                ]);

                // Stock move out
                if ($warehouseId && $qty > 0) {
                    $this->stockMoveService->logMove([
                        'company_id'   => $companyId,
                        'warehouse_id' => $warehouseId,
                        'product_id'   => $productId,
                        'type'         => 'out',
                        'quantity'     => $qty,
                        'reference'    => $code,
                        'source_type'  => PosSale::class,
                        'source_id'    => $sale->id,
                        'created_by'   => $userId,
                    ]);
                }
            }

            // Log payments
            foreach ($data['payments'] as $payment) {
                PosPayment::create([
                    'pos_sale_id'     => $sale->id,
                    'payment_method'  => $payment['payment_method'],
                    'amount'          => $payment['amount'],
                    'reference_number'=> $payment['reference_number'] ?? null,
                    'paid_at'         => Carbon::now(),
                ]);
            }

            return $sale;
        });
    }
}
