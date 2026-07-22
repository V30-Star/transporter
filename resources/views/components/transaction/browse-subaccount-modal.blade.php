@props([
    'tableId' => 'subaccountTable',
    'showControls' => true,
    'controlsId' => 'subaccountTableControls',
    'showPagination' => true,
    'paginationId' => 'subaccountTablePagination',
    'routeName' => 'subaccounts.browse',
    'eventName' => 'subaccount-browse-open',
    'openDelay' => 0,
    'title' => 'Pilih Sub Account',
    'description' => '',
    'closeButtonClass' => 'px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm',
])

<style>
    #{{ $tableId }}_wrapper .dt-layout-row,
    #{{ $tableId }}_wrapper .dataTables_wrapper .row {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 16px !important;
        flex-wrap: nowrap !important;
        width: 100% !important;
    }

    #{{ $tableId }}_wrapper .dt-layout-cell,
    #{{ $tableId }}_wrapper .dataTables_filter,
    #{{ $tableId }}_wrapper .dataTables_length,
    #{{ $tableId }}_wrapper .dataTables_info,
    #{{ $tableId }}_wrapper .dataTables_paginate,
    #{{ $tableId }}_wrapper .dt-search,
    #{{ $tableId }}_wrapper .dt-length,
    #{{ $tableId }}_wrapper .dt-info,
    #{{ $tableId }}_wrapper .dt-paging {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        white-space: nowrap !important;
        flex-wrap: nowrap !important;
        width: auto !important;
        margin: 0 !important;
    }

    #{{ $tableId }}_wrapper .dataTables_filter,
    #{{ $tableId }}_wrapper .dt-search {
        flex: 1 1 auto !important;
        justify-content: flex-start !important;
    }

    #{{ $tableId }}_wrapper .dataTables_length,
    #{{ $tableId }}_wrapper .dt-length {
        margin-left: auto !important;
        flex: 0 0 auto !important;
        justify-content: flex-end !important;
    }

    #{{ $tableId }}_wrapper .dataTables_filter label,
    #{{ $tableId }}_wrapper .dataTables_length label,
    #{{ $tableId }}_wrapper .dt-search label,
    #{{ $tableId }}_wrapper .dt-length label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        white-space: nowrap !important;
        flex-wrap: nowrap !important;
        margin: 0 !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate,
    #{{ $tableId }}_wrapper .dt-paging,
    #{{ $paginationId }} .dataTables_paginate,
    #{{ $paginationId }} .dt-paging {
        gap: 6px !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button,
    #{{ $paginationId }} .dataTables_paginate .paginate_button,
    #{{ $paginationId }} .dt-paging .dt-paging-button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 38px !important;
        height: 38px !important;
        padding: 0 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        color: #374151 !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        line-height: 1 !important;
        margin: 0 !important;
        box-shadow: none !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button.current,
    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button.current:hover,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button.current,
    #{{ $paginationId }} .dataTables_paginate .paginate_button.current,
    #{{ $paginationId }} .dataTables_paginate .paginate_button.current:hover,
    #{{ $paginationId }} .dt-paging .dt-paging-button.current {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button:hover,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button:hover,
    #{{ $paginationId }} .dataTables_paginate .paginate_button:hover,
    #{{ $paginationId }} .dt-paging .dt-paging-button:hover {
        background: #eff6ff !important;
        border-color: #93c5fd !important;
        color: #1d4ed8 !important;
    }

    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button.disabled,
    #{{ $tableId }}_wrapper .dataTables_paginate .paginate_button.disabled:hover,
    #{{ $tableId }}_wrapper .dt-paging .dt-paging-button.disabled,
    #{{ $paginationId }} .dataTables_paginate .paginate_button.disabled,
    #{{ $paginationId }} .dataTables_paginate .paginate_button.disabled:hover,
    #{{ $paginationId }} .dt-paging .dt-paging-button.disabled {
        background: #f9fafb !important;
        border-color: #e5e7eb !important;
        color: #9ca3af !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
    }
</style>

<script>
    function subaccountBrowser() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                    this.table = null;
                }

                $('#{{ $tableId }}').off('click.subpick');
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
                            data: 'fsubaccountcode',
                            name: 'fsubaccountcode',
                            className: 'font-mono text-sm'
                        },
                        {
                            data: 'fsubaccountname',
                            name: 'fsubaccountname',
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
                    dom: @json($showControls || $showPagination ? '<"subaccount-browser-top"fl>rt<"subaccount-browser-bottom"ip>' : '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip'),
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
                    createdRow: function(row) {
                        $(row).addClass('bg-white');
                        $(row).find('td').addClass('p-3 text-sm text-gray-700 border-b border-r border-gray-200 align-middle');
                        $(row).find('td:last-child').removeClass('border-r').addClass('text-center');
                    },
                    drawCallback: function() {
                        const $table = $('#{{ $tableId }}');
                        $table.addClass('border border-gray-200');
                        $table.find('tbody tr').addClass('bg-white');
                        $table.find('tbody td').addClass('p-3 text-sm text-gray-700 border-b border-r border-gray-200 align-middle');
                        $table.find('tbody td:last-child').removeClass('border-r').addClass('text-center');
                    },
                    initComplete: function() {
                        const api = this.api();
                        const $c = $(api.table().container());
                        const $searchInput = $c.find('.dt-search .dt-input, .dataTables_filter input');
                        const $lengthSelect = $c.find('.dt-length select, .dataTables_length select');

                        $searchInput.css({
                            width: @json($showControls ? '300px' : '280px'),
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });

                        $lengthSelect.css({
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

                        @if ($showControls)
                            const $filter = $c.find('.dataTables_filter, .dt-search');
                            const $length = $c.find('.dataTables_length, .dt-length');
                            const controls = document.getElementById(@js($controlsId));

                            if (controls) {
                                controls.innerHTML = '';
                                controls.className = 'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 w-full';
                                controls.setAttribute('style', 'display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:16px !important; width:100% !important;');
                                if ($filter.length) {
                                    $filter.addClass('order-1 shrink-0 whitespace-nowrap').appendTo(controls);
                                }
                                if ($length.length) {
                                    $length.addClass('order-2 shrink-0 whitespace-nowrap').appendTo(controls);
                                }
                                controls.querySelectorAll('.dataTables_filter, .dt-search, .dataTables_length, .dt-length')
                                    .forEach((el) => {
                                        el.style.display = 'inline-flex';
                                        el.style.alignItems = 'center';
                                        el.style.gap = '8px';
                                        el.style.whiteSpace = 'nowrap';
                                        el.style.flexWrap = 'nowrap';
                                        el.style.width = 'auto';
                                    });
                                controls.querySelectorAll('.dataTables_filter, .dt-search').forEach((el) => {
                                        el.style.flex = '1 1 auto';
                                        el.style.justifyContent = 'flex-start';
                                        el.style.minWidth = '0';
                                        el.style.width = '100%';
                                    });
                                controls.querySelectorAll('.dataTables_length, .dt-length').forEach((el) => {
                                        el.style.flex = '0 0 auto';
                                        el.style.justifyContent = 'flex-end';
                                        el.style.justifySelf = 'end';
                                        el.style.marginLeft = '0';
                                    });
                                controls.querySelectorAll('label').forEach((label) => {
                                        label.style.display = 'inline-flex';
                                        label.style.alignItems = 'center';
                                        label.style.gap = '8px';
                                        label.style.whiteSpace = 'nowrap';
                                        label.style.flexWrap = 'nowrap';
                                        label.style.marginBottom = '0';
                                    });
                                controls.querySelectorAll('.dataTables_filter input, .dt-search .dt-input, .dataTables_length select, .dt-length select')
                                    .forEach((field) => {
                                        field.style.display = 'inline-block';
                                        field.style.verticalAlign = 'middle';
                                    });
                                controls.querySelectorAll('.dataTables_filter input, .dt-search .dt-input').forEach((field) => {
                                        field.style.width = '100%';
                                        field.style.maxWidth = '300px';
                                    });
                            }
                        @endif

                        @if ($showPagination)
                            const $info = $c.find('.dataTables_info, .dt-info');
                            const $paginate = $c.find('.dataTables_paginate, .dt-paging');
                            const pagination = document.getElementById(@js($paginationId));

                            if (pagination) {
                                pagination.innerHTML = '';
                                pagination.className = 'flex items-center justify-between gap-4 flex-nowrap';
                                pagination.setAttribute('style', 'display:flex !important; align-items:center !important; justify-content:space-between !important; gap:16px !important; flex-wrap:nowrap !important; width:100% !important;');
                                if ($info.length) {
                                    $info.addClass('order-1 shrink-0 whitespace-nowrap').appendTo(pagination);
                                }
                                if ($paginate.length) {
                                    $paginate.addClass('order-2 ml-auto shrink-0 whitespace-nowrap').appendTo(pagination);
                                }
                                pagination.querySelectorAll('.dataTables_info, .dt-info, .dataTables_paginate, .dt-paging')
                                    .forEach((el) => {
                                        el.style.display = 'inline-flex';
                                        el.style.alignItems = 'center';
                                        el.style.whiteSpace = 'nowrap';
                                        el.style.flexWrap = 'nowrap';
                                        el.style.width = 'auto';
                                    });
                            }
                        @endif

                        // Focus search after all DOM moves are complete
                        setTimeout(() => {
                            const inp = document.querySelector('#{{ $controlsId }} input[type="search"], #{{ $controlsId }} .dt-input, #{{ $controlsId }} .dataTables_filter input, #{{ $controlsId }} input')
                                || document.querySelector('#{{ $tableId }}_wrapper input[type="search"], #{{ $tableId }}_wrapper input');
                            if (inp && document.activeElement !== inp) {
                                inp.focus();
                                if (!inp.value) inp.select?.();
                            }
                        }, 50);
                    }
                });

                $('#{{ $tableId }}').on('click.subpick', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    if (data) this.choose(data);
                });
            },

            focusSearch() {
                const focus = (attempt = 0) => {
                    const input = this.$el?.querySelector?.('input[type="search"], .dt-input, .dataTables_filter input, input')
                        || document.querySelector('#{{ $controlsId }} input, #{{ $tableId }}_wrapper input');
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
                this.open = true;
                this.$nextTick(() => {
                    const delay = Number(@json($openDelay)) || 0;
                    if (delay > 0) {
                        setTimeout(() => {
                            this.initDataTable();
                            this.focusSearch();
                        }, delay);
                        return;
                    }
                    this.initDataTable();
                    this.focusSearch();
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
                window.dispatchEvent(new CustomEvent('subaccount-picked', {
                    detail: {
                        fsubaccountid: row.fsubaccountid,
                        fsubaccountcode: row.fsubaccountcode,
                        fsubaccountname: row.fsubaccountname
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

<div x-data="subaccountBrowser()">
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-black/50" @click="close()"></div>

    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6"
        aria-modal="true" role="dialog">
        <div class="relative w-full max-w-7xl rounded-xl bg-white shadow-2xl flex flex-col overflow-hidden" style="height: min(760px, calc(100vh - 1.5rem));">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <h3 class="text-xl font-bold text-gray-800">{{ $title }}</h3>
                <button type="button" @click="close()" class="{{ $closeButtonClass }}">
                    {{ "Tutup" }}
                </button>
            </div>

            @if ($showControls)
                <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                    <div id="{{ $controlsId }}"></div>
                </div>
            @endif

            <div class="flex-1 overflow-auto p-6" style="min-height: 0;">
                <div class="min-w-max">
                <table id="{{ $tableId }}" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="p-3 text-left font-semibold text-gray-700 border-r border-gray-200">{{ "Sub Account Kode" }}</th>
                            <th class="p-3 text-left font-semibold text-gray-700 border-r border-gray-200">{{ "Sub Account Nama" }}</th>
                            <th class="p-3 text-center font-semibold text-gray-700">{{ "Aksi" }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>

            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                @if ($showPagination)
                    <div id="{{ $paginationId }}"></div>
                @endif
            </div>
        </div>
    </div>
</div>
