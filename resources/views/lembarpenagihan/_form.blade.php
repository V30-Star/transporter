@php
    $isReadOnly = in_array($action, ['view', 'delete'], true);
    $isDelete = $action === 'delete';
    $formAction = $action === 'create' ? route('lembarpenagihan.store') : route('lembarpenagihan.update', $header->ftagihanid);
    $detailRows = $details->map(fn($d) => [
        'frefcode' => trim((string) $d->frefcode),
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
    <form method="POST" action="{{ $isDelete ? route('lembarpenagihan.destroy', $header->ftagihanid) : $formAction }}" class="bg-white rounded shadow p-4"
        x-data="tagihanForm({{ Js::from($detailRows) }})">
        @csrf
        @if ($action === 'edit') @method('PATCH') @endif
        @if ($isDelete) @method('DELETE') @endif

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">No. Tagihan</label>
                @if ($action === 'create')
                    <div class="flex items-center gap-3" x-data="{ autoCode: true }">
                        <input type="text" name="ftagihanno" value="{{ old('ftagihanno') }}"
                            :disabled="autoCode" :class="autoCode ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'"
                            class="w-full border rounded px-3 py-2">
                        <label class="inline-flex items-center select-none font-bold">
                            <input type="checkbox" x-model="autoCode" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>
                @else
                    <input type="text" name="ftagihanno" value="{{ old('ftagihanno', $header->ftagihanno ?? $nextNo) }}" readonly class="w-full border rounded px-3 py-2 bg-gray-100">
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Customer</label>
                <select name="fcustno" class="w-full border rounded px-3 py-2" {{ $isReadOnly ? 'disabled' : '' }}>
                    <option value="">Pilih Customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->fcustomercode }}" {{ old('fcustno', $header->fcustno ?? '') === $customer->fcustomercode ? 'selected' : '' }}>
                            {{ $customer->fcustomercode }} - {{ $customer->fcustomername }}
                        </option>
                    @endforeach
                </select>
                @if ($isReadOnly)<input type="hidden" name="fcustno" value="{{ $header->fcustno }}">@endif
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal</label>
                <input type="date" name="ftagihandate" value="{{ old('ftagihandate', isset($header) ? \Carbon\Carbon::parse($header->ftagihandate)->format('Y-m-d') : date('Y-m-d')) }}" class="w-full border rounded px-3 py-2" {{ $isReadOnly ? 'readonly' : '' }}>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Keterangan</label>
            <textarea name="fnote" rows="2" class="w-full border rounded px-3 py-2" {{ $isReadOnly ? 'readonly' : '' }}>{{ old('fnote', $header->fnote ?? '') }}</textarea>
        </div>

        <div class="overflow-auto border rounded mb-4">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">No.</th>
                        <th class="border px-2 py-1">No.Nota</th>
                        <th class="border px-2 py-1">Tanggal Nota</th>
                        <th class="border px-2 py-1 text-right">Nilai Nota</th>
                        <th class="border px-2 py-1 text-right">Ongkos Kirim</th>
                        <th class="border px-2 py-1 text-right">Sisa Piutang</th>
                        @if (!$isReadOnly)<th class="border px-2 py-1">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in visibleRows()" :key="row.empty ? `empty-${index}` : row.frefsono + index">
                        <tr>
                            <td class="border px-2 py-1" x-text="index + 1"></td>
                            <td class="border px-2 py-1">
                                <span x-text="row.empty ? '' : row.frefsono"></span>
                                <template x-if="!row.empty">
                                    <span>
                                        <input type="hidden" :name="`frefsono[${index}]`" :value="row.frefsono">
                                        <input type="hidden" :name="`frefcode[${index}]`" :value="row.frefcode">
                                    </span>
                                </template>
                            </td>
                            <td class="border px-2 py-1" x-text="row.empty ? '' : row.fsodate"></td>
                            <td class="border px-2 py-1 text-right" x-text="row.empty ? '' : money(row.famountbil)"></td>
                            <td class="border px-2 py-1 text-right" x-text="row.empty ? '' : money(row.fongkos)"></td>
                            <td class="border px-2 py-1 text-right">
                                <span x-text="row.empty ? '' : money(row.famount)"></span>
                                <template x-if="!row.empty">
                                    <input type="hidden" :name="`famount[${index}]`" :value="row.famount">
                                </template>
                            </td>
                            @if (!$isReadOnly)
                                <td class="border px-2 py-1 text-center">
                                    <button type="button" @click="!row.empty && rows.splice(index, 1)" :disabled="row.empty" class="px-2 py-1 bg-red-600 text-white rounded disabled:opacity-40">-</button>
                                </td>
                            @endif
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        @if (!$isReadOnly)
            <div class="mb-4 flex gap-2">
                <button type="button" @click="openInvoiceModal()" class="px-4 py-2 bg-blue-600 text-white rounded">Add Faktur</button>
            </div>

            <div x-show="invoiceModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden" style="height: min(760px, calc(100vh - 1.5rem));">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Browse Faktur</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Pilih faktur yang diinginkan</p>
                        </div>
                        <button type="button" @click="closeInvoiceModal()" class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">Tutup</button>
                    </div>
                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                        <div id="invoiceTableControls"></div>
                    </div>
                    <div class="flex-1 overflow-auto p-6" style="min-height: 0;">
                        <div class="bg-white min-w-max">
                            <table id="invoiceBrowseTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">No.Faktur</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Tanggal</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Customer</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nilai Nota</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Ongkos Kirim</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Sisa Piutang</th>
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                        <div id="invoiceTablePagination"></div>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex justify-end mb-4">
            <div class="border rounded p-3 w-72 bg-gray-50">
                <div class="flex justify-between font-semibold">
                    <span>Total Tagihan:</span>
                    <span x-text="money(total())"></span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('lembarpenagihan.index') }}" class="px-4 py-2 bg-gray-100 rounded">Kembali</a>
            @if (!$isReadOnly)<button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>@endif
            @if ($isDelete)<button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Hapus</button>@endif
        </div>
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        #invoiceBrowseTable_wrapper .dt-layout-row,
        #invoiceBrowseTable_wrapper .dataTables_wrapper .row {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 16px !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
        }

        #invoiceBrowseTable_wrapper .dt-layout-cell,
        #invoiceBrowseTable_wrapper .dataTables_filter,
        #invoiceBrowseTable_wrapper .dataTables_length,
        #invoiceBrowseTable_wrapper .dataTables_info,
        #invoiceBrowseTable_wrapper .dataTables_paginate,
        #invoiceBrowseTable_wrapper .dt-search,
        #invoiceBrowseTable_wrapper .dt-length,
        #invoiceBrowseTable_wrapper .dt-info,
        #invoiceBrowseTable_wrapper .dt-paging {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            white-space: nowrap !important;
            flex-wrap: nowrap !important;
            width: auto !important;
            margin: 0 !important;
        }

        #invoiceBrowseTable_wrapper .dataTables_filter,
        #invoiceBrowseTable_wrapper .dt-search {
            flex: 1 1 auto !important;
            justify-content: flex-start !important;
        }

        #invoiceBrowseTable_wrapper .dataTables_length,
        #invoiceBrowseTable_wrapper .dt-length {
            margin-left: auto !important;
            flex: 0 0 auto !important;
            justify-content: flex-end !important;
        }

        #invoiceBrowseTable_wrapper .dataTables_paginate,
        #invoiceBrowseTable_wrapper .dt-paging,
        #invoiceTablePagination .dataTables_paginate,
        #invoiceTablePagination .dt-paging {
            gap: 6px !important;
        }

        #invoiceBrowseTable_wrapper .dataTables_paginate .paginate_button,
        #invoiceBrowseTable_wrapper .dt-paging .dt-paging-button,
        #invoiceTablePagination .dataTables_paginate .paginate_button,
        #invoiceTablePagination .dt-paging .dt-paging-button {
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

        #invoiceBrowseTable_wrapper .dataTables_paginate .paginate_button.current,
        #invoiceBrowseTable_wrapper .dataTables_paginate .paginate_button.current:hover,
        #invoiceBrowseTable_wrapper .dt-paging .dt-paging-button.current,
        #invoiceTablePagination .dataTables_paginate .paginate_button.current,
        #invoiceTablePagination .dataTables_paginate .paginate_button.current:hover,
        #invoiceTablePagination .dt-paging .dt-paging-button.current {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }

        #invoiceBrowseTable_wrapper .dataTables_paginate .paginate_button:hover,
        #invoiceBrowseTable_wrapper .dt-paging .dt-paging-button:hover,
        #invoiceTablePagination .dataTables_paginate .paginate_button:hover,
        #invoiceTablePagination .dt-paging .dt-paging-button:hover {
            background: #eff6ff !important;
            border-color: #93c5fd !important;
            color: #1d4ed8 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        function tagihanForm(initialRows) {
            return {
                rows: initialRows || [],
                visibleRows() {
                    const display = [...this.rows];
                    while (display.length < 5) display.push({ empty: true });
                    return display;
                },
                invoiceModalOpen: false,
                invoiceTable: null,
                openInvoiceModal() {
                    this.invoiceModalOpen = true;
                    this.$nextTick(() => this.initInvoiceTable());
                },
                closeInvoiceModal() {
                    this.invoiceModalOpen = false;
                    if (this.invoiceTable) {
                        $('#invoiceBrowseTable').off('.invoicepick');
                        this.invoiceTable.destroy();
                        this.invoiceTable = null;
                    }
                },
                initInvoiceTable() {
                    if (this.invoiceTable) {
                        this.invoiceTable.ajax.reload(null, false);
                        this.invoiceTable.columns.adjust().draw(false);
                        return;
                    }

                    $('#invoiceBrowseTable').off('.invoicepick');

                    this.invoiceTable = $('#invoiceBrowseTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('lembarpenagihan.pickable-invoices') }}",
                            type: 'GET',
                            data: (d) => ({
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                                fcustno: document.querySelector('[name="fcustno"]')?.value || '',
                            }),
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
                        dom: '<"invoice-browser-top"fl>rt<"invoice-browser-bottom"ip>',
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

                            const controls = document.getElementById('invoiceTableControls');
                            if (controls) {
                                controls.innerHTML = '';
                                controls.className = 'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 w-full';
                                controls.setAttribute('style', 'display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:16px !important; width:100% !important;');
                                $container.find('.dataTables_filter, .dt-search').addClass('order-1 shrink-0 whitespace-nowrap').appendTo(controls);
                                $container.find('.dataTables_length, .dt-length').addClass('order-2 shrink-0 whitespace-nowrap').appendTo(controls);
                            }

                            const pagination = document.getElementById('invoiceTablePagination');
                            if (pagination) {
                                pagination.innerHTML = '';
                                pagination.className = 'flex items-center justify-between gap-4 flex-nowrap';
                                pagination.setAttribute('style', 'display:flex !important; align-items:center !important; justify-content:space-between !important; gap:16px !important; flex-wrap:nowrap !important; width:100% !important;');
                                $container.find('.dataTables_info, .dt-info').addClass('order-1 shrink-0 whitespace-nowrap').appendTo(pagination);
                                $container.find('.dataTables_paginate, .dt-paging').addClass('order-2 ml-auto shrink-0 whitespace-nowrap').appendTo(pagination);
                            }
                        },
                    });

                    $('#invoiceBrowseTable').on('click.invoicepick', '.btn-choose', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const data = this.invoiceTable?.row($(event.currentTarget).closest('tr')).data();
                        this.pickInvoice(data);
                    });

                    $('#invoiceBrowseTable').on('click.invoicepick', 'tbody tr', (event) => {
                        if ($(event.target).closest('button, a, input, select, textarea').length) return;
                        const data = this.invoiceTable?.row(event.currentTarget).data();
                        this.pickInvoice(data);
                    });
                },
                pickInvoice(invoice) {
                    if (!invoice || !invoice.fsono || this.rows.some(row => row.frefsono === invoice.fsono)) return;
                    this.rows.push({
                        frefcode: 'INV',
                        frefsono: invoice.fsono,
                        fsodate: this.formatDate(invoice.fsodate),
                        famountbil: Number(invoice.famountbil || 0),
                        fongkos: Number(invoice.fongkos || 0),
                        famount: Number(invoice.famount || 0),
                    });
                    this.closeInvoiceModal();
                },
                total() { return this.rows.reduce((sum, row) => sum + Number(row.famount || 0), 0); },
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
    </script>
@endpush
