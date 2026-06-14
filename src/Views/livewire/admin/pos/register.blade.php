<div class="h-[calc(100vh-4rem)] flex flex-col bg-gray-50 dark:bg-gray-950 overflow-hidden">
    @if(!$activeShift)
        <!-- No active shift alert -->
        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center space-y-6">
            <div class="w-20 h-20 bg-amber-50 dark:bg-amber-950/20 text-amber-500 rounded-full flex items-center justify-center shadow-lg">
                <i data-lucide="shield-alert" class="w-10 h-10"></i>
            </div>
            <div class="max-w-md space-y-2">
                <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ __('pos::pos.no_shifts') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">You must open a cashier shift before processing transactions.</p>
            </div>
            <a href="{{ route('admin.pos.shifts') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition-all">
                {{ __('pos::pos.shifts') }}
            </a>
        </div>
    @else
        <!-- Main POS Screen -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
            <!-- Left: Products Catalog -->
            <div class="flex-1 flex flex-col p-6 overflow-hidden space-y-4">
                <!-- Search & Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-black text-gray-900 dark:text-white">{{ __('pos::pos.register') }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Terminal: <span class="font-bold text-blue-600">{{ $activeTerminal->name }} ({{ $activeTerminal->code }})</span></p>
                    </div>
                    <div class="w-full md:w-80 relative flex items-center">
                        <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="{{ __('pos::pos.barcode_scan_or_search') }}" class="w-full ps-10 pe-4 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        @forelse($products as $product)
                            <button wire:click="addToCart({{ $product->id }})" class="group flex flex-col text-start bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                <div class="w-full aspect-square bg-gray-50 dark:bg-gray-800 rounded-xl flex items-center justify-center mb-3 text-gray-300 dark:text-gray-700 overflow-hidden relative">
                                    <i data-lucide="package" class="w-8 h-8 group-hover:scale-110 transition-transform"></i>
                                </div>
                                <h3 class="font-bold text-sm text-gray-900 dark:text-white truncate w-full">{{ $product->translated_name }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">SKU: {{ $product->sku ?? '-' }}</p>
                                <div class="mt-3 flex items-center justify-between w-full">
                                    <span class="text-sm font-extrabold text-blue-600 dark:text-blue-400">{{ number_format($product->price, 2) }}</span>
                                    <span class="text-[10px] uppercase font-bold text-gray-400 bg-gray-50 dark:bg-gray-800 px-1.5 py-0.5 rounded">Qty: {{ $product->stock ?? 0 }}</span>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-full py-16 text-center text-sm text-gray-400">No products found.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right: Checkout Sidebar -->
            <div class="w-full lg:w-[420px] bg-white dark:bg-gray-900 border-t lg:border-t-0 lg:border-s border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden shadow-2xl">
                <!-- Customer Selector -->
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                    <select wire:model.live="customerId" class="flex-1 text-sm bg-transparent border-0 focus:ring-0 text-gray-950 dark:text-white font-bold">
                        <option value="">{{ __('pos::pos.walk_in_customer') }}</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cart Items List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @forelse($cart as $index => $item)
                        <div class="flex items-start justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-850 rounded-xl relative group">
                            <div class="space-y-1 flex-1">
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white leading-tight">{{ $item['name'] }}</h4>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-blue-600 dark:text-blue-400 font-extrabold">{{ number_format($item['unit_price'], 2) }}</span>
                                    <span class="text-gray-400">x</span>
                                    <input type="number" step="1" value="{{ $item['quantity'] }}" wire:change="updateQuantity({{ $index }}, $event.target.value)" class="w-12 text-center p-0.5 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 rounded text-xs font-bold focus:ring-1 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="text-end flex flex-col justify-between h-full space-y-3">
                                <button wire:click="removeFromCart({{ $index }})" class="text-gray-400 hover:text-red-500 self-end opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                <span class="text-sm font-extrabold text-gray-900 dark:text-white">{{ number_format($item['total'], 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-center text-gray-400 py-16 space-y-2">
                            <i data-lucide="shopping-cart" class="w-10 h-10 text-gray-300"></i>
                            <p class="text-sm">{{ __('pos::pos.no_items_in_cart') }}</p>
                        </div>
                    @endforelse
                </div>

                <!-- Summary calculations -->
                <div class="p-4 bg-gray-50 dark:bg-gray-850/50 border-t border-gray-100 dark:border-gray-800 space-y-3 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>{{ __('pos::pos.subtotal') }}</span>
                        <span class="font-bold text-gray-800 dark:text-gray-300">{{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>{{ __('pos::pos.discount') }}</span>
                        <input type="number" step="0.01" wire:model.live="cartDiscount" class="w-20 text-end py-0 px-1 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 rounded text-xs font-bold text-gray-800 dark:text-gray-300 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>{{ __('pos::pos.tax') }} ({{ $cartTaxRate }}%)</span>
                        <span class="font-bold text-gray-800 dark:text-gray-300">{{ number_format($this->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-black text-gray-900 dark:text-white pt-2 border-t border-dashed border-gray-200 dark:border-gray-700">
                        <span>{{ __('pos::pos.total') }}</span>
                        <span>{{ number_format($this->total, 2) }}</span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="p-4 grid grid-cols-2 gap-3 border-t border-gray-100 dark:border-gray-800">
                    <button wire:click="clearCart()" class="py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition-all text-sm">
                        Clear
                    </button>
                    <button wire:click="openCheckout()" class="py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/20 transition-all text-sm">
                        {{ __('pos::pos.pay') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Checkout Modal -->
    @if($showCheckoutModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ __('pos::pos.checkout') }}</h3>
                    <button wire:click="closeCheckout()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="submitCheckout" class="p-6 space-y-4">
                    <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <span class="text-sm font-bold text-gray-500">{{ __('pos::pos.total') }}</span>
                        <span class="text-xl font-black text-blue-600 dark:text-blue-400">{{ number_format($this->total, 2) }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('pos::pos.payment_method') }}</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" wire:click="$set('paymentMethod', 'cash')" class="py-2.5 text-sm font-bold rounded-xl border flex flex-col items-center justify-center gap-1 transition-all {{ $paymentMethod === 'cash' ? 'bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-950/20' : 'bg-transparent border-gray-200 dark:border-gray-700 text-gray-500' }}">
                                <i data-lucide="banknote" class="w-5 h-5"></i>
                                <span>{{ __('pos::pos.cash') }}</span>
                            </button>
                            <button type="button" wire:click="$set('paymentMethod', 'card')" class="py-2.5 text-sm font-bold rounded-xl border flex flex-col items-center justify-center gap-1 transition-all {{ $paymentMethod === 'card' ? 'bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-950/20' : 'bg-transparent border-gray-200 dark:border-gray-700 text-gray-500' }}">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                                <span>{{ __('pos::pos.card') }}</span>
                            </button>
                            <button type="button" wire:click="$set('paymentMethod', 'split')" class="py-2.5 text-sm font-bold rounded-xl border flex flex-col items-center justify-center gap-1 transition-all {{ $paymentMethod === 'split' ? 'bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-950/20' : 'bg-transparent border-gray-200 dark:border-gray-700 text-gray-500' }}">
                                <i data-lucide="arrow-left-right" class="w-5 h-5"></i>
                                <span>{{ __('pos::pos.split') }}</span>
                            </button>
                        </div>
                    </div>

                    @if($paymentMethod === 'cash')
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.amount') }} *</label>
                            <input type="number" step="0.01" wire:model.live="cashAmountPaid" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            @error('cashAmountPaid') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            <div class="mt-2 text-xs font-bold text-gray-500">
                                Change: <span class="text-green-600">{{ number_format(max(0, $cashAmountPaid - $this->total), 2) }}</span>
                            </div>
                        </div>
                    @endif

                    @if($paymentMethod === 'card')
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Transaction Ref Reference</label>
                            <input type="text" wire:model="paymentReference" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                    @endif

                    @if($paymentMethod === 'split')
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Cash Amount Paid *</label>
                                <input type="number" step="0.01" wire:model.live="cashAmountPaid" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                @error('cashAmountPaid') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Card Amount Paid</label>
                                <input type="number" step="0.01" wire:model.live="cardAmountPaid" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>
                        </div>
                    @endif

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                        <button type="button" wire:click="closeCheckout()" class="px-5 py-2 text-sm font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition-all">
                            {{ __('pos::pos.cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all">
                            {{ __('pos::pos.pay') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Receipt Modal -->
    @if($showReceiptModal && $completedSaleData)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-sm w-full border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ __('pos::pos.receipt') }}</h3>
                    <button wire:click="closeReceiptModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <!-- Printable Receipt Content -->
                    <div id="pos-receipt-print" class="bg-white text-black p-4 rounded border font-mono text-xs space-y-4">
                        <div class="text-center space-y-1 border-b pb-4">
                            <h2 class="text-base font-extrabold uppercase">COL3 ERP POS</h2>
                            <p>Terminal: {{ $activeTerminal->name }}</p>
                            <p>Ref: {{ $completedSaleData['code'] }}</p>
                            <p>Date: {{ date('Y-m-d H:i') }}</p>
                        </div>
                        <div class="border-b pb-2">
                            <p class="font-bold">{{ __('pos::pos.sold_to') }}: {{ $completedSaleData['customer_name'] }}</p>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-start pb-1">{{ __('pos::pos.items') }}</th>
                                    <th class="text-center pb-1">{{ __('pos::pos.qty') }}</th>
                                    <th class="text-end pb-1">{{ __('pos::pos.price') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($completedSaleData['items'] as $item)
                                    <tr>
                                        <td class="py-1 max-w-[150px] truncate">{{ $item['name'] }}</td>
                                        <td class="text-center py-1">{{ $item['quantity'] }}</td>
                                        <td class="text-end py-1">{{ number_format($item['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="border-t pt-2 space-y-1">
                            <div class="flex justify-between">
                                <span>{{ __('pos::pos.subtotal') }}</span>
                                <span>{{ number_format($completedSaleData['subtotal'], 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ __('pos::pos.discount') }}</span>
                                <span>-{{ number_format($completedSaleData['discount'], 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ __('pos::pos.tax') }}</span>
                                <span>{{ number_format($completedSaleData['tax'], 2) }}</span>
                            </div>
                            <div class="flex justify-between font-extrabold text-sm border-t pt-1 border-dashed">
                                <span>{{ __('pos::pos.total') }}</span>
                                <span>{{ number_format($completedSaleData['total'], 2) }}</span>
                            </div>
                            @if($completedSaleData['change'] > 0)
                                <div class="flex justify-between font-bold text-xs text-gray-700">
                                    <span>Change</span>
                                    <span>{{ number_format($completedSaleData['change'], 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-850 flex gap-2">
                    <button onclick="window.print()" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition-all text-sm flex items-center justify-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>{{ __('pos::pos.print_receipt') }}</span>
                    </button>
                    <button wire:click="closeReceiptModal()" class="flex-1 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-750 dark:hover:bg-gray-700 text-gray-800 dark:text-white font-bold rounded-xl transition-all text-sm">
                        {{ __('pos::pos.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
