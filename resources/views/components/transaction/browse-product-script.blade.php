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
        return {
            open: false,
            forEdit: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                    this.table = null;
                }

                $('#{{ $tableId }}').off('click', '.btn-choose');

                this.table = $('#{{ $tableId }}').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('products.browse') }}",
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
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: @json($showControls || $showPagination ? '<"product-browser-top"fl>rt<"product-browser-bottom"ip>' : '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip'),
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
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());
                        const $searchInput = $container.find('.dt-search .dt-input, .dataTables_filter input');
                        const $lengthSelect = $container.find('.dt-length select, .dataTables_length select');

                        $searchInput.css({
                            width: @json($showControls ? '300px' : '280px'),
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        $lengthSelect.css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });

                        @if ($showControls)
                            const $filter = $container.find('.dataTables_filter, .dt-search');
                            const $length = $container.find('.dataTables_length, .dt-length');
                            const controls = document.getElementById(@js($controlsId));

                            if (controls) {
                                controls.innerHTML = '';
                                controls.className = 'flex items-center justify-between gap-4 flex-wrap';
                                if ($filter.length) {
                                    $filter.addClass('order-1').appendTo(controls);
                                }
                                if ($length.length) {
                                    $length.addClass('order-2 ml-auto').appendTo(controls);
                                }
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

                $('#{{ $tableId }}').on('click', '.btn-choose', (e) => {
                    const product = this.table.row($(e.target).closest('tr')).data();
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
