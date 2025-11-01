@extends('layouts.app')

@section('title', 'Master Product')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) {
            this.deleteUrl = url;
            this.showDeleteModal = true;
        },
        closeDelete() {
            this.showDeleteModal = false;
            this.deleteUrl = null;
        }
    }" x-on:open-delete.window="openDelete($event.detail)" class="bg-white rounded shadow p-4">

        <div x-data="{
            activeTable: 'null',
            toggleTable(table) { this.activeTable = table; }
        }" class="bg-white rounded shadow p-4">
        
            @if ($message = Session::get('danger'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ $message }}</span>
                </div>
                <br>
            @endif

            @php
                $canCreate = in_array('createProduct', explode(',', session('user_restricted_permissions', '')));
                $canEdit = in_array('updateProduct', explode(',', session('user_restricted_permissions', '')));
                $canDelete = in_array('deleteProduct', explode(',', session('user_restricted_permissions', '')));
                $showActionsColumn = $canEdit || $canDelete;
            @endphp

            <div class="flex justify-end items-center mb-4">
                <div></div>

                @if ($canCreate)
                    <a href="{{ route('product.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                    </a>
                @endif
            </div>

            <div id="statusFilterTemplate" class="hidden">
                <div class="flex items-center gap-2" id="statusFilterWrap">
                    <span class="text-sm text-gray-700">Status</span>
                    <select data-role="status-filter" class="border rounded px-2 py-1">
                        <option value="all">All</option>
                        <option value="active" selected>Active</option>
                        <option value="nonactive">Non Active</option>
                    </select>
                </div>
            </div>

            {{-- Table Data Produk --}}
            <table id="productTable" class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-2">Kode Product</th>
                        <th class="border px-2 py-2">Nama Product</th>
                        <th class="border px-2 py-2 no-sort">Satuan</th>
                        <th class="border px-2 py-2 no-sort">Stok</th>
                        <th class="border px-2 py-2 no-sort">Status</th>
                        <th class="border px-2 py-2" data-col="statusRaw">StatusRaw</th>
                        @if ($showActionsColumn)
                            <th class="border px-2 py-1 col-aksi">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @forelse($products as $item)
                        <tr class="hover:bg-gray-50">
                            <td>{{ $item->fprdcode }}</td>
                            <td>{{ $item->fprdname }}</td>
                            <td>{{ $item->fsatuankecil }}</td>
                            <td>{{ $item->fminstock }}</td>
                            <td>
                                @php $isActive = (string)$item->fnonactive === '0'; @endphp
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                    {{ $isActive ? 'Active' : 'Non Active' }}
                                </span>
                            </td>
                            <td>{{ (string) $item->fnonactive }}</td>
                            @if ($showActionsColumn)
                                <td class="border px-2 py-1 space-x-2">
                                    @if ($canEdit)
                                        <a href="{{ route('product.edit', $item->fprdid) }}">
                                            <button
                                                class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                                <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                            </button>
                                        </a>
                                    @endif
                                    @if ($canDelete)
                                        <button @click="openDelete('{{ route('product.destroy', $item->fprdid) }}')"
                                            class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showActionsColumn ? 5 : 4 }}" class="text-center py-4">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Tombol untuk menampilkan Tabel Stok --}}
            <div class="mt-4 flex justify-start space-x-2">
                <button @click="toggleTable('stok')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Lihat Stok
                </button>
                <button @click="toggleTable('customer')"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Lihat Customer
                </button>
                <button @click="toggleTable('supplier')"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Lihat Supplier
                </button>

            </div>

            <!-- Tabel Stok -->
            <div x-show="activeTable === 'stok'" x-cloak class="mt-4">
                <h3 class="font-semibold text-xl mb-3">Tabel Stok untuk Produk</h3>
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Gudang#</th>
                            <th class="border px-2 py-1">Nama Gudang</th>
                            <th class="border px-2 py-1">Stok</th>
                            <th class="border px-2 py-1">Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr>
                                <td class="border px-2 py-1">{{ $item->fprdcode }}</td>
                                <td class="border px-2 py-1">{{ $item->fprdname }}</td>
                                <td class="border px-2 py-1">{{ $item->fstok }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tabel Customer -->
            <div x-show="activeTable === 'customer'" x-cloak class="mt-4">
                <h3 class="font-semibold text-xl mb-3">Tabel Customer untuk Produk</h3>
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Faktur#</th>
                            <th class="border px-2 py-1">Customer</th>
                            <th class="border px-2 py-1">Tanggal Jual</th>
                            <th class="border px-2 py-1">Harga Jual</th>
                            <th class="border px-2 py-1">Qty</th>
                            <th class="border px-2 py-1">Satuan</th>
                            <th class="border px-2 py-1">Ref.PO</th>
                            <th class="border px-2 py-1">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr>
                                <td class="border px-2 py-1">{{ $item->fprdcode }}</td>
                                <td class="border px-2 py-1">{{ $item->fprdname }}</td>
                                <td class="border px-2 py-1">{{ $item->fstok }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tabel Supplier -->
            <div x-show="activeTable === 'supplier'" x-cloak class="mt-4">
                <h3 class="font-semibold text-xl mb-3">Tabel Supplier untuk Produk</h3>
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Nama Supplier</th>
                            <th class="border px-2 py-1">Tanggal Beli</th>
                            <th class="border px-2 py-1">Harga Beli</th>
                            <th class="border px-2 py-1">Qty</th>
                            <th class="border px-2 py-1">Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr>
                                <td class="border px-2 py-1">{{ $item->fprdcode }}</td>
                                <td class="border px-2 py-1">{{ $item->fprdname }}</td>
                                <td class="border px-2 py-1">{{ $item->fstok }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                                <td class="border px-2 py-1">{{ $item->fsatuan }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{--  Modal Delete  --}}
            <div x-show="showDeleteModal" x-cloak
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                    <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                    <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>

                    <div class="flex justify-end space-x-2">
                        <button @click="closeDelete()"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                        <form :action="deleteUrl" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
        <style>
            /* Tata letak kontrol */
            .dt-container .dt-length,
            .dt-container .dt-search {
                display: flex;
                align-items: center;
                gap: .5rem;
            }

            .dt-container .dt-length .dt-input {
                width: 4.5rem;
                padding: .35rem .5rem;
            }

            /* Stabilkan tabel */
            #productTable {
                width: 100% !important;
            }

            #productTable th,
            #productTable td {
                text-align: left !important;
                vertical-align: middle;
            }

            /* Kolom Aksi: jangan mepet, tapi tetap ringkas */
            #productTable th:last-child,
            #productTable td:last-child {
                white-space: nowrap;
                text-align: center;
            }

            #productTable td:last-child {
                padding: .25rem .5rem;
            }

            .btn-aksi {
                padding: .25rem .5rem;
                font-size: .825rem;
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

            .dataTables_wrapper .dt-search {
                display: flex;
                align-items: center;
                gap: .75rem;
                flex-wrap: wrap;
            }

            #statusFilterWrap {
                margin-right: .25rem;
            }
        </style>
    @endpush

    @push('scripts')
        {{-- jQuery + DataTables JS (CDN) --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                /* no-op */
            });

            $(function() {
                // Inisialisasi DataTables
                const hasActions = {{ $showActionsColumn ? 'true' : 'false' }};
                const columns = hasActions ? [{
                        title: 'Kode Product'
                    },
                    {
                        title: 'Nama Product'
                    },
                    {
                        title: 'Satuan'
                    },
                    {
                        title: 'Stok'
                    },
                    {
                        title: 'Aksi',
                        orderable: false,
                        searchable: false
                    }
                ] : [{
                        title: 'Kode Product'
                    },
                    {
                        title: 'Nama Product'
                    },
                    {
                        title: 'Satuan'
                    },
                    {
                        title: 'Stok'
                    },
                ];

                $('#productTable').DataTable({
                    autoWidth: false,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [
                        [0, 'asc']
                    ],
                    layout: {
                        topStart: 'search', // Search pindah ke kiri
                        topEnd: 'pageLength', // Length menu pindah ke kanan
                        bottomStart: 'info',
                        bottomEnd: 'paging'
                    },
                    columnDefs: [{
                            targets: 'col-aksi',
                            orderable: false,
                            searchable: false,
                            width: 120
                        },
                        {
                            targets: 'no-sort',
                            orderable: false
                        }
                    ],
                    language: {
                        lengthMenu: "Show _MENU_ entries"
                    },
                    initComplete: function() {
                        const api = this.api();

                        const $toolbarSearch = $(api.table().container()).find('.dt-search');
                        const $filter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);

                        const $select = $filter.find('select[data-role="status-filter"]');
                        $select.attr('id', 'statusFilterDT');

                        $toolbarSearch.append($filter);

                        const statusRawIdx = api.columns().indexes().toArray()
                            .find(i => $(api.column(i).header()).attr('data-col') === 'statusRaw');

                        if (statusRawIdx === undefined) {
                            console.warn('Kolom StatusRaw tidak ditemukan.');
                            return;
                        }

                        api.column(statusRawIdx).visible(false);

                        const $searchInput = $toolbarSearch.find('.dt-input');
                        $searchInput.css({
                            width: '400px',
                            maxWidth: '100%'
                        });


                        $select.on('change', function() {
                            const v = this.value;
                            if (v === 'active') {
                                api.column(statusRawIdx).search('^0$', true, false).draw();
                            } else if (v === 'nonactive') {
                                api.column(statusRawIdx).search('^1$', true, false).draw();
                            } else {
                                api.column(statusRawIdx).search('', false, false)
                                    .draw();
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
