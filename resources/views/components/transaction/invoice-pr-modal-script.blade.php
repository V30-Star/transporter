@php
    $pickableRoute = $pickableRoute ?? route('tr_poh.pickable');
    $itemsRouteTemplate = $itemsRouteTemplate ?? route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']);
@endphp

<script>
    window.prhFormModal = function() {
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

                this.table = $('#prTable').DataTable({
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
                                order_dir: d.order[0].dir
                            };
                        }
                    },
                    columns: [{
                            data: 'fprno',
                            name: 'fprno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fprdate',
                            name: 'fprdate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                            className: 'text-sm',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function() {
                                return '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
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
                        [1, 'desc']
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
                            const inp = document.querySelector('#prTable_wrapper input[type="search"], #prTable_wrapper input');
                            if (inp && document.activeElement !== inp) {
                                inp.focus();
                                if (!inp.value) inp.select?.();
                            }
                        }, 50);
                    }
                });

                const self = this;
                $('#prTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            focusSearch() {
                const focus = (attempt = 0) => {
                    const input = this.$el?.querySelector?.('input[type="search"], .dt-input, .dataTables_filter input, input')
                        || document.querySelector('#prTable_wrapper input');
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
                    const url = @js($itemsRouteTemplate).replace('PR_ID_PLACEHOLDER', row.fprhid);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();

                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefcode ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(row, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: row,
                            items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error(e);
                    console.log('Gagal mengambil detail PR. Lihat konsol untuk detail.');
                }
            }
        };
    };
</script>
