@php
    $tableId      = $tableId      ?? 'returpenjualanBrowseTable';
    $controlsId   = $controlsId   ?? 'returpenjualanTableControls';
    $paginationId = $paginationId ?? 'returpenjualanTablePagination';
    $routeName    = $routeName    ?? 'returpenjualan.browse';
    $openDelay    = $openDelay    ?? 0;
    $destroyOnClose = $destroyOnClose ?? true;
@endphp

<script>
    function returPenjualanBrowser() {
        const dataTableLanguage = {
            processing: @json('Memuat data...'),
            search: @json('Search' . ':'),
            lengthMenu: @json('Tampilkan _MENU_'),
            info: @json('Menampilkan _START_ - _END_ dari _TOTAL_ data'),
            infoEmpty: @json('Tidak ada data'),
            infoFiltered: @json('(disaring dari _MAX_ total data)'),
            zeroRecords: @json('Tidak ada data yang ditemukan'),
            emptyTable: @json('Tidak ada data tersedia'),
            paginate: {
                first: @json('Pertama'),
                last: @json('Terakhir'),
                next: @json('Selanjutnya'),
                previous: @json('Sebelumnya')
            }
        };

        return {
            open: false,
            dataTable: null,

            initDataTable() {
                // If already initialized, just re-adjust columns
                if (this.dataTable) {
                    this.dataTable.columns.adjust().draw(false);
                    return;
                }

                // Clear any stale listeners on the element before DataTable rewrites DOM
                $('#{{ $tableId }}').off('.rpick');

                this.dataTable = $('#{{ $tableId }}').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route($routeName) }}",
                        type: 'GET',
                        data: function(d) {
                            const params = {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                            };
                            // Optionally filter by selected customer
                            const custCode = (
                                document.getElementById('customerCodeHidden')?.value ||
                                document.getElementById('modal_filter_customer_id')?.value ||
                                ''
                            ).trim();
                            if (custCode) params.customer_code = custCode;
                            return params;
                        }
                    },
                    columns: [
                        {
                            data: 'fbranchcode',
                            name: 'fbranchcode',
                            className: 'text-sm',
                            defaultContent: '-',
                            width: '8%',
                            render: function(data) {
                                return (data || '').toString().trim() || '-';
                            }
                        },
                        {
                            data: 'fsono',
                            name: 'fsono',
                            className: 'font-mono text-sm',
                            width: '15%'
                        },
                        {
                            data: 'frefno',
                            name: 'frefno',
                            className: 'font-mono text-sm',
                            defaultContent: '-',
                            width: '15%',
                            render: function(data) {
                                return (data || '').toString().trim() || '-';
                            }
                        },
                        {
                            data: 'fsodate',
                            name: 'fsodate',
                            className: 'text-sm',
                            width: '10%',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: null,
                            name: 'fcustomername',
                            className: 'text-sm',
                            width: '20%',
                            render: function(data, type, row) {
                                const code = (row.fcustno || '').toString().trim();
                                const name = (row.fcustomername || '').toString().trim();
                                if (code && name) return `${code} - ${name}`;
                                return code || name || '-';
                            }
                        },
                        {
                            data: 'famountso',
                            name: 'famountso',
                            className: 'text-sm text-right',
                            width: '12%',
                            render: function(data) {
                                if (data == null) return '-';
                                return Number(data).toLocaleString('id-ID', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 2
                                });
                            }
                        },
                        {
                            data: 'fket',
                            name: 'fket',
                            className: 'text-sm',
                            defaultContent: '-',
                            orderable: false,
                            render: function(data) {
                                const v = (data || '').toString().trim();
                                return v ? `<span title="${v}">${v.length > 40 ? v.substring(0, 40) + '…' : v}</span>` : '-';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '10%',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-rose-600 hover:bg-rose-700 text-white transition-colors duration-150">' +
                                    @json('Pilih') +
                                    '</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"customer-browser-top"fl>rt<"customer-browser-bottom"ip>',
                    language: dataTableLanguage,
                    order: [
                        [3, 'desc']
                    ],
                    autoWidth: false,
                    scrollX: true,
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

                        $container.find('.dataTables_scroll').css({ width: '100%' });
                        $container.find('.dataTables_scrollBody').css({
                            overflowX: 'auto',
                            overflowY: 'auto'
                        });

                        const controls = document.getElementById(@js($controlsId));
                        if (controls) {
                            controls.innerHTML = '';
                            controls.className = 'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 w-full';
                            controls.setAttribute('style', 'display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:16px !important; width:100% !important;');

                            const $filter = $container.find('.dataTables_filter, .dt-search');
                            const $length = $container.find('.dataTables_length, .dt-length');

                            if ($filter.length) {
                                $filter.addClass('order-1 shrink-0 whitespace-nowrap').appendTo(controls);
                            }
                            if ($length.length) {
                                $length.addClass('order-2 shrink-0 whitespace-nowrap').appendTo(controls);
                            }
                        }

                        const pagination = document.getElementById(@js($paginationId));
                        if (pagination) {
                            pagination.innerHTML = '';
                            pagination.className = 'flex items-center justify-between gap-4 flex-nowrap';
                            pagination.setAttribute('style', 'display:flex !important; align-items:center !important; justify-content:space-between !important; gap:16px !important; flex-wrap:nowrap !important; width:100% !important;');

                            const $info    = $container.find('.dataTables_info, .dt-info');
                            const $paginate = $container.find('.dataTables_paginate, .dt-paging');

                            if ($info.length) {
                                $info.addClass('order-1 shrink-0 whitespace-nowrap').appendTo(pagination);
                            }
                            if ($paginate.length) {
                                $paginate.addClass('order-2 ml-auto shrink-0 whitespace-nowrap').appendTo(pagination);
                            }
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

                // Pilih button click (delegated on table)
                $('#{{ $tableId }}').on('click.rpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.dataTable?.row($(e.currentTarget).closest('tr')).data();
                    if (!data) return;

                    this.chooseReturPenjualan(data);
                });

                // Single-row click (delegated on table, targeting tbody tr)
                $('#{{ $tableId }}').on('click.rpick', 'tbody tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const tr   = e.currentTarget;
                    const data = this.dataTable?.row(tr).data();
                    if (!data) return;

                    this.chooseReturPenjualan(data);
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

                if (@json($destroyOnClose) && this.dataTable) {
                    $('#{{ $tableId }}').off('.rpick');
                    this.dataTable.destroy();
                    this.dataTable = null;
                }
            },

            chooseReturPenjualan(row) {
                window.dispatchEvent(new CustomEvent('returpenjualan-selected', {
                    detail: row || {}
                }));
                this.close();
            },

            init() {
                window.addEventListener('returpenjualan-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        };
    }
</script>
