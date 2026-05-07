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
    function supplierBrowser() {
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
            dataTable: null,

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
                            className: 'text-sm',
                            defaultContent: '-',
                            orderable: false,
                            width: '30%'
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
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });

                        const $filter = $container.find('.dataTables_filter, .dt-search');
                        const $length = $container.find('.dataTables_length, .dt-length');
                        const controls = document.getElementById(@js($controlsId));
                        if (controls) {
                            controls.innerHTML = '';
                            if ($filter.length) $filter.appendTo(controls);
                            if ($length.length) $length.appendTo(controls);
                        }
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
                        setTimeout(() => this.initDataTable(), delay);
                        return;
                    }
                    this.initDataTable();
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
                const sel = document.getElementById('modal_filter_supplier_id');
                const hid = document.getElementById('supplierCodeHidden');
                if (!sel) {
                    this.close();
                    return;
                }
                let opt = [...sel.options].find(o => o.value == String(supplier.fsuppliercode));
                const label = `${supplier.fsuppliername} (${supplier.fsuppliercode})`;
                if (!opt) {
                    opt = new Option(label, supplier.fsuppliercode, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = supplier.fsuppliercode;
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
