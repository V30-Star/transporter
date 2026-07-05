@extends('layouts.app')

@section('title', $action === 'delete' ? 'Penerimaan Barang - Delete' : ($action === 'view' ? 'Penerimaan Barang - View'
    : 'Penerimaan Barang - Edit'))

@section('content')
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updatePenerimaanBarang', $permissions, true);
        $canDeletePermission = in_array('deletePenerimaanBarang', $permissions, true);
        $canViewHpp = $canViewHpp ?? in_array('viewProductHpp', explode(',', session('user_restricted_permissions', '')));
    @endphp
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        .penerimaan-detail-table th,
        .penerimaan-detail-table td {
            padding: .25rem .375rem !important;
        }

        .penerimaan-detail-table input:not([type="hidden"]),
        .penerimaan-detail-table select,
        .penerimaan-detail-table button {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .penerimaan-detail-table .rounded-l.border,
        .penerimaan-detail-table .rounded-r.border {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .penerimaan-detail-table button {
            display: inline-flex;
            align-items: center;
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

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    @php
        $usageLocked = !empty($isUsageLocked);
    @endphp
    @php
        $isDelete = $action === 'delete';
        $isView = $action === 'view';
        $isEdit = $action === 'edit';
        $isReadOnly = $isDelete || $isView;
        $canPrint =
            in_array('viewTr_prh', $permissions, true) ||
            in_array('updatePenerimaanBarang', $permissions, true) ||
            in_array('deletePenerimaanBarang', $permissions, true) ||
            in_array('createPenerimaanBarang', $permissions, true);
    @endphp
    @if ($usageLocked && !$isView)
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-white w-[92vw] max-w-xl rounded-2xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-orange-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-orange-700">
                            {{ $isDelete ? 'Penerimaan Barang Tidak Dapat Dihapus' : 'Penerimaan Barang Tidak Dapat Diedit' }}
                        </h3>
                        <p class="text-sm text-orange-500 mt-0.5">{{ $usageLockMessage }}</p>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 hover:bg-orange-200 flex items-center justify-center transition-colors"
                        title="Tutup">
                        <x-heroicon-o-x-mark class="w-4 h-4 text-orange-600" />
                    </button>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                    <button type="button" @click="open = false"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
        @if ($isReadOnly)
        {{-- Read Only View Mode --}}
        <div class="max-w-[1600px] mx-auto py-8 px-6">
            {{-- ─── CARD 1: Identitas Penerimaan Barang ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Penerimaan Barang</p>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        {{-- Cabang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                        </div>

                        {{-- Transaksi# --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Transaksi#</label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                value="{{ $displayFstockmtno ?? $penerimaanbarang->fstockmtno }}" disabled>
                        </div>

                        {{-- Supplier --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Supplier</label>
                            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>
                                <option value=""></option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->fsuppliercode }}"
                                        {{ old('fsupplier', $penerimaanbarang->fsupplier) == $supplier->fsuppliercode ? 'selected' : '' }}>
                                        {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        {{-- Gudang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Gudang</label>
                            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>
                                <option value=""></option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->fwhcode }}"
                                        {{ old('ffrom', $penerimaanbarang->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                        {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal</label>
                            <input type="date"
                                value="{{ old('fstockmtdate', \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>
                        </div>
                    </div>

                    {{-- Keterangan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                        <textarea rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>{{ $penerimaanbarang->fket }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ─── CARD 2: Detail Item (ReadOnly) ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden" x-data="{ showDescModal: false, descItemName: '', descValue: '' }">
               <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                </div>
                <div class="p-4">
                    <div class="overflow-auto border border-gray-200 rounded-lg">
                        <table class="penerimaan-detail-table min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="p-2 text-left w-10 text-xs font-semibold text-gray-500 uppercase">#</th>
                                    <th class="p-2 text-left w-36 text-xs font-semibold text-gray-500 uppercase">Kode Produk</th>
                                    <th class="p-2 text-left text-xs font-semibold text-gray-500 uppercase" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                    <th class="p-2 text-left w-24 text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                                    <th class="p-2 text-left w-24 text-xs font-semibold text-gray-500 uppercase">Ref.PO#</th>
                                    <th class="p-2 text-right w-24 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                    @if ($canViewHpp)
                                        <th class="p-2 text-right w-28 text-xs font-semibold text-gray-500 uppercase">@ Harga</th>
                                        <th class="p-2 text-right w-28 text-xs font-semibold text-gray-500 uppercase">Total Harga</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($savedItems as $index => $item)
                                    <tr class="border-t border-gray-150 align-top hover:bg-gray-50">
                                        <td class="p-2 text-gray-400">{{ $index + 1 }}</td>

                                        {{-- Kode Produk --}}
                                        <td class="p-2 font-mono text-gray-800">
                                            <input type="text"
                                                class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                value="{{ $item['fitemcode'] }}" disabled>
                                        </td>

                                        {{-- Nama Produk --}}
                                        <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                            <div class="desc-inline-field flex w-full min-w-0 flex-nowrap items-stretch">
                                                <div class="desc-inline-field__text min-w-0 flex-1 rounded-l-lg border border-gray-300 bg-gray-50 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                    style="flex:1 1 auto !important; min-width:0 !important;">{{ $item['fitemname'] ?? '-' }}</div>
                                                <button type="button" @click="descItemName = @js($item['fitemname'] ?? ''); descValue = @js($item['fdesc'] ?? ''); showDescModal = true"
                                                    class="desc-inline-field__button inline-flex w-10 shrink-0 items-center justify-center border border-l-0 border-gray-300 rounded-r-lg px-2 py-1 transition-colors border-gray-300 bg-white text-gray-500 hover:bg-gray-50"
                                                    style="display:inline-flex !important; flex:0 0 2rem !important; width:2rem !important; justify-content:center !important; align-items:center !important;"
                                                    title="Deskripsi item">
                                                    <x-heroicon-o-document-text class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>

                                        {{-- Satuan --}}
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                value="{{ $item['fsatuan'] ?? '-' }}" disabled>
                                        </td>

                                        {{-- Ref.PO# --}}
                                        <td class="p-2">
                                            <input type="text"
                                                class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                value="{{ $item['frefdtno'] ?? '-' }}" disabled>
                                        </td>

                                        {{-- Qty --}}
                                        <td class="p-2 text-right">
                                            <input type="text"
                                                class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm text-right cursor-not-allowed"
                                                value="{{ number_format($item['fqty'], 2) }}" disabled>
                                        </td>

                                        {{-- @ Harga & Total --}}
                                        @if ($canViewHpp)
                                            <td class="p-2 text-right">
                                                <input type="text"
                                                    class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm text-right cursor-not-allowed"
                                                    value="{{ number_format($item['fprice'], 2) }}" disabled>
                                            </td>

                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border border-gray-250 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm text-right cursor-not-allowed"
                                                    value="{{ number_format($item['ftotal'], 2) }}" disabled>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals --}}
                    @if ($canViewHpp)
                        <div class="mt-3 flex justify-end">
                            <div class="w-[480px] shrink-0 max-w-full">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-gray-800">Total Harga</span>
                                        <span class="font-bold text-gray-900">{{ number_format($famountponet, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- MODAL: deskripsi item (ReadOnly) --}}
                <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
                    x-transition.opacity>
                    <div class="absolute inset-0" @click="showDescModal = false"></div>
                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Nama Produk</div>
                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="descItemName || '-'"></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Deskripsi</div>
                                <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2 bg-gray-50 cursor-not-allowed text-sm text-gray-700" disabled></textarea>
                            </div>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end bg-gray-50">
                            <button type="button" @click="showDescModal = false"
                                class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── CARD 3: Aksi / Footer (ReadOnly) ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                    <button type="button"
                        onclick="window.location.href='{{ route('penerimaanbarang.index') }}'"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Kembali
                    </button>
                    <div class="flex gap-2">
                        @if ($action === 'delete' && $canDeletePermission)
                            @if ($usageLocked)
                                <button type="button" disabled title="{{ $usageLockMessage }}"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-red-300 text-white text-sm font-medium rounded-lg cursor-not-allowed opacity-70 border border-red-200">
                                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                                    Hapus
                                </button>
                            @else
                                <button type="button" onclick="showDeleteModal()"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                    Hapus
                                </button>
                            @endif
                        @elseif ($action === 'view' && $canPrint)
                            <a href="{{ route('penerimaanbarang.print', $penerimaanbarang->fstockmtid) }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                                    </path>
                                </svg>
                                Print
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Edit Mode --}}
        <div class="max-w-[1600px] mx-auto py-8 px-6">
            <form action="{{ route('penerimaanbarang.update', $penerimaanbarang->fstockmtid) }}" method="POST" data-form-draft="true"
                data-draft-key="penerimaanbarang:edit:{{ $penerimaanbarang->fstockmtid }}" x-data="mainForm()" x-init="init()"
                @submit.prevent="submitForm($el)">
                @csrf
                @method('PATCH')

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold mb-1">Tidak dapat menyimpan</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ─── CARD 1: Identitas Penerimaan Barang ────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Penerimaan Barang</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="grid grid-cols-3 gap-3">
                            {{-- Cabang --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                                <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                    value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
                                <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $fbranchcode) }}">
                            </div>

                            {{-- Transaksi# --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Transaksi#</label>
                                <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                    value="{{ $displayFstockmtno ?? $penerimaanbarang->fstockmtno }}" disabled>
                                <input type="hidden" name="fstockmtno" value="{{ $penerimaanbarang->fstockmtno }}">
                            </div>

                            {{-- Supplier --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                                <div class="flex">
                                    <div class="relative flex-1">
                                        <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                            class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-55 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->fsuppliercode }}"
                                                    {{ old('fsupplier', $penerimaanbarang->fsupplier) == $supplier->fsuppliercode ? 'selected' : '' }}>
                                                    {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse supplier"
                                            @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="fsupplier" id="supplierCodeHidden" value="{{ old('fsupplier', $penerimaanbarang->fsupplier) }}">
                                    <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                        class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Browse Supplier">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                    @if (in_array('createSupplier', explode(',', session('user_restricted_permissions', '')), true))
                                        <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                                            class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                            title="Tambah Supplier">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                        </a>
                                    @endif
                                </div>
                                @error('fsupplier')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            {{-- Gudang --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Gudang <span class="text-red-500">*</span></label>
                                <div class="flex">
                                    <div class="relative flex-1">
                                        <select id="warehouseSelect"
                                            class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-55 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($warehouses as $wh)
                                                <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                                                    data-branch="{{ $wh->fbranchcode }}"
                                                    {{ old('ffrom', $penerimaanbarang->ffrom) == $wh->fwhcode ? 'selected' : '' }}>
                                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse warehouse"
                                            @click="window.dispatchEvent(new CustomEvent('penerimaanbarang-warehouse-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom', $penerimaanbarang->ffrom) }}">
                                    <button type="button" @click="window.dispatchEvent(new CustomEvent('penerimaanbarang-warehouse-browse-open'))"
                                        class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Browse Gudang">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                    <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                                        class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Tambah Gudang">
                                        <x-heroicon-o-plus class="w-4 h-4" />
                                    </a>
                                </div>
                                @error('ffrom')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tanggal --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal <span class="text-red-500">*</span></label>
                                <input type="date" name="fstockmtdate"
                                    value="{{ old('fstockmtdate', \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('Y-m-d')) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fstockmtdate') border-red-400 @enderror">
                                @error('fstockmtdate')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Keterangan --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                            <textarea name="fket" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-400 @enderror"
                                placeholder="Tulis keterangan tambahan di sini...">{{ old('fket', $penerimaanbarang->fket) }}</textarea>
                            @error('fket')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ─── CARD 2: Detail Item ────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                     <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Item</p>
                    </div>
                    <div class="p-4">
                        <div class="overflow-auto border border-gray-200 rounded-lg">
                            <table class="penerimaan-detail-table min-w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="p-2 text-left w-10 text-xs font-semibold text-gray-500 uppercase">#</th>
                                        <th class="p-2 text-left w-36 text-xs font-semibold text-gray-500 uppercase">Kode Produk</th>
                                        <th class="p-2 text-left text-xs font-semibold text-gray-500 uppercase" style="width: 20rem; min-width: 20rem;">Nama Produk</th>
                                        <th class="p-2 text-left w-24 text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                                        <th class="p-2 text-left w-24 text-xs font-semibold text-gray-500 uppercase">Ref.PO#</th>
                                        <th class="p-2 text-right w-24 text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                        @if ($canViewHpp)
                                            <th class="p-2 text-right w-28 text-xs font-semibold text-gray-500 uppercase">@ Harga</th>
                                            <th class="p-2 text-right w-28 text-xs font-semibold text-gray-500 uppercase">Total Harga</th>
                                        @endif
                                        <th class="p-2 text-center w-16 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(it, i) in savedItems" :key="it.uid || `row-${i}`">
                                        <tr class="border-t border-gray-150 align-top hover:bg-gray-50">
                                            <td class="p-2 text-gray-400" x-text="i + 1"></td>

                                            {{-- Kode Produk --}}
                                            <td class="p-2 font-mono text-gray-800">
                                                <input type="text"
                                                    class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                    x-model.trim="it.fitemcode" disabled>
                                            </td>

                                            {{-- Nama Produk --}}
                                            <td class="p-2" style="width: 20rem; min-width: 20rem;">
                                                <div class="desc-inline-field flex w-full min-w-0 flex-nowrap items-stretch">
                                                    <div class="desc-inline-field__text min-w-0 flex-1 rounded-l-lg border border-gray-300 bg-gray-50 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                        style="flex:1 1 auto !important; min-width:0 !important;"
                                                        x-text="it.fitemname || '-'"></div>
                                                    <button type="button" @click="openDesc(it)"
                                                        class="desc-inline-field__button inline-flex w-10 shrink-0 items-center justify-center border border-l-0 border-gray-300 rounded-r-lg px-2 py-1 transition-colors"
                                                        style="display:inline-flex !important; flex:0 0 2rem !important; width:2rem !important; justify-content:center !important; align-items:center !important;"
                                                        :class="it.fdesc ?
                                                            'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100' :
                                                            'border-gray-300 bg-white text-gray-500 hover:bg-gray-50'"
                                                        title="Deskripsi item">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>

                                            {{-- Satuan --}}
                                            <td class="p-2">
                                                <template x-if="unitOptions(it).length > 1 && !it.frefdtid">
                                                    <select class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:border-blue-500"
                                                        :id="'unit_saved_' + i" x-model="it.fsatuan"
                                                        @focus="activeRow = it.uid" @blur="activeRow = null"
                                                        @change="onRowUpdated(i)" @keydown.enter.prevent="focusSavedQty(i)">
                                                        <template x-for="u in unitOptions(it)" :key="u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="unitOptions(it).length <= 1 || it.frefdtid">
                                                    <input type="text"
                                                        class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                        :value="it.fsatuan || '-'" disabled>
                                                </template>
                                            </td>

                                            {{-- Ref.PO# --}}
                                            <td class="p-2">
                                                <input type="text"
                                                    class="w-full border border-gray-200 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm cursor-not-allowed"
                                                    :value="it.frefdtno || '-'" disabled>
                                            </td>

                                            {{-- Qty --}}
                                            <td class="p-2 text-right">
                                                <input type="number" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-right text-sm focus:outline-none focus:border-blue-500"
                                                    :id="'qty_saved_' + i" x-model.number="it.fqty" min="0" step="any"
                                                    @focus="activeRow = it.uid; $event.target.select()"
                                                    @blur="activeRow = null" @input="onRowUpdated(i)"
                                                    @change="onRowUpdated(i)" @keydown.enter.prevent="focusSavedPrice(i)">
                                                <div class="text-[10px] text-amber-700 font-medium text-right mt-0.5"
                                                    x-show="it.frefdtid && calcMaxQty(it) > 0"
                                                    x-html="formatPoRemainHint(it)">
                                                </div>
                                            </td>

                                            {{-- @ Harga --}}
                                            @if ($canViewHpp)
                                                <td class="p-2 text-right">
                                                    <input type="text" inputmode="decimal"
                                                        class="w-full border border-gray-300 rounded-lg px-2 py-1 text-right text-sm focus:outline-none focus:border-blue-500"
                                                        x-model="it.fpriceInput" @input="onPriceInput(it)" :id="'price_saved_' + i"
                                                        @focus="activeRow = it.uid; focusPriceInput(it); $event.target.select()"
                                                        @blur="activeRow = null; blurPriceInput(it)" @change="recalc(it)"
                                                        @keydown.enter.prevent="focusSavedDisc(i)">
                                                </td>

                                                {{-- Total --}}
                                                <td class="p-2">
                                                    <input type="text"
                                                        class="w-full border border-gray-250 rounded-lg px-2 py-1 bg-gray-50 text-gray-500 text-sm text-right cursor-not-allowed"
                                                        :value="formatTransactionAmount(it.ftotal)" disabled>
                                                </td>
                                            @endif

                                            {{-- Aksi --}}
                                            <td class="p-2 text-center">
                                                <div class="flex items-center justify-center">
                                                    <button type="button" @click="removeSaved(i)"
                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors border border-red-200"
                                                        title="Hapus baris">
                                                        <x-heroicon-o-minus class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Hidden Submit inputs --}}
                        <div class="hidden">
                            <template x-for="it in savedItems" :key="'submit-' + it.uid">
                                <div>
                                    <input type="hidden" name="fitemcode[]" :value="it.fitemcode">
                                    <input type="hidden" name="fitemname[]" :value="it.fitemname">
                                    <input type="hidden" name="fsatuan[]" :value="it.fsatuan">
                                    <input type="hidden" name="frefdtno[]" :value="it.frefdtno">
                                    <input type="hidden" name="frefdtid[]" :value="it.frefdtid">
                                    <input type="hidden" name="fnoacak[]" :value="it.fnoacak">
                                    <input type="hidden" name="frefnoacak[]" :value="it.frefnoacak">
                                    <input type="hidden" name="fnouref[]" :value="it.fnouref">
                                    <input type="hidden" name="frefpr[]" :value="it.frefpr">
                                    <input type="hidden" name="fprhid[]" :value="it.fprhid">
                                    <input type="hidden" name="fqty[]" :value="it.fqty">
                                    <input type="hidden" name="fprice[]" :value="it.fprice">
                                    <input type="hidden" name="ftotal[]" :value="it.ftotal">
                                    <input type="hidden" name="fdesc[]" :value="it.fdesc">
                                    <input type="hidden" name="fketdt[]" :value="it.fketdt">
                                </div>
                            </template>
                        </div>

                        {{-- Add PO + Panel Totals --}}
                        <div x-data="pohFormModal()">
                            <div class="mt-3 flex justify-between items-start gap-4 flex-wrap">
                                <div class="flex justify-start">
                                    <button type="button" @click="openModal()"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-sm transition-colors">
                                        <x-heroicon-o-plus class="h-4 w-4" />
                                        Add PO
                                    </button>
                                </div>

                                {{-- Panel Totals --}}
                                @if ($canViewHpp)
                                    <div class="w-[480px] shrink-0 max-w-full">
                                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3 text-sm">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-gray-800">Total Harga</span>
                                                <span class="font-bold text-gray-900" x-text="fmtCurr(totalHarga)"></span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="famountponet" :value="totalHarga">
                                    </div>
                                @endif
                            </div>

                            {{-- Modal backdrop --}}
                            <div x-show="show" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                                @keydown.escape.window="closeModal()"></div>

                            {{-- MODAL PO --}}
                            <div x-show="show" x-cloak x-transition.opacity
                                class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-8" aria-modal="true"
                                role="dialog">
                                <div class="relative w-full max-w-5xl rounded-2xl bg-white shadow-2xl flex flex-col overflow-hidden"
                                    style="height: 600px;">
                                    <div
                                        class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-teal-50 to-white">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-800">Add PO</h3>
                                            <p class="text-sm text-gray-500 mt-0.5">Pilih Purchase Order yang diinginkan</p>
                                        </div>
                                        <button type="button" @click="closeModal()"
                                            class="h-9 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm transition-colors">
                                            Tutup
                                        </button>
                                    </div>
                                    <div class="flex-1 overflow-y-auto p-6" style="min-height: 0;">
                                        <table id="poTable" class="min-w-full text-sm display nowrap stripe hover"
                                            style="width:100%">
                                            <thead class="sticky top-0 z-10">
                                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                    <th class="p-3 text-left font-semibold text-gray-700">PO No</th>
                                                    <th class="p-3 text-left font-semibold text-gray-700">Ref No PO</th>
                                                    <th class="p-3 text-left font-semibold text-gray-700">Supplier</th>
                                                    <th class="p-3 text-left font-semibold text-gray-700">Tanggal</th>
                                                    <th class="p-3 text-center font-semibold text-gray-700">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                    <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
                                </div>
                            </div>

                            {{-- Modal Duplikasi --}}
                            <div x-show="showDupModal" x-cloak x-transition.opacity
                                class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                <div class="absolute inset-0 bg-black/40" @click="closeDupModal()"></div>
                                <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6">
                                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Peringatan Duplikasi</h3>
                                    <p class="mb-4 text-gray-600">Ditemukan <strong x-text="dupCount"></strong> item yang sudah ada dalam
                                        daftar.</p>
                                    <div class="mb-4 max-h-48 overflow-auto border rounded-lg p-2 bg-gray-50"
                                        x-show="dupSample.length > 0">
                                        <p class="text-sm font-medium mb-2 text-gray-700">Contoh item duplikat:</p>
                                        <template x-for="(item, idx) in dupSample" :key="idx">
                                            <div class="text-xs py-1 text-gray-600">• <span x-text="item.fitemcode"></span></div>
                                        </template>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="closeDupModal()"
                                            class="h-9 px-4 rounded-lg bg-gray-150 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">Batal</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL: deskripsi item --}}
                <div x-show="showDescModal" x-cloak class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
                    x-transition.opacity>
                    <div class="absolute inset-0 bg-black/50" @click="closeDesc()"></div>
                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center">
                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                            <h3 class="text-lg font-semibold text-gray-800">Isi Deskripsi Item</h3>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <div class="text-sm text-gray-700">Nama Produk</div>
                                    <button type="button" @click="copyDescName()"
                                        class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">
                                        Copy
                                    </button>
                                </div>
                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                    x-text="descItemName || '-'"></div>
                            </div>
                            <label class="block text-sm text-gray-700 font-bold">Deskripsi</label>
                            <textarea x-model="descValue" rows="5" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="Tulis deskripsi item di sini..."></textarea>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                            <button type="button" @click="closeDesc()"
                                class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                Batal
                            </button>
                            <button type="button" @click="applyDesc()"
                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>

                {{-- MODAL: supplier warning modal --}}
                <div x-show="showWarningModal" x-cloak class="fixed inset-0 z-[96] flex items-center justify-center bg-black/50"
                    x-transition.opacity>
                    <div class="absolute inset-0" @click="closeWarning()"></div>
                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center bg-amber-50 text-amber-700">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                            <h3 class="text-lg font-semibold" x-text="warningTitle"></h3>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <p class="text-sm text-gray-700" x-text="warningMessage"></p>
                            <template x-show="warningItems.length > 0">
                                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                                    <template x-for="item in warningItems" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                            <button type="button" @click="closeWarning()"
                                class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                Tutup
                            </button>
                            <button type="button" x-show="warningCanProceed" @click="confirmWarningAndSubmit()"
                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                Lanjut Simpan
                            </button>
                        </div>
                    </div>
                </div>

                {{-- MODAL: belum ada item --}}
                <div x-show="showNoItems" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50"
                    x-transition.opacity>
                    <div class="absolute inset-0" @click="showNoItems=false"></div>
                    <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center bg-red-50 text-red-700">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                            <h3 class="text-lg font-semibold">Tidak Ada Item</h3>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-sm text-gray-700">Anda belum menambahkan item. Silakan isi baris "Detail Item"
                                terlebih dahulu.</p>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                            <button type="button" @click="showNoItems=false"
                                class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">OK</button>
                        </div>
                    </div>
                </div>

                {{-- MODAL: supplier belum dipilih --}}
                <div x-show="showNoSupplier" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50"
                    x-transition.opacity>
                    <div class="absolute inset-0" @click="showNoSupplier=false"></div>
                    <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center bg-amber-50 text-amber-700">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                            <h3 class="text-lg font-semibold">Supplier Belum Dipilih</h3>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-sm text-gray-700">Silakan pilih <strong>Supplier</strong> terlebih dahulu sebelum
                                menambahkan item.</p>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                            <button type="button" @click="showNoSupplier=false"
                                class="h-9 px-4 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">Tutup</button>
                            <button type="button"
                                @click="showNoSupplier=false; window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="h-9 px-4 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600 transition-colors">
                                Pilih Supplier
                            </button>
                        </div>
                    </div>
                </div>

                {{-- MODAL: Produk duplikat --}}
                <div x-show="showDupItemModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50"
                    x-transition.opacity>
                    <div class="absolute inset-0" @click="showDupItemModal=false"></div>
                    <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                        <div class="px-5 py-4 border-b flex items-center bg-red-50 text-red-700">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                            <h3 class="text-lg font-semibold">Produk Sudah Ada</h3>
                        </div>
                        <div class="px-5 py-4 space-y-1">
                            <p class="text-sm text-gray-700">
                                Produk <strong x-text="dupItemName"></strong>
                                <template x-if="dupItemSatuan">
                                    <span> (<span x-text="dupItemSatuan"></span>)</span>
                                </template>
                                sudah ada di daftar item.
                            </p>
                            <p class="text-sm text-gray-500">Satu produk dengan satuan yang sama hanya boleh ditambahkan satu
                                kali.</p>
                        </div>
                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                            <button type="button" @click="showDupItemModal=false"
                                class="h-9 px-4 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">OK</button>
                        </div>
                    </div>
                </div>

                <x-transaction.browse-supplier-modal :open-delay="50" :destroy-on-close="true" />
                <x-transaction.browse-warehouse-modal event-name="penerimaanbarang-warehouse-browse-open" />
                <x-transaction.browse-product-modal />

                {{-- ─── CARD 3: Aksi / Footer (Edit) ────────────────────── --}}
                <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                        <button type="button"
                            onclick="window.location.href='{{ route('penerimaanbarang.index') }}'"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                            <x-heroicon-o-arrow-left class="w-4 h-4" />
                            Keluar
                        </button>
                        @if ($canEditPermission)
                            @if ($usageLocked)
                                <button type="button" disabled title="{{ $usageLockMessage }}"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-blue-300 text-white text-sm font-medium rounded-lg cursor-not-allowed opacity-70 border border-blue-200">
                                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                                    Simpan
                                </button>
                            @else
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <x-heroicon-o-check class="w-4 h-4" />
                                    Simpan
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <input type="hidden" id="itemsCount" :value="savedItems.length">
            </form>
        </div>
    @endif


    {{-- MODAL KONFIRMASI HAPUS --}}
    @if ($action === 'delete' && $canDeletePermission)
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus Penerimaan Barang ini?</h3>
                <form action="{{ route('penerimaanbarang.destroy', $penerimaanbarang->fstockmtid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Tidak</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Ya,
                            Hapus</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
    @endif

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        {{-- â”€â”€ Identik dengan create.blade.php â”€â”€ --}}
        window.CURRENCY_MAP = window.CURRENCY_MAP || {};

        window.PRODUCT_MAP = {
            @foreach ($products as $p)
                @php
                    $defaultUnit = match ((string) ($p->fsatuandefault ?? '')) {
                        '1' => trim((string) ($p->fsatuankecil ?? '')),
                        '2' => trim((string) ($p->fsatuanbesar ?? '')),
                        '3' => trim((string) ($p->fsatuanbesar2 ?? '')),
                        default => trim((string) ($p->fsatuankecil ?? '')) ?: trim((string) ($p->fsatuanbesar ?? '')) ?: trim((string) ($p->fsatuanbesar2 ?? '')),
                    };
                @endphp
                    "{{ $p->fprdcode }}": {
                        id: @json($p->fprdid),
                        name: @json($p->fprdname),
                        default_unit: @json($defaultUnit),
                        units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                        stock: @json($p->fminstock ?? 0),
                        unit_ratios: {
                            satuankecil: 1,
                            satuanbesar: @json((float) ($p->fqtykecil ?? 1)),
                            satuanbesar2: @json((float) ($p->fqtykecil2 ?? 1)),
                        },
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

        window.fetchLastPrice = async function(fprdcode, fsupplier, fsatuan) {
            if (!fprdcode || !fsupplier || !fsatuan) return null;
            try {
                const url = new URL("{{ route('tr_poh.lastPrice') }}", window.location.origin);
                url.searchParams.set('fprdcode', fprdcode);
                url.searchParams.set('fsupplier', fsupplier);
                url.searchParams.set('fsatuan', fsatuan);
                const res = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return null;
                return await res.json();
            } catch (e) {
                return null;
            }
        };

        // â”€â”€â”€ mainForm() â€” sama persis dengan create, satu-satunya beda:
        //     savedItems diisi dari $savedItems (data existing dari DB)
        function mainForm() {
            function newRow() {
                return {
                    uid: null,
                    fitemcode: '',
                    fitemname: '',
                    units: [],
                    fsatuan: '',
                    frefdtno: '',
                    fnoacak: '',
                    frefnoacak: '',
                    fnouref: '',
                    frefpr: '',
                    fprhid: '',
                    fprno: '',
                    fpono: '',
                    fqty: 0,
                    fprice: 0,
                    fpriceInput: '0.00',
                    ftotal: 0,
                    fdesc: '',
                    fketdt: '',
                    maxqty: 0,
                    fqtypr: 0,
                    fqtypr_satuan: '',
                    fsatuankecil: '',
                    fsatuanbesar: '',
                    fsatuanbesar2: '',
                    fqtykecil: 0,
                    fqtykecil2: 0,
                    maxqty_satuan: '',
                    unit_ratios: {
                        satuankecil: 1,
                        satuanbesar: 1,
                        satuanbesar2: 1
                    },
                    frefdtid: '',
                    fqtykecil_ref: 0,
                    fqtypo: 0,
                    fqtysisapo: 0,
                    fqtyditer: 0,
                    fqtymaxedit: 0,
                };
            }

            return {
                autoCode: true,
                selectedCurrId: '',
                selectedCurrCode: 'IDR',
                rateValue: 1,
                includePPN: false,
                ppnMode: 0,
                ppnRate: 11,
                // â”€â”€ Diisi dari DB (perbedaan utama vs create) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                savedItems: @json($savedItems),
                activeRow: null,
                browseTarget: null,
                showNoItems: false,
                showNoSupplier: false,
                showDupItemModal: false,
                dupItemName: '',
                dupItemSatuan: '',
                showDescModal: false,
                descValue: '',
                descItemName: '',
                descReadonly: false,
                _descTarget: null,
                showWarningModal: false,
                warningTitle: 'Perhatian',
                warningMessage: '',
                warningItems: [],
                warningCanProceed: false,
                pendingSubmitForm: null,
                pendingValidRows: [],
                minimumVisibleRows: @json(count($savedItems ?? []) + 5),

                rowHasContent(row) {
                    if (!row) return false;
                    return this.isRowFilled(row);
                },

                ensureMinimumRows() {
                    while (this.savedItems.length < this.minimumVisibleRows) {
                        this.savedItems.push(this.createRow());
                    }
                },

                ensureTrailingRow(index = null) {
                    if ($action === 'delete') return;
                    if (!this.savedItems.length) {
                        this.ensureMinimumRows();
                        return;
                    }

                    const targetIndex = index === null ? this.savedItems.length - 1 : index;
                    if (targetIndex !== this.savedItems.length - 1) return;

                    if (this.rowHasContent(this.savedItems[targetIndex])) {
                        this.savedItems.push(this.createRow());
                    }
                },

                onRowUpdated(index = null) {
                    const row = typeof index === 'number' ? this.savedItems[index] : null;
                    if (row) {
                        this.recalc(row);
                        this.calcMaxQty(row);
                    }
                    this.ensureTrailingRow(index);
                },

                get totalHarga() {
                    return this.savedItems.reduce((s, it) => s + (it.ftotal || 0), 0);
                },

                fmtCurr(n) {
                    const v = Number(n || 0);
                    if (!isFinite(v)) return '-';
                    return v.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },
                rupiah(n) {
                    const v = Number(n || 0);
                    if (!isFinite(v)) return '-';
                    return v.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                recalc(row) {
                    const qty = Math.max(0, +row.fqty || 0);
                    const price = Math.max(0, +row.fprice || 0);
                    const disc = Math.min(100, Math.max(0, +row.fdisc || 0));
                    row.fqty = qty;
                    row.fprice = price;
                    if (typeof row.fpriceInput === 'undefined') {
                        row.fpriceInput = this.fmt(price);
                    }
                    row.fdisc = disc;
                    row.ftotal = +(qty * price * (1 - disc / 100)).toFixed(2);
                },
                sanitizePriceValue(value) {
                    let str = (value ?? '').toString().trim();
                    if (str === '') return '';
                    if (str.includes(',')) {
                        str = str.replace(/\./g, '').replace(',', '.');
                    }
                    const raw = str.replace(/[^0-9.]/g, '');
                    const parts = raw.split('.');
                    if (parts.length <= 1) return raw;
                    return `${parts.shift()}.${parts.join('')}`;
                },
                focusPriceInput(row) {
                    const price = Math.max(0, +row.fprice || 0);
                    row.fpriceInput = price > 0 ? this.fmt(price) : '';
                },
                onPriceInput(row) {
                    row.fpriceInput = this.sanitizePriceValue(row.fpriceInput);
                    row.fprice = Math.max(0, +(row.fpriceInput || 0));
                    this.recalc(row);
                },
                blurPriceInput(row) {
                    row.fprice = Math.max(0, +(this.sanitizePriceValue(row.fpriceInput) || 0));
                    row.fpriceInput = this.fmt(row.fprice);
                    this.recalc(row);
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

                formatPoRemainHint(row) {
                    return '';
                },

                enforcePoQtyRow(row) {
                    const n = +row.fqty;
                    if (!Number.isFinite(n)) {
                        row.fqty = 1;
                        return;
                    }
                    if (n < 0.001) row.fqty = 0.001;

                    if (!row.frefdtid) return;
                    row.maxqty = this.calcMaxQty(row);
                },

                hydrateRowFromMeta(row, meta, keepMaxqty = false, forceDefaultUnit = false) {
                    if (!meta) {
                        row.fitemname = '';
                        row.units = [];
                        row.fsatuan = '';
                        if (!keepMaxqty) row.maxqty = 0;
                        return;
                    }
                    row.fitemname = meta.name || '';
                    const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                    const currentSatuan = (row.fsatuan || '').trim();
                    const defaultUnit = (meta.default_unit || '').toString().trim();
                    const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                    if (currentSatuan && !units.includes(currentSatuan)) units.unshift(currentSatuan);
                    row.units = units;
                    if (forceDefaultUnit) {
                        row.fsatuan = resolvedDefaultUnit;
                    } else if (!currentSatuan) {
                        row.fsatuan = resolvedDefaultUnit;
                    }
                    if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                    if (!keepMaxqty) row.maxqty = 0;
                },
                unitOptions(row) {
                    const options = [
                        ...(Array.isArray(row?.units) ? row.units : []),
                        row?.fsatuankecil || '',
                        row?.fsatuanbesar || '',
                        row?.fsatuanbesar2 || '',
                    ].map(u => (u ?? '').toString().trim()).filter(Boolean);
                    const currentSatuan = (row?.fsatuan || '').toString().trim();
                    if (currentSatuan) options.unshift(currentSatuan);
                    return options.filter((value, index, self) => self.indexOf(value) === index);
                },
                metaFromProductPayload(product) {
                    if (!product) return null;
                    const smallUnit = (product.fsatuankecil ?? '').toString().trim();
                    const largeUnit = (product.fsatuanbesar ?? '').toString().trim();
                    const largeUnit2 = (product.fsatuanbesar2 ?? '').toString().trim();
                    const defaultKey = (product.fsatuandefault ?? '').toString().trim();
                    const upperDefaultKey = defaultKey.toUpperCase();
                    const defaultUnit = ({
                        '1': smallUnit,
                        '2': largeUnit,
                        '3': largeUnit2,
                    }[defaultKey] || ([smallUnit, largeUnit, largeUnit2].find((unit) => unit.toUpperCase() ===
                        upperDefaultKey) || smallUnit || largeUnit || largeUnit2));
                    const units = [defaultUnit, smallUnit, largeUnit, largeUnit2]
                        .filter(Boolean)
                        .filter((value, index, self) => self.indexOf(value) === index);
                    return {
                        id: product.fprdid ?? null,
                        name: product.fprdname ?? '',
                        default_unit: defaultUnit,
                        units,
                        stock: Number(product.fminstock ?? 0),
                        fsatuankecil: product.fsatuankecil ?? '',
                        fsatuanbesar: product.fsatuanbesar ?? '',
                        fsatuanbesar2: product.fsatuanbesar2 ?? '',
                        fqtykecil: Number(product.fqtykecil ?? 0),
                        fqtykecil2: Number(product.fqtykecil2 ?? 0),
                        unit_ratios: {
                            satuankecil: 1,
                            satuanbesar: Number(product.fqtykecil ?? 1),
                            satuanbesar2: Number(product.fqtykecil2 ?? 1),
                        }
                    };
                },

                onCodeTypedRow(row) {
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), false, true);
                    this.$nextTick(() => this.applyLastPrice(row));
                },
                onCodeTypedSaved(item, index = null) {
                    this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode), false, true);
                    this.$nextTick(() => this.applyLastPrice(item));
                    this.onRowUpdated(index);
                },

                getSupplier() {
                    return (document.getElementById('supplierCodeHidden')?.value || '').trim();
                },

                async applyLastPrice(row) {
                    const supplier = this.getSupplier();
                    const code = (row.fitemcode || '').trim();
                    const satuan = (row.fsatuan || '').trim();
                    if (!code || !supplier || !satuan) return;
                    const hist = await window.fetchLastPrice(code, supplier, satuan);
                    if (!hist) return;
                    if (!row.fprice || row.fprice === 0) {
                        row.fprice = hist.fprice;
                        row.fpriceInput = Number(row.fprice || 0).toFixed(2);
                        row.fdisc = hist.fdisc ?? 0;
                        this.recalc(row);
                    }
                },

                isComplete(row) {
                    return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
                },

                normalizeNoAcak(value) {
                    const normalized = String(value ?? '').trim();
                    return /^\d{3}$/.test(normalized) ? normalized : '';
                },

                generateUniqueNoAcak() {
                    const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                    let candidate = '';
                    do {
                        candidate = Array.from({
                            length: 3
                        }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                    } while (used.has(candidate));

                    return candidate;
                },

                calcMaxQty(row) {
                    const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
                    const hasEditMax = row.fqtymaxedit !== undefined && row.fqtymaxedit !== null && row.fqtymaxedit !== '';
                    if (hasEditMax) return Math.max(0, Number(row.fqtymaxedit) || 0);

                    const satuanPO = (row.fsatuan || '').trim();
                    const satKecil = (row.fsatuankecil || '').trim();
                    const satBesar = (row.fsatuanbesar || '').trim();
                    const satBesar2 = (row.fsatuanbesar2 || '').trim();
                    const rasio = Number(row.fqtykecil || 0);
                    const rasio2 = Number(row.fqtykecil2 || 0);

                    const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row
                        .fqtykecil_ref !== '';
                    if (!hasRemainField) return 0;
                    const sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);

                    if (!satuanPO || eq(satuanPO, satKecil)) return sisaKecil;
                    if (eq(satuanPO, satBesar) && rasio > 0) return Math.floor(sisaKecil / rasio);
                    if (eq(satuanPO, satBesar2) && rasio2 > 0) return Math.floor(sisaKecil / rasio2);
                    return sisaKecil;
                },

                isDupeItem(candidate) {
                    const cPod = String(candidate.frefdtid ?? '').trim();
                    if (cPod) {
                        return this.savedItems.some(it => String(it.frefdtid ?? '').trim() === cPod);
                    }
                    const cCode = (candidate.fitemcode || '').trim().toLowerCase();
                    const cName = (candidate.fitemname || '').trim().toLowerCase();
                    const cMeta = this.productMeta(candidate.fitemcode);
                    const cId = cMeta?.id ?? null;
                    return this.savedItems.some(it => {
                        const itCode = (it.fitemcode || '').trim().toLowerCase();
                        const itName = (it.fitemname || '').trim().toLowerCase();
                        const itMeta = this.productMeta(it.fitemcode);
                        const itId = itMeta?.id ?? null;
                        if (itCode === cCode) return true;
                        if (cId && itId && cId === itId) return true;
                        if (cName && itName && cName === itName) return true;
                        return false;
                    });
                },

                openDesc(targetRow, readonly = false) {
                    this._descTarget = targetRow;
                    this.descItemName = targetRow.fitemname || '';
                    this.descValue = targetRow.fdesc || '';
                    this.descReadonly = readonly;
                    this.showDescModal = true;
                },
                copyDescName() {
                    this.descValue = this.descItemName || '';
                },
                closeDesc() {
                    this.showDescModal = false;
                    this._descTarget = null;
                    this.descItemName = '';
                    this.descValue = '';
                    this.descReadonly = false;
                },
                applyDesc() {
                    if (this._descTarget) {
                        this._descTarget.fdesc = this.descValue;
                        const index = this.savedItems.findIndex((row) => row.uid === this._descTarget.uid);
                        this.onRowUpdated(index >= 0 ? index : null);
                    }
                    this.closeDesc();
                },

                focusSavedUnit(item, i) {
                    if (item.units.length > 1) this.$nextTick(() => document.getElementById('unit_saved_' + i)?.focus());
                    else this.focusSavedQty(i);
                },
                focusSavedQty(i) {
                    this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                },
                focusSavedPrice(i) {
                    this.$nextTick(() => document.getElementById('price_saved_' + i)?.focus());
                },
                focusSavedDisc(i) {
                    this.$nextTick(() => document.getElementById('disc_saved_' + i)?.focus());
                },
                removeSaved(i) {
                    if (this.savedItems.length === 1) {
                        this.savedItems.splice(0, 1, this.createRow());
                        this.ensureMinimumRows();
                        return;
                    }
                    this.savedItems.splice(i, 1);
                    this.ensureMinimumRows();
                },
                isRowSavable(row) {
                    return !!((row.fitemcode || '').trim() && (row.fsatuan || '').trim() && Number(row.fqty) > 0);
                },
                isRowFilled(row) {
                    return [
                            row.fitemcode,
                            row.fitemname,
                            row.fsatuan,
                            row.frefdtno,
                            row.fqty,
                            row.fprice,
                            row.fdesc,
                            row.fketdt
                        ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0) ||
                        Number(row.fqty || 0) > 0;
                },
                rowWarningLabel(row) {
                    return `Data Produk ${row.fitemname || row.fitemcode || '(tanpa nama)'} qty masih 0, tidak akan tersimpan.`;
                },
                closeWarning() {
                    this.showWarningModal = false;
                    this.warningTitle = 'Perhatian';
                    this.warningMessage = '';
                    this.warningItems = [];
                    this.warningCanProceed = false;
                    this.pendingSubmitForm = null;
                    this.pendingValidRows = [];
                },
                confirmWarningAndSubmit() {
                    if (!this.warningCanProceed || !this.pendingSubmitForm || this.pendingValidRows.length < 1) {
                        this.closeWarning();
                        return;
                    }
                    this.savedItems = this.pendingValidRows.map((row) => ({
                        ...row
                    }));
                    const form = this.pendingSubmitForm;
                    this.closeWarning();
                    this.$nextTick(() => form.submit());
                },

                onPrPicked(e) {
                    const {
                        header,
                        items
                    } = e.detail || {};
                    if (!items || !Array.isArray(items)) return;
                    const skipped = [],
                        toAdd = [];
                    items.forEach(src => {
                        const fsatuan = (src.fsatuan ?? '').trim();
                        const meta = this.productMeta(src.fitemcode ?? '');
                        const fitemname = meta ? (meta.name || src.fitemname || '') : (src.fitemname ?? '');
                        const candidate = {
                            fitemcode: (src.fitemcode ?? '').trim(),
                            fitemname,
                            fsatuan,
                            frefdtid: src.frefdtid ?? '',
                        };
                        if (this.isDupeItem(candidate)) {
                            skipped.push(src);
                            return;
                        }

                        const units = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                                .filter(Boolean))] :
                            (Array.isArray(src.units) && src.units.length ? src.units : [fsatuan].filter(Boolean));
                        if (fsatuan && !units.includes(fsatuan)) units.unshift(fsatuan);

                        const row = {
                            uid: cryptoRandom(),
                            fitemcode: src.fitemcode ?? '',
                            fitemname,
                            units,
                            fsatuan: fsatuan || units[0] || '',
                            frefdtno: src.fpono || header?.fpono || '',
                            fnoacak: this.generateUniqueNoAcak(),
                            frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                            fnouref: src.fnouref ?? '',
                            frefpr: String(header?.fprhid ?? src.fprhid ?? ''),
                            fprhid: String(src.fprhid ?? header?.fprhid ?? ''),
                            fprno: String(header?.fpono ?? src.fpono ?? ''),
                            fpono: String(header?.fpono ?? src.fpono ?? ''),
                            fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                                Number(src.fqty) : 1,
                            fqtypo: 0,
                            fqtysisapo: Number(src.fqtysisapo ?? 0),
                            fqtyditer: Number(src.fqtyditer ?? 0),
                            fqtymaxedit: Number(src.fqtymaxedit ?? src.fqtysisapo ?? src.maxqty ?? 0),
                            fqtykecil_ref: Number(src.fqtykecil_ref ?? src.fqtyremain ?? src.fqtykecil_sisa ??
                                0),
                            frefdtid: src.frefdtid ?? '',
                            fsatuankecil: src.fsatuankecil || meta?.fsatuankecil || '',
                            fsatuanbesar: src.fsatuanbesar || meta?.fsatuanbesar || '',
                            fsatuanbesar2: src.fsatuanbesar2 || meta?.fsatuanbesar2 || '',
                            fqtykecil: Number(src.fqtykecil ?? meta?.fqtykecil ?? 0),
                            fqtykecil2: Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0),
                            maxqty_satuan: src.maxqty_satuan ?? '',
                            fprice: Number(src.fprice ?? 0),
                            fpriceInput: Number(src.fprice ?? 0).toFixed(2),
                            ftotal: Number(src.ftotal ?? 0),
                            fdesc: src.fdesc ?? src.fketdt ?? '',
                            fketdt: src.fketdt ?? '',
                        };
                        row.maxqty = this.calcMaxQty(row);
                        if (!(Number(row.maxqty) > 0)) return;
                        if (Number(row.maxqty) > 0) {
                            row.fqty = Number(row.maxqty);
                        }
                        if (!row.ftotal && row.fqty && row.fprice)
                            row.ftotal = +(row.fqty * row.fprice * (1 - (row.fdisc || 0) / 100)).toFixed(2);
                        toAdd.push(row);
                        if (!row.fprice || row.fprice === 0)
                            this.$nextTick(() => this.applyLastPrice(row));
                    });

                    if (toAdd.length > 0) {
                        const shouldReplaceStarter = this.savedItems.every((row) => !this.isRowFilled(row));
                        if (shouldReplaceStarter) {
                            this.savedItems = toAdd;
                        } else {
                            this.savedItems.push(...toAdd);
                        }
                        this.ensureMinimumRows();
                        this.ensureTrailingRow();
                    }

                    if (skipped.length > 0 && toAdd.length === 0) {
                        this.showDupItemModal = true;
                        this.dupItemName = skipped.map(s => s.fitemname || s.fitemcode).join(', ');
                        this.dupItemSatuan = '';
                    }
                },

                itemKey(it) {
                    const id = (it.frefdtid ?? '').toString().trim();
                    if (id) return `pod:${id}`;
                    return `manual:${(it.fitemcode??'').toString().trim()}::${(it.fsatuan??'').toString().trim()}`;
                },
                getCurrentItemKeys() {
                    return this.savedItems.map(it => this.itemKey(it));
                },

                openBrowseFor(where, idx = null) {
                    return;
                },

                submitForm(form) {
                    const seenCodes = new Set();
                    for (const row of this.savedItems) {
                        const code = (row.fitemcode || '').trim().toUpperCase();
                        if (!code) continue;
                        if (seenCodes.has(code)) {
                            this.warningTitle = 'Produk Duplikat';
                            this.warningMessage = `Kode produk ${code} tidak boleh sama dalam satu Penerimaan Barang.`;
                            this.warningItems = [];
                            this.warningCanProceed = false;
                            this.pendingSubmitForm = null;
                            this.pendingValidRows = [];
                            this.showWarningModal = true;
                            return;
                        }
                        seenCodes.add(code);
                    }

                    const validRows = this.savedItems.filter((row) => this.isRowSavable(row));
                    const warningRows = this.savedItems.filter((row) => this.isRowFilled(row) && !this.isRowSavable(row));

                    if (warningRows.length > 0) {
                        this.warningTitle = 'Qty Belum Diisi';
                        this.warningMessage = validRows.length > 0 ?
                            'Beberapa item tidak akan disimpan karena qty masih 0.' :
                            'Tidak ada item yang bisa disimpan karena qty masih 0 atau data belum lengkap.';
                        this.warningItems = warningRows.map((row) => this.rowWarningLabel(row));
                        this.warningCanProceed = validRows.length > 0;
                        this.pendingSubmitForm = form;
                        this.pendingValidRows = validRows;
                        this.showWarningModal = true;
                        return;
                    }

                    if (validRows.length < 1) {
                        this.showNoItems = true;
                        return;
                    }
                    this.savedItems = validRows.map((row) => ({
                        ...row
                    }));
                    this.$nextTick(() => form.submit());
                },
                createRow(source = {}) {
                    const row = {
                        ...newRow(),
                        ...source,
                        uid: source.uid || cryptoRandom(),
                        fnoacak: this.normalizeNoAcak(source.fnoacak) || this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(source.frefnoacak),
                    };
                    return row;
                },

                init() {
                    this.savedItems = this.savedItems.map(it => {
                        const meta = this.productMeta(it.fitemcode);
                        const units = (it.units && it.units.length) ?
                            [...new Set((it.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))] :
                            [
                                it.fsatuankecil || meta?.fsatuankecil || '',
                                it.fsatuanbesar || meta?.fsatuanbesar || '',
                                it.fsatuanbesar2 || meta?.fsatuanbesar2 || '',
                            ].map(u => (u ?? '').toString().trim()).filter(Boolean).filter((value, index, self) =>
                                self.indexOf(value) === index);
                        const fsatuankecil = it.fsatuankecil || meta?.fsatuankecil || '';
                        const fsatuanbesar = it.fsatuanbesar || meta?.fsatuanbesar || '';
                        const fsatuanbesar2 = it.fsatuanbesar2 || meta?.fsatuanbesar2 || '';
                        const fqtykecil = Number(it.fqtykecil ?? meta?.fqtykecil ?? 0);
                        const fqtykecil2 = Number(it.fqtykecil2 ?? meta?.fqtykecil2 ?? 0);
                        const row = {
                            ...it,
                            uid: it.uid || cryptoRandom(),
                            units,
                            fsatuankecil,
                            fsatuanbesar,
                            fsatuanbesar2,
                            fqtykecil,
                            fqtykecil2,
                            fqtysisapo: Number(it.fqtysisapo ?? 0),
                            fqtymaxedit: Number(it.fqtymaxedit ?? 0),
                            fqtykecil_ref: Number(it.fqtykecil_ref ?? it.fqtyremain ?? 0),
                            fnoacak: this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak(),
                            frefnoacak: this.normalizeNoAcak(it.frefnoacak),
                            fpriceInput: Number(it.fprice ?? 0).toFixed(2),
                        };
                        return row;
                    });

                    // â”€â”€ Guard CURRENCY_MAP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                    if (window.CURRENCY_MAP && typeof window.CURRENCY_MAP === 'object') {
                        const idrEntry = Object.values(window.CURRENCY_MAP).find(c => c.code === 'IDR');
                        if (idrEntry && !this.selectedCurrId) {
                            this.selectedCurrId = String(idrEntry.id);
                            this.selectedCurrCode = idrEntry.code;
                            this.rateValue = 1;
                        }
                    }

                    window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                    window.isDupeItem = (candidate) => this.isDupeItem(candidate);
                    if (this._ac) this._ac.abort();
                    this._ac = new AbortController();
                    const sig = {
                        signal: this._ac.signal,
                        passive: true
                    };

                    window.addEventListener('show-no-supplier', () => {
                        this.showNoSupplier = true;
                    }, sig);
                    window.addEventListener('pr-picked', (e) => this.onPrPicked(e), sig);
                    window.addEventListener('product-chosen', (e) => {
                        return;
                    }, sig);

                    if (this.savedItems.length === 0) {
                        this.savedItems = [this.createRow()];
                    }
                    if ('{{ $action }}' !== 'delete') {
                        this.ensureMinimumRows();
                        this.ensureTrailingRow();
                    }
                }
            };
        }

        // â”€â”€â”€ pohFormModal â€” identik create â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        window.pohFormModal = function() {
            return {
                show: false,
                table: null,
                showDupModal: false,
                dupCount: 0,
                dupSample: [],
                pendingHeader: null,
                pendingUniques: [],

                initDataTable() {
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                    if (!document.getElementById('poTable')) return;

                    this.table = $('#poTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('penerimaanbarang.pickable') }}",
                            type: 'GET',
                            data: function(d) {
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    supplier: document.getElementById('supplierCodeHidden')?.value || '',
                                    search: d.search.value,
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            }
                        },
                        columns: [{
                                data: 'fpono',
                                name: 'fpono',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fpono',
                                name: 'fpono',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                render: d => d || '<span class="text-gray-400">-</span>'
                            },
                            {
                                data: 'fpodate',
                                name: 'fpodate',
                                className: 'text-sm',
                                render: d => formatDate(d)
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '100px',
                                render: () =>
                                    '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-teal-600 hover:bg-teal-700 text-white transition-colors duration-150">Pilih</button>'
                            }
                        ],
                        dom: '<"flex flex-col gap-3 md:flex-row md:items-center mb-4"<"w-full md:w-auto"f><"w-full md:w-auto md:ml-auto md:text-right"l>>rt<"flex flex-col gap-3 md:flex-row md:items-center md:justify-between mt-4"i p>',
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
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
                            [3, 'desc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.children().first().css({
                                display: 'flex',
                                width: '100%',
                                gap: '12px',
                                alignItems: 'center',
                                justifyContent: 'space-between'
                            });
                            $c.find('.dt-search, .dataTables_filter').css({
                                marginRight: '12px'
                            });
                            $c.find('.dt-length, .dataTables_length').css({
                                marginLeft: 'auto',
                                textAlign: 'right'
                            });
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '280px',
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

                    const self = this;
                    $('#poTable').off('click.pohpick').on('click.pohpick', '.btn-pick', function() {
                        const data = self.table.row($(this).closest('tr')).data();
                        if (data) self.pick(data);
                    });
                },

                openModal() {
                    this.show = true;
                    this.$nextTick(() => setTimeout(() => this.initDataTable(), 50));
                },
                closeModal() {
                    this.show = false;
                    if (this.table) {
                        this.table.destroy();
                        this.table = null;
                    }
                },

                openDupModal(header, duplicates, uniques) {
                    window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
                },
                closeDupModal() {
                    window.transactionReferenceModalHelper.closeDupModal(this);
                },
                applySupplierFromPo(header, row) {
                    const supplierCode = (header?.fsupplier || row?.fsuppliercode || '').toString().trim();
                    if (!supplierCode) return;

                    const supplierName = (row?.fsuppliername || '').toString().trim();
                    const label = supplierName ? `${supplierName} (${supplierCode})` : supplierCode;
                    const sel = document.getElementById('modal_filter_supplier_id');
                    const hid = document.getElementById('supplierCodeHidden');

                    if (hid) {
                        hid.value = supplierCode;
                        hid.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }

                    if (!sel) return;

                    let opt = Array.from(sel.options).find(o => String(o.value) === supplierCode);
                    if (!opt) {
                        opt = new Option(label, supplierCode, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                    }

                    sel.value = supplierCode;
                    Array.from(sel.options).forEach(option => {
                        option.selected = String(option.value) === supplierCode;
                    });
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                },
                confirmAddUniques() {
                    window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
                },

                async pick(row) {
                    try {
                        const url = `{{ route('penerimaanbarang.items', ['id' => 'PO_ID_PLACEHOLDER']) }}`
                            .replace('PO_ID_PLACEHOLDER', row.fpohid);
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const json = await res.json();
                        this.applySupplierFromPo(json.header, row);
                        const items = json.items || [];

                        const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                        const keyOf = src =>
                            `${(src.fprdcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                        const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                        const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                        if (duplicates.length > 0) {
                            this.openDupModal(row, duplicates, uniques);
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('pr-picked', {
                            detail: {
                                header: row,
                                items
                            }
                        }));
                        this.closeModal();
                    } catch (e) {
                        console.error(e);
                        window.showAppErrorAlert('TERJADI KESALAHAN', 'GAGAL MENGAMBIL DETAIL PO.');
                    }
                }
            };
        };

        function formatDate(s) {
            if (!s || s === 'No Date') return '-';
            const d = new Date(s);
            if (isNaN(d)) return '-';
            const p = n => n.toString().padStart(2, '0');
            return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
        }

        // â”€â”€â”€ supplierBrowser â€” identik create â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        window.addEventListener('warehouse-picked', (ev) => {
            const {
                fwhcode,
                fwhname
            } = ev.detail || {};

            const sel = document.getElementById('warehouseSelect');
            const hidFrom = document.getElementById('warehouseCodeHidden');

            if (sel) {
                const code = String(fwhcode || '').trim();
                let opt = [...sel.options].find((o) => String(o.value).trim() === code);
                if (code && !opt) {
                    opt = new Option(fwhname ? `${fwhname} (${code})` : code, code, true, true);
                    sel.add(opt);
                }
                sel.value = opt ? opt.value : code;
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hidFrom) {
                hidFrom.value = fwhcode || '';
                hidFrom.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });

        // â”€â”€â”€ productBrowser â€” identik create â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    </script>
    @include('components.transaction.browse-warehouse-script', [
        'eventName' => 'penerimaanbarang-warehouse-browse-open',
    ])
    @include('components.transaction.browse-product-script', [
        'destroyOnClose' => true,
        'openDelay' => 50,
    ])
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('prh', {
                descPreview: {
                    uid: null,
                    index: null,
                    label: '',
                    text: ''
                },
                descList: []
            });
        });
    </script>
@endpush
