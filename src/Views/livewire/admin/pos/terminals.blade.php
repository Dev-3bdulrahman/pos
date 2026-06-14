<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
        <div class="space-y-1">
            <h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ __('pos::pos.terminals') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('pos::pos.title') }}</p>
        </div>
        <div>
            <button wire:click="openTerminalModal()" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-500/20 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>{{ __('pos::pos.add_terminal') }}</span>
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
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.terminal_name') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.terminal_code') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.warehouse') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('pos::pos.status') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">{{ __('pos::pos.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($terminals as $terminal)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ $terminal->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $terminal->code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-750 dark:text-gray-300">{{ $terminal->warehouse?->translated_name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $terminal->status === 'active' ? 'bg-green-50 text-green-700 dark:bg-green-900/20' : 'bg-gray-100 text-gray-500 dark:bg-gray-800' }}">
                                    {{ __('pos::pos.' . $terminal->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openTerminalModal({{ $terminal->id }})" title="{{ __('pos::pos.edit') }}" class="p-1.5 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-all">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="confirmTerminalDelete({{ $terminal->id }})" title="{{ __('pos::pos.delete') }}" class="p-1.5 text-red-500 hover:text-red-700 rounded-lg hover:bg-red-50 transition-all">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('pos::pos.no_terminals') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($terminals->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">{{ $terminals->links() }}</div>
        @endif
    </div>

    <!-- Terminal Create/Edit Modal -->
    @if($showTerminalModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden animate__animated animate__fadeInUp animate__faster">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">
                        {{ $terminalId ? __('pos::pos.edit_terminal') : __('pos::pos.add_terminal') }}
                    </h3>
                    <button wire:click="closeTerminalModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form wire:submit.prevent="saveTerminal" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.terminal_name') }} *</label>
                        <input type="text" wire:model="terminalName" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('terminalName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.terminal_code') }} *</label>
                        <input type="text" wire:model="terminalCode" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        @error('terminalCode') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.warehouse') }}</label>
                        <select wire:model="warehouseId" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">-- {{ __('pos::pos.select_warehouse') }} --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->translated_name }}</option>
                            @endforeach
                        </select>
                        @error('warehouseId') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('pos::pos.status') }}</label>
                        <select wire:model="terminalStatus" class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="active">{{ __('pos::pos.active') }}</option>
                            <option value="inactive">{{ __('pos::pos.inactive') }}</option>
                        </select>
                        @error('terminalStatus') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                        <button type="button" wire:click="closeTerminalModal()" class="px-5 py-2 text-sm font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl transition-all">
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

<script>
    function confirmTerminalDelete(id) {
        window.dispatchEvent(new CustomEvent('swal:confirm', {
            detail: {
                title: '{{ __('pos::pos.confirm_delete_terminal') }}',
                text: '{{ __('pos::pos.delete_confirm_text') }}',
                icon: 'warning',
                confirmButtonText: '{{ __('pos::pos.delete') }}',
                cancelButtonText: '{{ __('pos::pos.cancel') }}',
                onConfirm: 'deleteTerminal',
                params: [id]
            }
        }));
    }
</script>
