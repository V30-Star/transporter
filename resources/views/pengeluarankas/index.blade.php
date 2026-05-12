@extends('layouts.app')

@section('title', "Pengeluaran Kas")

@section('content')
        <div class="bg-white rounded shadow p-4">
        @php
            $availableYears = collect($records ?? [])
                ->map(function ($record) {
                    $date = $record->fkasmtdate ?? null;
                    if (!$date) {
                        return null;
                    }

                    try {
                        return \Carbon\Carbon::parse($date)->format('Y');
                    } catch (\Throwable $e) {
                        return null;
                    }
                })
                ->filter()
                ->unique()
                ->sortDesc()
                ->values();
        @endphp
        <div class="flex justify-end items-center mb-4">
            <div></div>
            <a href="{{ route('pengeluarankas.create') }}"
                class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" /> {{ "Tambah Baru" }}
            </a>
        </div>

        <table id="pengeluaranKasTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">{{ "Voucher No." }}</th>
                    <th class="border px-2 py-2">{{ "Tanggal" }}</th>
                    <th class="border px-2 py-2">{{ "No.Giro/Cek" }}</th>
                    <th class="border px-2 py-2">{{ "Account" }}</th>
                    <th class="border px-2 py-2">{{ "Keterangan" }}</th>
                    <th class="border px-2 py-2 text-right">{{ "Nilai Bayar" }}</th>
                    <th class="border px-2 py-2 no-sort">{{ "Aksi" }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr
                        data-filter-year="{{ \Carbon\Carbon::parse($record->fkasmtdate)->format('Y') }}"
                        data-filter-month="{{ (int) \Carbon\Carbon::parse($record->fkasmtdate)->format('n') }}">
                        <td class="border px-2 py-2">{{ $record->fkasmtno }}</td>
                        <td class="border px-2 py-2">
                            {{ optional($record->fkasmtdate)->format('d/m/Y') ?? \Carbon\Carbon::parse($record->fkasmtdate)->format('d/m/Y') }}
                        </td>
                        <td class="border px-2 py-2">{{ $record->fnogiro ?: '-' }}</td>
                        <td class="border px-2 py-2">{{ $record->account_summary }}</td>
                        <td class="border px-2 py-2">{{ $record->description_summary }}</td>
                        <td class="border px-2 py-2 text-right">
                            <div class="inline-flex items-center justify-end gap-2 w-full">
                                <span>{{ number_format((float) $record->payment_amount, 2, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="border px-2 py-2 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <a href="{{ route('pengeluarankas.view', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                    <x-heroicon-o-eye class="w-4 h-4 mr-1" /> {{ "View" }}
                                </a>
                                <a href="{{ route('pengeluarankas.edit', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> {{ "Edit" }}
                                </a>
                                <a href="{{ route('pengeluarankas.delete', $record->fkasmtno) }}"
                                    class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <x-heroicon-o-trash class="w-4 h-4 mr-1" /> {{ "Hapus" }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        #pengeluaranKasTable {
            width: 100% !important;
        }

        #pengeluaranKasTable th,
        #pengeluaranKasTable td {
            vertical-align: middle;
        }

        #pengeluaranKasTable th:last-child,
        #pengeluaranKasTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .dt-container .dt-search,
        .dt-container .dt-length {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .dt-container .dt-search .dt-input,
        .dataTables_wrapper .dt-search .dt-input {
            width: 28rem !important;
            min-width: 28rem !important;
            max-width: 100%;
        }

        #pengeluaranKasTable td.text-right .inline-flex {
            white-space: nowrap;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        $(function() {
            const filterState = {
                year: '',
                month: ''
            };

            const dateFilter = function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'pengeluaranKasTable') {
                    return true;
                }

                if (!filterState.year && !filterState.month) {
                    return true;
                }

                const rowNode = settings.aoData?.[dataIndex]?.nTr;
                if (!rowNode) {
                    return true;
                }

                const rowYear = String(rowNode.dataset.filterYear || '');
                const rowMonth = String(rowNode.dataset.filterMonth || '');

                if (filterState.year && rowYear !== filterState.year) {
                    return false;
                }

                if (filterState.month && rowMonth !== filterState.month) {
                    return false;
                }

                return true;
            };

            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(fn => fn !== dateFilter);
            $.fn.dataTable.ext.search.push(dateFilter);

            const table = $('#pengeluaranKasTable').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging',
                },
                columnDefs: [{
                        targets: 'no-sort',
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: 5,
                        className: 'text-right'
                    },
                    {
                        targets: [1, 3, 5],
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [0, 2, 4],
                        orderable: true,
                        searchable: true
                    }
                ],
                initComplete: function() {
                    const wrapper = document.querySelector('#pengeluaranKasTable_wrapper .dt-search');
                    if (!wrapper || wrapper.querySelector('[data-role="year-filter"]')) {
                        return;
                    }

                    const filterWrap = document.createElement('div');
                    filterWrap.className = 'flex items-center gap-3 flex-wrap';
                    filterWrap.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-700">Tahun</span>
                            <select data-role="year-filter" class="border rounded px-2 py-1 w-24">
                                <option value="">Semua</option>
                                @foreach ($availableYears as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-700">Bulan</span>
                            <select data-role="month-filter" class="border rounded px-2 py-1">
                                <option value="">Semua</option>
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                    `;

                    wrapper.appendChild(filterWrap);

                    const yearSelect = wrapper.querySelector('[data-role="year-filter"]');
                    const monthSelect = wrapper.querySelector('[data-role="month-filter"]');

                    yearSelect?.addEventListener('change', function() {
                        filterState.year = this.value;
                        table.draw();
                    });

                    monthSelect?.addEventListener('change', function() {
                        filterState.month = this.value;
                        table.draw();
                    });
                }
            });
        });
    </script>
@endpush
