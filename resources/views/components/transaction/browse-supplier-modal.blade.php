@props([
    'tableId' => 'supplierBrowseTable',
    'controlsId' => 'supplierTableControls',
    'paginationId' => 'supplierTablePagination',
    'routeName' => 'suppliers.browse',
    'eventName' => 'supplier-browse-open',
    'openDelay' => 0,
    'destroyOnClose' => false,
])

<style>
    #{{ $tableId }}_wrapper .dt-layout-row,
    #{{ $tableId }}_wrapper .dataTables_wrapper .row {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 16px !important;
        flex-wrap: nowrap !important;
        width: 100% !important;
    }

    #{{ $tableId }}_wrapper .dt-layout-cell,
    #{{ $tableId }}_wrapper .dataTables_filter,
    #{{ $tableId }}_wrapper .dataTables_length,
    #{{ $tableId }}_wrapper .dataTables_info,
    #{{ $tableId }}_wrapper .dataTables_paginate,
    #{{ $tableId }}_wrapper .dt-search,
    #{{ $tableId }}_wrapper .dt-length,
    #{{ $tableId }}_wrapper .dt-info,
    #{{ $tableId }}_wrapper .dt-paging {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        white-space: nowrap !important;
        flex-wrap: nowrap !important;
        width: auto !important;
        margin: 0 !important;
    }

    #{{ $tableId }}_wrapper .dataTables_filter,
    #{{ $tableId }}_wrapper .dt-search {
        flex: 1 1 auto !important;
        justify-content: flex-start !important;
    }

    #{{ $tableId }}_wrapper .dataTables_length,
    #{{ $tableId }}_wrapper .dt-length {
        margin-left: auto !important;
        flex: 0 0 auto !important;
        justify-content: flex-end !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate,
    #{{ $tableId }}_wrapper .dt-paging,
    #{{ $paginationId }} .dataTables_paginate,
    #{{ $paginationId }} .dt-paging {
        gap: 6px !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button,
    #{{ $paginationId }} .dataTables_paginate .paginate_button,
    #{{ $paginationId }} .dt-paging .dt-paging-button {
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

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button.current,
    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button.current:hover,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button.current,
    #{{ $paginationId }} .dataTables_paginate .paginate_button.current,
    #{{ $paginationId }} .dataTables_paginate .paginate_button.current:hover,
    #{{ $paginationId }} .dt-paging .dt-paging-button.current {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button:hover,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button:hover,
    #{{ $paginationId }} .dataTables_paginate .paginate_button:hover,
    #{{ $paginationId }} .dt-paging .dt-paging-button:hover {
        background: #eff6ff !important;
        border-color: #93c5fd !important;
        color: #1d4ed8 !important;
    }
</style>

<script>
    function supplierBrowser() {
        const dataTableLanguage = {
            processing: @json("Memuat data..."),
            search: @json("Search" . ':'),
            lengthMenu: @json("Tampilkan _MENU_"),
            info: @json("Menampilkan _START_ - _END_ dari _TOTAL_ data"),
            infoEmpty: @json("Tidak ada data"),
            infoFiltered: @json("(disaring dari _MAX_ total data)"),
            zeroRecords: @json("Tidak ada data yang ditemukan"),
            emptyTable: @json("Tidak ada data tersedia"),
            paginate: {
                first: @json("Pertama"),
                last: @json("Terakhir"),
                next: @json("Selanjutnya"),
                previous: @json("Sebelumnya")
            }
        };

        return {
            open: false,
            dataTable: null,

            focusSearch() {
                const focus = (attempt = 0) => {
                    const input = document.querySelector('#{{ $controlsId }} .dt-search .dt-input, #{{ $controlsId }} .dataTables_filter input, #{{ $tableId }}_wrapper .dt-search .dt-input, #{{ $tableId }}_wrapper .dataTables_filter input');
                    if (!input && attempt < 10) {
                        setTimeout(() => focus(attempt + 1), 100);
                        return;
                    }

                    input?.focus();
                    input?.select?.();
                };

                focus();
            },

            initDataTable() {
                if (this.dataTable) {
                    this.dataTable.destroy();
                    this.dataTable = null;
                }

                $('#{{ $tableId }}').off('click.suppick');
                this.dataTable = $('#{{ $tableId }}').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route($routeName) }}",
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
                            className: 'text-sm align-top',
                            defaultContent: '-',
                            orderable: false,
                            width: '30%',
                            render: function(data) {
                                const value = (data || '-').toString();
                                return `<div class="supplier-address-cell whitespace-normal break-words leading-5 min-w-0 max-w-[28rem]">${value}</div>`;
                            }
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
                            render: function() {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">' + @json("Pilih") + '</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"supplier-browser-top"fl>rt<"supplier-browser-bottom"ip>',
                    language: dataTableLanguage,
                    order: [
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    scrollX: false,
                    scrollCollapse: true,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });

                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });

                        $container.find('.dataTables_scroll').css({
                            width: '100%'
                        });

                        $container.find('.dataTables_scrollBody').css({
                            overflowX: 'auto',
                            overflowY: 'auto'
                        });

                        const $filter = $container.find('.dataTables_filter, .dt-search');
                        const $length = $container.find('.dataTables_length, .dt-length');
                        const controls = document.getElementById(@js($controlsId));
                        if (controls) {
                            controls.innerHTML = '';
                            controls.className = 'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 w-full';
                            controls.setAttribute('style', 'display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:16px !important; width:100% !important;');
                            if ($filter.length) $filter.addClass('order-1 shrink-0 whitespace-nowrap').appendTo(controls);
                            if ($length.length) $length.addClass('order-2 shrink-0 whitespace-nowrap').appendTo(controls);
                            $filter.css({ margin: 0, flex: '1 1 auto' });
                            $length.css({ margin: 0, flex: '0 0 auto' });
                            $container.find('.dataTables_filter label, .dt-search label').css({
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.5rem',
                                margin: 0,
                                width: '100%'
                            });
                            $container.find('.dataTables_length label, .dt-length label').css({
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.5rem',
                                margin: 0,
                                whiteSpace: 'nowrap'
                            });
                        }

                        const $info = $container.find('.dataTables_info, .dt-info');
                        const $paginate = $container.find('.dataTables_paginate, .dt-paging');
                        const pagination = document.getElementById(@js($paginationId));
                        if (pagination) {
                            pagination.innerHTML = '';
                            pagination.className = 'flex items-center justify-between gap-4 flex-nowrap';
                            pagination.setAttribute('style', 'display:flex !important; align-items:center !important; justify-content:space-between !important; gap:16px !important; flex-wrap:nowrap !important; width:100% !important;');
                            if ($info.length) $info.addClass('order-1 shrink-0 whitespace-nowrap').appendTo(pagination);
                            if ($paginate.length) $paginate.addClass('order-2 ml-auto shrink-0 whitespace-nowrap').appendTo(pagination);
                        }

                        setTimeout(() => {
                            const input = document.querySelector('#{{ $controlsId }} .dt-search .dt-input, #{{ $controlsId }} .dataTables_filter input, #{{ $tableId }}_wrapper .dt-search .dt-input, #{{ $tableId }}_wrapper .dataTables_filter input');
                            input?.focus();
                            input?.select?.();
                        }, 100);
                    }
                });

                $('#{{ $tableId }}').on('click.suppick', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (data) this.chooseSupplier(data);
                });
            },

            openBrowse() {
                this.open = true;
                this.$nextTick(() => {
                    const delay = Number(@json($openDelay)) || 0;
                    if (delay > 0) {
                        setTimeout(() => {
                            this.initDataTable();
                            this.focusSearch();
                        }, delay);
                        return;
                    }
                    this.initDataTable();
                    this.focusSearch();
                });
            },

            close() {
                this.open = false;
                if (!this.dataTable) {
                    return;
                }

                if (@json($destroyOnClose)) {
                    this.dataTable.destroy();
                    this.dataTable = null;
                    return;
                }

                this.dataTable.search('').draw();
            },

            chooseSupplier(supplier) {
                if (typeof window.applyTransactionSupplierSelection === 'function') {
                    window.applyTransactionSupplierSelection(supplier);
                    this.close();
                    return;
                }

                const normalize = (value) => String(value ?? '').trim();
                const code = normalize(supplier.fsuppliercode ?? supplier.fsupplier ?? supplier.supplier_code);
                const name = normalize(supplier.fsuppliername ?? supplier.supplier_name);
                const tempo = normalize(supplier.ftempo);
                const currency = normalize(supplier.fcurrency ?? supplier.currency_code).toUpperCase();
                const sel = document.getElementById('modal_filter_supplier_id');
                const hid = document.getElementById('supplierCodeHidden');

                if (sel && code) {
                    let opt = [...sel.options].find((o) => normalize(o.value) === code);
                    const label = name ? `${name} (${code})` : code;

                    if (!opt) {
                        opt = new Option(label, code, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                        opt.selected = true;
                    }

                    opt.dataset.tempo = tempo;
                    opt.dataset.currency = currency;
                    sel.value = code;
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                if (hid) {
                    hid.value = code;
                    hid.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    hid.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                window.dispatchEvent(new CustomEvent('supplier-picked', {
                    detail: {
                        ...supplier,
                        fsuppliercode: code,
                        fsuppliername: name,
                        ftempo: tempo,
                        fcurrency: currency,
                    }
                }));

                this.close();
            },

            init() {
                window.addEventListener(@js($eventName), () => {
                    if (window.fpbSupplierBrowseLocked) {
                        return;
                    }
                    this.openBrowse();
                }, {
                    passive: true
                });
            }
        };
    }
</script>

<div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
        style="height: min(760px, calc(100vh - 1.5rem));">
        <div
            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
            <div>
                <h3 class="text-xl font-bold text-gray-800">{{ "Browse Supplier" }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ "Pilih supplier yang diinginkan" }}</p>
            </div>
            <button type="button" @click="close()"
                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                {{ "Tutup" }}
            </button>
        </div>

        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
            <div id="{{ $controlsId }}" class="flex items-center justify-between gap-4 w-full"></div>
        </div>

        <div class="flex-1 overflow-auto p-6" style="min-height: 0;">
            <div class="bg-white min-w-max">
                <table id="{{ $tableId }}" class="min-w-full text-sm display stripe hover"
                    style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Kode" }}</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Nama Supplier" }}</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Alamat" }}</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Telepon" }}</th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Aksi" }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
            <div id="{{ $paginationId }}"></div>
        </div>
    </div>
</div>
