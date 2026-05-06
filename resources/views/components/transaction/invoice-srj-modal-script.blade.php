@php
    $pickableRoute = $pickableRoute ?? route('suratjalan.pickable');
    $itemsRouteTemplate = $itemsRouteTemplate ?? route('suratjalan.items', ['id' => 'PLACEHOLDER']);
@endphp

<script>
    window.srjFormModal = function() {
        return {
            showSrjModal: false,
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

                this.table = $('#srjTable').DataTable({
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
                            data: 'fstockmtno',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'frefpo',
                            defaultContent: '-',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fstockmtdate',
                            className: 'text-sm',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '100px',
                            render: function() {
                                return '<button type="button" class="btn-pick-srj px-4 py-1.5 rounded-md text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    dom: '<"#srjHeader"fl>rt<"#srjFooter"ip>',
                    language: {
                        processing: "Memuat data...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_",
                        paginate: {
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    order: [
                        [2, 'desc']
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());
                        const $header = $container.find('#srjHeader');

                        $header.addClass('flex items-center justify-between mb-4 gap-4');

                        $header.find('.dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            outline: 'none'
                        }).addClass('focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500');

                        $header.find('.dataTables_length select').css({
                            padding: '6px 30px 6px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            backgroundPosition: 'right 8px center',
                            appearance: 'none',
                            minWidth: '80px'
                        }).addClass('focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500');

                        $container.find('#srjFooter').addClass('flex items-center justify-between mt-4');
                    }
                });

                const self = this;
                $('#srjTable').off('click', '.btn-pick-srj').on('click', '.btn-pick-srj', function() {
                    const data = self.table.row($(this).closest('tr')).data();
                    self.pick(data);
                });
            },

            openSrjModal() {
                this.showSrjModal = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            closeSrjModal() {
                this.showSrjModal = false;
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
                    window.dispatchEvent(new CustomEvent('srj-picked', {
                        detail: {
                            header: this.pendingHeader,
                            items: safeUniques
                        }
                    }));
                }

                this.closeDupModal();
                this.closeSrjModal();
            },

            async pick(row) {
                try {
                    if (row.fdiscontinue == '1') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Produk Discontinue',
                            html: `Produk <b>${row.fprdname}</b> sudah tidak diproduksi lagi.<br><br>Penyimpanan Batal.`,
                            confirmButtonColor: '#f59e0b',
                            confirmButtonText: 'Kembali'
                        });
                        return;
                    }

                    const url = @js($itemsRouteTemplate).replace('PLACEHOLDER', row.fstockmtid);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error(`Server error: ${res.status}`);
                    }

                    const json = await res.json();
                    const items = json.items || [];
                    const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                    const keyOf = (src) =>
                        `${(src.fitemcode ?? '').toString().trim()}::${(src.frefdtno ?? '').toString().trim()}`;

                    const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                    const uniques = items.filter(src => !currentKeys.has(keyOf(src)));

                    if (duplicates.length > 0) {
                        this.openDupModal(json.header, duplicates, uniques);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('srj-picked', {
                        detail: {
                            header: json.header,
                            items: items
                        }
                    }));

                    this.closeSrjModal();
                } catch (e) {
                    console.error('Error SRJ:', e);
                    window.toast?.error(`Gagal mengambil detail SRJ: ${e.message}`);
                }
            }
        };
    };
</script>
