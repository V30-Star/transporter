@php
    $tableId = $tableId ?? 'customerBrowseTable';
    $controlsId = $controlsId ?? 'customerTableControls';
    $routeName = $routeName ?? 'customer.browse';
    $openDelay = $openDelay ?? 0;
    $destroyOnClose = $destroyOnClose ?? true;
@endphp

<script>
    window.applyTransactionCustomerSelection = function(customer = {}) {
        const normalize = (value) => String(value ?? '').trim();
        const code = normalize(customer.fcustomercode ?? customer.fcustno ?? customer.customer_code ?? customer.fsupplier);

        if (!code) {
            return false;
        }

        const selects = Array.from(document.querySelectorAll('#modal_filter_customer_id'));
        const hiddenInputs = Array.from(document.querySelectorAll('#customerCodeHidden'));

        if (!selects.length) {
            return false;
        }

        const name = normalize(customer.fcustomername ?? customer.customer_name ?? customer.fsuppliername);
        const label = name ? `${name} (${code})` : code;

        selects.forEach((sel) => {
            let opt = [...sel.options].find(o => normalize(o.value) === code);

            if (!opt) {
                opt = new Option(label, code, true, true);
                sel.add(opt);
            } else {
                opt.text = label;
                opt.selected = true;
            }

            opt.dataset.fkodefp = normalize(customer.fkodefp);
            opt.dataset.ftempo = normalize(customer.ftempo);
            opt.dataset.fsalesman = normalize(customer.fsalesman);
            sel.value = code;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        });

        hiddenInputs.forEach((hid) => {
            hid.value = code;
            hid.dispatchEvent(new Event('input', { bubbles: true }));
            hid.dispatchEvent(new Event('change', { bubbles: true }));
        });

        window.dispatchEvent(new CustomEvent('customer-selected', {
            detail: {
                fcustomercode: code,
                fcustomername: name,
                ftempo: normalize(customer.ftempo),
                fsalesman: normalize(customer.fsalesman),
                f1: normalize(customer.fkirimaddress1 ?? customer.f1),
                f2: normalize(customer.fkirimaddress2 ?? customer.f2),
                f3: normalize(customer.fkirimaddress3 ?? customer.f3),
                fkodefp: normalize(customer.fkodefp),
            }
        }));

        return true;
    };

    function customerBrowser() {
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
                    this.dataTable.destroy();
                    this.dataTable = null;
                }

                // Clear any stale listeners on the element (before DataTable rewrites the DOM)
                $('#{{ $tableId }}').off('.custpick');

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
                    createdRow: function(row, data) {
                        if (data.fblokir == 1) {
                            $(row).addClass('text-red-600 italic');
                        }
                    },
                    columns: [{
                            data: 'fcustomercode',
                            name: 'fcustomercode',
                            className: 'font-mono text-sm',
                            width: '15%'
                        },
                        {
                            data: 'fcustomername',
                            name: 'fcustomername',
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
                            render: function(data, type, row) {
                                if (row.fblokir == 1) {
                                     return '<span class="text-xs font-bold text-red-500">' + @json("BLOKIR") + '</span>';
                                }
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
                $('#{{ $tableId }}').on('click.custpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const tr = $(e.currentTarget).closest('tr')[0];
                    const data = this.dataTable?.row(tr).data();
                    if (data && data.fblokir != 1) {
                        this.chooseCustomer(data);
                    }
                });

                // Single-row click (delegated on table, targeting tbody tr)
                $('#{{ $tableId }}').on('click.custpick', 'tbody tr', (e) => {
                    if ($(e.target).closest('button, a, input, select, textarea').length) {
                        return;
                    }

                    const tr = e.currentTarget;
                    const data = this.dataTable?.row(tr).data();
                    if (!data || data.fblokir == 1) {
                        return;
                    }

                    this.chooseCustomer(data);
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
                    $('#{{ $tableId }}').off('.custpick');
                    this.dataTable.destroy();
                    this.dataTable = null;
                }
            },

            chooseCustomer(customer) {
                window.applyTransactionCustomerSelection(customer);
                window.dispatchEvent(new CustomEvent('customer-picked', {
                    detail: customer || {}
                }));
                this.close();
            },

            init() {
                window.addEventListener('customer-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        };
    }
</script>
