@props([
    'tableId' => 'accountTable',
    'controlsId' => 'accountTableControls',
    'paginationId' => 'accountTablePagination',
    'routeName' => 'accounts.browse',
    'eventName' => 'account-browse-open',
    'openDelay' => 0,
])

<script>
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
                                order_dir: d.order[0].dir
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
                    }
                });

                $('#{{ $tableId }}').on('click.accpick', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    if (data) this.choose(data);
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
                window.dispatchEvent(new CustomEvent('account-picked', {
                    detail: {
                        faccid: row.faccid,
                        faccount: row.faccount,
                        faccname: row.faccname
                    }
                }));
                this.close();
            },

            init() {
                window.addEventListener(@js($eventName), () => this.openModal(), {
                    passive: true
                });
            }
        };
    }
</script>

<div x-data="accountBrowser()" x-show="open" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
        style="height: 650px;">
        <div
            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Browse Account</h3>
                <p class="text-sm text-gray-500 mt-0.5">Pilih account yang diinginkan</p>
            </div>
            <button type="button" @click="close()"
                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                Tutup
            </button>
        </div>

        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
            <div id="accountTableControls"></div>
        </div>

        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
            <div class="bg-white">
                <table id="accountTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Account
                                Kode</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Account
                                Nama</th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
            <div id="accountTablePagination"></div>
        </div>
    </div>
</div>
