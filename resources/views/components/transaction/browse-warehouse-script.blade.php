@php
    $tableId = $tableId ?? 'warehouseTable';
    $controlsId = $controlsId ?? 'warehouseTableControls';
    $paginationId = $paginationId ?? 'warehouseTablePagination';
    $routeName = $routeName ?? 'gudang.browse';
    $eventName = $eventName ?? 'warehouse-browse-open';
    $openDelay = $openDelay ?? 0;
@endphp

<script>
    window.warehouseBrowser = function() {
        const dataTableLanguage = {
            processing: @json(__('ui.load_data')),
            search: @json(__('ui.search') . ':'),
            lengthMenu: @json(__('ui.show_menu')),
            info: @json(__('ui.showing_data')),
            infoEmpty: @json(__('ui.no_data')),
            infoFiltered: @json(__('ui.filtered_from_total')),
            zeroRecords: @json(__('ui.no_data_found')),
            emptyTable: @json(__('ui.no_data_available')),
            paginate: {
                first: @json(__('ui.first')),
                last: @json(__('ui.last')),
                next: @json(__('ui.next')),
                previous: @json(__('ui.previous'))
            }
        };

        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                    this.table = null;
                }

                $('#{{ $tableId }}').off('click.whpick');
                this.table = $('#{{ $tableId }}').DataTable({
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
                            data: null,
                            name: 'fwhcode',
                            className: 'text-sm',
                            render: (d, t, row) => `<span class="font-mono font-semibold">${row.fwhcode}</span> - ${row.fwhname}`
                        },
                        {
                            data: 'fbranchcode',
                            name: 'fbranchcode',
                            className: 'text-sm',
                            render: d => d || '<span class="text-gray-400">-</span>'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '100px',
                            render: function() {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">' + @json(__('ui.choose')) + '</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    language: dataTableLanguage,
                    order: [
                        [0, 'asc']
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const $c = $(this.api().table().container());
                        $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();
                        $c.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });

                $('#{{ $tableId }}').on('click.whpick', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    if (data) this.choose(data);
                });
            },

            openModal() {
                this.open = true;
                this.$nextTick(() => {
                    const delay = Number(@json($openDelay)) || 0;
                    if (delay > 0) {
                        setTimeout(() => this.initDataTable(), delay);
                        return;
                    }
                    this.initDataTable();
                });
            },

            close() {
                this.open = false;
                if (this.table) {
                    this.table.destroy();
                    this.table = null;
                }
            },

            choose(w) {
                window.dispatchEvent(new CustomEvent('warehouse-picked', {
                    detail: {
                        fwhid: w.fwhid,
                        fwhcode: w.fwhcode,
                        fwhname: w.fwhname,
                        fbranchcode: w.fbranchcode
                    }
                }));
                this.close();
            },

            init() {
                window.addEventListener(@js($eventName), () => this.openModal());
            }
        };
    };
</script>
