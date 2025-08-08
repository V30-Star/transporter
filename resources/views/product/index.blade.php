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
    }" class="bg-white rounded shadow p-4">
        <div x-data="{
            activeTable: 'null', // Default tampilkan tabel stok
            toggleTable(table) {
                this.activeTable = table; // Ganti tabel aktif sesuai pilihan
            }
        }" class="bg-white rounded shadow p-4">

            {{--  Search & Filter Form  --}}
            <form method="GET" action="{{ route('product.index') }}"
                class="flex flex-wrap justify-between items-center mb-4 gap-2">
                <div class="flex items-center space-x-2 w-full">
                    <label class="font-semibold">Search:</label>
                    <input type="text" name="search" value="{{ $search }}" class="border rounded px-2 py-1 w-1/4"
                        placeholder="Cari...">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cari</button>
                </div>
            </form>

            @php
                $canCreate = !in_array('createProduct', explode(',', session('user_restricted_permissions', '')));
                $canEdit = !in_array('updateProduct', explode(',', session('user_restricted_permissions', '')));
                $canDelete = !in_array('deleteProduct', explode(',', session('user_restricted_permissions', '')));
                $showActionsColumn = $canEdit || $canDelete;
            @endphp

            {{--  Table Data Produk --}}
            <table class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">Kode Product</th>
                        <th class="border px-2 py-1">Nama Product</th>
                        <th class="border px-2 py-1">Tipe</th>
                        <th class="border px-2 py-1">Status (Nonaktifkan)</th>
                        <th class="border px-2 py-1">Tanggal Dibuat</th>
                        <th class="border px-2 py-1">Dibuat Oleh</th>
                        @if ($showActionsColumn)
                            <th class="border px-2 py-1">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-1">{{ $item->fproductcode }}</td>
                            <td class="border px-2 py-1">{{ $item->fproductname }}</td>
                            <td class="border px-2 py-1">{{ $item->ftype }}</td>
                            <td class="border px-2 py-1">
                                <input type="checkbox" disabled {{ $item->fnonactive == '1' ? 'checked' : '' }}>
                            </td>
                            <td class="border px-2 py-1">{{ \Carbon\Carbon::parse($item->fcreatedat)->format('d M Y H:i') }}
                            </td>
                            <td class="border px-2 py-1">{{ $item->fcreatedby }}</td>
                            @if ($showActionsColumn)
                                <td class="border px-2 py-1 space-x-2">
                                    @if ($canEdit)
                                        <a href="{{ route('product.edit', $item->fproductid) }}">
                                            <button
                                                class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                                <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                                                Edit
                                            </button>
                                        </a>
                                    @endif

                                    @if ($canDelete)
                                        <button @click="openDelete('{{ route('product.destroy', $item->fproductid) }}')"
                                            class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                            Hapus
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4 flex justify-between items-center">
                <div class="space-x-2">
                    @if ($canCreate)
                        <a href="{{ route('product.create') }}"
                            class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                            Baru
                        </a>
                    @endif
                </div>
                <div class="flex items-center space-x-2">
                    <button class="px-3 py-1 rounded border hover:bg-gray-100 disabled:opacity-50" disabled>
                        &larr;
                    </button>
                    <span class="text-sm">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
                    <button class="px-3 py-1 rounded border hover:bg-gray-100"
                        {{ $products->hasMorePages() ? '' : 'disabled' }}>
                        &rarr;
                    </button>
                </div>
            </div>

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
                                <td class="border px-2 py-1">{{ $item->fproductcode }}</td>
                                <td class="border px-2 py-1">{{ $item->fproductname }}</td>
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
                                <td class="border px-2 py-1">{{ $item->fproductcode }}</td>
                                <td class="border px-2 py-1">{{ $item->fproductname }}</td>
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
                                <td class="border px-2 py-1">{{ $item->fproductcode }}</td>
                                <td class="border px-2 py-1">{{ $item->fproductname }}</td>
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

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    @endsection
