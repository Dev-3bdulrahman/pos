<?php

namespace Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos;

use Dev3bdulrahman\Pos\Models\PosShift;
use Dev3bdulrahman\Pos\Models\PosTerminal;
use Dev3bdulrahman\Pos\Services\PosService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Exception;

#[Layout('layouts.admin')]
class Shifts extends Component
{
    use WithPagination;

    public string $search = '';

    // Open Shift Modal Fields
    public bool $showOpenShiftModal = false;
    public ?int $openTerminalId = null;
    public float $openingBalance = 0.00;

    // Close Shift Modal Fields
    public bool $showCloseShiftModal = false;
    public ?int $closeShiftId = null;
    public float $expectedClosingBalance = 0.00;
    public float $actualClosingBalance = 0.00;
    public string $closeNotes = '';

    // Cash Drawer Movement Modal Fields
    public bool $showMovementModal = false;
    public ?int $movementShiftId = null;
    public string $movementType = 'cash_in'; // cash_in / cash_out
    public float $movementAmount = 0.00;
    public string $movementReason = '';

    protected PosService $posService;

    public function boot(PosService $posService): void
    {
        $this->posService = $posService;
    }

    public function openOpenShiftModal(): void
    {
        $this->resetValidation();
        $this->reset(['openTerminalId', 'openingBalance']);
        $this->showOpenShiftModal = true;
    }

    public function closeOpenShiftModal(): void
    {
        $this->showOpenShiftModal = false;
    }

    public function submitOpenShift(): void
    {
        $this->validate([
            'openTerminalId' => 'required|integer',
            'openingBalance' => 'required|numeric|min:0',
        ]);

        $companyId = session('active_company_id', 1);

        try {
            $this->posService->openShift([
                'company_id'      => $companyId,
                'user_id'         => auth()->id(),
                'terminal_id'     => $openTerminalId = $this->openTerminalId,
                'opening_balance' => $this->openingBalance,
            ]);

            $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.shift_opened_success')]);
            $this->showOpenShiftModal = false;
        } catch (Exception $e) {
            $this->addError('openTerminalId', $e->getMessage());
        }
    }

    public function openCloseShiftModal(int $shiftId): void
    {
        $this->resetValidation();
        $this->reset(['actualClosingBalance', 'closeNotes']);
        
        $shift = PosShift::with('sales.payments', 'cashMovements')->findOrFail($shiftId);
        
        // Calculate Expected Balance
        $cashPaymentsSum = 0.00;
        foreach ($shift->sales as $sale) {
            if ($sale->status !== 'returned') {
                $cashPaymentsSum += $sale->payments()->where('payment_method', 'cash')->sum('amount');
            }
        }
        $cashInSum = $shift->cashMovements->where('type', 'cash_in')->sum('amount');
        $cashOutSum = $shift->cashMovements->where('type', 'cash_out')->sum('amount');
        
        $this->expectedClosingBalance = $shift->opening_balance + $cashPaymentsSum + $cashInSum - $cashOutSum;
        $this->actualClosingBalance = $this->expectedClosingBalance; // Default to expected for quick submit
        $this->closeShiftId = $shiftId;
        $this->showCloseShiftModal = true;
    }

    public function closeCloseShiftModal(): void
    {
        $this->showCloseShiftModal = false;
    }

    public function submitCloseShift(): void
    {
        $this->validate([
            'actualClosingBalance' => 'required|numeric|min:0',
            'closeNotes'           => 'nullable|string',
        ]);

        try {
            $this->posService->closeShift(
                $this->closeShiftId,
                $this->actualClosingBalance,
                $this->closeNotes
            );

            $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.shift_closed_success')]);
            $this->showCloseShiftModal = false;
        } catch (Exception $e) {
            $this->addError('actualClosingBalance', $e->getMessage());
        }
    }

    public function openMovementModal(int $shiftId): void
    {
        $this->resetValidation();
        $this->reset(['movementAmount', 'movementReason']);
        $this->movementShiftId = $shiftId;
        $this->movementType = 'cash_in';
        $this->showMovementModal = true;
    }

    public function closeMovementModal(): void
    {
        $this->showMovementModal = false;
    }

    public function submitMovement(): void
    {
        $this->validate([
            'movementAmount' => 'required|numeric|min:0.01',
            'movementReason' => 'required|string|max:255',
            'movementType'   => 'required|in:cash_in,cash_out',
        ]);

        try {
            $this->posService->logCashMovement([
                'shift_id'   => $this->movementShiftId,
                'type'       => $this->movementType,
                'amount'     => $this->movementAmount,
                'reason'     => $this->movementReason,
                'created_by' => auth()->id(),
            ]);

            $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.cash_movement_logged')]);
            $this->showMovementModal = false;
        } catch (Exception $e) {
            $this->addError('movementAmount', $e->getMessage());
        }
    }

    public function render()
    {
        $companyId = session('active_company_id', 1);

        $shifts = PosShift::with(['user', 'terminal'])
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('terminal', function ($tq) {
                    $tq->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('opened_at')
            ->paginate(15);

        // Terminals to open a shift on
        $terminals = PosTerminal::where('company_id', $companyId)
            ->where('status', 'active')
            ->get();

        return view('pos::livewire.admin.pos.shifts', [
            'shifts'    => $shifts,
            'terminals' => $terminals,
        ])->title(__('pos::pos.shifts'));
    }
}
