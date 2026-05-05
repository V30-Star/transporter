@props([
    'tableId' => 'productTable',
    'showControls' => false,
    'controlsId' => 'productTableControls',
    'showPagination' => false,
    'paginationId' => 'productTablePagination',
    'title' => 'Browse Produk',
    'description' => 'Pilih produk yang diinginkan',
    'closeButtonClass' => 'px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm',
])

<div x-data="productBrowser()" x-show="open" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
        style="height: 650px;">
        <div
            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
            <div>
                <h3 class="text-xl font-bold text-gray-800">{{ $title }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ $description }}</p>
            </div>
            <button type="button" @click="close()" class="{{ $closeButtonClass }}">
                Tutup
            </button>
        </div>

        @if ($showControls)
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="{{ $controlsId }}"></div>
            </div>
        @endif

        <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
            <div class="bg-white">
                <table id="{{ $tableId }}" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama Produk</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Satuan</th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Merek</th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Stock</th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
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
