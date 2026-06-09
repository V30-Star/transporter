@php
    $tableId = $tableId ?? 'salesmanBrowseTable';
    $controlsId = $controlsId ?? 'salesmanTableControls';
    $routeName = $routeName ?? 'salesman.browse';
    $openDelay = $openDelay ?? 0;
    $destroyOnClose = $destroyOnClose ?? true;
@endphp

<script>
    window.applyTransactionSalesmanSelection = function(salesman = {}) {
        const normalize = (value) => String(value ?? '').trim();
        const code = normalize(salesman.fsalesmancode ?? salesman.fsalesman ?? salesman.salesman_code);

        const selects = Array.from(document.querySelectorAll('#modal_filter_salesman_id'));
        const hiddenInputs = Array.from(document.querySelectorAll('#salesmanCodeHidden'));

        if (!selects.length) {
            return false;
        }

        const name = normalize(salesman.fsalesmanname ?? salesman.salesman_name);
        const label = name ? `${name} (${code})` : code;

        selects.forEach((sel) => {
            let opt = code ? [...sel.options].find(o => normalize(o.value) === code) : null;

            if (code && !opt) {
                opt = new Option(label, code, true, true);
                sel.add(opt);
            }

            if (opt) {
                opt.text = label;
                opt.selected = true;
                sel.value = code;
            } else {
                sel.value = "";
            }

            sel.value = code || '';
            sel.dispatchEvent(new Event('change', { bubbles: true }));
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

        window.dispatchEvent(new CustomEvent('salesman-picked', {
            detail: salesman
        }));

        return true;
    };

    function salesmanBrowser() {
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
                // Always destroy & clear listeners before reinitialising
                if (this.dataTable) {
                    this.dataTable.columns.adjust().draw(false);
                    return;
                }

                // Clear any stale listeners on the element (before DataTable rewrites the DOM)
                $('#{{ $tableId }}').off('.salespick');

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
                            data: 'fsalesmancode',
                            name: 'fsalesmancode',
                            className: 'font-mono text-sm',
                            width: '15%'
                        },
                        {
                            data: 'fsalesmanname',
                            name: 'fsalesmanname',
                            className: 'text-sm',
                            width: '25%'
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
                    }
                });

                // Pilih button click (delegated on table)
                $('#{{ $tableId }}').on('click.salespick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.dataTable?.row($(e.currentTarget).closest('tr')).data();
                    if (!data) {
                        return;
                    }

                    this.chooseSalesman(data);
                });

                // Single-row click (delegated on table, targeting tbody tr)
                $('#{{ $tableId }}').on('click.salespick', 'tbody tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const tr = e.currentTarget;
                    const data = this.dataTable?.row(tr).data();
                    if (!data) {
                        return;
                    }

                    this.chooseSalesman(data);
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

                // Always destroy so listeners are fully cleared on next open
                if (this.dataTable) {
                    $('#{{ $tableId }}').off('.salespick');
                    this.dataTable.destroy();
                    this.dataTable = null;
                }
            },

            chooseSalesman(salesman) {
                window.applyTransactionSalesmanSelection(salesman);
                this.close();
            },

            init() {
                window.addEventListener('salesman-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        };
    }
</script>
