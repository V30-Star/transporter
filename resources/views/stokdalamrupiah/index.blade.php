@extends('layouts.app')

@section('title', 'Stok Dalam Rupiah')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="window.location.href='{{ route('dashboard') }}'"></div>
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 m-4 z-10 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-coins text-blue-600 text-xl"></i>
                    <h3 class="text-xl font-bold text-gray-800">Laporan Stok Dalam Rupiah</h3>
                </div>
                <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                    class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="stokDalamRupiahForm" method="GET" action="{{ route('stokdalamrupiah.print') }}" target="_blank">
                <div class="space-y-4">
                    {{-- Per Tanggal --}}
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">
                            Per Tanggal <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="report_date" id="report_date"
                            value="{{ old('report_date', date('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    {{-- Gudang --}}
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Gudang</label>
                        <select name="warehouse" id="warehouse" class="select2 w-full">
                            <option value="">-- Semua Gudang --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->fwhcode }}" {{ old('warehouse') == $wh->fwhcode ? 'selected' : '' }}>
                                    {{ $wh->fwhcode }} - {{ $wh->fwhname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Group Produk & Merek --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Group Produk</label>
                            <select name="group_code" id="group_code" class="select2 w-full">
                                <option value="">-- Semua Group --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupcode }}" {{ old('group_code') == $group->fgroupcode ? 'selected' : '' }}>
                                        {{ $group->fgroupcode }} - {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Merek</label>
                            <select name="merek" id="merek" class="select2 w-full">
                                <option value="">-- Semua Merek --</option>
                                @foreach ($mereks as $m)
                                    <option value="{{ $m->fmerekcode }}" {{ old('merek') == $m->fmerekcode ? 'selected' : '' }}>
                                        {{ $m->fmerekcode }} - {{ $m->fmerekname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Produk Dari & s/d --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Produk Dari</label>
                            <select name="product_from" id="product_from" class="select2 w-full">
                                <option value="">-- Awal --</option>
                                @foreach ($products as $prd)
                                    <option value="{{ $prd->fprdcode }}" {{ old('product_from') == $prd->fprdcode ? 'selected' : '' }}>
                                        {{ $prd->fprdcode }} - {{ $prd->fprdname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Produk s/d</label>
                            <select name="product_to" id="product_to" class="select2 w-full">
                                <option value="">-- Akhir --</option>
                                @foreach ($products as $prd)
                                    <option value="{{ $prd->fprdcode }}" {{ old('product_to') == $prd->fprdcode ? 'selected' : '' }}>
                                        {{ $prd->fprdcode }} - {{ $prd->fprdname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 mt-6 pt-4 border-t">
                    <button type="button" onclick="window.location.href='{{ route('dashboard') }}'"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Keluar
                    </button>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-print"></i> Preview / Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });
            $('#product_from').on('change', function() {
                $('#product_to').val($(this).val()).trigger('change');
            });
        });
    </script>
@endsection
