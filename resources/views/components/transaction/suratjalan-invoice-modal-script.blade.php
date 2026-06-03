@php
    $pickableRoute = $pickableRoute ?? route('invoice.pickable');
    $itemsRouteTemplate = $itemsRouteTemplate ?? route('invoice.items', ['id' => 'INV_ID_PLACEHOLDER']);
    $itemsRoutePlaceholder = $itemsRoutePlaceholder ?? 'INV_ID_PLACEHOLDER';
    $tableId = $tableId ?? 'invoiceTable';
    $controlsId = $controlsId ?? 'invoiceTableControls';
    $paginationId = $paginationId ?? 'invoiceTablePagination';
    $numberColumnLabel = $numberColumnLabel ?? 'fsono';
    $numberColumnName = $numberColumnName ?? 'fsono';
    $referenceColumnLabel = $referenceColumnLabel ?? 'frefno';
    $referenceColumnName = $referenceColumnName ?? 'frefno';
    $partyColumnLabel = $partyColumnLabel ?? 'fcustomername';
    $partyColumnName = $partyColumnName ?? 'fcustomername';
    $dateColumnLabel = $dateColumnLabel ?? 'fsodate';
    $dateColumnName = $dateColumnName ?? 'fsodate';
    $itemIdField = $itemIdField ?? 'ftranmtid';
    $pickEventName = $pickEventName ?? 'pr-picked';
    $detailEntityLabel = $detailEntityLabel ?? 'Faktur Penjualan';
@endphp

<script>
    window.invoiceFormModal = function() {
        const tableSelector = '#' + @js($tableId);
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

                this.table = $(tableSelector).DataTable({
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
                            data: @js($numberColumnLabel),
                            name: @js($numberColumnName),
                            className: 'font-mono text-sm'
                        },
                        {
                            data: @js($dateColumnLabel),
                            name: @js($dateColumnName),
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: @js($referenceColumnLabel),
                            name: @js($referenceColumnName),
                            className: 'font-mono text-sm',
                            render: function(data) {
                                return data || '<span class="text-gray-400">-</span>';
                            }
                        },
                        {
                            data: @js($partyColumnLabel),
                            name: @js($partyColumnName),
                            className: 'text-sm',
                            render: function(data) {
                                return data || '<span class="text-gray-400">-</span>';
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
                    dom: '<"#' + @js($controlsId) + '"lf>rt<"#' + @js($paginationId) + '"ip>',
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
                        }).focus();

                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });

                const self = this;
                $(tableSelector).off('click', '.btn-pick').on('click', '.btn-pick', function() {
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
                window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
            },

            async pick(row) {
                try {
                    const itemId = row?.[@js($itemIdField)];
                    const url = @js($itemsRouteTemplate).replace(@js($itemsRoutePlaceholder), itemId);

                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: ${res.status}`);
                    }

                    const json = await res.json();
                    const items = (json.items || []).filter(src => Number(src.maxqty ?? src.fqtyremain ?? 0) > 0);
                    if (items.length === 0) {
                        window.toast?.warning('Semua item Faktur ini sudah habis atau sudah digunakan.');
                        return;
                    }
                    window.applyTransactionCustomerSelection?.({
                        fcustomercode: json.header?.fcustno ?? row?.fcustno ?? '',
                        fcustomername: json.header?.fcustomername ?? row?.fcustomername ?? '',
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

                    window.dispatchEvent(new CustomEvent(@js($pickEventName), {
                        detail: {
                            header: json.header,
                            items: items
                        }
                    }));

                    this.closeModal();
                } catch (e) {
                    console.error('Error:', e);
                    window.toast?.error(`Gagal mengambil detail ${@js($detailEntityLabel)}: ${e.message}`);
                }
            }
        };
    };
</script>
