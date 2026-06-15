@extends('layouts.app')

@section('title', 'Laporan Pelunasan Customer')

@section('content')
    <div class="p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-xl font-bold mb-4">Laporan Pelunasan Customer</h2>

        <div class="flex flex-wrap items-center gap-4 mb-6">
            {{-- Tombol Pemicu Modal --}}
            <button onclick="toggleModal(true)"
                style="padding: 6px 16px; background-color: #3b82f6; color: white; font-size: 0.875rem; border-radius: 0.25rem; display: inline-flex; align-items: center;"
                class="hover:bg-blue-600 transition-colors"> Search Data
            </button>
        </div>

        {{-- --- MODAL FILTER POP-UP --- --}}
        <div id="filterModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full p-6" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-semibold">Laporan Pelunasan Customer</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-500 hover:text-gray-800 text-xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('reportingpelunasancustomer.print') }}" target="_blank">
                    <div class="space-y-4">
                        {{-- Cabang / Branch checkboxes --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">Cabang / Branch</label>
                                @if ($isAuthorized)
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selectAllBranches(true)"
                                            class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                                            Select All
                                        </button>
                                        <button type="button" onclick="selectAllBranches(false)"
                                            class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200">
                                            Unselect All
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div id="branchCheckboxesArea" class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($branches as $b)
                                        @php
                                            $isChecked = $isAuthorized || ($userBranchCode === $b->fcabangkode);
                                        @endphp
                                        <label class="flex items-center text-sm cursor-pointer select-none">
                                            @if (!$isAuthorized && $userBranchCode === $b->fcabangkode)
                                                <input type="hidden" name="branch_codes[]" value="{{ $b->fcabangkode }}">
                                            @endif
                                            <input type="checkbox" name="branch_codes[]" value="{{ $b->fcabangkode }}"
                                                class="branch-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                                {{ $isChecked ? 'checked' : '' }}
                                                {{ !$isAuthorized ? 'disabled' : '' }}>
                                            <span class="text-gray-700 font-medium">{{ $b->fcabangkode }} - {{ $b->fcabangname }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Filter Tanggal Dari --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Dari</label>
                                <input type="date" name="date_from" value="{{ $dateFrom }}"
                                    class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            {{-- Filter Tanggal Sampai --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Sampai</label>
                                <input type="date" name="date_to" value="{{ $dateTo }}"
                                    class="mt-1 block w-full border rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <hr class="border-t border-gray-200">

                        <div id="alpineBrowseWrapper" x-data="pelunasanCustomerReport()">
                            <div class="grid grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Account</label>
                                    <div class="flex">
                                        <input type="text" name="account_no" x-model="accountCode" 
                                            class="w-full border border-gray-300 rounded-l px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" readonly>
                                        <button type="button" @click="openAccountBrowse()" 
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r text-gray-500 flex items-center justify-center cursor-pointer" 
                                            title="Browse Account">
                                            <x-heroicon-o-plus class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center mt-6">
                                    <label class="flex items-center text-sm cursor-pointer select-none">
                                        <input type="checkbox" name="only_giro_mundur" value="1" class="mr-2 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-semibold text-gray-700">Hanya Giro Mundur</span>
                                    </label>
                                </div>
                            </div>

                            <hr class="border-t border-gray-200 my-4">

                             <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-medium text-gray-700">Salesman</label>
                                    <label class="flex items-center text-xs cursor-pointer select-none">
                                        <input type="hidden" name="all_salesman" value="0">
                                        <input type="checkbox" name="all_salesman" value="1" x-model="allSalesman" class="mr-1 w-3.5 h-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-semibold text-gray-600">Semua</span>
                                    </label>
                                </div>
                                <div class="flex">
                                    <input type="text" name="salesman" x-model="salesmanCode" 
                                        :class="allSalesman ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-300' : 'focus:ring-blue-500 focus:border-blue-500 border-gray-300'"
                                        class="w-full border rounded-l px-3 py-2 text-sm" :readonly="allSalesman">
                                    <button type="button" @click="openSalesmanBrowse()" :disabled="allSalesman" 
                                        :class="allSalesman ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-300' : 'bg-white hover:bg-gray-50 border-gray-300 text-gray-500 cursor-pointer'"
                                        class="border border-l-0 px-3 py-2 rounded-r flex items-center justify-center" 
                                        title="Browse Salesman">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <hr class="border-t border-gray-200 my-4">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Customer</label>
                                    <div class="flex">
                                        <input type="text" name="customer_from" x-model="customerFromCode" 
                                            class="w-full border border-gray-300 rounded-l px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" readonly>
                                        <button type="button" @click="openCustomerBrowse('from')" 
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r text-gray-500 flex items-center justify-center cursor-pointer" 
                                            title="Browse Customer">
                                            <x-heroicon-o-plus class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sd Customer</label>
                                    <div class="flex">
                                        <input type="text" name="customer_to" x-model="customerToCode" 
                                            class="w-full border border-gray-300 rounded-l px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500" readonly>
                                        <button type="button" @click="openCustomerBrowse('to')" 
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 rounded-r text-gray-500 flex items-center justify-center cursor-pointer" 
                                            title="Browse Customer">
                                            <x-heroicon-o-plus class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 mt-6 pt-4 border-t">
                        {{-- Tombol Reset --}}
                        <a href="{{ route('reportingpelunasancustomer.index') }}"
                            class="px-5 py-2 bg-gray-300 text-gray-800 text-sm font-bold rounded hover:bg-gray-400 transition-colors">
                            Reset
                        </a>
                        {{-- Tombol Preview & Cetak --}}
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition-colors">
                            🖨️ Preview & Print
                        </button>
                    </div>
                </form>
            </div>
        </div>
        {{-- --- END MODAL FILTER POP-UP --- --}}
    </div>

    <x-transaction.browse-account-modal :fend="1" show-controls="true" show-pagination="true" />
    <x-transaction.browse-salesman-modal />
    <x-transaction.browse-customer-modal />

    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    @include('components.transaction.browse-salesman-script')
    @include('components.transaction.browse-customer-script')

    <script>
        // Fungsi untuk mengontrol modal
        function toggleModal(show) {
            const modal = document.getElementById('filterModal');
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function selectAllBranches(status) {
            document.querySelectorAll('#branchCheckboxesArea .branch-checkbox').forEach(checkbox => {
                checkbox.checked = status;
            });
        }

        $(function() {
            // Tampilkan modal otomatis jika belum ada filter
            @if (!$hasFilter)
                toggleModal(true);
            @endif
        });

        function pelunasanCustomerReport() {
            return {
                accountCode: '',
                accountLabel: '',
                allSalesman: true,
                salesmanCode: '',
                salesmanLabel: '',
                activeCustomerField: null,
                customerFromCode: '',
                customerFromLabel: '',
                customerToCode: '',
                customerToLabel: '',

                init() {
                    window.addEventListener('account-picked', (event) => {
                        const detail = event.detail || {};
                        this.accountCode = String(detail.faccount || '').trim();
                        this.accountLabel = this.accountCode && detail.faccname ? `${this.accountCode} - ${detail.faccname}` : this.accountCode;
                    });
                    window.addEventListener('salesman-picked', (event) => {
                        const detail = event.detail || {};
                        this.salesmanCode = String(detail.fsalesmancode || detail.fsalesman || '').trim();
                        this.salesmanLabel = this.salesmanCode && detail.fsalesmanname ? `${this.salesmanCode} - ${detail.fsalesmanname}` : this.salesmanCode;
                        this.allSalesman = false;
                    });
                    window.addEventListener('customer-picked', (event) => {
                        const detail = event.detail || {};
                        const code = String(detail.fcustomercode || '').trim();
                        const label = code && detail.fcustomername ? `${code} - ${detail.fcustomername}` : code;
                        if (this.activeCustomerField === 'from') {
                            this.customerFromCode = code;
                            this.customerFromLabel = label;
                        }
                        if (this.activeCustomerField === 'to') {
                            this.customerToCode = code;
                            this.customerToLabel = label;
                        }
                    });
                },
                openAccountBrowse() {
                    window.dispatchEvent(new CustomEvent('account-browse-open'));
                },
                openSalesmanBrowse() {
                    if (!this.allSalesman) {
                        window.dispatchEvent(new CustomEvent('salesman-browse-open'));
                    }
                },
                openCustomerBrowse(field) {
                    this.activeCustomerField = field;
                    window.dispatchEvent(new CustomEvent('customer-browse-open'));
                },
            };
        }
    </script>
@endsection
