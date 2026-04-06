@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Permintaan Pembelian' : 'Edit Permintaan Pembelian')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
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
            inset: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 34px
        }

        .slider:before {
            content: "";
            position: absolute;
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

        [x-cloak] {
            display: none !important
        }

        #supplierSelect,
        #supplierSelect:disabled {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
        }

        #supplierSelect::-ms-expand {
            display: none
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0
        }

        input[type=number] {
            -moz-appearance: textfield
        }
    </style>

    {{-- ═══════════════════════════════════════════════════════════════════
     MODAL BLOCKED BY PO
════════════════════════════════════════════════════════════════════ --}}
    @if ((!empty($blockedByPO) && $blockedByPO) || session('blocked_by_po'))
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div class="relative bg-white w-[92vw] max-w-2xl rounded-2xl shadow-2xl overflow-hidden">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-red-100 bg-red-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-red-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-red-700">
                            PR Tidak Dapat {{ $action === 'delete' ? 'Dihapus' : 'Diedit' }}
                        </h3>
                        <p class="text-sm text-red-500 mt-0.5">
                            PR <strong>{{ $tr_prh->fprno }}</strong> sudah terikat dengan Purchase Order berikut:
                        </p>
                    </div>
                    {{-- Tombol X tutup modal --}}
                    <button type="button" @click="open = false"
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 flex items-center justify-center transition-colors"
                        title="Tutup">
                        <x-heroicon-o-x-mark class="w-4 h-4 text-red-600" />
                    </button>
                </div>

                {{-- Body: tabel daftar PO --}}
                <div class="px-6 py-4 max-h-72 overflow-y-auto">
                    @if (!empty($existingPO) && $existingPO->isNotEmpty())
                        <table class="w-full text-sm border rounded overflow-hidden">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th class="px-3 py-2 text-left font-semibold">#</th>
                                    <th class="px-3 py-2 text-left font-semibold">No. PO</th>
                                    <th class="px-3 py-2 text-left font-semibold">Tanggal PO</th>
                                    <th class="px-3 py-2 text-left font-semibold">Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($existingPO as $idx => $po)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 font-mono font-medium text-blue-700">
                                            {{ $po->fpohno ?? $po->fpono }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $po->fpodate ? \Carbon\Carbon::parse($po->fpodate)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $po->fsuppliername ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-600">PR ini sudah memiliki Purchase Order terkait.</p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center gap-3">
                    <p class="text-xs text-gray-500">
                        Batalkan PO terkait terlebih dahulu sebelum {{ $action === 'delete' ? 'menghapus' : 'mengedit' }} PR
                        ini.
                    </p>
                    <button type="button" @click="open = false"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                        Kembali
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">
            @if ($action === 'delete')
                <div class="space-y-4">
                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = !empty($tr_prh->fuserapproved) || (int) $tr_prh->fapproval === 1;
                    @endphp

                    {{-- HEADER FORM READONLY --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $fcabang }}" disabled>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">PR#</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $tr_prh->fprno }}" disabled>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">Supplier</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                                value="{{ $tr_prh->fsuppliername }} ({{ $tr_prh->fsupplier }})" disabled>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input disabled type="date" value="{{ $fmt($tr_prh->fprdate) }}"
                                class="w-full border rounded px-3 py-2 text-gray-700">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                            <input disabled type="date" value="{{ $fmt($tr_prh->fneeddate) }}"
                                class="w-full border rounded px-3 py-2 text-gray-700">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                            <input disabled type="date" value="{{ $fmt($tr_prh->fduedate) }}"
                                class="w-full border rounded px-3 py-2 text-gray-700">
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea readonly rows="3" class="w-full border rounded px-3 py-2 text-gray-700">{{ $tr_prh->fket }}</textarea>
                        </div>
                    </div>

                    {{-- DETAIL ITEM READONLY --}}
                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>
                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-44">Kode Produk</th>
                                        <th class="p-2 text-left">Nama Produk</th>
                                        <th class="p-2 text-left w-40">Satuan</th>
                                        <th class="p-2 text-right w-28">Qty</th>
                                        <th class="p-2 text-right w-28">Qty PO</th>
                                        <th class="p-2 text-left w-56">Ket Item</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tr class="border-t align-top">
                                            <td class="p-2" x-text="i + 1"></td>
                                            <td class="p-2 font-mono" x-text="it.fitemcode"></td>
                                            <td class="p-2 text-gray-800">
                                                <div x-text="it.fitemname"></div>
                                                <div x-show="it.fdesc" class="mt-1 text-xs">
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-2">Deskripsi</span>
                                                    <span class="align-middle text-gray-600" x-text="it.fdesc"></span>
                                                </div>
                                            </td>
                                            <td class="p-2" x-text="it.fsatuan"></td>
                                            <td class="p-2 text-right" x-text="it.fqty"></td>
                                            <td class="p-2 text-right" x-text="it.fqtypo"></td>
                                            <td class="p-2" x-text="it.fketdt || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-center space-x-4">
                        @if (!empty($blockedByPO) && $blockedByPO)
                            <button type="button" disabled class="bg-red-300 text-white px-6 py-2 rounded cursor-not-allowed flex items-center gap-2">
                                <x-heroicon-o-lock-closed class="w-5 h-5" />
                                Hapus (Terkunci)
                            </button>
                        @else
                            <button type="button" onclick="showDeleteModal()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                                <x-heroicon-o-trash class="w-5 h-5 mr-2" /> Hapus
                            </button>
                        @endif
                        <button type="button" onclick="window.location.href='{{ route('tr_prh.index') }}'" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                        </button>
                    </div>
                </div>

                {{-- Modal Konfirmasi Delete --}}
                <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                        <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus Permintaan Pembelian ini?</h3>
                        <form action="{{ route('tr_prh.destroy', $tr_prh->fprhid) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="flex justify-end space-x-2">
                                <button onclick="closeDeleteModal()" type="button" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Tidak</button>
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Ya, Hapus</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    function showDeleteModal() { document.getElementById('deleteModal')?.classList.remove('hidden'); }
                    function closeDeleteModal() { document.getElementById('deleteModal')?.classList.add('hidden'); }
                </script>

            @else
                {{-- MODE EDIT --}}
                <form action="{{ route('tr_prh.update', $tr_prh->fprhid) }}" method="POST"
                    x-data="{ showNoItems: false, blockedByPO: {{ $blockedByPO ? 'true' : 'false' }} }"
                    @submit.prevent="
                        const n = Number(document.getElementById('itemsCount')?.value || 0);
                        if (n < 1) { showNoItems = true; return; }
                        $el.submit();
                    ">
                    @csrf
                    @method('PATCH')

                    @if (!empty($blockedByPO) && $blockedByPO)
                        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200">
                            <x-heroicon-o-lock-closed class="w-5 h-5 text-amber-500 flex-shrink-0" />
                            <p class="text-sm text-amber-700">
                                <strong>Mode hanya baca.</strong> PR ini tidak dapat diedit karena sudah memiliki Purchase Order terkait.
                            </p>
                        </div>
                    @endif

                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = !empty($tr_prh->fuserapproved) || (int) $tr_prh->fapproval === 1;
                    @endphp

                    {{-- HEADER FORM EDITABLE --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" :class="blockedByPO ? 'opacity-60 pointer-events-none' : ''">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200" value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">PR#</label>
                            <input type="text" name="fprno" class="w-full border rounded px-3 py-2 bg-gray-200" value="{{ $tr_prh->fprno }}" disabled>
                        </div>

                        <div class="lg:col-span-4" x-data="supplierBrowser()" @supplier-chosen.window="onSupplierChosen($event.detail)">
                            <label class="block text-sm font-medium mb-1">Supplier</label>
                            <div class="flex">
                                <input type="text" x-model="supplierDisplay" class="flex-1 border rounded-l px-3 py-2 bg-gray-100" readonly>
                                <input type="hidden" name="fsupplier" x-model="supplierId">
                                <button type="button" @click="openModal()" class="border border-l-0 px-3 py-2 bg-white hover:bg-gray-50">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('supplier.create') }}" target="_blank" class="border border-l-0 rounded-r px-3 py-2 bg-white hover:bg-gray-50">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>
                            @error('fsupplier') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input type="date" name="fprdate" value="{{ old('fprdate', $fmt($tr_prh->fprdate)) }}" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                            <input type="date" name="fneeddate" value="{{ old('fneeddate', $fmt($tr_prh->fneeddate)) }}" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                            <input type="date" name="fduedate" value="{{ old('fduedate', $fmt($tr_prh->fduedate)) }}" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="lg:col-span-12">
                            <label class="block text-sm font-medium">Keterangan</label>
                            <textarea name="fket" rows="2" class="w-full border rounded px-3 py-2">{{ old('fket', $tr_prh->fket) }}</textarea>
                        </div>
                    </div>

                    {{-- DETAIL ITEM INLINE EDITABLE --}}
                    <div x-data="itemsTable()" x-init="init()" class="mt-6 space-y-2">
                        <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>
                        <div class="overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left w-10">#</th>
                                        <th class="p-2 text-left w-48">Kode Produk</th>
                                        <th class="p-2 text-left">Nama & Deskripsi</th>
                                        <th class="p-2 text-left w-36">Satuan</th>
                                        <th class="p-2 text-right w-24">Qty</th>
                                        <th class="p-2 text-right w-24">Qty PO</th>
                                        <th class="p-2 text-left w-48">Ket Item</th>
                                        <th class="p-2 text-center w-20">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- SAVED ITEMS --}}
                                    <template x-for="(it, i) in savedItems" :key="it.uid">
                                        <tr class="border-t align-top transition-colors" :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                                            <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                        x-model.trim="it.fitemcode" @input="onCodeTypedSaved(it)" @focus="activeRow = it.uid" @blur="activeRow = null"
                                                        :disabled="blockedByPO">
                                                    <button type="button" @click="openBrowseFor('saved', i)" class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50" :disabled="blockedByPO">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 mb-1" :value="it.fitemname" disabled>
                                                <textarea x-model="it.fdesc" rows="1" class="w-full border rounded px-2 py-1 text-xs" placeholder="Deskripsi deskripsi..." :disabled="blockedByPO" @focus="activeRow = it.uid" @blur="activeRow = null"></textarea>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="it.units.length > 1">
                                                    <select class="w-full border rounded px-2 py-1" x-model="it.fsatuan" :disabled="blockedByPO">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="it.units.length <= 1">
                                                    <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600" :value="it.fsatuan || '-'" disabled>
                                                </template>
                                            </td>
                                            <td class="p-2">
                                                <input type="number" class="w-full border rounded px-2 py-1 text-right" x-model.number="it.fqty" min="1" :disabled="blockedByPO" @focus="activeRow = it.uid; $event.target.select()" @blur="activeRow = null">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number" class="w-full border rounded px-2 py-1 text-right bg-gray-100 text-gray-500" :value="it.fqtypo" disabled>
                                            </td>
                                            <td class="p-2">
                                                <input type="text" class="w-full border rounded px-2 py-1" x-model="it.fketdt" :disabled="blockedByPO" @focus="activeRow = it.uid" @blur="activeRow = null">
                                            </td>
                                            <td class="p-2 text-center text-red-500">
                                                <button type="button" @click="removeSaved(i)" class="p-1 hover:bg-red-50 rounded" :disabled="blockedByPO">
                                                    <x-heroicon-o-trash class="w-5 h-5" />
                                                </button>
                                            </td>
                                            {{-- HIDDEN INPUTS FOR POST --}}
                                            <td class="hidden">
                                                <input type="hidden" name="fprdid[]" :value="it.fprdid">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                                <input type="hidden" name="fqty[]" :value="it.fqty">
                                                <input type="hidden" name="fqtypo[]" :value="it.fqtypo">
                                                <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                                <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                            </td>
                                        </tr>
                                    </template>

                                    {{-- DRAFT ROW --}}
                                    <tr class="border-t bg-green-50 align-top" x-show="!blockedByPO">
                                        <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>
                                        <td class="p-2">
                                            <div class="flex">
                                                <input type="text" class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                    x-ref="draftCode" x-model.trim="draft.fitemcode" @input="onCodeTypedDraft()" @keydown.enter.prevent="handleEnterOnDraftCode()">
                                                <button type="button" @click="openBrowseFor('draft')" class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 mb-1" :value="draft.fitemname" disabled>
                                            <textarea x-model="draft.fdesc" rows="1" class="w-full border rounded px-2 py-1 text-xs" placeholder="Deskripsi draft..."></textarea>
                                        </td>
                                        <td class="p-2">
                                            <template x-if="draft.units.length > 1">
                                                <select class="w-full border rounded px-2 py-1" x-model="draft.fsatuan" x-ref="draftUnit">
                                                    <template x-for="u in draft.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="draft.units.length <= 1">
                                                <input type="text" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600" :value="draft.fsatuan || '-'" disabled>
                                            </template>
                                        </td>
                                        <td class="p-2">
                                            <input type="number" class="w-full border rounded px-2 py-1 text-right" x-model.number="draft.fqty" min="1" x-ref="draftQty" @keydown.enter.prevent="addIfComplete()">
                                        </td>
                                        <td class="p-2 text-right">-</td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1" x-model="draft.fketdt" @keydown.enter.prevent="addIfComplete()">
                                        </td>
                                        <td class="p-2 text-center text-emerald-600">
                                            <button type="button" @click="addIfComplete()" class="p-1 hover:bg-emerald-50 rounded">
                                                <x-heroicon-o-plus-circle class="w-6 h-6" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" id="itemsCount" :value="savedItems.length">
                    </div>

                    <div class="mt-8 flex justify-center gap-4">
                        <button type="submit" class="bg-blue-600 text-white px-8 py-2.5 rounded shadow hover:bg-blue-700 flex items-center transition" :class="blockedByPO ? 'hidden' : ''">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan Perubahan
                        </button>
                        <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'" class="bg-gray-500 text-white px-8 py-2.5 rounded shadow hover:bg-gray-600 flex items-center transition">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                        </button>
                    </div>

                    {{-- Local Modals --}}
                    <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50" x-transition.opacity>
                        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full text-center">
                            <h4 class="text-lg font-bold text-red-600 mb-2">Item Kosong</h4>
                            <p class="text-sm text-gray-600 mb-4">Harap tambahkan minimal satu item sebelum mensimpan.</p>
                            <button @click="showNoItems = false" type="button" class="w-full py-2 bg-gray-100 hover:bg-gray-200 rounded font-medium">OK</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- MODAL SUPPLIER --}}
    <div x-data="supplierBrowser()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-transition.opacity>
        <div class="bg-white rounded-xl shadow-2xl w-[90vw] max-w-4xl h-[80vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center bg-blue-50">
                <h3 class="text-lg font-bold">Pilih Supplier</h3>
                <button @click="close()" class="text-gray-500 hover:text-gray-800"><x-heroicon-o-x-mark class="w-6 h-6" /></button>
            </div>
            <div class="flex-1 p-6 overflow-auto">
                <table id="supplierBrowseTable" class="w-full text-sm">
                    <thead><tr class="text-left font-bold border-b"><th>Kode</th><th>Nama</th><th>Alamat</th><th>Aksi</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL PRODUCT --}}
    <div x-data="productBrowser()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-transition.opacity>
        <div class="bg-white rounded-xl shadow-2xl w-[90vw] max-w-5xl h-[85vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center bg-emerald-50">
                <h3 class="text-lg font-bold">Pilih Produk</h3>
                <button @click="close()" class="text-gray-500 hover:text-gray-800"><x-heroicon-o-x-mark class="w-6 h-6" /></button>
            </div>
            <div class="flex-1 p-6 overflow-auto">
                <table id="productTable" class="w-full text-sm">
                    <thead><tr class="text-left font-bold border-b"><th>Kode</th><th>Nama</th><th>Satuan</th><th>Stok</th><th>Aksi</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #3b82f6 !important; color: white !important; border: none !important; }
        #productTable_wrapper, #supplierBrowseTable_wrapper { padding: 1rem 0; }
    </style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                id: @json($p->fprdid),
                name: @json($p->fprdname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fqty ?? 0)
            },
        @endforeach
    };

    window.cryptoRandom = () => 'r' + Math.random().toString(16).slice(2, 10);

    function supplierBrowser() {
        return {
            open: false,
            supplierId: "{{ old('fsupplier', $tr_prh->fsupplier) }}",
            supplierDisplay: "{{ $tr_prh->fsuppliername }} ({{ $tr_prh->fsupplier }})",
            dataTable: null,
            openModal() {
                this.open = true;
                this.$nextTick(() => this.initDT());
            },
            close() { this.open = false; },
            initDT() {
                if (this.dataTable) return;
                this.dataTable = $('#supplierBrowseTable').DataTable({
                    processing: true, serverSide: true,
                    ajax: "{{ route('suppliers.browse') }}",
                    columns: [
                        { data: 'fsupplierid' }, { data: 'fsuppliername' }, { data: 'fsupplieraddress' },
                        { data: null, render: (d) => `<button type="button" class="bg-blue-600 text-white px-3 py-1 rounded text-xs select-supp" data-json='${JSON.stringify(d)}'>Pilih</button>` }
                    ]
                });
                $('#supplierBrowseTable tbody').on('click', '.select-supp', (e) => {
                    const data = JSON.parse($(e.target).attr('data-json'));
                    window.dispatchEvent(new CustomEvent('supplier-chosen', { detail: data }));
                    this.close();
                });
            },
            onSupplierChosen(d) {
                this.supplierId = d.fsupplierid;
                this.supplierDisplay = `${d.fsuppliername} (${d.fsupplierid})`;
            }
        }
    }

    function productBrowser() {
        return {
            open: false,
            dataTable: null,
            target: 'draft',
            targetIdx: null,
            init() {
                window.addEventListener('browse-product', (e) => {
                    this.target = e.detail.target;
                    this.targetIdx = e.detail.index;
                    this.open = true;
                    this.$nextTick(() => this.initDT());
                });
            },
            close() { this.open = false; },
            initDT() {
                if (this.dataTable) return;
                this.dataTable = $('#productTable').DataTable({
                    processing: true, serverSide: true,
                    ajax: "{{ route('products.browse') }}",
                    columns: [
                        { data: 'fprdcode' }, { data: 'fprdname' }, { data: 'fsatuankecil' }, { data: 'fqty' },
                        { data: null, render: (d) => `<button type="button" class="bg-emerald-600 text-white px-3 py-1 rounded text-xs select-prod" data-code="${d.fprdcode}">Pilih</button>` }
                    ]
                });
                $('#productTable tbody').on('click', '.select-prod', (e) => {
                    const code = $(e.currentTarget).data('code');
                    window.dispatchEvent(new CustomEvent('product-chosen', { detail: { code, target: this.target, index: this.targetIdx } }));
                    this.close();
                });
            }
        }
    }

    function itemsTable() {
        return {
            savedItems: @json($savedItems ?? []),
            activeRow: null,
            draft: { fitemcode: '', fitemname: '', units: [], fsatuan: '', fqty: 1, fdesc: '', fketdt: '' },
            init() {
                // Initial hydration
                this.savedItems = this.savedItems.map(it => {
                    const meta = window.PRODUCT_MAP[it.fitemcode];
                    return {
                        ...it,
                        uid: it.uid || cryptoRandom(),
                        units: meta ? meta.units : [it.fsatuan]
                    };
                });

                window.addEventListener('product-chosen', (e) => {
                    const { code, target, index } = e.detail;
                    if (target === 'saved') {
                        const it = this.savedItems[index];
                        it.fitemcode = code;
                        this.onCodeTypedSaved(it);
                    } else {
                        this.draft.fitemcode = code;
                        this.onCodeTypedDraft();
                    }
                });
            },
            onCodeTypedDraft() {
                const meta = window.PRODUCT_MAP[this.draft.fitemcode];
                if (meta) {
                    this.draft.fprdid = meta.id;
                    this.draft.fitemname = meta.name;
                    this.draft.units = meta.units;
                    this.draft.fsatuan = meta.units[0] || '';
                } else {
                    this.draft.fprdid = 0; this.draft.fitemname = ''; this.draft.units = []; this.draft.fsatuan = '';
                }
            },
            onCodeTypedSaved(it) {
                const meta = window.PRODUCT_MAP[it.fitemcode];
                if (meta) {
                    it.fprdid = meta.id;
                    it.fitemname = meta.name;
                    it.units = meta.units;
                    if (!it.units.includes(it.fsatuan)) it.fsatuan = it.units[0] || '';
                }
            },
            handleEnterOnDraftCode() {
                if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                else this.$refs.draftQty?.focus();
            },
            addIfComplete() {
                if (!this.draft.fitemcode || !this.draft.fitemname || !this.draft.fqty) return;
                this.savedItems.push({
                    ...this.draft,
                    uid: cryptoRandom(),
                    fqtypo: 0
                });
                this.draft = { fitemcode: '', fitemname: '', units: [], fsatuan: '', fqty: 1, fdesc: '', fketdt: '' };
                this.$nextTick(() => this.$refs.draftCode.focus());
            },
            removeSaved(i) { this.savedItems.splice(i, 1); },
            openBrowseFor(target, index = null) {
                window.dispatchEvent(new CustomEvent('browse-product', { detail: { target, index } }));
            }
        }
    }
</script>
@endpush
