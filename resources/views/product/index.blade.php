@extends('layouts.app')

@section('title', 'Master Product')

@section('content')
    @php
        $canCreate = in_array('createProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteProduct', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
    @endphp

    <div x-data class="bg-white rounded shadow p-4">

        @if ($message = Session::get('danger'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ $message }}</span>
            </div>
        @endif

        {{-- Tombol Tambah Baru --}}
        <div class="flex justify-end items-center mb-4">
            @if ($canCreate)
                <a href="{{ route('product.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Template Filter Status (hidden, akan di-clone ke toolbar DataTables) --}}
        <div id="statusFilterTemplate" style="display: none;">
            <div class="flex items-center gap-2" id="statusFilterWrap">
                <span class="text-sm text-gray-700">Status</span>
                <select data-role="status-filter" class="border rounded px-2 py-1">
                    <option value="all">All</option>
                    <option value="active" selected>Active</option>
                    <option value="nonactive">Non Active</option>
                </select>
            </div>
        </div>

        {{-- Tabel — tbody kosong, data dimuat via AJAX --}}
        <table id="productTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2">
                        <div class="flex items-center justify-between">
                            <span>Kode Product</span>
                            <div class="flex items-center gap-1">
                                {{-- <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded" data-column="0" title="Filter Kolom">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button> --}}
                                <span class="sort-icon cursor-pointer" data-column="0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text" class="dt-column-search w-full px-2 py-1.5 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500" data-column="0" placeholder="Cari...">
                        </div>
                    </th>
                    <th class="border px-3 py-2">
                        <div class="flex items-center justify-between">
                            <span>Nama Product</span>
                            <div class="flex items-center gap-1">
                                {{-- <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded" data-column="1" title="Filter Kolom">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button> --}}
                                <span class="sort-icon cursor-pointer" data-column="1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text" class="dt-column-search w-full px-2 py-1.5 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500" data-column="1" placeholder="Cari...">
                        </div>
                    </th>
                    <th class="border px-3 py-2">
                        <div class="flex items-center justify-between">
                            <span>Merek</span>
                            <div class="flex items-center gap-1">
                                <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded" data-column="2" title="Filter Kolom">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                                <span class="sort-icon cursor-pointer" data-column="2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text" class="dt-column-search w-full px-2 py-1.5 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500" data-column="2" placeholder="Cari...">
                        </div>
                    </th>
                    <th class="border px-3 py-2 no-sort">
                        <div class="flex items-center justify-between">
                            <span>Satuan</span>
                            {{-- <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded" data-column="3" title="Filter Kolom">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button> --}}
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text" class="dt-column-search w-full px-2 py-1.5 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500" data-column="3" placeholder="Cari...">
                        </div>
                    </th>
                    <th class="border px-3 py-2 no-sort">
                        <div class="flex items-center justify-between">
                            <span>Stok</span>
                            {{-- <button type="button" class="col-search-btn p-1 hover:bg-gray-200 rounded" data-column="4" title="Filter Kolom">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button> --}}
                        </div>
                        <div class="col-search-input mt-2 hidden">
                            <input type="text" class="dt-column-search w-full px-2 py-1.5 border border-gray-300 rounded text-sm uppercase focus:outline-none focus:ring-1 focus:ring-blue-500" data-column="4" placeholder="Cari...">
                        </div>
                    </th>
                    <th class="border px-3 py-2 no-sort">
                        <div class="flex items-center justify-between">
                            <span>Status</span>
                        </div>
                    </th>
                    @if ($showActionsColumn)
                        <th class="border px-3 py-2 col-aksi no-sort">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        {{-- Modal Konfirmasi Hapus --}}
        <div x-show="$store.productStore.showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="!$store.productStore.isDeleting && $store.productStore.closeDelete()"
                class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="$store.productStore.closeDelete()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        :disabled="$store.productStore.isDeleting">Batal</button>
                    <button @click="$store.productStore.confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="$store.productStore.isDeleting">
                        <span x-show="!$store.productStore.isDeleting">Hapus</span>
                        <span x-show="$store.productStore.isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Laporan --}}
        <div x-show="$store.laporanStore.showModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-transition>
            <div @click.away="$store.laporanStore.closeModal()"
                class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Laporan Product</h3>
                    <button @click="$store.laporanStore.closeModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="px-6 py-2 border-b bg-gray-50">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="$store.laporanStore.activeTab = 'customer'"
                            :class="{'border-blue-500 text-blue-600': $store.laporanStore.activeTab === 'customer', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': $store.laporanStore.activeTab !== 'customer'}"
                            class="whitespace-nowrap pb-2 px-1 border-b-2 font-medium text-sm">Penjualan</button>
                        
                        <button @click="$store.laporanStore.activeTab = 'stok'"
                            :class="{'border-blue-500 text-blue-600': $store.laporanStore.activeTab === 'stok', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': $store.laporanStore.activeTab !== 'stok'}"
                            class="whitespace-nowrap pb-2 px-1 border-b-2 font-medium text-sm">Stok</button>
                            
                        <button @click="$store.laporanStore.activeTab = 'supplier'"
                            :class="{'border-blue-500 text-blue-600': $store.laporanStore.activeTab === 'supplier', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': $store.laporanStore.activeTab !== 'supplier'}"
                            class="whitespace-nowrap pb-2 px-1 border-b-2 font-medium text-sm">Pembelian</button>
                    </nav>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <div x-show="$store.laporanStore.isLoading" class="flex justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <div x-show="!$store.laporanStore.isLoading">
                        <div x-show="$store.laporanStore.activeTab === 'customer'">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Faktur#</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Customer</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Tanggal Jual</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Harga Jual</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Qty.</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Satuan</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Ref.PO</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, i) in $store.laporanStore.customerData" :key="i">
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fsono"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fcustomername"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fsodate"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="Number(item.fprice).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="Number(item.fqty).toLocaleString('id-ID', { minimumFractionDigits: 0 })"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="item.fsatuan"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="item.fsono"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 truncate max-w-xs" :title="item.fdesc" x-text="item.fdesc || '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="$store.laporanStore.customerData.length === 0">
                                        <td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada riwayat Laporan SO dari Customer.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div x-show="$store.laporanStore.activeTab === 'stok'">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Gudang#</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Nama Gudang</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Stok</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="item in $store.laporanStore.stokData" :key="item.fwhcode">
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fwhcode"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fwhname"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="(Number(item.fsaldo) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fsatuanbesar"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="$store.laporanStore.stokData.length === 0">
                                        <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada data stok.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div x-show="$store.laporanStore.activeTab === 'supplier'">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Faktur#</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Supplier</th>
                                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-800 uppercase">Tanggal</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Harga</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Qty.</th>
                                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-800 uppercase">Satuan</th>
                                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-800 uppercase">Ccy</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, i) in $store.laporanStore.supplierData" :key="i">
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fstockmtno"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fsuppliername"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900" x-text="item.fstockmtdate"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="Number(item.fprice).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="Number(item.fqty).toLocaleString('id-ID', { minimumFractionDigits: 0 })"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-right" x-text="item.fsatuan"></td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 text-center" x-text="item.fcurrency"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="$store.laporanStore.supplierData.length === 0">
                                        <td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500">Tidak ada riwayat Laporan Pembelian/ADJ dari Supplier.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Toast Notifikasi --}}
        <div x-show="$store.productStore.showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
            <div :class="$store.productStore.notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
                class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
                <span x-text="$store.productStore.notificationMessage"></span>
                <button @click="$store.productStore.showNotification = false"
                    class="ml-4 text-white hover:text-gray-200">×</button>
            </div>
        </div>

    </div>
@endsection


@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        .dt-container .dt-length .dt-input {
            width: 4.5rem;
            padding: .35rem .5rem;
        }

        #productTable {
            width: 100% !important;
        }

        #productTable th,
        #productTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #productTable th:last-child,
        #productTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        #productTable td:last-child {
            padding: .25rem .5rem;
        }

        .dt-container .dt-search {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dt-container .dt-search .dt-input {
            width: 300px;
            text-transform: uppercase;
        }

        #statusFilterWrap {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0;
        }

        .col-search-btn {
            transition: background-color 0.15s;
        }

        .col-search-btn:hover svg {
            color: #2563eb;
        }

        .sort-icon {
            transition: color 0.15s;
        }
    </style>
@endpush


@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        // =============================================
        // Alpine.js — Store Delete (pakai $store karena
        // tombol Hapus di-render oleh DataTables JS,
        // bukan oleh Blade, sehingga x-data biasa tidak bisa)
        // =============================================
        document.addEventListener('alpine:init', () => {
            Alpine.store('productStore', {
                showDeleteModal: false,
                deleteUrl: '',
                isDeleting: false,
                currentRow: null,
                showNotification: false,
                notificationMessage: '',
                notificationType: 'success',

                openDelete(url, rowEl) {
                    this.deleteUrl = url;
                    this.currentRow = rowEl;
                    this.showDeleteModal = true;
                    this.isDeleting = false;
                },

                closeDelete() {
                    if (!this.isDeleting) {
                        this.showDeleteModal = false;
                        this.deleteUrl = '';
                        this.currentRow = null;
                    }
                },

                confirmDelete() {
                    this.isDeleting = true;
                    const rowToDelete = this.currentRow;

                    fetch(this.deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json().then(data => ({
                            ok: response.ok,
                            data
                        })))
                        .then(result => {
                            this.showDeleteModal = false;
                            this.isDeleting = false;
                            this.currentRow = null;

                            if (result.ok) {
                                // Server-side: cukup reload DataTables
                                $('#productTable').DataTable().ajax.reload(null, false);
                                this.notify('success', result.data.message || 'Data berhasil dihapus');
                            } else {
                                this.notify('error', result.data.message || 'Gagal menghapus data');
                            }
                        })
                        .catch(() => {
                            this.showDeleteModal = false;
                            this.isDeleting = false;
                            this.notify('error', 'Terjadi kesalahan. Silakan coba lagi.');
                        });
                },

                notify(type, message) {
                    this.notificationType = type;
                    this.notificationMessage = message;
                    this.showNotification = true;
                    setTimeout(() => {
                        this.showNotification = false;
                    }, 3000);
                }
            });

            Alpine.store('laporanStore', {
                showModal: false,
                isLoading: false,
                activeTab: 'customer',
                stokData: [],
                customerData: [],
                supplierData: [],
                
                openModal(fprdid) {
                    this.showModal = true;
                    this.activeTab = 'customer';
                    this.loadData(fprdid);
                },
                
                closeModal() {
                    this.showModal = false;
                    this.stokData = [];
                    this.customerData = [];
                    this.supplierData = [];
                },
                
                loadData(fprdid) {
                    this.isLoading = true;
                    fetch(`/master/product/${fprdid}/laporan`)
                        .then(res => res.json())
                        .then(data => {
                            this.stokData = data.stok || [];
                            this.customerData = data.customer || [];
                            this.supplierData = data.supplier || [];
                            this.isLoading = false;
                        })
                        .catch(err => {
                            console.error('Error fetching laporan:', err);
                            this.isLoading = false;
                        });
                }
            });
        });


        // =============================================
        // jQuery — Inisialisasi DataTables (server-side)
        // =============================================
        $(function() {
            const canEdit = {{ $canEdit ? 'true' : 'false' }};
            const canDelete = {{ $canDelete ? 'true' : 'false' }};
            const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};

            // ------------------------------------------
            // Definisi kolom
            // ------------------------------------------
            const columns = [{
                    data: 'fprdcode',
                    name: 'fprdcode',
                    searchable: true,
                    orderable: false
                },
                {
                    data: 'fprdname',
                    name: 'fprdname',
                    searchable: true,
                    orderable: false
                },
                {
                    data: 'fmerek',
                    name: 'fmerek',
                    searchable: true,
                    orderable: false
                },
                {
                    data: 'fsatuankecil',
                    name: 'fsatuankecil',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'fminstock',
                    name: 'fminstock',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
            ];

            if (hasActions) {
                columns.push({
                    data: 'fprdid',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(fprdid) {
                        let html = '<div class="space-x-2">';

                        const viewUrl = `{{ config('app.url') }}/master/product/${fprdid}/view`;

                        html += `<a href="${viewUrl}" class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg> View
                        </a>`;

                        html += `
                            <button onclick="openLaporanModal('${fprdid}')" class="inline-flex items-center bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Laporan
                            </button>
                        `;

                        if (canEdit) {
                            html += `
                                <a href="{{ config('app.url') }}/master/product/${fprdid}/edit">
                                    <button class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </button>
                                </a>`;
                        }

                        if (canDelete) {
                            const deleteUrl = '/master/product/' + fprdid;
                            html += `
                                <button onclick="openProductDelete('${deleteUrl}', this)"
                                    class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Hapus
                                </button>`;
                        }

                        html += '</div>';
                        return html;
                    }
                });
            }

            // ------------------------------------------
            // Inisialisasi DataTables server-side
            // ------------------------------------------
            const table = $('#productTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('product.index') }}',
                    type: 'GET',
                    data: function(d) {
                        // Kirim nilai filter status ke server
                        d.status = $('#statusFilterDT').val() || 'active';
                    }
                },
                columns: columns,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging',
                },
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    processing: 'Loading...',
                },
                drawCallback: function(settings) {
                    if (!this.api().settings()[0]._sortApplied) {
                        this.api().settings()[0]._sortApplied = true;
                        this.api().order([0, 'asc']).draw();
                        updateSortIcons(0, 'asc');
                    }
                }
            });

            // ------------------------------------------
            // Clone template filter Status ke toolbar Search
            // ------------------------------------------
            const $container = $(table.table().container());
            const $toolbarSearch = $container.find('.dt-search');

            const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
            const $select = $filter.find('select[data-role="status-filter"]');
            $select.attr('id', 'statusFilterDT');
            $toolbarSearch.after($filter);

            // Event: saat dropdown berubah, reload AJAX dengan status baru
            $select.on('change', function() {
                table.ajax.reload();
            });

            // Force uppercase for global search input
            $toolbarSearch.find('.dt-input').on('input', function() {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(start, end);
            });

            // ------------------------------------------
            // Per-Column Search Toggle
            // ------------------------------------------
            $container.on('click', '.col-search-btn', function(e) {
                e.stopPropagation();
                const columnIndex = $(this).data('column');
                const $th = $(this).closest('th');
                const $searchInput = $th.find('.col-search-input');
                
                // Close other open search inputs
                $('.col-search-input').not($searchInput).addClass('hidden');
                
                // Toggle current search input
                $searchInput.toggleClass('hidden');
                
                // Focus input if shown
                if (!$searchInput.hasClass('hidden')) {
                    $searchInput.find('input').focus();
                }
            });

            // Close search inputs when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.col-search-btn').length && !$(e.target).closest('.col-search-input').length) {
                    $('.col-search-input').addClass('hidden');
                }
            });

            // ------------------------------------------
            // Column Search - search on input
            // ------------------------------------------
            $container.on('input', '.dt-column-search', function() {
                const columnIndex = $(this).data('column');
                const searchValue = $(this).val();
                table.column(columnIndex).search(searchValue).draw();
            });

            // ------------------------------------------
            // Custom Sort - click on sort icon
            // ------------------------------------------
            let sortState = { column: 0, direction: 'asc' };

            $container.on('click', '.sort-icon', function(e) {
                e.stopPropagation();
                const columnIndex = $(this).data('column');
                
                // Toggle direction if same column, otherwise default to asc
                if (sortState.column === columnIndex) {
                    sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState.column = columnIndex;
                    sortState.direction = 'asc';
                }
                
                // Apply sort to DataTables
                table.order([sortState.column, sortState.direction]);
                table.draw();
                
                // Update sort icons
                updateSortIcons(sortState.column, sortState.direction);
            });

            function updateSortIcons(activeColumn, direction) {
                $('.sort-icon').each(function() {
                    const $icon = $(this).find('svg');
                    const colIndex = $(this).data('column');
                    
                    if (colIndex === activeColumn) {
                        if (direction === 'asc') {
                            $icon.attr('d', 'M5 15l7-7 7 7');
                        } else {
                            $icon.attr('d', 'M19 9l-7 7-7-7');
                        }
                        $icon.removeClass('text-gray-400').addClass('text-blue-600');
                    } else {
                        $icon.attr('d', 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4');
                        $icon.removeClass('text-blue-600').addClass('text-gray-400');
                    }
                });
            }
        });

        // Fungsi global untuk tombol Hapus yang di-render oleh DataTables JS
        // (tidak bisa pakai Alpine @click karena elemen dibuat dinamis)
        function openProductDelete(url, btnEl) {
            const row = btnEl.closest('tr');
            Alpine.store('productStore').openDelete(url, row);
        }

        function openLaporanModal(fprdid) {
            if (Alpine.store('laporanStore')) {
                Alpine.store('laporanStore').openModal(fprdid);
            }
        }
    </script>
@endpush
