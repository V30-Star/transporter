@extends('layouts.app')

@section('title',
    $action === 'delete'
    ? 'Permintaan Pembelian - Delete'
    : ($action === 'view'
    ? 'Permintaan Pembelian -
    View'
    : 'Permintaan Pembelian - Edit'))

@section('content')
    @php
        $canCreateSupplier = in_array('createSupplier', explode(',', session('user_restricted_permissions', '')), true);
    @endphp
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
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

        .desc-inline-field {
            display: flex;
            width: 100%;
            min-width: 0;
            align-items: stretch;
            flex-wrap: nowrap;
        }

        .desc-inline-field__text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .desc-inline-field__button {
            flex: 0 0 auto;
            width: 2.5rem;
            justify-content: center;
        }

        .pr-detail-table th,
        .pr-detail-table td {
            padding: .25rem .375rem !important;
        }

        .pr-detail-table input:not([type="hidden"]),
        .pr-detail-table select,
        .pr-detail-table button,
        .pr-detail-table .desc-inline-field__text {
            min-height: 1.875rem;
            padding-top: .25rem !important;
            padding-bottom: .25rem !important;
            line-height: 1.25rem;
        }

        .pr-detail-table button {
            display: inline-flex;
            align-items: center;
        }

        .pr-detail-table .desc-inline-field__button {
            width: 2rem !important;
            flex-basis: 2rem !important;
        }

    </style>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden mb-4 rounded-xl"
            role="alert">
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
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updateTr_prh', $permissions, true);
        $canDeletePermission = in_array('deleteTr_prh', $permissions, true);
        $canPrint =
            in_array('viewTr_prh', $permissions, true) ||
            in_array('updateTr_prh', $permissions, true) ||
            in_array('deleteTr_prh', $permissions, true) ||
            in_array('createTr_prh', $permissions, true);
        $isDelete = $action === 'delete';
        $isView = $action === 'view';
        $isEdit = $action === 'edit';
        $isReadOnly = $isDelete || $isView;
    @endphp
    @php
        $isUsageLocked = !empty($blockedByPO) && $blockedByPO;
        $canClosePr = $isEdit && $tr_prh->fclose != '1' && $isUsageLocked && (string) ($tr_prh->fprdin ?? '') === '0';
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════════
     MODAL BLOCKED BY PO
     ════════════════════════════════════════════════════════════════════ --}}
    @if ($isEdit && ((!empty($blockedByPO) && $blockedByPO) || session('blocked_by_po')))
        <div x-data="{ open: true }" x-show="open" x-cloak
            class="fixed inset-0 z-[99] flex items-center justify-center bg-black/60 backdrop-blur-sm" x-transition.opacity>
            <div class="relative bg-white w-[92vw] max-w-2xl rounded-2xl shadow-2xl overflow-hidden" x-transition.scale>
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-red-100 bg-red-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-red-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-red-700">
                            PR Tidak Dapat {{ $isDelete ? 'Dihapus' : 'Diedit' }}
                        </h3>
                        <p class="text-sm text-red-500 mt-0.5">
                            PR <strong>{{ $tr_prh->fprno }}</strong> sudah terikat dengan Purchase Order berikut:
                        </p>
                    </div>
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
                        Batalkan PO terkait terlebih dahulu sebelum {{ $isDelete ? 'menghapus' : 'mengedit' }} PR ini.
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

    <div>
        <div>
            @if ($isReadOnly)
                <div class="space-y-4">
                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = !empty($tr_prh->fuserapproved) || (int) $tr_prh->fapproval === 1;
                    @endphp

                    {{-- ─── CARD 1: Identitas Permintaan ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Permintaan</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Cabang --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                                    <input type="text"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                                        value="{{ $fbranchlabel ?? $fcabang }}" disabled>
                                </div>

                                {{-- PR# --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">PR#</label>
                                    <input type="text"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                                        value="{{ $tr_prh->fprno }}" disabled>
                                </div>

                                {{-- Tanggal --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal</label>
                                    <input disabled type="date" value="{{ $fmt($tr_prh->fprdate) }}"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Supplier --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Supplier</label>
                                    <input type="text"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                                        value="{{ $tr_prh->fsuppliercode }} - {{ $tr_prh->fsuppliername }}" disabled>
                                </div>

                                {{-- Tanggal Dibutuhkan --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Dibutuhkan</label>
                                    <input disabled type="date" value="{{ $fmt($tr_prh->fneeddate) }}"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                                </div>

                                {{-- Tanggal Paling Lambat --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Paling Lambat</label>
                                    <input disabled type="date" value="{{ $fmt($tr_prh->fduedate) }}"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                                </div>
                            </div>

                            {{-- Keterangan --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                                <textarea readonly rows="2"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">{{ $tr_prh->fket }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item (Read-Only) ────────────────────── --}}
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
                            <div x-data="readOnlyItemsTable(@js($savedItems ?? []))" x-init="init()" class="space-y-2">
                                <div class="overflow-auto border rounded">
                                    <table class="pr-detail-table min-w-full text-sm balanced-detail-table"
                                        data-skip-auto-detail-style="true">
                                        <colgroup>
                                            <col style="width:2%;">
                                            <col style="width:18%;">
                                            <col style="width:35%;">
                                            <col style="width:12%;">
                                            <col style="width:10%;">
                                            <col style="width:10%;">
                                            <col style="width:13%;">
                                        </colgroup>
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="p-2 text-left w-10">#</th>
                                                <th class="p-2 text-left w-44">Kode Produk</th>
                                                <th class="p-2 text-left w-96">Nama Produk</th>
                                                <th class="p-2 text-left w-40">Satuan</th>
                                                <th class="p-2 text-right w-28 whitespace-nowrap">Qty</th>
                                                <th class="p-2 text-right w-28 whitespace-nowrap">Qty PO</th>
                                                <th class="p-2 text-left w-56">Ket Item</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(it, i) in savedItems" :key="it.uid">
                                                <tr class="border-t align-top">
                                                    <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                    <td class="p-2">
                                                        <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded font-mono" x-text="it.fitemcode"></div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="flex w-full max-w-full">
                                                            <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                                x-text="it.fitemname"></div>
                                                            <button type="button" @click="openDesc('saved', i)"
                                                                class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors"
                                                                :class="descButtonClass(it.fdesc)" title="Deskripsi item">
                                                                <x-heroicon-o-document-text class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="it.fsatuan"></div>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium" x-text="formatQtyValue(it.fqty)"></div>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <div class="px-2 py-1 text-sm text-gray-500 bg-gray-50 border rounded text-right animate-pulse" x-text="formatQtyValue(it.fqtypo)"></div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded" x-text="it.fketdt || '-'"></div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div x-show="showDescModal" x-cloak
                                    class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
                                    x-transition.opacity>
                                    <div class="absolute inset-0" @click="closeDesc()"></div>
                                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                        x-transition.scale>
                                        <div class="px-5 py-4 border-b flex items-center">
                                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                                                <p class="text-xs text-gray-500" x-text="descItemLabel"></p>
                                            </div>
                                        </div>
                                        <div class="px-5 py-4 space-y-4">
                                            <div>
                                                <div class="mb-1 flex items-center justify-between gap-3">
                                                    <div class="text-sm text-gray-700 font-medium">Nama Produk</div>
                                                    <button type="button" @click="descValue = descItemLabel || ''"
                                                        class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">
                                                        Copy
                                                    </button>
                                                </div>
                                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                                    x-text="descItemLabel || '-'"></div>
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-700 font-bold mb-1">Deskripsi</label>
                                                <textarea x-model="descValue" rows="5"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500 cursor-not-allowed"
                                                    readonly></textarea>
                                            </div>
                                        </div>
                                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                            <button type="button" @click="closeDesc()"
                                                class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                                Tutup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 3: Approval & Aksi (Read-Only) ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Approval & Aksi</p>
                        </div>
                        <div class="p-4 space-y-4">
                            <div
                                class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <div>
                                    <p class="text-sm text-gray-800 font-medium">Status Approval</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Dokumen ini telah disetujui oleh otoritas
                                        wewenang</p>
                                </div>
                                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                    class="{{ $isApproved ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                        :class="{{ $isApproved ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Buttons --}}
                        <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
                            <button type="button" onclick="window.location.href='{{ route('tr_prh.index') }}'"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                Kembali
                            </button>
                            <div class="flex items-center gap-2">
                                @if ($isDelete && $canDeletePermission)
                                    @if (!empty($blockedByPO) && $blockedByPO)
                                        <button type="button" disabled
                                            class="inline-flex items-center gap-2 px-5 py-2 bg-red-300 text-white text-sm font-medium rounded-lg cursor-not-allowed opacity-70">
                                            <x-heroicon-o-lock-closed class="w-4 h-4" />
                                            Hapus (Terkunci)
                                        </button>
                                    @else
                                        <button type="button" onclick="showDeleteModal()"
                                            class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                            Hapus
                                        </button>
                                    @endif
                                @endif
                                @if ($isView && $canPrint)
                                    <a href="{{ route('tr_prh.print', $tr_prh->fprno) }}" target="_blank"
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

                {{-- Modal Konfirmasi Delete --}}
                @if ($canDeletePermission)
                    <div id="deleteModal"
                        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Konfirmasi Hapus</h3>
                            <p class="text-sm text-gray-600 mb-4">Apakah anda yakin ingin menghapus Permintaan Pembelian
                                ini?</p>
                            <form action="{{ route('tr_prh.destroy', $tr_prh->fprhid) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="flex justify-end gap-2">
                                    <button onclick="closeDeleteModal()" type="button"
                                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm font-medium transition-colors">Tidak</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">Ya,
                                        Hapus</button>
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
                @endif
            @else
                {{-- MODE EDIT --}}
                <form action="{{ route('tr_prh.update', $tr_prh->fprhid) }}" method="POST" data-form-draft="true"
                    data-draft-key="tr_prh:edit:{{ $tr_prh->fprhid }}" data-disable-form-persist="true"
                    x-data="{ blockedByPO: {{ $blockedByPO ? 'true' : 'false' }} }"
                    @submit.prevent="window.dispatchEvent(new CustomEvent('tr-prh-edit-submit-request'))">
                    @csrf
                    @method('PATCH')

                    @if (!empty($blockedByPO) && $blockedByPO)
                        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200">
                            <x-heroicon-o-lock-closed class="w-5 h-5 text-amber-500 flex-shrink-0" />
                            <p class="text-sm text-amber-700">
                                <strong>Mode hanya baca.</strong> PR ini tidak bisa diedit karena sudah memiliki purchase
                                Order terkait.
                            </p>
                        </div>
                    @endif

                    @php
                        $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : '';
                        $isApproved = !empty($tr_prh->fuserapproved) || (int) $tr_prh->fapproval === 1;
                    @endphp

                    {{-- ─── CARD 1: Identitas Permintaan ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden"
                        :class="blockedByPO ? 'opacity-65 pointer-events-none' : ''">
                        <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Permintaan</p>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                {{-- Cabang --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Cabang</label>
                                    <input type="text"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                        value="{{ $fbranchlabel ?? $fcabang }}" disabled>
                                    <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                                </div>

                                {{-- PR# --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">PR#</label>
                                    <input type="text" name="fprno"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                        value="{{ $tr_prh->fprno }}" disabled>
                                </div>
                                {{-- Tanggal --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal <span
                                            class="text-red-500">*</span></label>
                                    <input type="date" name="fprdate"
                                        value="{{ old('fprdate', $fmt($tr_prh->fprdate)) }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                {{-- Supplier --}}
                                <div x-data="{
                                    supplierId: '{{ old('fsupplier', $tr_prh->fsupplier) }}',
                                    supplierDisplay: '{{ $tr_prh->fsuppliercode }} - {{ $tr_prh->fsuppliername }}'
                                }"
                                    @supplier-chosen.window="supplierId = $event.detail.fsuppliercode; supplierDisplay = $event.detail.fsuppliername + ' (' + $event.detail.fsuppliercode + ')'">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Supplier <span
                                            class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <input type="text" x-model="supplierDisplay"
                                            class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
                                            readonly>
                                        <input type="hidden" name="fsupplier" x-model="supplierId">
                                        <button type="button" @click="$dispatch('browse-supplier')"
                                            class="border border-l-0 border-gray-300 px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors">
                                            <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                        </button>
                                        @if ($canCreateSupplier)
                                            <a href="{{ route('supplier.create') }}" target="_blank"
                                                class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                            </a>
                                        @endif
                                    </div>
                                    @error('fsupplier')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Tanggal Dibutuhkan --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Dibutuhkan <span
                                            class="text-red-500">*</span></label>
                                    <input type="date" name="fneeddate"
                                        value="{{ old('fneeddate', $fmt($tr_prh->fneeddate)) }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>

                                {{-- Tanggal Paling Lambat --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Tanggal Paling Lambat <span
                                            class="text-red-500">*</span></label>
                                    <input type="date" name="fduedate"
                                        value="{{ old('fduedate', $fmt($tr_prh->fduedate)) }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>

                            {{-- Keterangan --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Keterangan</label>
                                <textarea name="fket" rows="2"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('fket', $tr_prh->fket) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 2: Detail Item (Editable) ────────────────────── --}}
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
                            <div x-data="itemsTableRowsEdit()" x-init="init()" class="space-y-2">
                                <div class="overflow-auto border rounded">
                                    <table class="pr-detail-table min-w-full text-sm balanced-detail-table"
                                        data-skip-auto-detail-style="true">
                                        <colgroup>
                                            <col style="width:2%;">
                                            <col style="width:18%;">
                                            <col style="width:35%;">
                                            <col style="width:12%;">
                                            <col style="width:10%;">
                                            <col style="width:10%;">
                                            <col style="width:10%;">
                                            <col style="width:3%;">
                                        </colgroup>
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="p-2 text-left w-10">#</th>
                                                <th class="p-2 text-left w-48">Kode Produk</th>
                                                <th class="p-2 text-left w-96">Nama Produk</th>
                                                <th class="p-2 text-left w-36">Satuan</th>
                                                <th class="p-2 text-right w-24 whitespace-nowrap">Qty</th>
                                                <th class="p-2 text-right w-24 whitespace-nowrap">Qty PO</th>
                                                <th class="p-2 text-left w-48">Ket Item</th>
                                                <th class="p-2 text-center w-20">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(row, i) in rows" :key="row.uid">
                                                <tr class="border-t align-top"
                                                    :class="i === 0 ? 'bg-green-50/40' : 'bg-white'">
                                                    <td class="p-2 text-gray-400" x-text="i + 1"></td>
                                                    <td class="p-2">
                                                        <div class="flex">
                                                            <input type="text"
                                                                class="flex-1 border rounded-l px-2 py-1 font-mono text-sm min-w-0 focus:ring-1 focus:ring-blue-500"
                                                                x-model.trim="row.fitemcode" @input="onCodeTyped(row, i)"
                                                                :disabled="blockedByPO">
                                                            <button type="button" @click="openBrowseFor(i)"
                                                                class="border border-l-0 px-2 py-1 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                                                :disabled="blockedByPO">
                                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <div class="flex w-full max-w-full">
                                                            <div class="min-w-0 flex-1 rounded-l border bg-gray-100 px-2 py-1 text-sm leading-5 text-gray-600 whitespace-normal break-words"
                                                                x-text="row.fitemname || '-'"></div>
                                                            <button type="button" @click="openDesc(i)"
                                                                class="shrink-0 inline-flex items-center border border-l-0 rounded-r bg-slate-50 px-2 py-1 text-slate-700 hover:bg-slate-100 transition-colors"
                                                                :class="descButtonClass(row.fdesc)" :disabled="blockedByPO"
                                                                title="Deskripsi item">
                                                                <x-heroicon-o-document-text class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-2">
                                                        <template x-if="row.units.length > 1">
                                                            <select
                                                                class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                                x-model="row.fsatuan" @change="onRowUpdated(i)"
                                                                :disabled="blockedByPO">
                                                                <template x-for="unit in row.units"
                                                                    :key="unit">
                                                                    <option :value="unit" x-text="unit">
                                                                    </option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="row.units.length <= 1">
                                                            <div class="px-2 py-1 text-sm text-gray-600 bg-gray-50 border rounded"
                                                                x-text="row.fsatuan || '-'"></div>
                                                        </template>
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="text" inputmode="decimal"
                                                            class="w-full border rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-500"
                                                            x-model="row.fqty" :disabled="blockedByPO"
                                                            @focus="unformatQtyInput(row)" @input="onQtyInput(row, i)"
                                                            @blur="formatQtyInput(row, i)">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <div class="px-2 py-1 text-sm text-gray-500 bg-gray-50 border rounded text-right"
                                                            x-text="formatQtyValue(row.fqtypo)"></div>
                                                    </td>
                                                    <td class="p-2">
                                                        <input type="text"
                                                            class="w-full border rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-500"
                                                            x-model="row.fketdt" :disabled="blockedByPO"
                                                            @input="onRowUpdated(i)">
                                                    </td>
                                                    <td class="p-2 text-center text-xs">
                                                        <button type="button" @click="removeRow(i)"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200"
                                                            :disabled="blockedByPO" title="Hapus baris">-</button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="hidden">
                                    <template x-for="row in rowsToSubmit" :key="'submit-' + row.uid">
                                        <div>
                                            <input type="hidden" name="fprdid[]" :value="row.fprdid || ''">
                                            <input type="hidden" name="fitemcode[]" :value="row.fitemcode">
                                            <input type="hidden" name="fitemname[]" :value="row.fitemname">
                                            <input type="hidden" name="fnoacak[]" :value="row.fnoacak">
                                            <input type="hidden" name="fsatuan[]" :value="row.fsatuan">
                                            <input type="hidden" name="fqty[]" :value="row.fqty">
                                            <input type="hidden" name="fdesc[]" :value="row.fdesc">
                                            <input type="hidden" name="fketdt[]" :value="row.fketdt">
                                        </div>
                                    </template>
                                </div>
                                <input type="hidden" id="itemsCount" :value="rowsToSubmit.length">
                                <div x-show="showDescModal" x-cloak
                                    class="fixed inset-0 z-[95] flex items-center justify-center bg-black/50"
                                    x-transition.opacity>
                                    <div class="absolute inset-0" @click="closeDesc()"></div>
                                    <div class="relative bg-white w-[92vw] max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                                        x-transition.scale>
                                        <div class="px-5 py-4 border-b flex items-center">
                                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 mr-2" />
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-800">Deskripsi Item</h3>
                                                <p class="text-xs text-gray-500" x-text="descItemLabel"></p>
                                            </div>
                                        </div>
                                        <div class="px-5 py-4 space-y-4">
                                            <div>
                                                <div class="mb-1 flex items-center justify-between gap-3">
                                                    <div class="text-sm text-gray-700 font-medium">Nama Produk</div>
                                                    <button type="button" @click="descValue = descItemLabel || ''"
                                                        x-show="!blockedByPO"
                                                        class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">
                                                        Copy
                                                    </button>
                                                </div>
                                                <div class="rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800"
                                                    x-text="descItemLabel || '-'"></div>
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-700 font-bold mb-1">Deskripsi</label>
                                                <textarea x-model="descValue" rows="5"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                                    placeholder="Tulis deskripsi item di sini..." :readonly="blockedByPO"></textarea>
                                            </div>
                                        </div>
                                        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 bg-gray-50">
                                            <button type="button" @click="closeDesc()"
                                                class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                                Tutup
                                            </button>
                                            <button type="button" @click="applyDesc()" x-show="!blockedByPO"
                                                class="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                                Simpan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── CARD 3: Approval & Aksi (Editable) ────────────────────── --}}
                    <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Approval & Aksi</p>
                        </div>
                        <div class="p-4 space-y-4">
                            <div
                                class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <div>
                                    <p class="text-sm text-gray-800 font-medium">Status Approval</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Dokumen ini disetujui</p>
                                </div>
                                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                    class="{{ $isApproved ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                        :class="{{ $isApproved ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Buttons --}}
                        <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
                            <button type="button" @click="window.location.href='{{ route('tr_prh.index') }}'"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                Kembali
                            </button>
                            <div class="flex items-center gap-2">
                                @if ($canEditPermission)
                                    @if ($isUsageLocked)
                                        <button type="button" disabled
                                            class="inline-flex items-center gap-2 px-5 py-2 bg-blue-300 text-white text-sm font-medium rounded-lg cursor-not-allowed opacity-70"
                                            title="{{ $usageLockMessage ?? 'Data ini sudah direferensi.' }}">
                                            <x-heroicon-o-lock-closed class="w-4 h-4" /> Simpan Perubahan
                                        </button>
                                    @else
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                            <x-heroicon-o-check class="w-4 h-4" /> Simpan Perubahan
                                        </button>
                                    @endif
                                    @if ($canClosePr)
                                        <button type="button" onclick="showClosePrModal()"
                                            class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                                            <x-heroicon-o-lock-closed class="w-4 h-4" /> Close
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Local Modals --}}
                    <div x-show="showNoItems" x-cloak
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50" x-transition.opacity>
                        <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-sm w-full text-center">
                            <h4 class="text-lg font-bold text-red-600 mb-2">Item Kosong</h4>
                            <p class="text-sm text-gray-600 mb-4">Belum ada item dengan Qty lebih dari 0 yang bisa
                                disimpan.</p>
                            <button @click="showNoItems = false" type="button"
                                class="w-full py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg font-medium text-sm transition-colors">OK</button>
                        </div>
                    </div>
                    <div x-show="showWarningModal" x-cloak
                        class="fixed inset-0 z-[101] flex items-center justify-center bg-black/50" x-transition.opacity>
                        <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-lg w-full">
                            <h4 class="text-lg font-bold text-amber-600 mb-2" x-text="warningTitle"></h4>
                            <p class="text-sm text-gray-600 mb-3" x-text="warningMessage"></p>
                            <template x-if="warningItems.length > 0">
                                <ul class="list-disc pl-5 text-sm text-gray-700 mb-4 space-y-1">
                                    <template x-for="item in warningItems" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                            </template>
                            <div class="flex justify-end gap-2 bg-gray-50 -mx-6 -mb-6 p-4 rounded-b-2xl border-t mt-4">
                                <button @click="closeWarning()" type="button"
                                    class="px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg font-medium text-sm transition-colors">Tutup</button>
                                <button x-show="warningCanProceed" @click="confirmWarningAndSubmit()" type="button"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-sm transition-colors">Lanjut
                                    Simpan</button>
                            </div>
                        </div>
                    </div>
                </form>
                @if ($canClosePr)
                    <form id="closePrForm" action="{{ route('tr_prh.update', $tr_prh->fprhid) }}" method="POST"
                        class="hidden">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="close_only" value="1">
                        <input type="hidden" name="fclose" value="1">
                    </form>
                    <div id="closePrModal"
                        class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Konfirmasi Close</h3>
                            <p class="text-sm text-gray-600 mb-4">Apakah anda yakin mau close PR
                                <strong>{{ $tr_prh->fprno }}</strong>?
                            </p>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeClosePrModal()"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm font-medium transition-colors">No</button>
                                <button type="submit" form="closePrForm"
                                    class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors">Yes</button>
                            </div>
                        </div>
                    </div>
                    <script>
                        function showClosePrModal() {
                            document.getElementById('closePrModal')?.classList.remove('hidden');
                        }

                        function closeClosePrModal() {
                            document.getElementById('closePrModal')?.classList.add('hidden');
                        }
                    </script>
                @endif
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

        function readOnlyItemsTable(initialItems = []) {
            const items = Array.isArray(initialItems) ? initialItems : [];

            return {
                savedItems: items.map((it) => ({
                    ...it,
                    uid: it.uid || cryptoRandom(),
                    fqty: Number(it.fqty || 0),
                    fqtypo: Number(it.fqtypo || 0),
                })),
                showDescModal: false,
                descSavedIndex: null,
                descValue: '',
                descItemLabel: '',
                formatQtyValue(value) {
                    const num = Number(value);
                    if (!Number.isFinite(num)) return '0,00';
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num);
                },
                hasDesc(value) {
                    return String(value ?? '').trim() !== '';
                },
                descButtonClass(value) {
                    return this.hasDesc(value) ?
                        'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-medium' :
                        'border-gray-300 bg-white text-gray-500 hover:bg-gray-50';
                },
                openDesc(target = 'saved', index = null) {
                    if (target !== 'saved' || index === null || !this.savedItems[index]) return;
                    const row = this.savedItems[index];
                    this.descSavedIndex = index;
                    this.descValue = (row.fdesc || '').toString();
                    this.descItemLabel = (row.fitemname || '').toString();
                    this.showDescModal = true;
                },
                closeDesc() {
                    this.showDescModal = false;
                    this.descSavedIndex = null;
                    this.descValue = '';
                    this.descItemLabel = '';
                },
                applyDesc() {
                    this.closeDesc();
                },
                init() {}
            };
        }

        function itemsTableRowsEdit() {
            const rawItems = @json($savedItems ?? []);
            const hydratedItems = rawItems.map((it) => {
                const code = (it.fitemcode || '').trim();
                const meta = window.PRODUCT_MAP?.[code] || null;
                const units = Array.isArray(it.units) && it.units.length > 0 ? [...new Set(it.units.map(unit => (
                        unit ?? '').toString().trim()).filter(Boolean))] :
                    (meta?.units?.length ? [...new Set(meta.units.map(unit => (unit ?? '').toString().trim())
                            .filter(Boolean))] :
                        (it.fsatuan ? [it.fsatuan] : []));
                const existingUnit = (it.fsatuan || '').toString().trim();
                const matchedUnit = units.find(unit => unit.toLowerCase() === existingUnit.toLowerCase()) ||
                    existingUnit;
                if (matchedUnit && !units.includes(matchedUnit)) {
                    units.unshift(matchedUnit);
                }

                return {
                    ...it,
                    uid: it.uid || cryptoRandom(),
                    units,
                    fsatuan: matchedUnit || (units[0] || ''),
                    fqty: Number(it.fqty || 0),
                    fqtypo: Number(it.fqtypo || 0),
                    fnoacak: (it.fnoacak || '').toString()
                };
            });

            return {
                rows: hydratedItems.length ? hydratedItems : [],
                rowsToSubmit: [],
                browseTargetIndex: null,
                showDescModal: false,
                descRowIndex: null,
                descValue: '',
                descItemLabel: '',
                showNoItems: false,
                showWarningModal: false,
                warningTitle: '',
                warningMessage: '',
                warningItems: [],
                warningCanProceed: false,
                minimumVisibleRows: @json(count($savedItems ?? []) + 5),

                emptyRow() {
                    return {
                        uid: cryptoRandom(),
                        fprdid: '',
                        fitemcode: '',
                        fitemname: '',
                        fnoacak: this.generateUniqueNoAcak(),
                        units: [],
                        fsatuan: '',
                        fqty: '',
                        fqtypo: 0,
                        fdesc: '',
                        fketdt: ''
                    };
                },

                sanitizeQtyValue(value) {
                    const raw = (value ?? '').toString().replace(',', '.').replace(/[^0-9.]/g, '');
                    const parts = raw.split('.');
                    if (parts.length <= 1) return raw;
                    return `${parts.shift()}.${parts.join('')}`;
                },

                formatQtyDisplay(value) {
                    const raw = this.sanitizeQtyValue(value);
                    if (raw === '') return '';
                    const numeric = Number(raw);
                    return Number.isFinite(numeric) ? numeric.toFixed(2) : '';
                },

                unformatQtyInput(row) {
                    const raw = this.sanitizeQtyValue(row?.fqty);
                    row.fqty = raw === '' ? '' : String(Number(raw));
                },

                onQtyInput(row, index) {
                    row.fqty = this.sanitizeQtyValue(row?.fqty);
                    this.onRowUpdated(index);
                },

                formatQtyInput(row, index = null) {
                    row.fqty = this.formatQtyDisplay(row?.fqty);
                    this.onRowUpdated(index);
                },

                rowHasContent(row) {
                    if (!row) return false;
                    return [
                        row.fitemcode,
                        row.fitemname,
                        row.fsatuan,
                        row.fqty,
                        row.fdesc,
                        row.fketdt
                    ].some((value) => String(value ?? '').trim() !== '' && Number(value ?? 0) !== 0) || Number(row
                        .fqty || 0) > 0;
                },

                ensureMinimumRows() {
                    while (this.rows.length < this.minimumVisibleRows) {
                        this.rows.push(this.emptyRow());
                    }
                },

                ensureTrailingRow(index = null) {
                    if (!this.rows.length) {
                        this.ensureMinimumRows();
                        return;
                    }

                    const targetIndex = index === null ? this.rows.length - 1 : index;
                    if (targetIndex !== this.rows.length - 1) return;

                    if (this.rowHasContent(this.rows[targetIndex])) {
                        this.rows.push(this.emptyRow());
                    }
                },

                onRowUpdated(index) {
                    this.ensureTrailingRow(index);
                },

                normalizeNoAcak(value) {
                    return (value || '').toString().replace(/\D/g, '').slice(0, 3);
                },

                formatQtyValue(value) {
                    const num = Number(value);
                    if (!Number.isFinite(num)) return '0,00';
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num);
                },

                generateUniqueNoAcak() {
                    const used = new Set(this.rows.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                    let candidate = '';

                    do {
                        candidate = Array.from({
                            length: 3
                        }, () => '123456789' [Math.floor(Math.random() * 9)]).join('');
                    } while (used.has(candidate));

                    return candidate;
                },

                hasDesc(value) {
                    return String(value ?? '').trim() !== '';
                },

                descButtonClass(value) {
                    return this.hasDesc(value) ?
                        'border-emerald-300 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-medium' :
                        'border-gray-300 bg-white text-gray-500 hover:bg-gray-50';
                },

                productMeta(code) {
                    const key = (code || '').trim();
                    return window.PRODUCT_MAP?.[key] || {
                        name: '',
                        default_unit: '',
                        units: []
                    };
                },

                hydrateRowFromMeta(row, meta, forceDefaultUnit = false) {
                    if (!meta) {
                        row.fitemname = '';
                        row.units = [];
                        row.fsatuan = '';
                        return;
                    }

                    row.fprdid = meta.id || row.fprdid || '';
                    row.fitemname = meta.name || '';
                    const units = [...new Set((meta.units || []).map(unit => (unit ?? '').toString().trim()).filter(
                        Boolean))];
                    const defaultUnit = (meta.default_unit || '').toString().trim();
                    const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
                    row.units = units;
                    const existingUnit = (row.fsatuan || '').toString().trim();
                    const matchedUnit = units.find(unit => unit.toLowerCase() === existingUnit.toLowerCase()) ||
                        existingUnit;
                    row.fsatuan = forceDefaultUnit ?
                        resolvedDefaultUnit :
                        (matchedUnit || (units[0] || ''));
                    row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                },

                onCodeTyped(row, index = null) {
                    this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);
                    this.onRowUpdated(index);
                },

                openDesc(index = null) {
                    const row = this.rows[index] || null;
                    if (!row) return;
                    this.descRowIndex = index;
                    this.descValue = (row.fdesc || '').toString();
                    this.descItemLabel = (row.fitemname || '').toString();
                    this.showDescModal = true;
                },

                closeDesc() {
                    this.showDescModal = false;
                    this.descRowIndex = null;
                    this.descValue = '';
                    this.descItemLabel = '';
                },

                applyDesc() {
                    if (this.descRowIndex !== null && this.rows[this.descRowIndex]) {
                        this.rows[this.descRowIndex].fdesc = (this.descValue || '').trim();
                        this.onRowUpdated(this.descRowIndex);
                    }
                    this.closeDesc();
                },

                showWarning(title, message, items = [], canProceed = false) {
                    this.warningTitle = title;
                    this.warningMessage = message;
                    this.warningItems = items;
                    this.warningCanProceed = canProceed;
                    this.showWarningModal = true;
                },

                closeWarning() {
                    this.showWarningModal = false;
                    this.warningTitle = '';
                    this.warningMessage = '';
                    this.warningItems = [];
                    this.warningCanProceed = false;
                },

                addRow(index) {
                    const row = this.rows[index];
                    if (!String(row?.fitemcode || '').trim()) {
                        this.showWarning('Kode Produk Belum Diisi',
                            'Isi kode produk terlebih dahulu sebelum menambah baris baru.');
                        return;
                    }
                    if (!String(row?.fitemname || '').trim()) {
                        this.showWarning('Produk Belum Valid',
                            'Produk pada baris ini belum ditemukan. Pilih produk yang valid terlebih dahulu.');
                        return;
                    }
                    this.rows.splice(index + 1, 0, this.emptyRow());
                },

                removeRow(index) {
                    this.rows.splice(index, 1);
                    this.ensureMinimumRows();
                },

                openBrowseFor(index) {
                    this.browseTargetIndex = index;
                    window.dispatchEvent(new CustomEvent('browse-product', {
                        detail: {
                            target: 'row',
                            index
                        }
                    }));
                },

                prepareRowsForSubmit() {
                    const validRows = [];
                    const zeroQtyRows = [];
                    const seenCodes = new Set();

                    for (const row of this.rows) {
                        const code = String(row.fitemcode || '').trim();
                        const name = String(row.fitemname || '').trim();
                        const sat = String(row.fsatuan || '').trim();
                        const qty = Number(row.fqty || 0);
                        const ket = String(row.fketdt || '').trim();
                        const desc = String(row.fdesc || '').trim();

                        if (!code && !name && !sat && !qty && !ket && !desc) {
                            continue;
                        }

                        if (!code) {
                            return {
                                invalidMessage: 'Masih ada baris detail item tanpa kode produk.',
                                validRows: [],
                                zeroQtyRows: []
                            };
                        }

                        if (!name) {
                            return {
                                invalidMessage: `Kode produk ${code} belum valid atau belum dipilih dari daftar produk.`,
                                validRows: [],
                                zeroQtyRows: []
                            };
                        }

                        if (!sat) {
                            return {
                                invalidMessage: `Satuan untuk produk ${name} belum dipilih.`,
                                validRows: [],
                                zeroQtyRows: []
                            };
                        }

                        const normalizedCode = code.toUpperCase();
                        if (seenCodes.has(normalizedCode)) {
                            return {
                                invalidMessage: `Produk ${name || code} sudah diinput. Kode produk yang sama tidak boleh dipakai lebih dari 1 kali.`,
                                validRows: [],
                                zeroQtyRows: []
                            };
                        }

                        seenCodes.add(normalizedCode);

                        if (!(qty > 0)) {
                            zeroQtyRows.push(name || code);
                            continue;
                        }

                        validRows.push({
                            ...row,
                            fitemcode: code,
                            fitemname: name,
                            fsatuan: sat,
                            fqty: qty,
                            fketdt: ket,
                            fdesc: desc,
                            fnoacak: this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak()
                        });
                    }

                    return {
                        invalidMessage: '',
                        validRows,
                        zeroQtyRows
                    };
                },

                handleSubmit(forceSubmit = false) {
                    this.showNoItems = false;
                    const prepared = this.prepareRowsForSubmit();

                    if (prepared.invalidMessage) {
                        this.showWarning('Data Item Belum Lengkap', prepared.invalidMessage);
                        return;
                    }

                    if (prepared.validRows.length < 1) {
                        this.showNoItems = true;
                        return;
                    }

                    this.rowsToSubmit = prepared.validRows;

                    if (prepared.zeroQtyRows.length > 0 && !forceSubmit) {
                        this.showWarning(
                            'Qty Produk Masih 0',
                            'Data produk berikut qty-nya masih 0, tidak akan tersimpan:',
                            prepared.zeroQtyRows,
                            true
                        );
                        return;
                    }

                    this.$nextTick(() => this.$root.closest('form')?.submit());
                },

                confirmWarningAndSubmit() {
                    this.closeWarning();
                    this.handleSubmit(true);
                },

                init() {
                    this.rows = this.rows.map(row => ({
                        ...row,
                        fnoacak: this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak(),
                        fqty: this.formatQtyDisplay(row.fqty)
                    }));
                    this.ensureMinimumRows();

                    window.addEventListener('product-chosen', (e) => {
                        const {
                            code,
                            target,
                            index
                        } = e.detail || {};
                        if (target !== 'row') return;

                        const row = this.rows[index];
                        if (!row) return;

                        row.fitemcode = (code || '').toString();
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), true);

                        // Force Alpine reactivity
                        this.rows.splice(index, 1, {
                            ...this.rows[index]
                        });
                        this.onRowUpdated(index);
                    });

                    window.addEventListener('tr-prh-edit-submit-request', () => this.handleSubmit(), {
                        passive: true
                    });
                }
            };
        }
    </script>
@endpush
