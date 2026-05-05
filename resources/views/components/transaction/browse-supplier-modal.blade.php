@props([
    'tableId' => 'supplierBrowseTable',
    'controlsId' => 'supplierTableControls',
    'paginationId' => 'supplierTablePagination',
    'routeName' => 'suppliers.browse',
    'eventName' => 'supplier-browse-open',
    'openDelay' => 0,
    'destroyOnClose' => false,
])

<script>
    function supplierBrowser() {
        return {
            open: false,
            dataTable: null,

            initDataTable() {
                if (this.dataTable) {
                    this.dataTable.destroy();
                    this.dataTable = null;
                }

                $('#{{ $tableId }}').off('click.suppick');
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
                            data: 'fsuppliercode',
                            name: 'fsuppliercode',
                            className: 'font-mono text-sm',
                            width: '15%'
                        },
                        {
                            data: 'fsuppliername',
                            name: 'fsuppliername',
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
                    dom: '<"supplier-table-toolbar mb-4"f<"supplier-table-length ml-auto"l>>rtip',
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
                            controls.className = 'flex items-center justify-between gap-4 w-full';
                            $filter.css({ margin: 0, flex: '1 1 auto' });
                            $length.css({ margin: 0, flex: '0 0 auto' });
                            $container.find('.dataTables_filter label, .dt-search label').css({
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.5rem',
                                margin: 0,
                                width: '100%'
                            });
                            $container.find('.dataTables_length label, .dt-length label').css({
                                display: 'flex',
                                alignItems: 'center',
                                gap: '0.5rem',
                                margin: 0,
                                whiteSpace: 'nowrap'
                            });
                        }
                    }
                });

                $('#{{ $tableId }}').on('click.suppick', '.btn-choose', (e) => {
                    const data = this.dataTable.row($(e.target).closest('tr')).data();
                    if (data) this.chooseSupplier(data);
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

            chooseSupplier(supplier) {
                const sel = document.getElementById('modal_filter_supplier_id');
                const hid = document.getElementById('supplierCodeHidden');
                if (!sel) {
                    this.close();
                    return;
                }
                let opt = [...sel.options].find(o => o.value == String(supplier.fsuppliercode));
                const label = `${supplier.fsuppliername} (${supplier.fsuppliercode})`;
                if (!opt) {
                    opt = new Option(label, supplier.fsuppliercode, true, true);
                    sel.add(opt);
                } else {
                    opt.text = label;
                    opt.selected = true;
                }
                sel.dispatchEvent(new Event('change'));
                if (hid) hid.value = supplier.fsuppliercode;
                this.close();
            },

            init() {
                window.addEventListener(@js($eventName), () => this.openBrowse(), {
                    passive: true
                });
            }
        };
    }
</script>

<div x-data="supplierBrowser()" x-show="open" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
        style="height: 650px;">
        <div
            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Browse Supplier</h3>
                <p class="text-sm text-gray-500 mt-0.5">Pilih supplier yang diinginkan</p>
            </div>
            <button type="button" @click="close()"
                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                Tutup
            </button>
        </div>

        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
            <div id="supplierTableControls" class="flex items-center justify-between gap-4 w-full"></div>
        </div>

        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
            <div class="bg-white">
                <table id="supplierBrowseTable" class="min-w-full text-sm display nowrap stripe hover"
                    style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama
                                Supplier</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Alamat</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Telepon</th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
            <div id="supplierTablePagination"></div>
        </div>
    </div>
</div>
