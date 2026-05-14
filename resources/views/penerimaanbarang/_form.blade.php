@php
    $action = $action ?? 'create';
    $isDelete = $action === 'delete';
    $isEdit = $action === 'edit';
    $isView = $action === 'view';
    $readOnly = $isDelete || $isView;
    $fbranchcode = $fbranchcode ?? '';
    $fcabang = $fcabang ?? '';
    $suppliers = $suppliers ?? collect();
    $warehouses = $warehouses ?? collect();
    $penerimaanbarang = $penerimaanbarang ?? new stdClass();
    $filterSupplierId = $filterSupplierId ?? '';
@endphp

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">Cabang</label>
        <input type="text"
            class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
            value="{{ trim(($fbranchcode ?? '') . ($fcabang ?? '' ? ' - ' . $fcabang : '')) }}" disabled>
        <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $fbranchcode) }}">
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">Transaksi#</label>
        @if ($action === 'create')
            <div class="flex items-center gap-3" x-data="{ autoCode: true }">
                <input type="text" name="fstockmtno" class="w-full border rounded px-3 py-2"
                    :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                <label class="inline-flex items-center select-none">
                    <input type="checkbox" x-model="autoCode" checked>
                    <span class="ml-2 text-sm text-gray-700">Auto</span>
                </label>
            </div>
        @else
            <div class="flex items-center gap-3">
                <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                    value="{{ $penerimaanbarang->fstockmtno ?? '' }}" disabled>
            </div>
            <input type="hidden" name="fstockmtno" value="{{ old('fstockmtno', $penerimaanbarang->fstockmtno ?? '') }}">
        @endif
    </div>

    <input type="hidden" name="fstockmtid" value="{{ old('fstockmtid', $action === 'create' ? 'fstockmtid' : ($penerimaanbarang->fstockmtid ?? '')) }}">

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">Supplier</label>
        <div class="flex">
            <div class="relative flex-1">
                <select id="modal_filter_supplier_id" name="filter_supplier_id"
                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed" disabled>
                    <option value=""></option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->fsuppliercode }}"
                            {{ old('fsupplier', $isEdit || $isView || $isDelete ? ($penerimaanbarang->fsupplier ?? '') : $filterSupplierId) == $supplier->fsuppliercode ? 'selected' : '' }}>
                            {{ $supplier->fsuppliername }} ({{ $supplier->fsuppliercode }})
                        </option>
                    @endforeach
                </select>
                @if (!$readOnly)
                    <div class="absolute inset-0" role="button" aria-label="Browse supplier"
                        @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                @endif
            </div>
            <input type="hidden" name="fsupplier" id="supplierCodeHidden"
                value="{{ old('fsupplier', $penerimaanbarang->fsupplier ?? '') }}">
            @if (!$readOnly)
                <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                    title="Browse Supplier">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                </button>
                <a href="{{ route('supplier.create') }}" target="_blank" rel="noopener"
                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Supplier">
                    <x-heroicon-o-plus class="w-5 h-5" />
                </a>
            @endif
        </div>
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium mb-1">Gudang</label>
        <div class="flex">
            <div class="relative flex-1">
                <select id="warehouseSelect"
                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed" disabled>
                    <option value=""></option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->fwhcode }}" data-id="{{ $wh->fwhid }}"
                            data-branch="{{ $wh->fbranchcode }}"
                            {{ old('ffrom', $penerimaanbarang->ffrom ?? '') == $wh->fwhcode ? 'selected' : '' }}>
                            {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                        </option>
                    @endforeach
                </select>
                @if (!$readOnly)
                    <div class="absolute inset-0" role="button" aria-label="Browse warehouse"
                        @click="window.dispatchEvent(new CustomEvent('penerimaanbarang-warehouse-browse-open'))"></div>
                @endif
            </div>
            <input type="hidden" name="ffrom" id="warehouseCodeHidden" value="{{ old('ffrom', $penerimaanbarang->ffrom ?? '') }}">
            @if (!$readOnly)
                <button type="button" @click="window.dispatchEvent(new CustomEvent('penerimaanbarang-warehouse-browse-open'))"
                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                    title="Browse Gudang">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                </button>
                <a href="{{ route('gudang.create') }}" target="_blank" rel="noopener"
                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50" title="Tambah Gudang">
                    <x-heroicon-o-plus class="w-5 h-5" />
                </a>
            @endif
        </div>
    </div>

    <div class="lg:col-span-4">
        <label class="block text-sm font-medium">Tanggal</label>
        <input type="date" name="fstockmtdate"
            value="{{ old('fstockmtdate', isset($penerimaanbarang->fstockmtdate) ? \Carbon\Carbon::parse($penerimaanbarang->fstockmtdate)->format('Y-m-d') : date('Y-m-d')) }}"
            class="w-full border rounded px-3 py-2 @error('fstockmtdate') border-red-500 @enderror"
            {{ $readOnly ? 'disabled' : '' }}>
        @error('fstockmtdate')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-12">
        <label class="block text-sm font-medium">Keterangan</label>
        <textarea name="fket" rows="3"
            class="w-full border rounded px-3 py-2 @error('fket') border-red-500 @enderror"
            placeholder="Tulis keterangan tambahan di sini..." {{ $readOnly ? 'disabled' : '' }}>{{ old('fket', $penerimaanbarang->fket ?? '') }}</textarea>
        @error('fket')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
