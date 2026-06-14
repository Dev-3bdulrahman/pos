<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
        <div class="space-y-1">
            <h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ __('pos::pos.shifts') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('pos::pos.title') }}</p>
        </div>
        <div>
            <button wire:click="openOpenShiftModal()" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-500/20 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>{{ __('pos::pos.open_shift') }}</span>
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ __('pos::pos.search') }}</label>
            <div class="relative flex items-center">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('pos::pos.search_placeholder') }}" class="w-full ps-10 pe-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.cashier') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.terminal') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.opened_at') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.closed_at') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.opening_balance') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.closing_balance') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.difference') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.status') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">{{ __('pos::pos.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($shifts as $shift)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ $shift->user?->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-550">{{ $shift->terminal?->name }} ({{ $shift->terminal?->code }})</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $shift->opened_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $shift->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-750 dark:text-gray-300">{{ number_format($shift->opening_balance, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-750 dark:text-gray-300">
                                @if($shift->status === 'open')
                                    -
                                @else
                                    {{ number_format($shift->actual_closing_balance, 2) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold {{ $shift->difference < 0 ? 'text-red-500' : ($shift->difference > 0 ? 'text-green-600' : 'text-gray-500') }}">
                                @if($shift->status === 'open')
                                    -
                                @else
                                    {{ number_format($shift->difference, 2) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $shift->status === 'open' ? 'bg-green-50 text-green-700 dark:bg-green-900/20' : 'bg-gray-100 text-gray-500 dark:bg-gray-800' }}">
                                    {{ __('pos::pos.status_' . $shift->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($shift->status === 'open')
                                        <button wire:click="openMovementModal({{ $shift->id }})" title="{{ __('pos::pos.log_movement') }}" class="p-1.5 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-all">
                                            <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                                        </button>
                                        <button wire:click="openCloseShiftModal({{ $shift->id }})" title="{{ __('pos::pos.close_shift') }}" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 dark:bg-red-950/20 dark:text-red-400 font-bold text-xs rounded-lg transition-all">
                                            {{ __('pos::pos.close_shift') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('pos::pos.no_shifts') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($shifts->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">{{ $shifts->links() }}</div>
        @endif
    </div>

    <!-- Open Shift Modal -->
    @if($showOpenShiftModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ __('pos::pos.open_shift') }}</h3>
                    <button wire:click="closeOpenShiftModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="submitOpenShift" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.terminal') }} *</label>
                        <select wire:model="openTerminalId" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- {{ __('pos::pos.terminal') }} --</option>
                            @foreach($terminals as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                            @endforeach
                        </select>
                        @error('openTerminalId') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.opening_balance') }} *</label>
                        <input type="number" step="0.01" wire:model="openingBalance" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('openingBalance') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                        <button type="button" wire:click="closeOpenShiftModal()" class="px-5 py-2 text-sm font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition-all">
                            {{ __('pos::pos.cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all">
                            {{ __('pos::pos.open_shift') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Close Shift Modal -->
    @if($showCloseShiftModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ __('pos::pos.close_shift') }}</h3>
                    <button wire:click="closeCloseShiftModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="submitCloseShift" class="p-6 space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ __('pos::pos.expected_closing_balance') }}</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($expectedClosingBalance, 2) }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.actual_closing_balance') }} *</label>
                        <input type="number" step="0.01" wire:model="actualClosingBalance" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('actualClosingBalance') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.notes') }}</label>
                        <textarea wire:model="closeNotes" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                        @error('closeNotes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                        <button type="button" wire:click="closeCloseShiftModal()" class="px-5 py-2 text-sm font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition-all">
                            {{ __('pos::pos.cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 text-sm font-bold bg-red-600 hover:bg-red-750 text-white rounded-xl shadow-lg transition-all">
                            {{ __('pos::pos.close_shift') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Drawer Adjustment Modal -->
    @if($showMovementModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ __('pos::pos.cash_movement') }}</h3>
                    <button wire:click="closeMovementModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="submitMovement" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.payment_method') }} *</label>
                        <select wire:model="movementType" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="cash_in">{{ __('pos::pos.cash_in') }}</option>
                            <option value="cash_out">{{ __('pos::pos.cash_out') }}</option>
                        </select>
                        @error('movementType') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.amount') }} *</label>
                        <input type="number" step="0.01" wire:model="movementAmount" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('movementAmount') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.reason') }} *</label>
                        <input type="text" wire:model="movementReason" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('movementReason') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                        <button type="button" wire:click="closeMovementModal()" class="px-5 py-2 text-sm font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition-all">
                            {{ __('pos::pos.cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all">
                            {{ __('pos::pos.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
