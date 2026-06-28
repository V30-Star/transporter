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
    <form method="POST" action="{{ $isDelete ? route('lembarpenagihan.destroy', $header->ftagihanid) : $formAction }}" class="bg-white rounded shadow p-6 md:p-8 max-w-[1800px] w-full mx-auto"
        x-data="tagihanForm()">
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
                @if ($isReadOnly)
                    <input type="text" class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-700" value="{{ $header->fcustno }} - {{ $header->fcustomername ?? '' }}" readonly>
                    <input type="hidden" name="fcustno" value="{{ $header->fcustno }}">
                @else
                    <div class="flex">
                        <div class="relative flex-1" for="modal_filter_customer_id">
                            <select id="modal_filter_customer_id" name="filter_customer_id"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->fcustomercode }}"
                                        {{ old('fcustno', $header->fcustno ?? '') === $customer->fcustomercode ? 'selected' : '' }}>
                                        {{ $customer->fcustomername }} ({{ $customer->fcustomercode }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                        </div>
                        <input type="hidden" name="fcustno" id="customerCodeHidden" value="{{ old('fcustno', $header->fcustno ?? '') }}">
                        <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r"
                            title="Browse Customer">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                    </div>
                @endif
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
            <table class="pr-detail-table min-w-full text-sm" id="tagihan-detail-table">
                <thead class="bg-gray-100">
                    <tr class="border-b">
                        <th class="p-2 text-left w-10">#</th>
                        <th class="p-2 text-left w-52">No.Nota</th>
                        <th class="p-2 text-left w-40">Tanggal Nota</th>
                        <th class="p-2 text-right w-36">Nilai Nota</th>
                        <th class="p-2 text-right w-36">Ongkos Kirim</th>
                        <th class="p-2 text-right w-36">Sisa Piutang</th>
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
                        <tr class="border-b align-middle bg-white" data-ref="{{ $row['frefsono'] }}">
                            <td class="p-2 text-gray-500 row-number">{{ $index + 1 }}</td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 font-mono text-sm bg-gray-100 text-gray-600" value="{{ $row['frefsono'] }}" readonly>
                                <input type="hidden" name="frefsono[{{ $index }}]" value="{{ $row['frefsono'] }}">
                                <input type="hidden" name="frefcode[{{ $index }}]" value="{{ $row['frefcode'] }}">
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 text-sm bg-gray-100 text-gray-600" value="{{ $row['fsodate'] }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" value="{{ number_format($row['famountbil'], 2, ',', '.') }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" value="{{ number_format($row['fongkos'], 2, ',', '.') }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" value="{{ number_format($row['famount'], 2, ',', '.') }}" readonly>
                                <input type="hidden" name="famount[{{ $index }}]" value="{{ $row['famount'] }}" class="row-amount">
                            </td>
                            @if (!$isReadOnly)
                                <td class="p-2 text-center">
                                    <button type="button" class="btn-remove-row inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200" title="Hapus baris">-</button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    @for ($i = 0; $i < $placeholderCount; $i++)
                        <tr class="border-b align-middle bg-white empty-row">
                            <td class="p-2 text-gray-500 row-number">{{ $actualCount + $i + 1 }}</td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 font-mono text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" readonly></td>
                            @if (!$isReadOnly)
                                <td class="p-2 text-center">
                                    <button type="button" class="btn-remove-row inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200" title="Hapus baris">-</button>
                                </td>
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        @if (!$isReadOnly)
            <div class="mb-4 flex gap-2">
                <button type="button" @click="openNotaModal()" class="px-4 py-2 bg-blue-600 text-white rounded">Add Retur</button>
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

        <div class="flex justify-end mb-4">
            <div class="border rounded p-3 w-72 bg-gray-50">
                <div class="flex justify-between font-semibold">
                    <span>Total Tagihan:</span>
                    <span id="total-tagihan-value">{{ number_format($header->famounttagihan ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-center gap-4">
            <a href="{{ route('lembarpenagihan.index') }}" class="px-4 py-2 bg-gray-100 rounded">Kembali</a>
            @if (!$isReadOnly)<button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>@endif
            @if ($isDelete)<button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Hapus</button>@endif
        </div>
        @if (!$isReadOnly)
            <x-transaction.browse-customer-modal />
        @endif
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
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
                const fsodate = item.fsodate || item.fdate || '';
                const famountbil = Number(item.famountbil ?? item.famountso ?? 0);
                const fongkos = Number(item.fongkos ?? item.fongkosangkut ?? 0);
                const famount = Number(item.famount ?? item.famountremain ?? item.famountso ?? 0);
                return { frefsono, frefcode, fsodate, famountbil, fongkos, famount };
            }

            function renderRow(item, index) {
                const normalized = normalizeItem(item);
                
                let aksiCol = '';
                if (!isReadOnly) {
                    aksiCol = `
                        <td class="p-2 text-center">
                            <button type="button" class="btn-remove-row inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200" title="Hapus baris">-</button>
                        </td>
                    `;
                }
                
                const tr = document.createElement('tr');
                tr.className = 'border-b align-middle bg-white';
                tr.setAttribute('data-ref', normalized.frefsono);
                tr.innerHTML = `
                    <td class="p-2 text-gray-500 row-number">${index + 1}</td>
                    <td class="p-2">
                        <input type="text" class="w-full border rounded px-2 py-1 font-mono text-sm bg-gray-100 text-gray-600" value="${normalized.frefsono}" readonly>
                        <input type="hidden" name="frefsono[${index}]" value="${normalized.frefsono}">
                        <input type="hidden" name="frefcode[${index}]" value="${normalized.frefcode}">
                    </td>
                    <td class="p-2">
                        <input type="text" class="w-full border rounded px-2 py-1 text-sm bg-gray-100 text-gray-600" value="${formatDate(normalized.fsodate)}" readonly>
                    </td>
                    <td class="p-2 text-right">
                        <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" value="${formatMoney(normalized.famountbil)}" readonly>
                    </td>
                    <td class="p-2 text-right">
                        <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" value="${formatMoney(normalized.fongkos)}" readonly>
                    </td>
                    <td class="p-2 text-right">
                        <input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" value="${formatMoney(normalized.famount)}" readonly>
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
                        emptyTr.className = 'border-b align-middle bg-white empty-row';
                        
                        let aksiCol = '';
                        if (!isReadOnly) {
                            aksiCol = `
                                <td class="p-2 text-center">
                                    <button type="button" class="btn-remove-row inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200" title="Hapus baris">-</button>
                                </td>
                            `;
                        }
                        
                        emptyTr.innerHTML = `
                            <td class="p-2 text-gray-500 row-number">${actualCount + i + 1}</td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 font-mono text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" readonly></td>
                            <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 text-right text-sm bg-gray-100 text-gray-600" readonly></td>
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
                    if (e.target && e.target.classList.contains('btn-remove-row')) {
                        e.preventDefault();
                        const tr = e.target.closest('tr');
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
