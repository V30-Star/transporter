@php
    $tableId = $tableId ?? 'customerBrowseTable';
    $controlsId = $controlsId ?? 'customerTableControls';
    $paginationId = $paginationId ?? 'customerTablePagination';
@endphp

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
</style>

<div x-data="customerBrowser()" x-init="init()" x-show="open" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden p-3 md:p-6">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
        style="height: min(760px, calc(100vh - 1.5rem));">
        <div
            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
            <div>
                <h3 class="text-xl font-bold text-gray-800">{{ "Browse Customer" }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ "Pilih customer yang diinginkan" }}</p>
            </div>
            <button type="button" @click="close()"
                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                {{ "Tutup" }}
            </button>
        </div>

        <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
            <div id="{{ $controlsId }}"></div>
        </div>

        <div class="flex-1 overflow-auto p-6" style="min-height: 0;">
            <div class="bg-white min-w-max">
                <table id="{{ $tableId }}" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Kode" }}</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Nama Customer" }}</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Alamat" }}</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Telepon" }}</th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">{{ "Aksi" }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
            <div id="{{ $paginationId }}"></div>
        </div>
    </div>
</div>
