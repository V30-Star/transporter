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

            getSelectedCustomerCode() {
                return (
                    document.getElementById('customerCodeHidden')?.value ||
                    document.getElementById('modal_filter_customer_id')?.value ||
                    ''
                ).toString().trim();
            },

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
                                only_remaining: 1
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
                                if (code) return `${code}`;
                                return code || '-';
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
                    dom: '<"#poTableControls"fl>rt<"#poTablePagination"ip>',
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
                        const $header = $container.find('#poTableControls');

                        $header.addClass('flex items-center justify-between mb-4 gap-4');

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
                        $container.find('.dataTables_scroll').css({
                            width: '100%'
                        });
                        $container.find('.dataTables_scrollBody').css({
                            overflowX: 'auto',
                            overflowY: 'auto'
                        });
                        $container.find('#poTablePagination').addClass('flex items-center justify-between mt-4');
                    }
                });

                const self = this;
                $('#poTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            openModal() {
                this.show = true;
                this.$nextTick(() => {
                    this.initDataTable();
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
                const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                const keyOf = (src) =>
                    `${(src.fitemcode ?? '').toString().trim()}::${(src.frefcode ?? '').toString().trim()}`;

                const safeUniques = this.pendingUniques.filter(src => !currentKeys.has(keyOf(src)));

                if (safeUniques.length > 0) {
                    window.dispatchEvent(new CustomEvent('pr-picked', {
                        detail: {
                            header: this.pendingHeader,
                            items: safeUniques
                        }
                    }));
                }

                this.closeDupModal();
                this.closeModal();
            },

            async pick(row) {
                try {
                    if (row.fnonactive == '1') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Produk Discontinue',
                            html: `Produk <b>${row.fprdname}</b> sudah tidak diproduksi lagi.<br><br>Penyimpanan Batal.`,
                            confirmButtonColor: '#f59e0b',
                            confirmButtonText: 'Kembali'
                        });
                        return;
                    }

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
                        window.toast?.warning('Semua item Sales Order ini sudah habis difakturkan.');
                        return;
                    }
                    window.applyTransactionCustomerSelection?.({
                        fcustomercode: json.header?.fcustno ?? row.fcustno ?? '',
                        fcustomername: row.fcustomername ?? '',
                    });
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

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
