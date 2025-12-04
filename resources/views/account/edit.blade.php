@extends('layouts.app')

@section('title', 'Master Account')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
        <form action="{{ route('account.update', $account->faccid) }}" method="POST">
            @csrf
            @method('PATCH')

            {{-- Account Header (Browse) --}}
            {{-- Bungkus semua dengan x-data --}}
            <div x-data="accHeaderBrowser()">

                {{-- Field Account Header --}}
                <div class="mt-4 lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Account Header</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="accHeaderSelect" name="faccupline_view"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($headers as $header)
                                    <option value="{{ $header->faccid }}"
                                        {{ old('faccupline', $account->faccupline) == $header->faccid ? 'selected' : '' }}>
                                        {{ $header->faccount }} - {{ $header->faccname }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="hidden" name="faccupline" id="accHeaderHidden"
                                value="{{ old('faccupline', $account->faccupline) }}">

                            {{-- overlay klik --}}
                            <div class="absolute inset-0" role="button" aria-label="Browse Account Header"
                                @click="openBrowse()"></div>
                        </div>

                        {{-- tombol browse --}}
                        <button type="button" @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none" title="Browse Account">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- MODAL ACCOUNT dengan DataTables --}}
                <div x-data="accountBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/40" @click="close()"></div>

                    <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                        <div class="p-4 border-b flex items-center gap-3">
                            <h3 class="text-lg font-semibold">Browse Account</h3>
                            <button type="button" @click="close()"
                                class="ml-auto px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                                Close
                            </button>
                        </div>

                        <div class="p-4 overflow-auto flex-1">
                            <table id="accountTable" class="min-w-full text-sm display nowrap" style="width:100%">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="text-left p-2">Account (Kode - Nama)</th>
                                        <th class="text-center p-2">Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kode Account --}}
            <div class="mt-4">
                <label class="block text-sm font-medium">Kode Account</label>
                <input type="text" name="faccount" id="faccount" value="{{ old('faccount', $account->faccount) }}"
                    class="w-full border rounded px-3 py-2 uppercase @error('faccount') border-red-500 @enderror"
                    maxlength="10" pattern="^\d+(-\d+)*$" title="Format harus angka & boleh pakai '-' (mis: 1-123)"
                    placeholder="Ketik untuk mencari..." autofocus>

                <p id="faccount-hint" class="hint-text"></p>

                @error('faccount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nama Account --}}
            <div class="mt-4">
                <label class="block text-sm font-medium">Nama Account</label>
                <input type="text" name="faccname" id="faccname" value="{{ old('faccname', $account->faccname) }}"
                    class="w-full border rounded px-3 py-2 uppercase @error('faccname') border-red-500 @enderror"
                    maxlength="50" placeholder="Ketik untuk mencari...">

                <p id="faccname-hint" class="hint-text"></p>

                @error('faccname')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Saldo Normal --}}
            <div class="mt-4">
                <label for="fnormal" class="block text-sm font-medium">Saldo Normal</label>
                <select name="fnormal" id="fnormal" class="w-full border rounded px-3 py-2">
                    <option value="D" {{ old('fnormal', $account->fnormal) == 'D' ? 'selected' : '' }}>Debit</option>
                    <option value="K" {{ old('fnormal', $account->fnormal) == 'K' ? 'selected' : '' }}>Kredit
                    </option>
                </select>
            </div>

            {{-- Account Type (per UI kamu saat ini: 1=Detil, 2=Header) --}}
            <div class="mt-4">
                <label for="fend" class="block text-sm font-medium">Account Type</label>
                <select name="fend" id="fend" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fend', $account->fend) == '1' ? 'selected' : '' }}>Detil</option>
                    <option value="0" {{ old('fend', $account->fend) == '0' ? 'selected' : '' }}>Header</option>
                </select>
            </div>

            {{-- Sub Account --}}
            <div class="mt-4" x-data="{ subAccount: {{ old('fhavesubaccount', $account->fhavesubaccount ?? 0) ? 'true' : 'false' }} }">
                <label for="fhavesubaccount" class="flex items-center space-x-2">
                    <input type="checkbox" name="fhavesubaccount" id="fhavesubaccount" value="1" x-model="subAccount">
                    <span class="text-sm">Ada Sub Account?</span>
                </label>

                <div class="mt-3">
                    <label for="ftypesubaccount" class="block text-sm font-medium">Type</label>
                    <select name="ftypesubaccount" id="ftypesubaccount" class="w-full border rounded px-3 py-2"
                        :disabled="!subAccount" :class="!subAccount ? 'bg-gray-200' : ''">
                        <option value="Sub Account"
                            {{ old('ftypesubaccount', ($account->ftypesubaccount ?? '') === 'S' ? 'Sub Account' : '') == 'Sub Account' ? 'selected' : '' }}>
                            Sub Account</option>
                        <option value="Customer"
                            {{ old('ftypesubaccount', ($account->ftypesubaccount ?? '') === 'C' ? 'Customer' : '') == 'Customer' ? 'selected' : '' }}>
                            Customer</option>
                        <option value="Supplier"
                            {{ old('ftypesubaccount', ($account->ftypesubaccount ?? '') === 'P' ? 'Supplier' : '') == 'Supplier' ? 'selected' : '' }}>
                            Supplier</option>
                    </select>
                </div>
            </div>

            {{-- Initial Jurnal --}}
            <div class="mt-4">
                <label class="block text-sm font-medium">Initial Jurnal#</label>
                <input type="text" name="finitjurnal" value="{{ old('finitjurnal', $account->finitjurnal) }}"
                    class="w-full border rounded px-3 py-2 @error('finitjurnal') border-red-500 @enderror" maxlength="2">
                @error('finitjurnal')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-red-600 text-sm mt-1">** Khusus Jurnal Kas/Bank</p>
            </div>

            {{-- User Level --}}
            <div class="mt-4">
                <label for="fuserlevel" class="block text-sm font-medium">User Level</label>
                <select name="fuserlevel" id="fuserlevel" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fuserlevel', $account->fuserlevel) == '1' ? 'selected' : '' }}>User
                    </option>
                    <option value="2" {{ old('fuserlevel', $account->fuserlevel) == '2' ? 'selected' : '' }}>
                        Supervisor</option>
                    <option value="3" {{ old('fuserlevel', $account->fuserlevel) == '3' ? 'selected' : '' }}>Admin
                    </option>
                </select>
            </div>

            {{-- Non Aktif --}}
            <div class="flex justify-center mt-4">
                <label for="statusToggle"
                    class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                    <span class="text-sm font-medium">Non Aktif</span>
                    <input type="checkbox" name="fnonactive" id="statusToggle"
                        class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                        {{ old('fnonactive', $account->fnonactive) == '1' ? 'checked' : '' }}>
                </label>
            </div>

            {{-- Tombol --}}
            <div class="mt-6 flex justify-center space-x-4">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                </button>
                <button type="button" onclick="window.location.href='{{ route('account.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                </button>
            </div>

            {{-- Footer info --}}
            @php
                $lastUpdate = $account->fupdatedat ?: $account->fcreatedat;
                $isUpdated = !empty($account->fupdatedat);
            @endphp

            <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>

                <span class="ml-2 text-right">
                    {{ \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}
                </span>
            </span>
        </form>
    </div>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    @endpush
    <style>
        .ui-autocomplete {
            background-color: white !important;
            z-index: 1050 !important;

            /* === Perubahan Utama: Tambahkan Max-Width === */
            /* Sesuaikan nilai '300px' ini sesuai kebutuhan Anda */
            max-width: 700px !important;
            /* ------------------------------------------- */

            /* Styling tambahan (sama seperti sebelumnya) */
            border: 1px solid #d1d5db !important;
            border-radius: 0.25rem !important;
            padding: 0 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }

        .ui-menu-item-wrapper {
            padding: 0.5rem 0.75rem !important;
        }

        .ui-menu-item-wrapper.ui-state-active,
        .ui-menu-item-wrapper:hover {
            background-color: #f3f4f6 !important;
            color: #1f2937 !important;
        }

        .hint-text {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
            font-style: italic;
        }

        /* Custom styling untuk autocomplete dropdown */
        .ui-autocomplete {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999 !important;
        }

        .ui-menu-item {
            padding: 8px 12px;
            font-size: 0.875rem;
        }

        .ui-state-active {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: white !important;
        }

        /* Lebarkan dropdown tampilkan data */
        #accountTable_wrapper .dt-length select,
        #accountTable_wrapper .dataTables_length select {
            min-width: 80px !important;
            width: auto !important;
            padding-right: 30px !important;
        }

        /* Pastikan wrapper length cukup lebar */
        #accountTable_wrapper .dt-length,
        #accountTable_wrapper .dataTables_length {
            min-width: 180px;
            white-space: nowrap;
        }

        /* Styling untuk select agar lebih rapi */
        #accountTable_wrapper .dt-length select,
        #accountTable_wrapper .dataTables_length select {
            padding: 6px 30px 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-position: right 8px center;
            background-size: 16px;
        }
    </style>

    @push('scripts')
        <!-- Load jQuery & jQuery UI -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script>
            window.accountBrowser = function() {
                return {
                    open: false,
                    table: null,

                    initDataTable() {
                        if (this.table) {
                            this.table.destroy();
                        }

                        this.table = $('#accountTable').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: "{{ route('account.browse') }}",
                                type: 'GET',
                                data: function(d) {
                                    return {
                                        draw: d.draw,
                                        page: (d.start / d.length) + 1,
                                        per_page: d.length,
                                        search: d.search.value
                                    };
                                },
                                dataSrc: function(json) {
                                    return json.data;
                                }
                            },
                            columns: [{
                                    data: null,
                                    name: 'faccount',
                                    render: function(data, type, row) {
                                        return `${row.faccount} - ${row.faccname}`;
                                    }
                                },
                                {
                                    data: null,
                                    orderable: false,
                                    searchable: false,
                                    className: 'text-center',
                                    render: function(data, type, row) {
                                        return '<button type="button" class="btn-choose px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>';
                                    }
                                }
                            ],
                            pageLength: 10,
                            lengthMenu: [
                                [10, 25, 50, 100],
                                [10, 25, 50, 100]
                            ],
                            // Gunakan dom instead of layout
                            dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                            // Atau gunakan dom yang lebih sederhana:
                            // dom: 'fltip',
                            language: {
                                processing: "Memuat...",
                                search: "Cari:",
                                lengthMenu: "Tampilkan _MENU_ data",
                                info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                                infoEmpty: "Menampilkan 0 data",
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
                                [0, 'asc']
                            ],
                            autoWidth: false,
                            initComplete: function() {
                                const api = this.api();
                                const $container = $(api.table().container());

                                // Lebarkan search input
                                $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                    width: '400px',
                                    maxWidth: '100%',
                                    minWidth: '300px'
                                });

                                // Opsional: lebarkan wrapper search juga
                                $container.find('.dt-search, .dataTables_filter').css({
                                    minWidth: '420px'
                                });

                                $container.find('.dt-search .dt-input, .dataTables_filter input').focus();
                            }
                        });

                        // Handle button click
                        $('#accountTable').on('click', '.btn-choose', (e) => {
                            const data = this.table.row($(e.target).closest('tr')).data();
                            this.choose(data);
                        });
                    },

                    openModal() {
                        this.open = true;
                        // Initialize DataTable setelah modal terbuka
                        this.$nextTick(() => {
                            this.initDataTable();
                        });
                    },

                    close() {
                        this.open = false;
                        if (this.table) {
                            this.table.search('').draw();
                        }
                    },

                    choose(w) {
                        window.dispatchEvent(new CustomEvent('account-picked', {
                            detail: {
                                faccid: w.faccid,
                                faccount: w.faccount,
                                faccname: w.faccname,
                            }
                        }));
                        this.close();
                    },

                    init() {
                        window.addEventListener('account-browse-open', () => this.openModal());
                    }
                }
            };

            // Helper: update field saat account-picked
            document.addEventListener('DOMContentLoaded', () => {
                window.addEventListener('account-picked', (ev) => {
                    let {
                        faccount,
                        faccid
                    } = ev.detail || {};

                    // Fallback untuk mencari faccid dari option jika tidak ada
                    if (!faccid && faccount) {
                        const sel = document.getElementById('accountSelect');
                        if (sel) {
                            const option = sel.querySelector(`option[value="${faccount}"]`);
                            if (option) {
                                faccid = option.getAttribute('data-faccid');
                            }
                        }
                    }

                    const sel = document.getElementById('accountSelect');
                    const hidId = document.getElementById('accountIdHidden');
                    const hidCode = document.getElementById('accountCodeHidden');

                    if (sel) {
                        sel.value = faccount || '';
                        sel.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }

                    if (hidId) {
                        hidId.value = faccid || '';
                    }

                    if (hidCode) {
                        hidCode.value = faccount || '';
                    }
                });
            });
        </script>
        <script>
            $(document).ready(function() {

                // ========================================
                // Autocomplete untuk ACCOUNT # (Kode)
                // ========================================
                $('#faccount').autocomplete({
                        source: function(request, response) {
                            $.ajax({
                                url: "{{ route('account.suggest') }}",
                                dataType: "json",
                                data: {
                                    term: request.term,
                                    field: 'faccount' // Cari berdasarkan kode
                                },
                                success: function(data) {
                                    response(data);
                                }
                            });
                        },
                        minLength: 1, // Minimal 1 karakter
                        select: function(event, ui) {
                            // Isi Account # dengan kode
                            $('#faccount').val(ui.item.code);

                            // Isi Account Name dengan nama
                            $('#faccname').val(ui.item.name);

                            // Tampilkan hint
                            $('#faccount-hint').text('Account Name: ' + ui.item.name);
                            $('#faccname-hint').text('Account #: ' + ui.item.code);

                            return false;
                        },
                        focus: function(event, ui) {
                            // Preview saat hover
                            $('#faccount').val(ui.item.code);
                            return false;
                        }
                    })
                    .on('input', function() {
                        // Clear hint saat user mengetik manual
                        const currentVal = $(this).val();
                        if (!currentVal) {
                            $('#faccount-hint').text('');
                            $('#faccname-hint').text('');
                        }
                    });

                // ========================================
                // Autocomplete untuk ACCOUNT NAME (Nama)
                // ========================================
                $('#faccname').autocomplete({
                        source: function(request, response) {
                            $.ajax({
                                url: "{{ route('account.suggest') }}",
                                dataType: "json",
                                data: {
                                    term: request.term,
                                    field: 'faccname' // Cari berdasarkan nama
                                },
                                success: function(data) {
                                    response(data);
                                }
                            });
                        },
                        minLength: 2, // Minimal 2 karakter untuk nama
                        select: function(event, ui) {
                            // Isi Account Name dengan nama
                            $('#faccname').val(ui.item.name);

                            // Isi Account # dengan kode
                            $('#faccount').val(ui.item.code);

                            // Tampilkan hint
                            $('#faccname-hint').text('Account #: ' + ui.item.code);
                            $('#faccount-hint').text('Account Name: ' + ui.item.name);

                            return false;
                        },
                        focus: function(event, ui) {
                            // Preview saat hover
                            $('#faccname').val(ui.item.name);
                            return false;
                        }
                    })
                    .on('input', function() {
                        // Clear hint saat user mengetik manual
                        const currentVal = $(this).val();
                        if (!currentVal) {
                            $('#faccount-hint').text('');
                            $('#faccname-hint').text('');
                        }
                    });

                // ========================================
                // Clear hint saat form di-reset
                // ========================================
                $('form').on('reset', function() {
                    $('#faccount-hint').text('');
                    $('#faccname-hint').text('');
                });
            });
        </script>
    @endpush
@endsection
