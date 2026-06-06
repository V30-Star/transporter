@php
    $tableId = $tableId ?? 'warehouseTable';
    $controlsId = $controlsId ?? 'warehouseTableControls';
    $paginationId = $paginationId ?? 'warehouseTablePagination';
    $routeName = $routeName ?? 'gudang.browse';
    $eventName = $eventName ?? 'warehouse-browse-open';
    $openDelay = $openDelay ?? 0;
@endphp

<script>
    window.applyTransactionWarehouseSelection = function(warehouse = {}) {
        const normalize = (value) => String(value ?? '').trim();
        const code = normalize(warehouse.fwhcode ?? warehouse.warehouse_code);

        if (!code) {
            return false;
        }

        const id = normalize(warehouse.fwhid ?? warehouse.warehouse_id);
        const name = normalize(warehouse.fwhname ?? warehouse.warehouse_name);
        const label = name ? `${name} (${code})` : code;
        const selects = Array.from(document.querySelectorAll(
            '#warehouseSelect, #warehouseSelectFrom, #warehouseSelectTo'
        ));
        const codeInputs = Array.from(document.querySelectorAll(
            '#warehouseCodeHidden, #warehouseCodeHiddenFrom, #warehouseCodeHiddenTo'
        ));
        const idInputs = Array.from(document.querySelectorAll('#warehouseIdHidden'));

        let activeValue = code;
        selects.forEach((sel) => {
            let opt = [...sel.options].find((o) => normalize(o.value) === code);

            if (!opt) {
                opt = new Option(label, code, true, true);
                sel.add(opt);
            } else {
                opt.text = label;
                opt.selected = true;
            }

            sel.value = opt.value;
            activeValue = opt.value;
            sel.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });

        codeInputs.forEach((hid) => {
            hid.value = code;
            hid.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            hid.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });

        idInputs.forEach((hid) => {
            hid.value = id;
            hid.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            hid.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });

        window.dispatchEvent(new CustomEvent('warehouse-picked', {
            detail: {
                fwhid: id,
                fwhcode: activeValue,
                fwhname: name,
                fbranchcode: normalize(warehouse.fbranchcode)
            }
        }));

        return selects.length > 0 || codeInputs.length > 0 || idInputs.length > 0;
    };

    window.warehouseBrowser = function() {
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
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.columns.adjust().draw(false);
                    return;
                }

                $('#{{ $tableId }}').off('click.whpick');
                $('#{{ $tableId }} tbody').off('click.whpick');
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
                            data: 'fbranchcode',
                            name: 'fbranchcode',
                            className: 'text-sm',
                            width: '15%',
                            render: d => d || '<span class="text-gray-400">-</span>'
                        },
                        {
                            data: 'fwhcode',
                            name: 'fwhcode',
                            className: 'font-mono text-sm font-semibold',
                            width: '20%'
                        },
                        {
                            data: 'fwhname',
                            name: 'fwhname',
                            className: 'text-sm',
                            width: '50%'
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
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                    language: dataTableLanguage,
                    order: [
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    scrollX: false,
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

                        $c.find('.dataTables_scroll').css({
                            width: '100%'
                        });

                        $c.find('.dataTables_scrollBody').css({
                            overflowX: 'auto',
                            overflowY: 'auto'
                        });
                    }
                });

                $('#{{ $tableId }}').on('click.whpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.table?.row($(e.currentTarget).closest('tr')).data();
                    if (!data) {
                        console.warn('Warehouse row data not found for selected row.');
                        return;
                    }

                    this.choose(data);
                });

                $('#{{ $tableId }} tbody').on('click.whpick', 'tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const data = this.table?.row(e.currentTarget).data();
                    if (!data) {
                        return;
                    }

                    this.choose(data);
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
                window.applyTransactionWarehouseSelection(w);
                this.close();
            },

            init() {
                window.addEventListener(@js($eventName), () => this.openModal());
            }
        };
    };
</script>
