@php
    $isReadOnly = in_array($action, ['view', 'delete'], true);
    $isDelete = $action === 'delete';
    $formAction = $action === 'create' ? route('lembarpenagihan.store') : route('lembarpenagihan.update', $header->ftagihanid);
    $detailRows = $details->map(fn($d) => [
        'frefcode' => trim((string) $d->frefcode),
        'ftrtagihanid' => trim((string) $d->ftrtagihanid),
        'frefsono' => trim((string) $d->frefsono),
        'fsodate' => $d->fsodate ? \Carbon\Carbon::parse($d->fsodate)->format('Y-m-d') : '',
        'famountbil' => (float) $d->famountbil,
        'fongkos' => (float) $d->fongkos,
        'famount' => (float) $d->famount,
    ])->values();
@endphp

@extends('layouts.app')

@section('title', $title)

@section('content')
    <div>
        <form method="POST" action="{{ $isDelete ? route('lembarpenagihan.destroy', $header->ftagihanid) : $formAction }}"
            x-data="tagihanForm()">
            @csrf
            @if ($action === 'edit') @method('PATCH') @endif
            @if ($isDelete) @method('DELETE') @endif

            {{-- ─── CARD 1: Identitas Penagihan ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas Penagihan</p>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        {{-- No. Tagihan --}}
                        @if ($action === 'create')
                            <div x-data="{ autoCode: true }">
                                <label class="block text-xs font-bold mb-1">No. Tagihan</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="ftagihanno" value="{{ old('ftagihanno') }}"
                                        :disabled="autoCode"
                                        :class="autoCode ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : 'bg-white'"
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :placeholder="autoCode ? 'Auto Generated' : ''">
                                    <label class="inline-flex items-center select-none font-medium text-sm text-gray-600 cursor-pointer">
                                        <input type="checkbox" x-model="autoCode" checked
                                            class="rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <span class="ml-1.5">Auto</span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-bold mb-1">No. Tagihan</label>
                                <input type="text" name="ftagihanno" value="{{ old('ftagihanno', $header->ftagihanno ?? $nextNo) }}" readonly
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                            </div>
                        @endif

                        {{-- Customer --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Customer <span class="text-red-500">*</span></label>
                            @if ($isReadOnly)
                                <input type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                                    value="{{ $header->fcustno }} - {{ $header->fcustomername ?? '' }}" readonly>
                                <input type="hidden" name="fcustno" value="{{ $header->fcustno }}">
                            @else
                                <div class="flex">
                                    <div class="relative flex-1" for="modal_filter_customer_id">
                                        <select id="modal_filter_customer_id" name="filter_customer_id"
                                            class="w-full border border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500"
                                            disabled>
                                            <option value=""></option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->fcustomercode }}"
                                                    {{ old('fcustno', $header->fcustno ?? '') === $customer->fcustomercode ? 'selected' : '' }}>
                                                    {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0 cursor-pointer" role="button" aria-label="Browse Customer"
                                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="fcustno" id="customerCodeHidden" value="{{ old('fcustno', $header->fcustno ?? '') }}">
                                    <button type="button"
                                        @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                        class="border border-l-0 border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-gray-50 text-gray-500 transition-colors"
                                        title="Browse Customer">
                                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-xs font-bold mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="ftagihandate"
                                value="{{ old('ftagihandate', isset($header) ? \Carbon\Carbon::parse($header->ftagihandate)->format('Y-m-d') : date('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 {{ $isReadOnly ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : '' }}"
                                {{ $isReadOnly ? 'readonly' : '' }}>
                        </div>
                    </div>

                    {{-- Keterangan --}}
                    <div>
                        <label class="block text-xs font-bold mb-1">Keterangan</label>
                        <textarea name="fnote" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 {{ $isReadOnly ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : '' }}"
                            {{ $isReadOnly ? 'readonly' : '' }}
                            placeholder="Tulis keterangan tambahan di sini...">{{ old('fnote', $header->fnote ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ─── CARD 2: Detail Penagihan ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Detail Penagihan</p>
                </div>
                <div class="p-4">
                    <div class="overflow-auto border rounded">
                        <table class="pr-detail-table min-w-full text-sm balanced-detail-table" id="tagihan-detail-table"
                            data-skip-auto-detail-style="true">
                            @if ($isReadOnly)
                                <colgroup>
                                    <col style="width:2%;">
                                    <col style="width:20%;">
                                    <col style="width:18%;">
                                    <col style="width:20%;">
                                    <col style="width:20%;">
                                    <col style="width:20%;">
                                </colgroup>
                            @else
                                <colgroup>
                                    <col style="width:2%;">
                                    <col style="width:20%;">
                                    <col style="width:15%;">
                                    <col style="width:18%;">
                                    <col style="width:18%;">
                                    <col style="width:18%;">
                                    <col style="width:9%;">
                                </colgroup>
                            @endif
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-10">#</th>
                                    <th class="p-2 text-left w-52">No.Nota</th>
                                    <th class="p-2 text-left w-40">Tanggal Nota</th>
                                    <th class="p-2 text-right w-36 whitespace-nowrap">Nilai Nota</th>
                                    <th class="p-2 text-right w-36 whitespace-nowrap">Ongkos Kirim</th>
                                    <th class="p-2 text-right w-36 whitespace-nowrap">Sisa Piutang</th>
                                    @if (!$isReadOnly)
                                        <th class="p-2 text-center w-24">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody id="tagihan-detail-body">
                                @php
                                    $actualCount = count($detailRows);
                                    $placeholderCount = max(0, 5 - $actualCount);
                                @endphp
                                @foreach ($detailRows as $index => $row)
                                    <tr class="border-t align-middle bg-white hover:bg-gray-50" data-ref="{{ $row['frefsono'] }}">
                                        <td class="p-2 text-gray-400 row-number">{{ $index + 1 }}</td>
                                        <td class="p-2">
                                            <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded font-mono">{{ $row['frefsono'] }}</div>
                                            <input type="hidden" name="frefsono[{{ $index }}]" value="{{ $row['frefsono'] }}">
                                            <input type="hidden" name="frefcode[{{ $index }}]" value="{{ $row['frefcode'] }}">
                                        </td>
                                        <td class="p-2">
                                            <div class="px-2 py-1 text-sm text-gray-650 bg-gray-50 border rounded">{{ $row['fsodate'] }}</div>
                                        </td>
                                        <td class="p-2 text-right">
                                            <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium">{{ number_format($row['famountbil'], 2, ',', '.') }}</div>
                                        </td>
                                        <td class="p-2 text-right">
                                            <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium">{{ number_format($row['fongkos'], 2, ',', '.') }}</div>
                                        </td>
                                        <td class="p-2 text-right">
                                            <div class="px-2 py-1 text-sm text-gray-700 bg-gray-50 border rounded text-right font-medium">{{ number_format($row['famount'], 2, ',', '.') }}</div>
                                            <input type="hidden" name="famount[{{ $index }}]" value="{{ $row['famount'] }}" class="row-amount">
                                        </td>
                                        @if (!$isReadOnly)
                                            <td class="p-2 text-center text-xs">
                                                <button type="button" class="btn-remove-row inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors" title="Hapus baris">-</button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                                @for ($i = 0; $i < $placeholderCount; $i++)
                                    <tr class="border-t align-middle bg-white empty-row">
                                        <td class="p-2 text-gray-400 row-number">{{ $actualCount + $i + 1 }}</td>
                                        <td class="p-2"><div class="px-2 py-1 text-sm text-gray-400 bg-gray-50 border border-dashed rounded font-mono">-</div></td>
                                        <td class="p-2"><div class="px-2 py-1 text-sm text-gray-400 bg-gray-50 border border-dashed rounded">-</div></td>
                                        <td class="p-2"><div class="px-2 py-1 text-sm text-gray-400 bg-gray-50 border border-dashed rounded text-right">-</div></td>
                                        <td class="p-2"><div class="px-2 py-1 text-sm text-gray-400 bg-gray-50 border border-dashed rounded text-right">-</div></td>
                                        <td class="p-2"><div class="px-2 py-1 text-sm text-gray-400 bg-gray-50 border border-dashed rounded text-right">-</div></td>
                                        @if (!$isReadOnly)
                                            <td class="p-2 text-center text-xs">
                                                <button type="button" class="btn-remove-row inline-flex h-8 w-8 items-center justify-center rounded bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed" title="Hapus baris" disabled>-</button>
                                            </td>
                                        @endif
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    @if (!$isReadOnly)
                        <div class="mt-3 flex gap-2">
                            <button type="button" @click="openNotaModal()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-lg transition-colors">
                                <x-heroicon-o-plus class="w-4 h-4" />
                                Add Retur
                            </button>
                        </div>

                        <div x-show="notaModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6">
                            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeNotaModal()"></div>
                            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden" style="height: min(760px, calc(100vh - 1.5rem));">
                                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800">Browse Retur Penjualan</h3>
                                        <p class="text-sm text-gray-500 mt-0.5">Pilih retur yang ingin ditambahkan</p>
                                    </div>
                                    <button type="button" @click="closeNotaModal()" class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">Tutup</button>
                                </div>
                                <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                                    <div id="notaTableControls"></div>
                                </div>
                                <div class="flex-1 overflow-auto p-6" style="min-height: 0;">
                                    <div class="bg-white min-w-max">
                                        <table id="notaBrowseTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                                            <thead class="sticky top-0 z-10">
                                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">No.Nota</th>
                                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Tanggal Nota</th>
                                                    <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Customer</th>
                                                    <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Nilai Nota</th>
                                                    <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Ongkos Kirim</th>
                                                    <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Sisa Piutang</th>
                                                    <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                                    <div id="notaTablePagination"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end mt-4">
                        <div class="w-[560px] shrink-0 max-w-full">
                            <div class="rounded-lg border bg-gray-50 p-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between text-base">
                                    <span class="font-extrabold text-gray-900">Total Tagihan</span>
                                    <span id="total-tagihan-value" class="font-extrabold text-blue-700 text-lg">
                                        {{ number_format($header->famounttagihan ?? 0, 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── CARD 3: Aksi ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Aksi</p>
                </div>
                <div class="p-4 space-y-4">
                    {{-- Empty placeholder for consistency with PR layout --}}
                </div>
                
                {{-- Footer Buttons --}}
                <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <a href="{{ route('lembarpenagihan.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Keluar
                    </a>
                    <div class="flex gap-2">
                        @if ($isDelete)
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                <x-heroicon-o-trash class="w-4 h-4" />
                                Hapus
                            </button>
                        @endif
                        @if (!$isReadOnly)
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Simpan
                            </button>
                        @endif
                        @if ($action === 'view')
                            <a href="{{ route('lembarpenagihan.print', $header->ftagihanno) }}" target="_blank"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <x-heroicon-o-printer class="w-4 h-4" />
                                Print
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        #notaBrowseTable_wrapper .dt-layout-row,
        #notaBrowseTable_wrapper .dataTables_wrapper .row {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 16px !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
        }

        #notaBrowseTable_wrapper .dt-layout-cell,
        #notaBrowseTable_wrapper .dataTables_filter,
        #notaBrowseTable_wrapper .dataTables_length,
        #notaBrowseTable_wrapper .dataTables_info,
        #notaBrowseTable_wrapper .dataTables_paginate,
        #notaBrowseTable_wrapper .dt-search,
        #notaBrowseTable_wrapper .dt-length,
        #notaBrowseTable_wrapper .dt-info,
        #notaBrowseTable_wrapper .dt-paging {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            white-space: nowrap !important;
            flex-wrap: nowrap !important;
            width: auto !important;
            margin: 0 !important;
        }

        #notaBrowseTable_wrapper .dataTables_filter,
        #notaBrowseTable_wrapper .dt-search {
            flex: 1 1 auto !important;
            justify-content: flex-start !important;
        }

        #notaBrowseTable_wrapper .dataTables_length,
        #notaBrowseTable_wrapper .dt-length {
            margin-left: auto !important;
            flex: 0 0 auto !important;
            justify-content: flex-end !important;
        }

        #notaBrowseTable_wrapper .dataTables_paginate,
        #notaBrowseTable_wrapper .dt-paging,
        #notaTablePagination .dataTables_paginate,
        #notaTablePagination .dt-paging {
            gap: 6px !important;
        }

        #notaBrowseTable_wrapper .dataTables_paginate .paginate_button,
        #notaBrowseTable_wrapper .dt-paging .dt-paging-button,
        #notaTablePagination .dataTables_paginate .paginate_button,
        #notaTablePagination .dt-paging .dt-paging-button {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 38px !important;
            height: 38px !important;
            padding: 0 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 10px !important;
            background: #ffffff !important;
            color: #374151 !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            line-height: 1 !important;
            margin: 0 !important;
            box-shadow: none !important;
        }

        #notaBrowseTable_wrapper .dataTables_paginate .paginate_button.current,
        #notaBrowseTable_wrapper .dataTables_paginate .paginate_button.current:hover,
        #notaBrowseTable_wrapper .dt-paging .dt-paging-button.current,
        #notaTablePagination .dataTables_paginate .paginate_button.current,
        #notaTablePagination .dataTables_paginate .paginate_button.current:hover,
        #notaTablePagination .dt-paging .dt-paging-button.current {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }

        #notaBrowseTable_wrapper .dataTables_paginate .paginate_button:hover,
        #notaBrowseTable_wrapper .dt-paging .dt-paging-button:hover,
        #notaTablePagination .dataTables_paginate .paginate_button:hover,
        #notaTablePagination .dt-paging .dt-paging-button:hover {
            background: #eff6ff !important;
            border-color: #93c5fd !important;
            color: #1d4ed8 !important;
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

        input::placeholder,
        textarea::placeholder {
            color: #9ca3af !important;
            font-weight: normal !important;
        }

        input:disabled::placeholder,
        textarea:disabled::placeholder {
            color: #9ca3af !important;
            -webkit-text-fill-color: #9ca3af !important;
            font-weight: normal !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @include('components.transaction.browse-customer-script')
    <script>
        function tagihanForm() {
            return {
                notaModalOpen: false,
                notaTable: null,
                openNotaModal() {
                    this.notaModalOpen = true;
                    this.$nextTick(() => this.initNotaTable());
                },
                closeNotaModal() {
                    this.notaModalOpen = false;
                    if (this.notaTable) {
                        $('#notaBrowseTable').off('.notapick');
                        this.notaTable.destroy();
                        this.notaTable = null;
                    }
                },
                initNotaTable() {
                    if (this.notaTable) {
                        this.notaTable.ajax.reload(null, false);
                        this.notaTable.columns.adjust().draw(false);
                        return;
                    }

                    $('#notaBrowseTable').off('.notapick');

                    this.notaTable = $('#notaBrowseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('lembarpenagihan.pickable-returns') }}",
                            type: 'GET',
                            data: (d) => {
                                const orderColumn = d.columns[d.order[0].column].data;
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    order_column: orderColumn || 'fsodate',
                                    order_dir: d.order[0].dir,
                                    customer_code: document.querySelector('[name="fcustno"]')?.value || '',
                                };
                            },
                        },
                        columns: [
                            { data: 'fsono', className: 'font-mono text-sm' },
                            { data: 'fsodate', render: data => this.formatDate(data) },
                            { data: null, render: data => `${data.fcustno || ''} - ${data.fcustomername || ''}` },
                            { data: 'famountbil', className: 'text-right', render: data => this.money(data) },
                            { data: 'fongkos', className: 'text-right', render: data => this.money(data) },
                            { data: 'famount', className: 'text-right', render: data => this.money(data) },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                render: () => '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>',
                            },
                        ],
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                        dom: '<"nota-browser-top"fl>rt<"nota-browser-bottom"ip>',
                        language: {
                            processing: 'Memuat data...',
                            search: 'Search:',
                            lengthMenu: 'Tampilkan _MENU_',
                            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                            infoEmpty: 'Tidak ada data',
                            infoFiltered: '(disaring dari _MAX_ total data)',
                            zeroRecords: 'Tidak ada data yang ditemukan',
                            emptyTable: 'Tidak ada data tersedia',
                            paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' },
                        },
                        order: [[1, 'desc']],
                        autoWidth: false,
                        initComplete: function() {
                            const api = this.api();
                            const $container = $(api.table().container());

                            $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px',
                            }).focus();

                            $container.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px',
                            });

                            const controls = document.getElementById('notaTableControls');
                            if (controls) {
                                controls.innerHTML = '';
                                controls.className = 'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 w-full';
                                controls.setAttribute('style', 'display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:16px !important; width:100% !important;');
                                $container.find('.dataTables_filter, .dt-search').addClass('order-1 shrink-0 whitespace-nowrap').appendTo(controls);
                                $container.find('.dataTables_length, .dt-length').addClass('order-2 shrink-0 whitespace-nowrap').appendTo(controls);
                            }

                            const pagination = document.getElementById('notaTablePagination');
                            if (pagination) {
                                pagination.innerHTML = '';
                                pagination.className = 'flex items-center justify-between gap-4 flex-nowrap';
                                pagination.setAttribute('style', 'display:flex !important; align-items:center !important; justify-content:space-between !important; gap:16px !important; flex-wrap:nowrap !important; width:100% !important;');
                                $container.find('.dataTables_info, .dt-info').addClass('order-1 shrink-0 whitespace-nowrap').appendTo(pagination);
                                $container.find('.dataTables_paginate, .dt-paging').addClass('order-2 ml-auto shrink-0 whitespace-nowrap').appendTo(pagination);
                            }
                        },
                    });

                    $('#notaBrowseTable').on('click.notapick', '.btn-choose', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const data = this.notaTable?.row($(event.currentTarget).closest('tr')).data();
                        this.pickNota(data);
                    });

                    $('#notaBrowseTable').on('click.notapick', 'tbody tr', (event) => {
                        if ($(event.target).closest('button, a, input, select, textarea').length) return;
                        const data = this.notaTable?.row(event.currentTarget).data();
                        this.pickNota(data);
                    });
                },
                pickNota(invoice) {
                    if (!invoice || !invoice.fsono) return;
                    window.dispatchEvent(new CustomEvent('invoice-picked', {
                        detail: {
                            items: [{
                                frefcode: 'REJ',
                                fsono: invoice.fsono,
                                fsodate: invoice.fsodate,
                                famountbil: Number(invoice.famountbil ?? invoice.famount ?? 0),
                                fongkos: Number(invoice.fongkos ?? 0),
                                famount: Number(invoice.famount ?? 0),
                            }]
                        }
                    }));
                    this.closeNotaModal();
                },
                money(value) { return Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value);
                    if (Number.isNaN(date.getTime())) return '';
                    const pad = number => number.toString().padStart(2, '0');
                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
                },
            };
        }

        // Vanilla JavaScript Table Handler
        document.addEventListener('DOMContentLoaded', () => {
            const isReadOnly = @json($isReadOnly);

            function formatMoney(value) {
                return Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function formatDate(value) {
                if (!value) return '';
                const date = new Date(value);
                if (isNaN(date.getTime())) return value;
                const pad = n => n.toString().padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
            }

            function normalizeItem(item) {
                const frefsono = item.frefsono || item.fsono || '';
                const frefcode = item.frefcode || item.ftrcode || 'INV';
                const ftrtagihanid = item.ftrtagihanid || '';
                const fsodate = item.fsodate || item.fdate || '';
                const famountbil = Number(item.famountbil ?? item.famountso ?? 0);
                const fongkos = Number(item.fongkos ?? item.fongkosangkut ?? 0);
                const famount = Number(item.famount ?? item.famountremain ?? item.famountso ?? 0);
                return { ftrtagihanid, frefsono, frefcode, fsodate, famountbil, fongkos, famount };
            }

            function renderRow(item, index) {
                const normalized = normalizeItem(item);
                
                let aksiCol = '';
                if (!isReadOnly) {
                    aksiCol = `
                        <td class="p-2 text-center">
                            <div class="flex items-center justify-center">
                                <button type="button" class="btn-remove-row inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors border border-red-200" title="Hapus baris">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    `;
                }
                
                const tr = document.createElement('tr');
                tr.className = 'border-t border-gray-150 align-middle bg-white';
                tr.setAttribute('data-ref', normalized.frefsono);
                tr.innerHTML = `
                    <td class="p-2 text-gray-400 row-number">${normalized.ftrtagihanid || (index + 1)}</td>
                    <td class="p-2">
                        <input type="text" class="w-full border border-gray-200 rounded-lg px-2 py-1 font-mono text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-150" value="${normalized.frefsono}" readonly>
                        <input type="hidden" name="frefsono[${index}]" value="${normalized.frefsono}">
                        <input type="hidden" name="frefcode[${index}]" value="${normalized.frefcode}">
                    </td>
                    <td class="p-2">
                        <input type="text" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-150" value="${formatDate(normalized.fsodate)}" readonly>
                    </td>
                    <td class="p-2 text-right">
                        <input type="text" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-right text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-150" value="${formatMoney(normalized.famountbil)}" readonly>
                    </td>
                    <td class="p-2 text-right">
                        <input type="text" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-right text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-150" value="${formatMoney(normalized.fongkos)}" readonly>
                    </td>
                    <td class="p-2 text-right">
                        <input type="text" class="w-full border border-gray-200 rounded-lg px-2 py-1 text-right text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-150" value="${formatMoney(normalized.famount)}" readonly>
                        <input type="hidden" name="famount[${index}]" value="${normalized.famount}" class="row-amount">
                    </td>
                    ${aksiCol}
                `;
                return tr;
            }

            function updateTableDOM() {
                const tbody = document.getElementById('tagihan-detail-body');
                if (!tbody) return;

                const rows = Array.from(tbody.querySelectorAll('tr'));
                const dataRows = rows.filter(tr => !tr.classList.contains('empty-row'));
                
                // Remove empty placeholder rows
                rows.forEach(tr => {
                    if (tr.classList.contains('empty-row')) {
                        tr.remove();
                    }
                });

                // Re-index data rows
                dataRows.forEach((tr, index) => {
                    tr.setAttribute('data-index', index);
                    
                    const numCell = tr.querySelector('.row-number');
                    if (numCell) numCell.textContent = index + 1;
                    
                    const frefsonoInput = tr.querySelector('input[name^="frefsono"]');
                    if (frefsonoInput) frefsonoInput.setAttribute('name', `frefsono[${index}]`);
                    
                    const frefcodeInput = tr.querySelector('input[name^="frefcode"]');
                    if (frefcodeInput) frefcodeInput.setAttribute('name', `frefcode[${index}]`);
                    
                    const famountInput = tr.querySelector('input[name^="famount"]');
                    if (famountInput) famountInput.setAttribute('name', `famount[${index}]`);
                });

                // Add empty placeholder rows back if total rows are less than 5
                const actualCount = dataRows.length;
                if (actualCount < 5) {
                    const needed = 5 - actualCount;
                    for (let i = 0; i < needed; i++) {
                        const emptyTr = document.createElement('tr');
                        emptyTr.className = 'border-t border-gray-150 align-middle bg-white empty-row';
                        
                        let aksiCol = '';
                        if (!isReadOnly) {
                            aksiCol = `
                                <td class="p-2 text-center">
                                    <div class="flex items-center justify-center">
                                        <button type="button" class="btn-remove-row inline-flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors border border-red-200" title="Hapus baris" disabled style="opacity: 0.5; cursor: not-allowed;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            `;
                        }
                        
                        emptyTr.innerHTML = `
                            <td class="p-2 text-gray-400 row-number">${actualCount + i + 1}</td>
                            <td class="p-2"><input type="text" class="w-full border border-gray-205 rounded-lg px-2 py-1 font-mono text-sm bg-gray-50 text-gray-400 cursor-not-allowed" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border border-gray-205 rounded-lg px-2 py-1 text-sm bg-gray-50 text-gray-400 cursor-not-allowed" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border border-gray-205 rounded-lg px-2 py-1 text-right text-sm bg-gray-50 text-gray-400 cursor-not-allowed" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border border-gray-205 rounded-lg px-2 py-1 text-right text-sm bg-gray-50 text-gray-400 cursor-not-allowed" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border border-gray-205 rounded-lg px-2 py-1 text-right text-sm bg-gray-50 text-gray-400 cursor-not-allowed" readonly></td>
                            ${aksiCol}
                        `;
                        tbody.appendChild(emptyTr);
                    }
                }

                // Update Total Tagihan
                updateTotalTagihan();
            }

            function updateTotalTagihan() {
                const tbody = document.getElementById('tagihan-detail-body');
                if (!tbody) return;
                
                let total = 0;
                const amountInputs = tbody.querySelectorAll('input[name^="famount"]');
                amountInputs.forEach(input => {
                    total += Number(input.value || 0);
                });
                
                const totalSpan = document.getElementById('total-tagihan-value');
                if (totalSpan) {
                    totalSpan.textContent = formatMoney(total);
                }
            }

            function addLinkedItems(itemsArray) {
                const tbody = document.getElementById('tagihan-detail-body');
                if (!tbody) return;
                
                const emptyRows = tbody.querySelectorAll('.empty-row');
                emptyRows.forEach(tr => tr.remove());
                
                let currentIndex = tbody.querySelectorAll('tr:not(.empty-row)').length;
                const existingRefs = Array.from(tbody.querySelectorAll('input[name^="frefsono"]')).map(input => input.value.trim().toLowerCase());
                
                itemsArray.forEach(item => {
                    const normalized = normalizeItem(item);
                    if (!normalized.frefsono) return;
                    
                    if (existingRefs.includes(normalized.frefsono.trim().toLowerCase())) {
                        return;
                    }
                    
                    const newRow = renderRow(normalized, currentIndex);
                    tbody.appendChild(newRow);
                    currentIndex++;
                });
                
                updateTableDOM();
            }

            // Register event listeners
            const eventNames = ['pr-picked', 'pr-linked', 'invoice-picked', 'invoice-selected'];
            eventNames.forEach(evtName => {
                window.addEventListener(evtName, (e) => {
                    const detail = e.detail;
                    if (!detail) return;
                    
                    let items = [];
                    if (detail.items && Array.isArray(detail.items)) {
                        items = detail.items;
                    } else if (detail.products && Array.isArray(detail.products)) {
                        items = detail.products;
                    } else if (Array.isArray(detail)) {
                        items = detail;
                    } else {
                        items = [detail];
                    }
                    
                    if (items.length > 0) {
                        addLinkedItems(items);
                    }
                });
            });

            // Delegated click listener for removing rows
            const tbody = document.getElementById('tagihan-detail-body');
            if (tbody) {
                tbody.addEventListener('click', (e) => {
                    const btn = e.target.closest('.btn-remove-row');
                    if (btn) {
                        e.preventDefault();
                        const tr = btn.closest('tr');
                        if (tr) {
                            tr.remove();
                            updateTableDOM();
                        }
                    }
                });
            }
            
            // Listen to customer selection changes to clear invoice rows
            const customerHidden = document.getElementById('customerCodeHidden');
            if (customerHidden) {
                customerHidden.addEventListener('change', () => {
                    const tbody = document.getElementById('tagihan-detail-body');
                    if (tbody) {
                        const dataRows = tbody.querySelectorAll('tr:not(.empty-row)');
                        dataRows.forEach(tr => tr.remove());
                        updateTableDOM();
                    }
                });
            }
            
            // Set initial state
            updateTableDOM();
        });
    </script>
@endpush
