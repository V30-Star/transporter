@extends('layouts.app')

@section('title', 'Master Account')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true, selected: 'rekening', subAccount: false }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
            <form action="{{ route('account.store') }}" method="POST">
                @csrf

                {{-- Account Header (Browse) --}}

                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Account</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="accountSelect" class="w-full border rounded-l px-3 py-2" disabled>
                                <option value=""></option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->faccount }}" data-faccid="{{ $account->faccid }}"
                                        data-branch="{{ $account->faccount }}"
                                        {{ old('faccupline') == $account->faccount ? 'selected' : '' }}>
                                        {{ $account->faccount }} - {{ $account->faccname }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="absolute inset-0" role="button" aria-label="Browse account"
                                @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"></div>
                        </div>

                        <input type="hidden" name="faccupline" id="accountCodeHidden" value="{{ old('faccupline') }}">
                        <input type="hidden" name="faccid" id="accountIdHidden" value="{{ old('faccid') }}">

                        <button type="button" @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none" title="Browse Account">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>

                        <a href="{{ route('account.create') }}" target="_blank" rel="noopener"
                            class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Account">
                            <x-heroicon-o-plus class="w-5 h-5" />
                        </a>
                    </div>

                    @error('faccupline')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Account #</label>
                    <input type="text" name="faccount" id="faccount" value="{{ old('faccount') }}"
                        class="w-full border rounded px-3 py-2 uppercase @error('faccount') border-red-500 @enderror"
                        maxlength="10" pattern="^\d+(-\d+)*$"
                        title="Format harus berupa angka dengan tanda hubung (misal: 1-123)"
                        placeholder="Ketik untuk mencari..." autofocus>

                    <!-- Hint untuk menampilkan nama account -->
                    <p id="faccount-hint" class="hint-text"></p>

                    @error('faccount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Name dengan Hint -->
                <div class="mt-4">
                    <label class="block text-sm font-medium">Account Name</label>
                    <input type="text" name="faccname" id="faccname" value="{{ old('faccname') }}"
                        class="w-full border rounded px-3 py-2 uppercase @error('faccname') border-red-500 @enderror"
                        maxlength="50" placeholder="Ketik untuk mencari...">

                    <!-- Hint untuk menampilkan kode account -->
                    <p id="faccname-hint" class="hint-text"></p>

                    @error('faccname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="fnormal" class="block text-sm font-medium">Saldo Normal</label>
                    <select name="fnormal" id="fnormal" class="w-full border rounded px-3 py-2">
                        <option value="D" {{ old('fnormal') == 'D' ? 'selected' : '' }}>Debit</option>
                        <option value="K" {{ old('fnormal') == 'K' ? 'selected' : '' }}>Kredit</option>
                    </select>
                </div>

                <div class="mt-4">
                    <label for="fend" class="block text-sm font-medium">Account Type</label>
                    <select name="fend" id="fend" class="w-full border rounded px-3 py-2">
                        <option value="1" {{ old('fend') == '1' ? 'selected' : '' }}>Detil</option>
                        <option value="0" {{ old('fend') == '0' ? 'selected' : '' }}>Header</option>
                    </select>
                </div>

                <div x-data="{ subAccount: {{ old('fhavesubaccount', 0) ? 'true' : 'false' }} }">
                    <div class="mt-4">
                        <label for="fhavesubaccount" class="flex items-center space-x-2">
                            <input type="checkbox" name="fhavesubaccount" id="fhavesubaccount" value="1"
                                x-model="subAccount" {{ old('fhavesubaccount') ? 'checked' : '' }}>
                            <span class="text-sm">Ada Sub Account?</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label for="ftypesubaccount" class="block text-sm font-medium">Type</label>
                        <select name="ftypesubaccount" id="ftypesubaccount" class="w-full border rounded px-3 py-2"
                            :disabled="!subAccount" :class="!subAccount ? 'bg-gray-200' : ''">
                            <option value="Sub Account" {{ old('ftypesubaccount') == 'Sub Account' ? 'selected' : '' }}>
                                Sub Account
                            </option>
                            <option value="Customer" {{ old('ftypesubaccount') == 'Customer' ? 'selected' : '' }}>
                                Customer
                            </option>
                            <option value="Supplier" {{ old('ftypesubaccount') == 'Supplier' ? 'selected' : '' }}>
                                Supplier
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Initial Jurnal#</label>
                    <input type="text" name="finitjurnal" value="{{ old('finitjurnal') }}"
                        class="w-full border rounded px-3 py-2 @error('finitjurnal') border-red-500 @enderror"
                        maxlength="2">
                    @error('finitjurnal')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-red-600 text-sm mt-1">** Khusus Jurnal Kas/Bank</p>
                </div>

                <div class="mt-4">
                    <label for="fuserlevel" class="block text-sm font-medium">User Level</label>
                    <select name="fuserlevel" id="fuserlevel" class="w-full border rounded px-3 py-2">
                        <option value="1" {{ old('fuserlevel') == '1' ? 'selected' : '' }}>User</option>
                        <option value="2" {{ old('fuserlevel') == '2' ? 'selected' : '' }}>
                            Supervisor
                        </option>
                        <option value="3" {{ old('fuserlevel') == '3' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                    </label>
                </div>
                <input type="hidden" name="fcurrency" value='IDR'>
                <input type="hidden" name="fnonactive" value='0'>
                <br>

                {{-- MODAL ACCOUNT dengan DataTables --}}
                <div x-data="accountBrowser()" x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
                        style="height: 650px;">

                        <div
                            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Browse Account</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Pilih account yang diinginkan</p>
                            </div>
                            <button type="button" @click="close()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                Tutup
                            </button>
                        </div>

                        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                        </div>

                        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                            <div class="bg-white">
                                <table id="accountTable" class="min-w-full text-sm display nowrap stripe hover"
                                    style="width:100%">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Account Kode</th>
                                            <th
                                                class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Account Nama</th>
                                            <th
                                                class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                                Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-center space-x-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <button type="button" @click="window.location.href='{{ route('account.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
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
                                // Mengirim parameter standar DataTables untuk server-side processing
                                return {
                                    draw: d.draw,
                                    start: d.start,
                                    length: d.length,
                                    search: d.search.value,
                                    // Menambahkan parameter order untuk sorting (diperlukan serverSide)
                                    order_column: d.columns[d.order[0].column].data,
                                    order_dir: d.order[0].dir
                                };
                            },
                            dataSrc: function(json) {
                                // Asumsi backend mengembalikan data di properti 'data' (seperti Laravel DataTables)
                                return json.data;
                            }
                        },
                        columns: [{
                                data: 'faccount',
                                name: 'faccount',
                                className: 'font-mono text-sm',
                                width: '30%'
                            },
                            {
                                data: 'faccname',
                                name: 'faccname',
                                className: 'text-sm',
                                width: '55%'
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                width: '15%',
                                render: function(data, type, row) {
                                    // Menggunakan styling yang mirip dengan button 'Pilih' di Supplier
                                    return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                                }
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        // Menggunakan DOM custom untuk kontrol DataTables (sama seperti Supplier)
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
                            [1, 'asc'] // Default order by Account Name
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const api = this.api();
                            const $container = $(api.table().container());

                            // Style search input (disamakan dengan Supplier)
                            $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();

                            // Style length select (disamakan dengan Supplier)
                            $container.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
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
                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                },

                close() {
                    this.open = false;
                    if (this.table) {
                        // Bersihkan pencarian saat ditutup (sama seperti Supplier)
                        this.table.search('').draw();
                    }
                },

                choose(w) {
                    // Dispatches event (tetap)
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
                    window.addEventListener('account-browse-open', () => this.openModal(), {
                        passive: true
                    });
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
@endpush
