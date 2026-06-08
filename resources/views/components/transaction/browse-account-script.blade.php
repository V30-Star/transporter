@php
    $tableId = $tableId ?? 'accountTable';
    $controlsId = $controlsId ?? 'accountTableControls';
    $paginationId = $paginationId ?? 'accountTablePagination';
    $routeName = $routeName ?? 'accounts.browse';
    $openDelay = $openDelay ?? 0;
    $fend = $fend ?? 1;
@endphp

<script>
    window.applyTransactionAccountSelection = function(account = {}) {
        const normalize = (value) => String(value ?? '').trim();
        const code = normalize(account.faccount ?? account.account_code);

        if (!code) {
            return false;
        }

        const id = normalize(account.faccid ?? account.account_id);
        const name = normalize(account.faccname ?? account.account_name);
        const label = name ? `${code} - ${name}` : code;
        const selects = Array.from(document.querySelectorAll('#accountSelect'));
        const codeInputs = Array.from(document.querySelectorAll('#accountCodeHidden'));
        const idInputs = Array.from(document.querySelectorAll('#accountIdHidden'));

        selects.forEach((sel) => {
            let opt = [...sel.options].find((o) => normalize(o.value) === code);

            if (!opt) {
                opt = new Option(label, code, true, true);
                sel.add(opt);
            } else {
                opt.text = label;
                opt.selected = true;
            }

            sel.value = code;
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

        window.dispatchEvent(new CustomEvent('account-picked', {
            detail: {
                faccid: id,
                faccount: code,
                faccname: name,
                fhavesubaccount: account.fhavesubaccount
                    ?? account.has_subaccount,
                ftypesubaccount: account.ftypesubaccount
            }
        }));

        return selects.length > 0 || codeInputs.length > 0 || idInputs.length > 0;
    };

    function accountBrowser() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                    this.table = null;
                }

                $('#{{ $tableId }}').off('click.accpick');
                $('#{{ $tableId }} tbody').off('click.accpick');
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
                                order_dir: d.order[0].dir,
                                fend: @json($fend)
                            };
                        }
                    },
                    columns: [{
                            data: 'faccount',
                            name: 'faccount',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'faccname',
                            name: 'faccname',
                            className: 'text-sm'
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
                        [0, 'asc']
                    ],
                    autoWidth: false,
                    scrollX: true,
                    scrollCollapse: true,
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

                $('#{{ $tableId }}').on('click.accpick', '.btn-choose', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const data = this.table.row($(e.target).closest('tr')).data();
                    if (data) this.choose(data);
                });

                $('#{{ $tableId }} tbody').on('click.accpick', 'tr', (e) => {
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

            choose(row) {
                window.applyTransactionAccountSelection(row);
                this.close();
            },

            init() {
                window.addEventListener('account-browse-open', () => this.openModal(), {
                    passive: true
                });
            }
        };
    }
</script>
