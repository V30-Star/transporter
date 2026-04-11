@extends('layouts.app')

@section('title', 'Master Account')

@section('content')
    @php
        $canCreate = in_array('createAccount', explode(',', session('user_restricted_permissions', '')));
        $canEdit   = in_array('updateAccount', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteAccount', explode(',', session('user_restricted_permissions', '')));
        $showActionsColumn = $canEdit || $canDelete;
    @endphp

    <div class="bg-white rounded shadow p-4">

        {{-- Tombol Tambah Baru --}}
        <div class="flex items-center justify-between mb-4">
            <div></div>
            @if ($canCreate)
                <a href="{{ route('account.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Baru
                </a>
            @endif
        </div>

        {{-- Template Filter Status (hidden, akan di-clone ke toolbar DataTables) --}}
        <div id="statusFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="statusFilterWrap">
                <span class="text-sm text-gray-700">Status</span>
                <select data-role="status-filter" class="border rounded px-2 py-1">
                    <option value="all">All</option>
                    <option value="active" selected>Active</option>
                    <option value="nonactive">Non Active</option>
                </select>
            </div>
        </div>

        <!-- {{-- Template Filter Year (hidden, akan di-clone ke toolbar DataTables) --}}
        <div id="yearFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="yearFilterWrap">
                <span class="text-sm text-gray-700">Year</span>
                <select data-role="year-filter" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    @foreach ($availableYears as $yr)
                        <option value="{{ (int) $yr }}">{{ (int) $yr }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Template Filter Month (hidden, akan di-clone ke toolbar DataTables) --}}
        <div id="monthFilterTemplate" class="hidden">
            <div class="flex items-center gap-2" id="monthFilterWrap">
                <span class="text-sm text-gray-700">Month</span>
                <select data-role="month-filter" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>
        </div> -->

        {{-- Tabel --}}
        <table id="accountTable" class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-2">Account #</th>
                    <th class="border px-2 py-2">Nama Account</th>
                    <th class="border px-2 py-2 no-sort">Type</th>
                    <th class="border px-2 py-2 no-sort">Saldo Normal</th>
                    <th class="border px-2 py-2 no-sort">Initial Jurnal</th>
                    <th class="border px-2 py-2 no-sort">Sub Account</th>
                    <th class="border px-2 py-2 no-sort">Status</th>
                    {{-- Kolom hidden untuk filter client-side --}}
                    <th data-col="statusRaw" class="border px-2 py-2">StatusRaw</th>
                    <th data-col="yearRaw" class="border px-2 py-2">YearRaw</th>
                    <th data-col="monthRaw" class="border px-2 py-2">MonthRaw</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-2 col-aksi">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($accounts as $account)
                    @php
                        $isActive    = (string) $account->fnonactive === '0';
                        $createdYear  = $account->fcreatedat ? \Carbon\Carbon::parse($account->fcreatedat)->year  : '';
                        $createdMonth = $account->fcreatedat ? \Carbon\Carbon::parse($account->fcreatedat)->month : '';
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $account->fend == 0 ? 'font-bold' : '' }}">
                        <td class="border px-2 py-1">{{ $account->faccount }}</td>
                        <td class="border px-2 py-1">{{ $account->faccname }}</td>
                        <td class="border px-2 py-1">{{ $account->fend == 1 ? 'Detil' : 'Header' }}</td>
                        <td class="border px-2 py-1">
                            {{ $account->fnormal == 'D' ? 'Debit' : ($account->fnormal == 'K' ? 'Kredit' : '-') }}
                        </td>
                        <td class="border px-2 py-1">{{ $account->finitjurnal ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $account->fhavesubaccount == 1 ? 'Yes' : 'No' }}</td>

                        {{-- Tampilan badge Status --}}
                        <td class="border px-2 py-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                {{ $isActive ? 'bg-green-100 text-green-700' : 'bg-red-200 text-red-700' }}">
                                {{ $isActive ? 'Active' : 'Non Active' }}
                            </span>
                        </td>

                        {{-- Kolom hidden: nilai mentah untuk filter --}}
                        <td class="border px-2 py-1">{{ $account->fnonactive }}</td>
                        <td class="border px-2 py-1">{{ $createdYear }}</td>
                        <td class="border px-2 py-1">{{ $createdMonth }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2 text-center">
                                <a href="{{ route('account.view', $account->faccid) }}">
                                    <button class="inline-flex items-center bg-slate-500 text-white px-4 py-2 rounded hover:bg-slate-600">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> View
                                    </button>
                                </a>
                                @if ($canEdit)
                                    <a href="{{ route('account.edit', $account->faccid) }}">
                                        <button class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <a href="{{ route('account.delete', $account->faccid) }}"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
@endsection


@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        /* Tata letak kontrol */
        .dt-container .dt-length,
        .dt-container .dt-search {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .dt-container .dt-length .dt-input {
            width: 4.5rem;
            padding: .35rem .5rem;
        }

        /* Stabilkan tabel */
        #accountTable {
            width: 100% !important;
        }

        #accountTable th,
        #accountTable td {
            text-align: left !important;
            vertical-align: middle;
        }

        #accountTable th:last-child,
        #accountTable td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        #accountTable td:last-child {
            padding: .25rem .5rem;
        }

        .dataTables_wrapper .dt-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        #statusFilterWrap {
            margin-right: .25rem;
        }
    </style>
@endpush


@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>

    <script>
        // =============================================
        // jQuery — Inisialisasi DataTables
        // =============================================
        $(function () {

            const table = $('#accountTable').DataTable({
                autoWidth  : false,
                pageLength : 10,
                lengthMenu : [10, 25, 50, 100],
                order      : [[0, 'asc']],
                layout: {
                    topStart   : 'search',
                    topEnd     : 'pageLength',
                    bottomStart: 'info',
                    bottomEnd  : 'paging',
                },
                columnDefs: [
                    { targets: 'col-aksi', orderable: false, searchable: false, width: 120 },
                    { targets: 'no-sort',  orderable: false },
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries",
                },
            });

            // ------------------------------------------
            // 1. Cari index kolom-kolom hidden
            // ------------------------------------------
            const statusRawIdx = table.columns().indexes().toArray()
                .find(i => $(table.column(i).header()).attr('data-col') === 'statusRaw');

            const yearRawIdx = table.columns().indexes().toArray()
                .find(i => $(table.column(i).header()).attr('data-col') === 'yearRaw');

            const monthRawIdx = table.columns().indexes().toArray()
                .find(i => $(table.column(i).header()).attr('data-col') === 'monthRaw');

            // Sembunyikan kolom-kolom hidden dari tampilan
            if (statusRawIdx !== undefined) table.column(statusRawIdx).visible(false);
            if (yearRawIdx   !== undefined) table.column(yearRawIdx).visible(false);
            if (monthRawIdx  !== undefined) table.column(monthRawIdx).visible(false);

            // Default: tampilkan hanya Active (fnonactive = 0)
            if (statusRawIdx !== undefined) {
                table.column(statusRawIdx).search('^0$', true, false).draw();
            }

            // ------------------------------------------
            // 2. Clone semua template filter ke toolbar Search
            // ------------------------------------------
            const $container     = $(table.table().container());
            const $toolbarSearch = $container.find('.dt-search');

            // Filter Status
            const $statusFilter = $('#statusFilterTemplate #statusFilterWrap').clone(true, true);
            const $statusSelect = $statusFilter.find('select[data-role="status-filter"]');
            $statusSelect.attr('id', 'statusFilterDT');
            $toolbarSearch.append($statusFilter);

            // Filter Year
            const $yearFilter = $('#yearFilterTemplate #yearFilterWrap').clone(true, true);
            const $yearSelect = $yearFilter.find('select[data-role="year-filter"]');
            $yearSelect.attr('id', 'yearFilterDT');
            $toolbarSearch.append($yearFilter);

            // Filter Month
            const $monthFilter = $('#monthFilterTemplate #monthFilterWrap').clone(true, true);
            const $monthSelect = $monthFilter.find('select[data-role="month-filter"]');
            $monthSelect.attr('id', 'monthFilterDT');
            $toolbarSearch.append($monthFilter);

            // ------------------------------------------
            // 3. Event Handler — Filter Status
            // ------------------------------------------
            $statusSelect.on('change', function () {
                const val = this.value;
                if (val === 'active') {
                    table.column(statusRawIdx).search('^0$', true, false).draw();
                } else if (val === 'nonactive') {
                    table.column(statusRawIdx).search('^1$', true, false).draw();
                } else {
                    table.column(statusRawIdx).search('', true, false).draw();
                }
            });

            // ------------------------------------------
            // 4. Event Handler — Filter Year & Month
            //    Keduanya pakai exact match (bukan regex)
            // ------------------------------------------
            function applyDateFilters() {
                const year  = $yearSelect.val();
                const month = $monthSelect.val();

                table.column(yearRawIdx).search(year   ? '^' + year  + '$' : '', true, false);
                table.column(monthRawIdx).search(month ? '^' + month + '$' : '', true, false);
                table.draw();
            }

            $yearSelect.on('change', applyDateFilters);
            $monthSelect.on('change', applyDateFilters);

            // ------------------------------------------
            // 5. Paksa input Search jadi UPPERCASE
            // ------------------------------------------
            $container.find('.dt-search input').css({
                'width'          : '400px',
                'max-width'      : '100%',
                'text-transform' : 'uppercase',
            });

            $container.on('input', '.dt-search input', function () {
                const start = this.selectionStart;
                const end   = this.selectionEnd;
                this.value  = this.value.toUpperCase();
                this.setSelectionRange(start, end); // jaga posisi kursor
                table.search(this.value).draw();
            });

        });
    </script>
@endpush