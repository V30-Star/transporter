@php
    $tableId = $tableId ?? 'supplierBrowseTable';
    $controlsId = $controlsId ?? 'supplierTableControls';
    $paginationId = $paginationId ?? 'supplierTablePagination';
    $routeName = $routeName ?? 'suppliers.browse';
    $eventName = $eventName ?? 'supplier-browse-open';
    $openDelay = $openDelay ?? 0;
    $destroyOnClose = $destroyOnClose ?? false;
@endphp

<script>
    window.applyTransactionSupplierSelection = function(supplier = {}) {
        const normalize = (value) => String(value ?? '').trim();
        const code = normalize(supplier.fsuppliercode ?? supplier.fsupplier ?? supplier.supplier_code);

        if (!code) {
            return false;
        }

        const selects = Array.from(document.querySelectorAll('#modal_filter_supplier_id'));
        const hiddenInputs = Array.from(document.querySelectorAll('#supplierCodeHidden'));
        const name = normalize(supplier.fsuppliername ?? supplier.supplier_name);
        const label = name ? `${name} (${code})` : code;
        const tempo = normalize(supplier.ftempo);
        const currency = normalize(supplier.fcurrency ?? supplier.currency_code).toUpperCase();
        const supplierData = {
            ...supplier,
            fsuppliercode: code,
            fsuppliername: name,
            ftempo: tempo,
            fcurrency: currency,
        };

        selects.forEach((sel) => {
            let opt = [...sel.options].find(o => normalize(o.value) === code);

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
        });

        hiddenInputs.forEach((hid) => {
            hid.value = code;
            hid.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            hid.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });

        window.dispatchEvent(new CustomEvent('supplier-picked', {
            detail: supplierData
        }));

        return selects.length > 0 || hiddenInputs.length > 0;
    };

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

            initDataTable() {
                if (this.dataTable) {
                    this.dataTable.destroy();
                    this.dataTable = null;
                }

                $('#{{ $tableId }}').off('click.suppick');
                $('#{{ $tableId }} tbody').off('click.suppick');
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

                        // Focus search after all DOM moves are complete
                        setTimeout(() => {
                            const inp = document.querySelector('#{{ $controlsId }} input[type="search"], #{{ $controlsId }} .dt-input, #{{ $controlsId }} .dataTables_filter input, #{{ $controlsId }} input');
                            if (inp && document.activeElement !== inp) {
                                inp.focus();
                                if (!inp.value) inp.select?.();
                            }
                        }, 50);
                    }
                });

                $('#{{ $tableId }}').on('click.suppick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (data) this.chooseSupplier(data);
                });

                $('#{{ $tableId }} tbody').on('click.suppick', 'tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const data = this.dataTable?.row(e.currentTarget).data();
                    if (!data) {
                        return;
                    }

                    this.chooseSupplier(data);
                });
            },

            focusSearch() {
                const focus = (attempt = 0) => {
                    const input = this.$el?.querySelector?.('input[type="search"], .dt-input, .dataTables_filter input, input')
                        || document.querySelector('#{{ $controlsId }} input, #{{ $tableId }}_wrapper input');
                    if (input) {
                        if (document.activeElement !== input) {
                            input.focus();
                            if (!input.value) {
                                input.select?.();
                            }
                        }
                    }
                    if (attempt < 15) {
                        setTimeout(() => focus(attempt + 1), 100);
                    }
                };

                focus();
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
                if (!window.applyTransactionSupplierSelection(supplier)) {
                    this.close();
                    return;
                }
                this.close();
            },

            init() {
                window.addEventListener(@js($eventName), () => this.openBrowse(), {
                    passive: true
                });
            }
        };
    }
</script>
