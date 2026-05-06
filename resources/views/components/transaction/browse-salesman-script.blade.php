@php
    $tableId = $tableId ?? 'salesmanBrowseTable';
    $controlsId = $controlsId ?? 'salesmanTableControls';
    $routeName = $routeName ?? 'salesman.browse';
    $openDelay = $openDelay ?? 0;
    $destroyOnClose = $destroyOnClose ?? false;
@endphp

<script>
    function salesmanBrowser() {
        return {
            open: false,
            dataTable: null,

            initDataTable() {
                if (this.dataTable) {
                    this.dataTable.destroy();
                    this.dataTable = null;
                }

                $('#{{ $tableId }}').off('click.salespick');
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
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
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

                $('#{{ $tableId }}').on('click.salespick', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (data) {
                        this.chooseSalesman(data);
                    }
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

            chooseSalesman(salesman) {
                const sel = document.getElementById('modal_filter_salesman_id');
                const hid = document.getElementById('salesmanCodeHidden');

                if (!sel) {
                    this.close();
                    return;
                }

                let opt = [...sel.options].find(o => o.value == String(salesman.fsalesmancode));
                const label = `${salesman.fsalesmanname} (${salesman.fsalesmancode})`;

                if (!opt) {
                    opt = new Option(label, salesman.fsalesmancode, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }

                sel.value = salesman.fsalesmancode;
                if (hid) {
                    hid.value = salesman.fsalesmancode;
                }

                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
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
