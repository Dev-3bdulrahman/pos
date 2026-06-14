<?php

namespace Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos;

use Dev3bdulrahman\Pos\Models\PosTerminal;
use Dev3bdulrahman\Pos\Models\PosShift;
use Dev3bdulrahman\Pos\Services\PosService;
use Dev3bdulrahman\Crm\Models\Customer;
use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;
use Exception;

#[Layout('layouts.admin')]
class Register extends Component
{
    // Search
    public string $productSearch = '';
    public string $categoryFilter = '';

    // Active Shift context
    public ?PosShift $activeShift = null;
    public ?PosTerminal $activeTerminal = null;

    // Cart items: [ [product_id, name, quantity, unit_price, discount, tax, total] ]
    public array $cart = [];

    // Overall cart adjustments
    public float $cartDiscount = 0.00;
    public float $cartTaxRate = 15.00; // default 15% VAT

    // Checkout details
    public ?int $customerId = null;
    public string $paymentMethod = 'cash'; // cash, card, split
    public float $cashAmountPaid = 0.00;
    public float $cardAmountPaid = 0.00;
    public string $paymentReference = '';

    // Modal controls
    public bool $showCheckoutModal = false;
    public bool $showReceiptModal = false;
    public ?array $completedSaleData = null;

    protected PosService $posService;

    public function boot(PosService $posService): void
    {
        $this->posService = $posService;
    }

    public function mount(): void
    {
        $companyId = session('active_company_id', 1);

        // Load active shift for currently logged-in user
        $this->activeShift = PosShift::with('terminal')
            ->where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($this->activeShift) {
            $this->activeTerminal = $this->activeShift->terminal;
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::findOrFail($productId);

        // Check if product already exists in cart
        foreach ($this->cart as $index => $item) {
            if ($item['product_id'] === $productId) {
                $this->cart[$index]['quantity']++;
                $this->recalculateCart();
                return;
            }
        }

        // Add new item
        $this->cart[] = [
            'product_id' => $product->id,
            'name'       => $product->translated_name,
            'quantity'   => 1,
            'unit_price' => (float) $product->price,
            'discount'   => 0.00,
            'tax'        => 0.00,
            'total'      => (float) $product->price,
        ];

        $this->recalculateCart();
    }

    public function updateQuantity(int $index, float $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);
            return;
        }

        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity'] = $quantity;
            $this->recalculateCart();
        }
    }

    public function updateItemDiscount(int $index, float $discount): void
    {
        if (isset($this->cart[$index]) && $discount >= 0) {
            $this->cart[$index]['discount'] = $discount;
            $this->recalculateCart();
        }
    }

    public function removeFromCart(int $index): void
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // reset keys
            $this->recalculateCart();
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->cartDiscount = 0.00;
        $this->recalculateCart();
    }

    public function recalculateCart(): void
    {
        foreach ($this->cart as $index => $item) {
            $sub = $item['quantity'] * $item['unit_price'];
            $discount = $item['discount'];
            
            // Calculate Item Tax based on item subtotal - item discount
            $taxRateDecimal = $this->cartTaxRate / 100;
            $taxableAmount = max(0, $sub - $discount);
            $itemTax = $taxableAmount * $taxRateDecimal;

            $this->cart[$index]['tax'] = round($itemTax, 2);
            $this->cart[$index]['total'] = round($taxableAmount + $itemTax, 2);
        }
    }

    // Subtotal of the cart
    public function getSubtotalProperty(): float
    {
        $sum = 0.00;
        foreach ($this->cart as $item) {
            $sum += $item['quantity'] * $item['unit_price'];
        }
        return $sum;
    }

    // Total discount applied
    public function getDiscountProperty(): float
    {
        $sum = $this->cartDiscount;
        foreach ($this->cart as $item) {
            $sum += $item['discount'];
        }
        return $sum;
    }

    // Total tax calculated
    public function getTaxProperty(): float
    {
        $sum = 0.00;
        foreach ($this->cart as $item) {
            $sum += $item['tax'];
        }
        return $sum;
    }

    // Final Total to pay
    public function getTotalProperty(): float
    {
        return max(0, $this->subtotal - $this->discount + $this->tax);
    }

    public function openCheckout(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('pos::pos.no_items_in_cart')]);
            return;
        }

        $total = $this->total;
        $this->cashAmountPaid = $total;
        $this->cardAmountPaid = 0.00;
        $this->paymentMethod = 'cash';
        $this->showCheckoutModal = true;
    }

    public function closeCheckout(): void
    {
        $this->showCheckoutModal = false;
    }

    public function submitCheckout(): void
    {
        $companyId = session('active_company_id', 1);
        $total = $this->total;

        // Validation based on payment method
        if ($this->paymentMethod === 'cash') {
            if ($this->cashAmountPaid < $total) {
                $this->addError('cashAmountPaid', 'Paid amount must be at least the total.');
                return;
            }
        } elseif ($this->paymentMethod === 'card') {
            $this->cardAmountPaid = $total;
        } elseif ($this->paymentMethod === 'split') {
            if (($this->cashAmountPaid + $this->cardAmountPaid) < $total) {
                $this->addError('cashAmountPaid', 'Total paid in cash + card must equal or exceed total.');
                return;
            }
        }

        // Prepare items array
        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount'   => $item['discount'],
                'tax'        => $item['tax'],
            ];
        }

        // Prepare payments array
        $payments = [];
        if ($this->paymentMethod === 'cash') {
            $payments[] = [
                'payment_method'   => 'cash',
                'amount'           => $total,
                'reference_number' => null,
            ];
        } elseif ($this->paymentMethod === 'card') {
            $payments[] = [
                'payment_method'   => 'card',
                'amount'           => $total,
                'reference_number' => $this->paymentReference,
            ];
        } else {
            // Split payment
            if ($this->cashAmountPaid > 0) {
                $payments[] = [
                    'payment_method'   => 'cash',
                    'amount'           => min($this->cashAmountPaid, $total),
                    'reference_number' => null,
                ];
            }
            $remaining = max(0, $total - $this->cashAmountPaid);
            if ($remaining > 0) {
                $payments[] = [
                    'payment_method'   => 'card',
                    'amount'           => $remaining,
                    'reference_number' => $this->paymentReference,
                ];
            }
        }

        try {
            $sale = $this->posService->processSale([
                'company_id'  => $companyId,
                'created_by'  => auth()->id(),
                'terminal_id' => $this->activeTerminal->id,
                'customer_id' => $this->customerId,
                'subtotal'    => $this->subtotal,
                'discount'    => $this->discount,
                'tax'         => $this->tax,
                'total'       => $total,
                'items'       => $items,
                'payments'    => $payments,
            ]);

            $this->completedSaleData = [
                'code'          => $sale->code,
                'subtotal'      => $sale->subtotal,
                'discount'      => $sale->discount,
                'tax'           => $sale->tax,
                'total'         => $sale->total,
                'customer_name' => $sale->customer?->name ?? __('pos::pos.walk_in_customer'),
                'items'         => $this->cart,
                'payments'      => $payments,
                'change'        => $this->paymentMethod === 'cash' ? max(0, $this->cashAmountPaid - $total) : 0.00,
            ];

            $this->dispatch('notify', ['type' => 'success', 'message' => __('pos::pos.sale_processed_success')]);
            $this->showCheckoutModal = false;
            $this->showReceiptModal = true;
            $this->clearCart();
        } catch (Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->completedSaleData = null;
    }

    public function render()
    {
        $companyId = session('active_company_id', 1);

        // Retrieve search products
        $products = [];
        if ($this->activeShift) {
            $query = Product::where('company_id', $companyId)
                ->where('status', 'active');

            if (!empty($this->productSearch)) {
                $query->search($this->productSearch);
            }

            $products = $query->take(24)->get();
        }

        // Customers list
        $customers = Customer::where('company_id', $companyId)->get();

        return view('pos::livewire.admin.pos.register', [
            'products'  => $products,
            'customers' => $customers,
        ])->title(__('pos::pos.register'));
    }
}
