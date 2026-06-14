<?php

namespace Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos;

use Dev3bdulrahman\Pos\Models\PosTerminal;
use Dev3bdulrahman\Inventory\Models\Warehouse;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Terminals extends Component
{
    use WithPagination;

    public string $search = '';

    // Terminal Form Fields
    public bool $showTerminalModal = false;
    public ?int $terminalId = null;
    public string $terminalName = '';
    public string $terminalCode = '';
    public ?int $warehouseId = null;
    public string $terminalStatus = 'active';

    protected function rules(): array
    {
        return [
            'terminalName'   => 'required|string|max:255',
            'terminalCode'   => 'required|string|max:50',
            'warehouseId'    => 'nullable|integer',
            'terminalStatus' => 'required|in:active,inactive',
        ];
    }

    public function openTerminalModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->resetFields();

        if ($id) {
            $terminal = PosTerminal::findOrFail($id);
            $this->terminalId = $terminal->id;
            $this->terminalName = $terminal->name;
            $this->terminalCode = $terminal->code;
            $this->warehouseId = $terminal->warehouse_id;
            $this->terminalStatus = $terminal->status;
        }

        $this->showTerminalModal = true;
    }

    public function closeTerminalModal(): void
    {
        $this->showTerminalModal = false;
        $this->resetFields();
    }

    public function saveTerminal(): void
    {
        $this->validate();

        $companyId = session('active_company_id', 1);

        $data = [
            'company_id'   => $companyId,
            'name'         => $this->terminalName,
            'code'         => $this->terminalCode,
            'warehouse_id' => $this->warehouseId,
            'status'       => $this->terminalStatus,
            'created_by'   => auth()->id(),
        ];

        if ($this->terminalId) {
            PosTerminal::findOrFail($this->terminalId)->update($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.saved_success')]);
        } else {
            PosTerminal::create($data);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.saved_success')]);
        }

        $this->closeTerminalModal();
    }

    public function deleteTerminal(int $id): void
    {
        PosTerminal::findOrFail($id)->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.deleted_success')]);
    }

    private function resetFields(): void
    {
        $this->reset([
            'terminalId', 'terminalName', 'terminalCode', 'warehouseId', 'terminalStatus'
        ]);
    }

    public function render()
    {
        $companyId = session('active_company_id', 1);

        $terminals = PosTerminal::with('warehouse')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->paginate(10);

        $warehouses = Warehouse::where('company_id', $companyId)->get();

        return view('pos::livewire.admin.pos.terminals', [
            'terminals'  => $terminals,
            'warehouses' => $warehouses,
        ])->title(__('pos::pos.terminals'));
    }
}
