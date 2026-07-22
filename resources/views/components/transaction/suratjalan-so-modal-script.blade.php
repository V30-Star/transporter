@php
    $pickableRoute = $pickableRoute ?? route('salesorder.pickable');
    $itemsRouteTemplate = $itemsRouteTemplate ?? route('salesorder.items', ['id' => 'SO_ID_PLACEHOLDER']);
@endphp

<script>
    window.soFormModal = function() {
        return {
            show: false,
            table: null,
            showDupModal: false,
            dupCount: 0,
            dupSample: [],
            pendingHeader: null,
            pendingUniques: [],

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#poTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: @js($pickableRoute),
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                                customer_code: document.getElementById('customerCodeHidden')?.value || '',
                                only_remaining: true
                            };
                        }
                    },
                    columns: [{
                            data: 'fbranchcode',
                            name: 'trsomt.fbranchcode',
                            className: 'text-sm',
                            defaultContent: '-',
                            render: function(data, type, row) {
                                const code = (data || '').toString().trim();
                                if (code) return code;
                                return '-';
                            }
                        },
                        {
                            data: 'fsono',
                            name: 'trsomt.fsono',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsodate',
                            name: 'trsomt.fsodate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'fcustno',
                            name: 'trsomt.fcustno',
                            className: 'text-sm',
                            render: function(data, type, row) {
                                const code = (data || '').toString().trim();
                                const name = (row.fcustomername || '').toString().trim();
                                if (code && name) return `${code} - ${name}`;
                                return code || name || '-';
                            }
                        },
                        {
                            data: 'faddress',
                            name: 'faddress',
                            className: 'text-sm',
                            defaultContent: '-',
                            render: function(data) {
                                return (data || '').toString().trim() || '-';
                            }
                        },
                        {
                            data: 'frefpo',
                            name: 'trsomt.frefpo',
                            className: 'text-sm',
                            defaultContent: '-',
                            render: function(data) {
                                return (data || '').toString().trim() || '-';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '100px',
                            render: function() {
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-bold bg-teal-600 hover:bg-teal-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"#poTableControls.flex flex-col gap-3 md:flex-row md:items-center mb-4"<"w-full md:w-auto"f><"w-full md:w-auto md:ml-auto md:text-right"l>>rt<"#poTablePagination"ip>',
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
                        [2, 'desc']
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
                        $container.find('.dataTables_scroll').css({
                            width: '100%'
                        });
                        $container.find('.dataTables_scrollBody').css({
                            overflowX: 'auto',
                            overflowY: 'auto'
                        });

                        // Focus search after all DOM moves are complete
                        setTimeout(() => {
                            const inp = document.querySelector('#poTable_wrapper input[type="search"], #poTable_wrapper input');
                            if (inp && document.activeElement !== inp) {
                                inp.focus();
                                if (!inp.value) inp.select?.();
                            }
                        }, 50);
                    }
                });

                const self = this;
                $('#poTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            focusSearch() {
                const focus = (attempt = 0) => {
                    const input = this.$el?.querySelector?.('input[type="search"], .dt-input, .dataTables_filter input, input')
                        || document.querySelector('#poTable_wrapper input');
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

            openModal() {
                this.show = true;
                this.$nextTick(() => {
                    this.initDataTable();
                    this.focusSearch();
                });
            },

            closeModal() {
                this.show = false;
                if (this.table) {
                    this.table.search('').draw();
                }
            },

            openDupModal(header, duplicates, uniques) {
                window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
            },

            closeDupModal() {
                window.transactionReferenceModalHelper.closeDupModal(this);
            },

            confirmAddUniques() {
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
            },

            async pick(row) {
                try {
                    const url = @js($itemsRouteTemplate).replace('SO_ID_PLACEHOLDER', row.ftrsomtid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: ${res.status}`);
                    }

                    const json = await res.json();
                    const items = (json.items || []).filter(src => Number(src.fqtyremain ?? 0) > 0);
                    if (items.length === 0) {
                        window.toast?.warning('Semua item SO ini sudah habis atau sudah digunakan.');
                        return;
                    }
                    window.applyTransactionCustomerSelection?.({
                        fcustomercode: json.header?.fcustno ?? row.fcustno ?? row.fcustomercode ?? '',
                        fcustomername: row.fcustomername ?? row.fsuppliername ?? '',
                    });
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) =>
                        (src.fitemcode ?? '').toString().trim().toUpperCase();

                    const seenKeys = new Set(currentKeys);
                    const duplicates = [];
                    const uniques = [];

                    items.forEach(src => {
                        const key = keyOf(src);
                        if (seenKeys.has(key)) {
                            duplicates.push(src);
                        } else {
                            uniques.push(src);
                            seenKeys.add(key);
                        }
                    });

                    if (duplicates.length > 0) {
                        this.openDupModal(json.header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: json.header,
                            items: items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error('Error:', e);
                    window.toast?.error(`Gagal mengambil detail Sales Order: ${e.message}`);
                }
            }
        };
    };
</script>
