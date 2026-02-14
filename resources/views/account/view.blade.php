@extends('layouts.app')

@section('title', 'View Account')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">

        {{-- ============================================ --}}
        {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
        {{-- ============================================ --}}
        <div class="space-y-4">
            <div class="lg:col-span-4">
                <label class="block text-sm font-medium mb-1" style="font-weight: bold;">Account Header</label>
                <div class="flex">
                    <div class="relative flex-1">
                        <select id="accountSelect" class="bg-gray-100 w-full border rounded-l px-3 py-2" disabled>
                            <option value=""></option>
                            @foreach ($headers as $header)
                                <option disabled value="{{ $header->faccount }}" data-faccid="{{ $header->faccid }}"
                                    data-branch="{{ $header->faccount }}"
                                    {{ old('faccupline', $account->faccupline) == $header->faccount ? 'selected' : '' }}>
                                    {{ $header->faccount }} - {{ $header->faccname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="faccupline" id="accountCodeHidden"
                        value="{{ old('faccupline', $account->faccupline) }}">
                    <input type="hidden" name="faccid" id="accountIdHidden" value="{{ old('faccid', $account->faccid) }}">
                </div>

                @error('faccupline')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" style="font-weight: bold;">Kode Account</label>
                <input type="text" value="{{ $account->faccount }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" style="font-weight: bold;">Nama Account</label>
                <input type="text" value="{{ $account->faccname }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" style="font-weight: bold;">Saldo Normal</label>
                <input type="text" value="{{ $account->fnormal == 'D' ? 'Debit' : 'Kredit' }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" style="font-weight: bold;">Account Type</label>
                <input type="text" value="{{ $account->fend == '1' ? 'Detil' : 'Header' }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
            </div>

            <div class="flex justify-center mt-4">
                <label class="flex items-center justify-between w-40 p-3 border rounded-lg bg-gray-100">
                    <span class="text-sm font-medium">Non Active</span>
                    <input type="checkbox" class="h-5 w-5 text-green-600 rounded"
                        {{ $account->fnonactive == '1' ? 'checked' : '' }} disabled>
                </label>
            </div>
        </div>

        <div class="mt-6 flex justify-center space-x-4">
            <button type="button" onclick="window.location.href='{{ route('account.index') }}'"
                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                Kembali
            </button>
        </div>
        {{-- FOOTER INFO --}}
        <br>
        <hr><br>
        @php
            $lastUpdate = $account->fupdatedat ?: $account->fcreatedat;
        @endphp
        <span class="text-sm text-gray-600 flex justify-between items-center">
            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
            <span>{{ \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
        </span>
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
                                width: '500px',
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

                const inputInit = document.getElementsByName('finitjurnal')[0];
                inputInit.placeholder = "Cek Initial jika ini Header khusus...";

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
