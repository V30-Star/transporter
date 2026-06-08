@php
    $tableId = $tableId ?? 'productTable';
    $showControls = $showControls ?? false;
    $controlsId = $controlsId ?? 'productTableControls';
    $showPagination = $showPagination ?? false;
    $paginationId = $paginationId ?? 'productTablePagination';
    $supportsForEdit = $supportsForEdit ?? false;
    $destroyOnClose = $destroyOnClose ?? false;
    $openDelay = $openDelay ?? 0;
@endphp

<script>
    function productBrowser() {
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
            forEdit: false,
            table: null,
            productCodeFilter: '',

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                    this.table = null;
                }

                $('#{{ $tableId }}').off('click.prodpick');
                $('#{{ $tableId }} tbody').off('click.prodpick');

                this.table = $('#{{ $tableId }}').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('products.browse') }}",
                        type: 'GET',
                        data: (d) => {
                            return {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                                fprdcode_exact: this.productCodeFilter || ''
                            };
                        }
                    },
                    columns: [{
                            data: 'fprdcode',
                            name: 'fprdcode',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fprdname',
                            name: 'fprdname',
                            className: 'text-sm'
                        },
                        {
                            data: 'fsatuanbesar',
                            name: 'fsatuanbesar',
                            className: 'text-sm',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'fmerekname',
                            name: 'fmerekname',
                            className: 'text-center text-sm',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'fminstock',
                            name: 'fminstock',
                            className: 'text-center text-sm'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '100px',
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
                    dom: @json($showControls || $showPagination ? '<"product-browser-top d-flex justify-content-between align-items-center flex-wrap gap-3"f<"product-browser-length ms-auto"l>>rt<"product-browser-bottom"ip>' : '<"d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"f<"ms-auto"l>>rtip'),
                    language: dataTableLanguage,
                    order: [
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    scrollX: true,
                    scrollCollapse: true,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());
                        const $searchWrap = $container.find('.dt-search, .dataTables_filter');
                        const $searchInput = $container.find('.dt-search .dt-input, .dataTables_filter input');
                        const $lengthWrap = $container.find('.dt-length, .dataTables_length');
                        const $lengthSelect = $container.find('.dt-length select, .dataTables_length select');

                        $searchInput.css({
                            width: @json($showControls ? '300px' : '280px'),
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        $searchWrap.css({
                            marginRight: 'auto'
                        });

                        $lengthWrap.css({
                            marginLeft: 'auto'
                        });

                        $lengthSelect.css({
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

                        @if ($showControls)
                            const controls = document.getElementById(@js($controlsId));

                            if (controls) {
                                controls.innerHTML = '';
                                controls.className = 'flex items-center justify-between gap-4 flex-wrap';
                                const filterEl = $searchWrap.first();
                                const lengthEl = $lengthWrap.first();
                                if (filterEl.length) filterEl.addClass('order-1').appendTo(controls);
                                if (lengthEl.length) lengthEl.addClass('order-2 ms-auto').appendTo(controls);
                            }
                        @endif

                        @if ($showPagination)
                            const $info = $container.find('.dataTables_info, .dt-info');
                            const $paginate = $container.find('.dataTables_paginate, .dt-paging');
                            const pagination = document.getElementById(@js($paginationId));

                            if (pagination) {
                                pagination.innerHTML = '';
                                pagination.className = 'flex items-center justify-between gap-4 flex-wrap';
                                if ($info.length) {
                                    $info.addClass('order-1').appendTo(pagination);
                                }
                                if ($paginate.length) {
                                    $paginate.addClass('order-2 ml-auto').appendTo(pagination);
                                }
                            }
                        @endif
                    }
                });

                $('#{{ $tableId }}').on('click.prodpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const product = this.table.row($(e.target).closest('tr')).data();
                    if (product) {
                        this.choose(product);
                    }
                });

                $('#{{ $tableId }} tbody').on('click.prodpick', 'tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const product = this.table?.row(e.currentTarget).data();
                    if (!product) {
                        return;
                    }

                    this.choose(product);
                });
            },

            close() {
                this.open = false;

                if (! this.table) {
                    return;
                }

                if (@json($destroyOnClose)) {
                    this.table.destroy();
                    this.table = null;
                    return;
                }

                this.table.search('').draw();
            },

            choose(product) {
                const detail = @json($supportsForEdit)
                    ? {
                        product: product,
                        forEdit: this.forEdit
                    }
                    : {
                        product: product
                    };

                window.dispatchEvent(new CustomEvent('product-chosen', {
                    detail: detail
                }));

                this.close();
            },

            init() {
                window.addEventListener('browse-open', (e) => {
                    this.open = true;
                    this.productCodeFilter = (e.detail && e.detail.productCodeFilter)
                        ? String(e.detail.productCodeFilter).trim()
                        : '';

                    if (@json($supportsForEdit)) {
                        this.forEdit = !!(e.detail && e.detail.forEdit);
                    }

                    this.$nextTick(() => {
                        const openDelay = Number(@json($openDelay)) || 0;

                        if (openDelay > 0) {
                            setTimeout(() => this.initDataTable(), openDelay);
                            return;
                        }

                        this.initDataTable();
                    });
                }, {
                    passive: true
                });
            }
        };
    }
</script>
