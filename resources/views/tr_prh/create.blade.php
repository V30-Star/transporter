@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 34px
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background: #fff;
            transition: .4s
        }

        input:checked+.slider {
            background: #4CAF50
        }

        input:checked+.slider:before {
            transform: translateX(26px)
        }

        .slider.round {
            border-radius: 34px
        }

        .slider.round:before {
            border-radius: 50%
        }

        [x-cloak] {
            display: none !important
        }

        /* Hilangkan caret bawaan select (termasuk yg disable) */
        #supplierSelect,
        #supplierSelect:disabled {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
        }

        #supplierSelect::-ms-expand {
            display: none;
        }
    </style>

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                <x-heroicon-o-scale class="w-8 h-8 text-blue-600" />
                <span>Permintaan Pembelian Baru</span>
            </h2>

            <form action="{{ route('tr_prh.store') }}" method="POST" class="mt-6">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                    <!-- Row 1 -->
                    <!-- Cabang -->
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Cabang</label>
                        <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $fcabang }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                    </div>

                    <!-- PR# + Auto -->
                    <div class="lg:col-span-4" x-data="{ autoCode: true }">
                        <label class="block text-sm font-medium mb-1">PR#</label>
                        <div class="flex items-center gap-3">
                            <input type="text" name="fprno" class="w-full border rounded px-3 py-2"
                                :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                            <label class="inline-flex items-center select-none whitespace-nowrap">
                                <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                                <span class="ml-2 text-sm text-gray-700">Auto</span>
                            </label>
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium mb-1">Supplier</label>
                        <div class="flex">
                            <div class="relative flex-1">
                                <select id="supplierSelect" name="fsupplier_view"
                                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                    disabled>
                                    <option value=""></option>
                                    @foreach ($supplier as $suppliers)
                                        <option value="{{ $suppliers->fsupplierid }}"
                                            {{ old('fsupplier') == $suppliers->fsupplierid ? 'selected' : '' }}>
                                            {{ $suppliers->fsuppliercode }} - {{ $suppliers->fsuppliername }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                            </div>

                            <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier') }}">

                            <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                title="Browse Supplier">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                            <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                title="Tambah Supplier (tab baru)">
                                <x-heroicon-o-plus class="w-5 h-5" />
                            </a>
                        </div>
                        @error('fsupplier')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Row 2 -->
                    <!-- Tanggal -->
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal</label>
                        <input type="date" name="fprdate" value="{{ old('fprdate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fprdate') border-red-500 @enderror">
                        @error('fprdate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tanggal Dibutuhkan -->
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                        <input type="date" name="fneeddate" value="{{ old('fneeddate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fneeddate') border-red-500 @enderror">
                        @error('fneeddate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tanggal Paling Lambat -->
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                        <input type="date" name="fduedate" value="{{ old('fduedate') ?? date('Y-m-d') }}"
                            class="w-full border rounded px-3 py-2 @error('fduedate') border-red-500 @enderror">
                        @error('fduedate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Keterangan (full baris) -->
                    <div class="lg:col-span-12">
                        <label class="block text-sm font-medium">Keterangan</label>
                        <textarea name="fket" rows="3" class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fket') }}</textarea>
                        @error('fket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>


                <!-- DETAIL PRODUK -->
                <div x-data="detailProduk()" class="mt-6 space-y-4">
                    <div class="rounded-lg border shadow-sm overflow-hidden">
                        <div class="bg-gray-50 border-b px-4 py-2">
                            <div class="font-medium text-gray-700">Input Item Barang</div>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-10 gap-4 items-end">

                                <!-- Kode Produk -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Kode Produk</label>
                                    <div class="flex">
                                        <input type="text" class="flex-1 border rounded-l px-2 py-1"
                                            x-model.trim="form.fitemcode" @input="onCodeTyped()">
                                        <button type="button" @click="openBrowse()"
                                            class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                            title="Cari Produk">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        <a href="{{ route('product.create') }}" target="_blank" rel="noopener"
                                            class="border border-l-0 rounded-r px-2 py-1 bg-white hover:bg-gray-50"
                                            title="Tambah Produk">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                        </a>
                                    </div>
                                </div>

                                <!-- Nama Produk -->
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium mb-1">Nama Produk</label>
                                    <input type="text"
                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                        :value="form.fitemname" disabled>
                                </div>

                                <!-- Qty -->
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium mb-1">Qty</label>
                                    <input type="number" :min="1"
                                        :max="form.maxqty > 0 ? form.maxqty : null" step="1"
                                        class="w-full border rounded px-2 py-1 text-right" x-model.number="form.fqty"
                                        @input="enforceQty()">
                                </div>

                                <!-- Satuan -->
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium mb-1">Satuan</label>
                                    <template x-if="form.units.length > 1">
                                        <select class="w-full border rounded px-2 py-1" x-model="form.fsatuan">
                                            <template x-for="u in form.units" :key="u">
                                                <option :value="u" x-text="u"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="form.units.length <= 1">
                                        <input type="text"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                            :value="form.fsatuan || '-'" disabled>
                                    </template>
                                </div>

                                <!-- Keterangan -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Keterangan</label>
                                    <input type="text" class="w-full border rounded px-2 py-1" x-model="form.fketdt">
                                </div>

                                <!-- Tombol Tambah + Clear -->
                                <div class="md:col-span-1 flex gap-2">
                                    <button type="button" @click="saveCurrent()"
                                        class="h-8 px-3 rounded bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                                        Tambah
                                    </button>
                                    <button type="button" @click="resetForm()"
                                        class="h-8 px-3 rounded bg-gray-100 text-gray-700 text-xs font-medium hover:bg-gray-200">
                                        Clear
                                    </button>
                                </div>
                                <div class="md:col-start-3 md:col-span-4">
                                    <label class="block text-sm font-medium mb-1">Desc</label>
                                    <textarea rows="2" class="w-full border rounded px-2 py-1" x-model="form.fdesc"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan -->
                    <div class="space-y-2">
                        <h3 class="text-base font-semibold text-gray-800">Ringkasan Detail Item</h3>
                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left">#</th>
                                        <th class="p-2 text-left">Kode Produk</th>
                                        <th class="p-2 text-left">Nama Produk</th>
                                        <th class="p-2 text-left">Satuan</th>
                                        <th class="p-2 text-right">Qty</th>
                                        <th class="p-2 text-left">Desc</th>
                                        <th class="p-2 text-left">Keterangan Item</th>
                                        <th class="p-2 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr x-show="savedItems.length === 0" x-cloak>
                                        <td colspan="8" class="p-4 text-center text-sm text-gray-500">
                                            Belum ada item. Isi form di atas lalu klik <b>Tambah Item</b>.
                                        </td>
                                    </tr>

                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tr class="border-t">
                                            <!-- # -->
                                            <td class="p-2" x-text="i + 1"></td>

                                            <!-- Kode & Nama (read-only selalu) -->
                                            <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                            <td class="p-2" x-text="it.fitemname"></td>

                                            <!-- Satuan -->
                                            <td class="p-2">
                                                <!-- mode view -->
                                                <span x-show="editingIndex !== i" x-text="it.fsatuan"></span>

                                                <!-- mode edit -->
                                                <div x-show="editingIndex === i">
                                                    <template x-if="editUnits.length > 1">
                                                        <select class="border rounded px-2 py-1 w-full"
                                                            x-model="editForm.fsatuan">
                                                            <template x-for="u in editUnits" :key="u">
                                                                <option :value="u" x-text="u">
                                                                </option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="editUnits.length <= 1">
                                                        <input type="text"
                                                            class="border rounded px-2 py-1 w-full bg-gray-100 text-gray-600"
                                                            :value="editForm.fsatuan" disabled>
                                                    </template>
                                                </div>
                                            </td>

                                            <!-- Qty -->
                                            <td class="p-2 text-right">
                                                <!-- mode view -->
                                                <span x-show="editingIndex !== i" x-text="it.fqty"></span>

                                                <!-- mode edit -->
                                                <div x-show="editingIndex === i">
                                                    <input type="number" class="border rounded px-2 py-1 w-28 text-right"
                                                        :min="1" :max="editMaxqty > 0 ? editMaxqty : null"
                                                        step="1" x-model.number="editForm.fqty"
                                                        @input="enforceEditQty()"
                                                        :placeholder="editMaxqty > 0 ? `Maks: ${editMaxqty}` : ''"
                                                        :title="editMaxqty > 0 ? `Maks: ${editMaxqty}` : ''">
                                                </div>
                                            </td>

                                            <!-- Desc -->
                                            <td class="p-2">
                                                <!-- view -->
                                                <span x-show="editingIndex !== i" x-text="it.fdesc || '-'"></span>

                                                <!-- edit -->
                                                <input x-show="editingIndex === i" type="text"
                                                    class="border rounded px-2 py-1 w-full" x-model="editForm.fdesc">
                                            </td>

                                            <!-- Ket -->
                                            <td class="p-2">
                                                <!-- view -->
                                                <span x-show="editingIndex !== i" x-text="it.fketdt || '-'"></span>

                                                <!-- edit -->
                                                <input x-show="editingIndex === i" type="text"
                                                    class="border rounded px-2 py-1 w-full" x-model="editForm.fketdt">
                                            </td>

                                            <!-- Aksi -->
                                            <td class="p-2 text-center">
                                                <!-- mode view -->
                                                <div x-show="editingIndex !== i"
                                                    class="flex items-center justify-center gap-2">
                                                    <button type="button" @click="startEdit(i)"
                                                        class="px-3 py-1 rounded text-xs bg-amber-100 text-amber-700 hover:bg-amber-200">
                                                        Edit
                                                    </button>
                                                    <button type="button" @click="removeSaved(i)"
                                                        class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">
                                                        Hapus
                                                    </button>
                                                </div>

                                                <!-- mode edit -->
                                                <div x-show="editingIndex === i"
                                                    class="flex items-center justify-center gap-2">
                                                    <button type="button" @click="applyEdit(i)"
                                                        class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700">
                                                        Simpan
                                                    </button>
                                                    <button type="button" @click="cancelEdit()"
                                                        class="px-3 py-1 rounded text-xs bg-gray-100 text-gray-700 hover:bg-gray-200">
                                                        Batal
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- hidden inputs (biarkan) -->
                                            <td class="hidden">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MODAL DUPLIKAT -->
                    <div x-show="dupeModalOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center"
                        x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="cancelDupe()"></div>
                        <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                            x-transition.scale>
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800">Item Duplikat</h3>
                            </div>
                            <div class="px-5 py-4 space-y-3">
                                <p class="text-sm text-gray-700">
                                    Item dengan kombinasi <b>kode, satuan, deskripsi</b> dan <b>ket item</b> yang sama
                                    sudah ada.
                                </p>
                                <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700 space-y-1">
                                    <div><span class="text-gray-500">Kode:</span> <span class="font-mono"
                                            x-text="dupeCandidate?.fitemcode"></span></div>
                                    <div><span class="text-gray-500">Nama:</span> <span
                                            x-text="dupeCandidate?.fitemname"></span></div>
                                    <div class="flex gap-6">
                                        <div><span class="text-gray-500">Satuan:</span> <span
                                                x-text="dupeCandidate?.fsatuan"></span></div>
                                        <div><span class="text-gray-500">Qty:</span> <span
                                                x-text="dupeCandidate?.fqty"></span></div>
                                    </div>
                                    <div><span class="text-gray-500">Desc:</span> <span
                                            x-text="dupeCandidate?.fdesc || '-'"></span></div>
                                    <div><span class="text-gray-500">Ket Item:</span> <span
                                            x-text="dupeCandidate?.fketdt || '-'"></span></div>
                                </div>
                                <p class="text-sm text-gray-700">Tambahkan lagi sebagai baris baru?</p>
                            </div>
                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                <button type="button" @click="cancelDupe()"
                                    class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                    Batal
                                </button>
                                <button type="button" @click="confirmDupe()"
                                    class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                                    Tambahkan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL ERROR VALIDASI -->
                    <div x-show="errorModalOpen" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center"
                        x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="closeErrorModal()"></div>
                        <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden"
                            x-transition.scale>
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800">Validasi Item</h3>
                            </div>
                            <div class="px-5 py-4">
                                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                    <template x-for="(msg, idx) in errorMessages" :key="idx">
                                        <li x-text="msg"></li>
                                    </template>
                                </ul>
                            </div>
                            <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
                                <button type="button" @click="closeErrorModal()"
                                    class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                    OK
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL BROWSE SUPPLIER -->
                <div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                        <div class="p-4 border-b flex items-center gap-3">
                            <h3 class="text-lg font-semibold">Browse Supplier</h3>
                            <div class="ml-auto flex items-center gap-2">
                                <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                    placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                <button type="button" @click="search()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                            </div>
                        </div>
                        <div class="p-0 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th class="text-left p-2">Supplier (Kode - Nama)</th>
                                        <th class="text-left p-2 w-40">Telepon</th>
                                        <th class="text-center p-2 w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="s in rows" :key="s.fsupplierid">
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="p-2" x-text="`${s.fsuppliercode} - ${s.fsuppliername}`">
                                            </td>
                                            <td class="p-2" x-text="s.ftelp || '-'"></td>
                                            <td class="p-2 text-center">
                                                <button type="button" @click="choose(s)"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="rows.length === 0">
                                        <td colspan="3" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-t flex items-center gap-2">
                            <div class="text-sm text-gray-600">
                                <span x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span>
                            </div>
                            <div class="ml-auto flex items-center gap-2">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                    :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded border">Prev</button>
                                <button type="button" @click="next()" :disabled="page >= lastPage"
                                    :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded border">Next</button>
                                <button type="button" @click="close()"
                                    class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL BROWSE PRODUCT -->
                <div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-5xl max-h-[85vh] flex flex-col">
                        <div class="p-4 border-b flex items-center gap-3">
                            <h3 class="text-lg font-semibold">Browse Produk</h3>
                            <div class="ml-auto flex items-center gap-2">
                                <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                    placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                <button type="button" @click="search()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                            </div>
                        </div>
                        <div class="p-0 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th class="text-left p-2 w-40">Kode</th>
                                        <th class="text-left p-2">Nama</th>
                                        <th class="text-left p-2 w-48">Satuan</th>
                                        <th class="text-center p-2 w-28">Stock</th>
                                        <th class="text-center p-2 w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in rows" :key="p.fproductcode">
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="p-2 font-mono" x-text="p.fproductcode"></td>
                                            <td class="p-2" x-text="p.fproductname"></td>
                                            <td class="p-2">
                                                <span
                                                    x-text="[p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2].filter(Boolean).join(' / ')"></span>
                                            </td>
                                            <td class="p-2 text-center" x-text="p.fminstock"></td>
                                            <td class="p-2 text-center">
                                                <button type="button" @click="choose(p)"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="rows.length === 0">
                                        <td colspan="5" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-t flex items-center gap-2">
                            <div class="text-sm text-gray-600">
                                <span x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span>
                            </div>
                            <div class="ml-auto flex items-center gap-2">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                    :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded border">Prev</button>
                                <button type="button" @click="next()" :disabled="page >= lastPage"
                                    :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded border">Next</button>
                                <button type="button" @click="close()"
                                    class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <br>

                <!-- Approval (tetap di bawah, vertikal) -->
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label class="block text-sm font-medium">Approval</label>
                    <label class="switch">
                        <input type="checkbox" name="fapproval" id="approvalToggle"
                            {{ session('fapproval') ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>

                <!-- Tombol Aksi -->
                <div class="mt-8 flex justify-center gap-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                    <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

<!-- DATA & SCRIPTS -->
<script>
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fproductcode }}": {
                name: @json($p->fproductname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0)
            },
        @endforeach
    };

    window.cryptoRandom = function() {
        try {
            if (window.crypto?.getRandomValues) {
                const arr = new Uint32Array(1);
                window.crypto.getRandomValues(arr);
                return 'r' + arr[0].toString(16);
            }
        } catch (e) {}
        return 'r' + (Date.now().toString(16) + Math.random().toString(16).slice(2));
    };

    function supplierBrowser() {
        return {
            open: false,
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('suppliers.browse') }}", window.location.origin);
                u.searchParams.set('q', this.keyword || '');
                u.searchParams.set('per_page', this.perPage);
                u.searchParams.set('page', this.page);
                return u.toString();
            },
            async fetch() {
                try {
                    const res = await fetch(this.apiUrl(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const j = await res.json();
                    this.rows = j.data || [];
                    this.page = j.current_page || 1;
                    this.lastPage = j.last_page || 1;
                    this.total = j.total || 0;
                } catch (e) {
                    console.error(e);
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
            },
            openBrowse() {
                this.open = true;
                this.page = 1;
                this.fetch();
            },
            close() {
                this.open = false;
                this.keyword = '';
                this.rows = [];
            },
            search() {
                this.page = 1;
                this.fetch();
            },
            prev() {
                if (this.page > 1) {
                    this.page--;
                    this.fetch();
                }
            },
            next() {
                if (this.page < this.lastPage) {
                    this.page++;
                    this.fetch();
                }
            },
            choose(s) {
                const sel = document.getElementById('supplierSelect');
                const hid = document.getElementById('supplierCodeHidden');
                if (!sel) {
                    this.close();
                    return;
                }
                let opt = Array.from(sel.options).find(o => o.value == String(s.fsupplierid));
                const label = `${s.fsuppliercode} - ${s.fsuppliername}`;
                if (!opt) {
                    opt = new Option(label, s.fsupplierid, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = s.fsuppliercode; // kirim code string
                this.close();
            },
            init() {
                window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    function productBrowser() {
        return {
            open: false,
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('products.browse') }}", window.location.origin);
                u.searchParams.set('q', this.keyword || '');
                u.searchParams.set('per_page', this.perPage);
                u.searchParams.set('page', this.page);
                return u.toString();
            },
            async fetch() {
                try {
                    const res = await fetch(this.apiUrl(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const j = await res.json();
                    this.rows = j.data || [];
                    this.page = j.current_page || 1;
                    this.lastPage = j.last_page || 1;
                    this.total = j.total || 0;
                } catch (e) {
                    console.error(e);
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
            },
            openBrowse() {
                this.open = true;
                this.page = 1;
                this.fetch();
            },
            close() {
                this.open = false;
                this.keyword = '';
                this.rows = [];
            },
            search() {
                this.page = 1;
                this.fetch();
            },
            prev() {
                if (this.page > 1) {
                    this.page--;
                    this.fetch();
                }
            },
            next() {
                if (this.page < this.lastPage) {
                    this.page++;
                    this.fetch();
                }
            },
            choose(p) {
                window.dispatchEvent(new CustomEvent('product-chosen', {
                    detail: {
                        product: p
                    }
                }));
                this.close();
            },
            init() {
                window.addEventListener('browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    function detailProduk() {
        return {
            // form input atas
            form: {
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                fqty: '',
                fdesc: '',
                fketdt: '',
                maxqty: 0
            },

            // state utama
            savedItems: [],
            dupeModalOpen: false,
            dupeCandidate: null,

            // ===== inline edit state =====
            editingIndex: null,
            editForm: {
                fsatuan: '',
                fqty: 1,
                fdesc: '',
                fketdt: ''
            },
            editUnits: [],
            editMaxqty: 0,

            // ===== methods =====
            resetForm() {
                this.form = {
                    fitemcode: '',
                    fitemname: '',
                    units: [],
                    fsatuan: '',
                    fqty: '',
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0
                };
            },

            saveCurrent() {
                const r = this.form;

                const errs = [];
                if (!r.fitemcode) errs.push('Kode Produk belum diisi.');
                if (!r.fitemname) errs.push('Nama Produk belum terisi.');
                if (!r.fsatuan) errs.push('Satuan belum dipilih.');
                if (!r.fqty || +r.fqty <= 0) errs.push('Qty harus > 0.');

                if (errs.length) {
                    this.showErrorModal(errs);
                    return;
                }

                const dupe = this.savedItems.find(it =>
                    it.fitemcode === r.fitemcode &&
                    it.fsatuan === r.fsatuan &&
                    (it.fdesc || '') === (r.fdesc || '') &&
                    (it.fketdt || '') === (r.fketdt || '')
                );

                const candidate = {
                    uid: cryptoRandom(),
                    fitemcode: r.fitemcode,
                    fitemname: r.fitemname,
                    fsatuan: r.fsatuan,
                    fqty: +r.fqty,
                    fdesc: r.fdesc || '',
                    fketdt: r.fketdt || ''
                };

                if (dupe) {
                    this.dupeCandidate = candidate;
                    this.dupeModalOpen = true;
                    return;
                }

                this.savedItems.push(candidate);
                this.resetForm();
            },

            confirmDupe() {
                if (this.dupeCandidate) this.savedItems.push(this.dupeCandidate);
                this.dupeCandidate = null;
                this.dupeModalOpen = false;
                this.resetForm();
            },
            cancelDupe() {
                this.dupeCandidate = null;
                this.dupeModalOpen = false;
            },

            removeSaved(i) {
                this.savedItems.splice(i, 1);
            },

            // ==== error modal ====
            errorModalOpen: false,
            errorMessages: [],
            showErrorModal(msgs) {
                this.errorMessages = Array.isArray(msgs) ? msgs : [String(msgs)];
                this.errorModalOpen = true;
            },
            closeErrorModal() {
                this.errorModalOpen = false;
                this.errorMessages = [];
            },

            onCodeTyped() {
                const key = (this.form.fitemcode || '').trim();
                const meta = window.PRODUCT_MAP[key] || null;

                if (!meta) {
                    this.form.fitemname = '';
                    this.form.units = [];
                    this.form.fsatuan = '';
                    this.form.maxqty = 0;
                    this.form.fqty = '';
                    return;
                }

                this.form.fitemname = meta.name || '';
                const cleanUnits = Array.from(new Set(
                    (meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean)
                ));
                this.form.units = cleanUnits;
                if (!this.form.units.includes(this.form.fsatuan)) {
                    this.form.fsatuan = this.form.units[0] || '';
                }

                this.form.maxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;
                if (this.form.maxqty > 0) {
                    if (!this.form.fqty || +this.form.fqty < 1) this.form.fqty = 1;
                    if (+this.form.fqty > this.form.maxqty) this.form.fqty = this.form.maxqty;
                } else {
                    this.form.fqty = '';
                }
            },

            applyChosenProduct(p) {
                const code = (p.fproductcode || '').toString();
                const name = (p.fproductname || '').toString();
                const units = Array.from(new Set(
                    [p.fsatuankecil, p.fsatuanbesar, p.fsatuanbesar2]
                    .map(u => (u ?? '').toString().trim())
                    .filter(Boolean)
                ));

                this.form.fitemcode = code;
                this.form.fitemname = name;
                this.form.units = units;
                if (!this.form.units.includes(this.form.fsatuan)) {
                    this.form.fsatuan = this.form.units[0] || '';
                }

                const stock = Number.isFinite(+p.fminstock) ? +p.fminstock : 0;
                this.form.maxqty = stock > 0 ? stock : 0;
                if (this.form.maxqty > 0) {
                    if (!this.form.fqty || +this.form.fqty < 1) this.form.fqty = 1;
                    if (+this.form.fqty > this.form.maxqty) this.form.fqty = this.form.maxqty;
                } else {
                    this.form.fqty = '';
                }
            },

            enforceQty() {
                const n = +this.form.fqty;
                if (!Number.isFinite(n)) {
                    this.form.fqty = '';
                    return;
                }
                if (n < 1) this.form.fqty = 1;
                if (this.form.maxqty > 0 && n > this.form.maxqty) this.form.fqty = this.form.maxqty;
            },

            // modal browse product (trigger)
            openBrowse() {
                window.dispatchEvent(new CustomEvent('browse-open'));
            },

            // dengar event pilih produk
            init() {
                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;
                    this.applyChosenProduct(product);
                });
            },

            // ===== inline edit methods =====
            startEdit(i) {
                const it = this.savedItems[i];
                this.editingIndex = i;

                const meta = window.PRODUCT_MAP[it.fitemcode] || {
                    units: [],
                    stock: 0
                };
                const cleanUnits = Array.from(new Set((meta.units || [])
                    .map(u => (u ?? '').toString().trim())
                    .filter(Boolean)
                ));

                this.editUnits = cleanUnits;
                this.editMaxqty = Number.isFinite(+meta.stock) && +meta.stock > 0 ? +meta.stock : 0;

                this.editForm = {
                    fsatuan: it.fsatuan,
                    fqty: Number(it.fqty) || 1,
                    fdesc: it.fdesc || '',
                    fketdt: it.fketdt || ''
                };

                if (this.editUnits.length && !this.editUnits.includes(this.editForm.fsatuan)) {
                    this.editForm.fsatuan = this.editUnits[0];
                }
                this.enforceEditQty();
            },

            cancelEdit() {
                this.editingIndex = null;
                this.editForm = {
                    fsatuan: '',
                    fqty: 1,
                    fdesc: '',
                    fketdt: ''
                };
                this.editUnits = [];
                this.editMaxqty = 0;
            },

            applyEdit(i) {
                if (!this.editForm.fsatuan) {
                    alert('Satuan harus diisi.');
                    return;
                }
                if (!this.editForm.fqty || +this.editForm.fqty < 1) {
                    alert('Qty minimal 1.');
                    return;
                }
                if (this.editMaxqty > 0 && +this.editForm.fqty > this.editMaxqty) {
                    alert('Qty melebihi stok maksimum.');
                    return;
                }

                const it = this.savedItems[i];
                it.fsatuan = this.editForm.fsatuan;
                it.fqty = +this.editForm.fqty;
                it.fdesc = this.editForm.fdesc || '';
                it.fketdt = this.editForm.fketdt || '';

                this.cancelEdit();
            },

            enforceEditQty() {
                const n = +this.editForm.fqty;
                if (!Number.isFinite(n)) {
                    this.editForm.fqty = '';
                    return;
                }
                if (n < 1) this.editForm.fqty = 1;
                if (this.editMaxqty > 0 && n > this.editMaxqty) this.editForm.fqty = this.editMaxqty;
            },
        }
    }
</script>
