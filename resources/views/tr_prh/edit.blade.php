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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">Gagal Memperbarui Data!</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    Periksa kembali data berikut sebelum menyimpan:
                </p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li class="text-danger mb-1">
                            <i class="bi bi-dot fs-5 align-middle"></i>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    @php
        $isUsageLocked = !empty($blockedByPO) && $blockedByPO;
    @endphp
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
                                value="{{ $tr_prh->fsuppliername }} ({{ $tr_prh->fsuppliercode }})" disabled>
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
                                                    <span
                                                        class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 mr-2">Deskripsi</span>
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
                            <button type="button" disabled
                                class="bg-red-300 text-white px-6 py-2 rounded cursor-not-allowed flex items-center gap-2">
                                <x-heroicon-o-lock-closed class="w-5 h-5" />
                                Hapus (Terkunci)
                            </button>
                        @else
                            <button type="button" onclick="showDeleteModal()"
                                class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                                <x-heroicon-o-trash class="w-5 h-5 mr-2" /> Hapus
                            </button>
                        @endif
                        <button type="button" onclick="window.location.href='{{ route('tr_prh.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                        </button>
                    </div>
                </div>

                {{-- Modal Konfirmasi Delete --}}
                <div id="deleteModal"
                    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                        <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus Permintaan Pembelian ini?</h3>
                        <form action="{{ route('tr_prh.destroy', $tr_prh->fprhid) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="flex justify-end space-x-2">
                                <button onclick="closeDeleteModal()" type="button"
                                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Tidak</button>
                                <button type="submit"
                                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Ya, Hapus</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    function showDeleteModal() {
                        document.getElementById('deleteModal')?.classList.remove('hidden');
                    }

                    function closeDeleteModal() {
                        document.getElementById('deleteModal')?.classList.add('hidden');
                    }
                </script>
            @else
                {{-- MODE EDIT --}}
                <form action="{{ route('tr_prh.update', $tr_prh->fprhid) }}" method="POST" x-data="{ showNoItems: false, blockedByPO: {{ $blockedByPO ? 'true' : 'false' }} }"
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
                                <strong>Mode hanya baca.</strong> PR ini tidak dapat diedit karena sudah memiliki Purchase
                                Order terkait.
                            </p>
                        </div>
                    @endif

                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = !empty($tr_prh->fuserapproved) || (int) $tr_prh->fapproval === 1;
                    @endphp

                    {{-- HEADER FORM EDITABLE --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4"
                        :class="blockedByPO ? 'opacity-60 pointer-events-none' : ''">
                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200"
                                value="{{ $fcabang }}" disabled>
                            <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium mb-1">PR#</label>
                            <input type="text" name="fprno" class="w-full border rounded px-3 py-2 bg-gray-200"
                                value="{{ $tr_prh->fprno }}" disabled>
                        </div>

                        <div class="lg:col-span-4" x-data="{
                            supplierId: '{{ old('fsupplier', $tr_prh->fsupplier) }}',
                            supplierDisplay: '{{ $tr_prh->fsuppliername }} ({{ $tr_prh->fsupplier }})'
                        }"
                            @supplier-chosen.window="supplierId = $event.detail.fsuppliercode; supplierDisplay = $event.detail.fsuppliername + ' (' + $event.detail.fsuppliercode + ')'">
                            <label class="block text-sm font-medium mb-1">Supplier</label>
                            <div class="flex">
                                <input type="text" x-model="supplierDisplay"
                                    class="flex-1 border rounded-l px-3 py-2 bg-gray-100" readonly>
                                <input type="hidden" name="fsupplier" x-model="supplierId">
                                <button type="button" @click="$dispatch('browse-supplier')"
                                    class="border border-l-0 px-3 py-2 bg-white hover:bg-gray-50">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                </button>
                                <a href="{{ route('supplier.create') }}" target="_blank"
                                    class="border border-l-0 rounded-r px-3 py-2 bg-white hover:bg-gray-50">
                                    <x-heroicon-o-plus class="w-5 h-5" />
                                </a>
                            </div>
                            @error('fsupplier')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal</label>
                            <input type="date" name="fprdate" value="{{ old('fprdate', $fmt($tr_prh->fprdate)) }}"
                                class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal Dibutuhkan</label>
                            <input type="date" name="fneeddate"
                                value="{{ old('fneeddate', $fmt($tr_prh->fneeddate)) }}"
                                class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-sm font-medium">Tanggal Paling Lambat</label>
                            <input type="date" name="fduedate" value="{{ old('fduedate', $fmt($tr_prh->fduedate)) }}"
                                class="w-full border rounded px-3 py-2">
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
                                        <tr class="border-t align-top transition-colors"
                                            :class="activeRow === it.uid ? 'bg-amber-50' : 'hover:bg-gray-50'">
                                            <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                            <td class="p-2">
                                                <div class="flex">
                                                    <input type="text"
                                                        class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                        x-model.trim="it.fitemcode" @input="onCodeTypedSaved(it)"
                                                        @focus="activeRow = it.uid" @blur="activeRow = null"
                                                        :disabled="blockedByPO">
                                                    <button type="button" @click="openBrowseFor('saved', i)"
                                                        class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50"
                                                        :disabled="blockedByPO">
                                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 mb-1"
                                                    :value="it.fitemname" disabled>
                                                <textarea x-model="it.fdesc" rows="1" class="w-full border rounded px-2 py-1 text-xs"
                                                    placeholder="Deskripsi deskripsi..." :disabled="blockedByPO" @focus="activeRow = it.uid"
                                                    @blur="activeRow = null"></textarea>
                                            </td>
                                            <td class="p-2">
                                                <template x-if="(it.units?.length || 0) > 1">
                                                    <select class="w-full border rounded px-2 py-1 text-sm"
                                                        :id="'unit_saved_' + i"
                                                        x-effect="$nextTick(() => { const el = document.getElementById('unit_saved_' + i); if (el) el.value = it.fsatuan; })"
                                                        @change="it.fsatuan = $event.target.value" :disabled="blockedByPO"
                                                        @focus="activeRow = it.uid" @blur="activeRow = null">
                                                        <template x-for="u in it.units" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="(it.units?.length || 0) <= 1">
                                                    <input type="text"
                                                        class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 text-sm"
                                                        :value="it.fsatuan || '-'" disabled>
                                                </template>
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                                    x-model.number="it.fqty" min="1" :disabled="blockedByPO"
                                                    @focus="activeRow = it.uid; $event.target.select()"
                                                    @blur="activeRow = null">
                                            </td>
                                            <td class="p-2 text-right">
                                                <input type="number"
                                                    class="w-full border rounded px-2 py-1 text-right bg-gray-100 text-gray-500"
                                                    :value="it.fqtypo" disabled>
                                            </td>
                                            <td class="p-2">
                                                <input type="text" class="w-full border rounded px-2 py-1"
                                                    x-model="it.fketdt" :disabled="blockedByPO"
                                                    @focus="activeRow = it.uid" @blur="activeRow = null">
                                            </td>
                                            <td class="p-2 text-center">
                                                <button type="button" @click="removeSaved(i)"
                                                    class="px-3 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200 whitespace-nowrap"
                                                    :disabled="blockedByPO">
                                                    Hapus
                                                </button>
                                            </td>
                                            {{-- HIDDEN INPUTS FOR POST --}}
                                            <td class="hidden">
                                                <input type="hidden" name="fprdid[]" :value="it.fprdid">
                                                <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                                <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                                <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
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
                                                <input type="text"
                                                    class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0"
                                                    x-ref="draftCode" x-model.trim="draft.fitemcode"
                                                    @input="onCodeTypedDraft()"
                                                    @keydown.enter.prevent="handleEnterOnDraftCode()">
                                                <button type="button" @click="openBrowseFor('draft')"
                                                    class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50">
                                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600 mb-1"
                                                :value="draft.fitemname" disabled>
                                            <textarea x-model="draft.fdesc" rows="1" class="w-full border rounded px-2 py-1 text-xs"
                                                placeholder="Deskripsi draft..."></textarea>
                                        </td>
                                        <td class="p-2">
                                            <template x-if="draft.units.length > 1">
                                                <select class="w-full border rounded px-2 py-1" x-model="draft.fsatuan"
                                                    x-ref="draftUnit">
                                                    <template x-for="u in draft.units" :key="u">
                                                        <option :value="u" x-text="u"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="draft.units.length <= 1">
                                                <input type="text"
                                                    class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600"
                                                    :value="draft.fsatuan || '-'" disabled>
                                            </template>
                                        </td>
                                        <td class="p-2">
                                            <input type="number" class="w-full border rounded px-2 py-1 text-right"
                                                x-model.number="draft.fqty" min="1" x-ref="draftQty"
                                                @keydown.enter.prevent="addIfComplete()">
                                        </td>
                                        <td class="p-2 text-right">-</td>
                                        <td class="p-2">
                                            <input type="text" class="w-full border rounded px-2 py-1"
                                                x-model="draft.fketdt" @keydown.enter.prevent="addIfComplete()">
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" @click="addIfComplete()"
                                                class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap">
                                                Tambah
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" id="itemsCount" :value="savedItems.length">
                    </div>

                    <div class="mt-8 flex justify-center gap-4">
                        @if ($isUsageLocked)
                            <button type="button" disabled
                                class="bg-blue-300 text-white px-8 py-2.5 rounded shadow flex items-center transition cursor-not-allowed opacity-70"
                                title="{{ $usageLockMessage ?? 'Data ini sudah digunakan.' }}">
                                <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Simpan Perubahan
                            </button>
                        @else
                            <button type="submit"
                                class="bg-blue-600 text-white px-8 py-2.5 rounded shadow hover:bg-blue-700 flex items-center transition">
                                <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan Perubahan
                            </button>
                        @endif
                        <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                            class="bg-gray-500 text-white px-8 py-2.5 rounded shadow hover:bg-gray-600 flex items-center transition">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                        </button>
                    </div>

                    {{-- Local Modals --}}
                    <div x-show="showNoItems" x-cloak
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50" x-transition.opacity>
                        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full text-center">
                            <h4 class="text-lg font-bold text-red-600 mb-2">Item Kosong</h4>
                            <p class="text-sm text-gray-600 mb-4">Harap tambahkan minimal satu item sebelum mensimpan.</p>
                            <button @click="showNoItems = false" type="button"
                                class="w-full py-2 bg-gray-100 hover:bg-gray-200 rounded font-medium">OK</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- MODAL SUPPLIER --}}
    <div x-data="supplierBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
            style="height: 650px;">
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Supplier</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Pilih supplier yang diinginkan</p>
                </div>
                <button type="button" @click="close()"
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                    Tutup
                </button>
            </div>
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="supplierTableControls"></div>
            </div>
            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                <div class="bg-white">
                    <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                        style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                    Supplier</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Alamat
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Telepon
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div id="supplierTablePagination"></div>
            </div>
        </div>
    </div>

    {{-- MODAL PRODUK --}}
    <div x-data="productBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
            style="height: 650px;">
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Produk</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Pilih produk yang diinginkan</p>
                </div>
                <button type="button" @click="close()"
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                    Tutup
                </button>
            </div>
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="productTableControls"></div>
            </div>
            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                <div class="bg-white">
                    <table id="productTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                    Produk</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Satuan
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Merek</th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Stock
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div id="productTablePagination"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        div#productTable_length select,
        .dataTables_wrapper #productTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#productTable_length,
        .dataTables_wrapper #productTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#productTable_length label,
        .dataTables_wrapper #productTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        div#supplierTable_length select,
        .dataTables_wrapper #supplierTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#supplierTable_length,
        .dataTables_wrapper #supplierTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#supplierTable_length label,
        .dataTables_wrapper #supplierTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        window.PRODUCT_MAP = @json($productMap ?? []);

        window.cryptoRandom = () => 'r' + Math.random().toString(16).slice(2, 10);

        function supplierBrowser() {
            return {
                open: false,
                supplierId: "{{ old('fsupplier', $tr_prh->fsupplier) }}",
                supplierDisplay: "{{ $tr_prh->fsuppliername }} ({{ $tr_prh->fsupplier }})",
                dataTable: null,
                init() {
                    window.addEventListener('browse-supplier', () => {
                        this.openModal();
                    });
                },
                openModal() {
                    this.open = true;
                    this.$nextTick(() => this.initDT());
                },
                close() {
                    this.open = false;
                },
                initDT() {
                    if (this.dataTable) this.dataTable.destroy();
                    this.dataTable = $('#supplierBrowseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('suppliers.browse') }}",
                            type: 'GET',
                            data: function(d) {
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            }
                        },
                        columns: [{
                                data: 'fsuppliercode',
                                name: 'fsuppliercode',
                                className: 'font-mono text-sm',
                                width: '15%'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                width: '25%'
                            },
                            {
                                data: 'faddress',
                                name: 'faddress',
                                className: 'text-sm',
                                defaultContent: '-',
                                orderable: false,
                                width: '30%'
                            },
                            {
                                data: 'ftelp',
                                name: 'ftelp',
                                className: 'text-sm',
                                defaultContent: '-',
                                orderable: false,
                                width: '15%'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '15%',
                                render: () =>
                                    '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                        language: {
                            processing: "Memuat data...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Tidak ada data",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data yang ditemukan",
                            emptyTable: "Tidak ada data tersedia",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Selanjutnya",
                                previous: "Sebelumnya"
                            }
                        },
                        order: [
                            [1, 'asc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    $('#supplierBrowseTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const data = this.dataTable.row($(e.target).closest('tr')).data();
                        if (!data) return;
                        window.dispatchEvent(new CustomEvent('supplier-chosen', {
                            detail: data
                        }));
                        this.close();
                    });
                },
                onSupplierChosen(d) {
                    this.supplierId = d.fsuppliercode;
                    this.supplierDisplay = `${d.fsuppliername} (${d.fsuppliercode})`;
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
                close() {
                    this.open = false;
                },
                initDT() {
                    if (this.dataTable) this.dataTable.destroy();
                    this.dataTable = $('#productTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('products.browse') }}",
                            type: 'GET',
                            data: function(d) {
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            }
                        },
                        columns: [{
                                data: 'fprdcode',
                                name: 'fprdcode',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fprdname',
                                name: 'fprdname',
                                className: 'text-sm'
                            },
                            {
                                data: 'fsatuanbesar',
                                name: 'fsatuanbesar',
                                className: 'text-sm',
                                render: d => d || '-'
                            },
                            {
                                data: 'fmerekname',
                                name: 'fmerekname',
                                className: 'text-center text-sm',
                                render: d => d || '-'
                            },
                            {
                                data: 'fminstock',
                                name: 'fminstock',
                                className: 'text-center text-sm'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '100px',
                                render: () =>
                                    '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                        language: {
                            processing: "Memuat data...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Tidak ada data",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data yang ditemukan",
                            emptyTable: "Tidak ada data tersedia",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Selanjutnya",
                                previous: "Sebelumnya"
                            }
                        },
                        order: [
                            [1, 'asc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    $('#productTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                        const productData = this.dataTable.row($(e.target).closest('tr')).data();
                        if (!productData) return;
                        window.dispatchEvent(new CustomEvent('product-chosen', {
                            detail: {
                                code: productData.fprdcode,
                                target: this.target,
                                index: this.targetIdx
                            }
                        }));
                        this.close();
                    });
                }
            }
        }

        function itemsTable() {
            // Hydrate savedItems BEFORE passing to Alpine
            const rawItems = @json($savedItems ?? []);

            const hydratedItems = rawItems.map(it => {
                const code = (it.fitemcode || '').trim();
                let meta = window.PRODUCT_MAP[code];

                if (!meta) {
                    const keys = Object.keys(window.PRODUCT_MAP || {});
                    meta = keys.reduce((found, key) => {
                        if (found) return found;
                        if (key.includes(code) || code.includes(key)) return window.PRODUCT_MAP[key];
                        return null;
                    }, null);
                }

                let units = [];
                if (meta && meta.units && meta.units.length > 0) {
                    units = meta.units;
                } else if (it.fsatuan) {
                    units = [it.fsatuan];
                }

                return {
                    ...it,
                    uid: it.uid || cryptoRandom(),
                    units: units,
                    fsatuan: it.fsatuan || (units[0] || '')
                };
            });

            return {
                savedItems: hydratedItems,
                activeRow: null,
                draft: {
                    fitemcode: '',
                    fitemname: '',
                    fnoacak: '',
                    units: [],
                    fsatuan: '',
                    fqty: 1,
                    fdesc: '',
                    fketdt: ''
                },
                normalizeNoAcak(value) {
                    return (value || '').toString().replace(/\D/g, '').slice(0, 3);
                },
                generateUniqueNoAcak() {
                    const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                    let candidate = '';

                    do {
                        candidate = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                    } while (used.has(candidate));

                    return candidate;
                },
                onCodeTypedDraft() {
                    const meta = window.PRODUCT_MAP[this.draft.fitemcode];
                    if (meta) {
                        this.draft.fprdid = meta.id;
                        this.draft.fitemname = meta.name;
                        this.draft.units = meta.units;
                        this.draft.fsatuan = meta.units[0] || '';
                        this.draft.fnoacak = this.normalizeNoAcak(this.draft.fnoacak) || this.generateUniqueNoAcak();
                    } else {
                        this.draft.fprdid = 0;
                        this.draft.fitemname = '';
                        this.draft.units = [];
                        this.draft.fsatuan = '';
                    }
                },
                onCodeTypedSaved(it) {
                    const meta = window.PRODUCT_MAP[it.fitemcode];
                    if (meta) {
                        it.fprdid = meta.id;
                        it.fitemname = meta.name;
                        it.units = meta.units;
                        if (!it.units.includes(it.fsatuan)) it.fsatuan = it.units[0] || '';
                        it.fnoacak = this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak();
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
                        fnoacak: this.normalizeNoAcak(this.draft.fnoacak) || this.generateUniqueNoAcak(),
                        fqtypo: 0
                    });
                    this.draft = {
                        fitemcode: '',
                        fitemname: '',
                        fnoacak: this.generateUniqueNoAcak(),
                        units: [],
                        fsatuan: '',
                        fqty: 1,
                        fdesc: '',
                        fketdt: ''
                    };
                    this.$nextTick(() => this.$refs.draftCode.focus());
                },
                removeSaved(i) {
                    this.savedItems.splice(i, 1);
                },
                openBrowseFor(target, index = null) {
                    window.dispatchEvent(new CustomEvent('browse-product', {
                        detail: {
                            target,
                            index
                        }
                    }));
                },
                productMeta(code) {
                    const key = (code || '').trim();
                    const meta = window.PRODUCT_MAP?.[key];
                    if (!meta) {
                        return {
                            name: '',
                            units: [],
                            stock: 0,
                            unit_ratios: {
                                satuankecil: 1,
                                satuanbesar: 1,
                                satuanbesar2: 1
                            }
                        };
                    }
                    return meta;
                },

                formatStockLimit(code, qty, satuan) {
                    const meta = this.productMeta(code);
                    if (!code || !meta.stock) return '';

                    const entered = Number(qty) || 0;
                    const remaining = Math.max(0, meta.stock - entered);
                    const units = meta.units || [];
                    const ratios = meta.unit_ratios || {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    };

                    if (!units.length || !satuan) return '';

                    const satKecil = units[0] || 'pcs';
                    const satBesar = units[1] || '';
                    const satBesar2 = units[2] || '';

                    let ratio = 1;
                    if (satuan === satBesar2 && ratios.satuanbesar2 > 0) {
                        ratio = ratios.satuanbesar2;
                    } else if (satuan === satBesar && ratios.satuanbesar > 0) {
                        ratio = ratios.satuanbesar;
                    } else if (satuan === satKecil) {
                        ratio = 1;
                    }

                    const limitValue = Math.floor(remaining / ratio);
                    return '<span class="font-medium">limit:</span> ' + limitValue + ' ' + satuan;
                },

                enforceQtyRow(row) {
                    // max qty validation removed (qty tidak lagi dibatasi mengikuti stok maksimum)
                    return;
                },

                init() {
                    this.savedItems = this.savedItems.map(it => ({
                        ...it,
                        fnoacak: this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak()
                    }));
                    this.draft.fnoacak = this.generateUniqueNoAcak();

                    window.addEventListener('product-chosen', (e) => {
                        const {
                            code,
                            target,
                            index
                        } = e.detail;
                        if (target === 'saved') {
                            const it = this.savedItems[index];
                            it.fitemcode = code;
                            this.onCodeTypedSaved(it);
                        } else {
                            this.draft.fitemcode = code;
                            this.onCodeTypedDraft();
                        }
                    });
                }
            }
        }
    </script>
@endpush
