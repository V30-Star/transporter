@extends('layouts.app')

@section('title', 'Laporan Rekap Penjualan')

@section('content')
    <div class="bg-white rounded shadow p-6 max-w-4xl mx-auto">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Laporan Rekap Penjualan</h3>
        <form method="GET" action="{{ route('reportingrekappenjualan.print') }}" target="_blank" class="space-y-4">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-bold uppercase">Cabang</label>
                    @if ($isAuthorized)
                        <div class="flex gap-2">
                            <button type="button" onclick="toggleBranches(true)" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded">Select All</button>
                            <button type="button" onclick="toggleBranches(false)" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded">Unselect All</button>
                        </div>
                    @endif
                </div>
                <div class="border rounded-lg p-3 bg-gray-50 max-h-40 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($branches as $branch)
                            @php $checked = $isAuthorized || $userBranchCode === $branch->fcabangkode; @endphp
                            <label class="flex items-center text-sm gap-2">
                                @if (!$isAuthorized && $checked)
                                    <input type="hidden" name="branch_codes[]" value="{{ $branch->fcabangkode }}">
                                @endif
                                <input type="checkbox" name="branch_codes[]" value="{{ $branch->fcabangkode }}" class="branch-checkbox" {{ $checked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                <span>{{ $branch->fcabangkode }} - {{ $branch->fcabangname }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase mb-1">Tanggal Dari</label>
                    <input type="date" name="date_from" value="{{ date('Y-m-01') }}" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase mb-1">Sd</label>
                    <input type="date" name="date_to" value="{{ date('Y-m-d') }}" class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase mb-1">Salesman</label>
                <select name="salesman" class="select2 w-full border rounded px-3 py-2">
                    <option value="">-- Semua Salesman --</option>
                    @foreach ($salesmans as $salesman)
                        <option value="{{ $salesman->fsalesmancode }}">{{ $salesman->fsalesmancode }} - {{ $salesman->fsalesmanname }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase mb-1">Group Produk</label>
                <select name="group_code" class="select2 w-full border rounded px-3 py-2">
                    <option value="">-- Semua Group --</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->fgroupcode }}">{{ $group->fgroupcode }} - {{ $group->fgroupname }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase mb-1">Merek</label>
                <select name="merek_code" class="select2 w-full border rounded px-3 py-2">
                    <option value="">-- Semua Merek --</option>
                    @foreach ($mereks as $merek)
                        <option value="{{ $merek->fmastercode }}">{{ $merek->fmastercode }} - {{ $merek->fmastername }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase mb-1">Produk Dari</label>
                    <select name="prd_from" class="select2 w-full border rounded px-3 py-2">
                        <option value="">-- Awal --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->fprdcode }}">{{ $product->fprdcode }} - {{ $product->fprdname }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase mb-1">Sd</label>
                    <select name="prd_to" class="select2 w-full border rounded px-3 py-2">
                        <option value="">-- Akhir --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->fprdcode }}">{{ $product->fprdcode }} - {{ $product->fprdname }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border rounded p-3 bg-gray-50 flex gap-6">
                <label class="flex items-center gap-2 font-semibold text-sm">
                    <input type="radio" name="group_by" value="group" checked>
                    By Group Produk
                </label>
                <label class="flex items-center gap-2 font-semibold text-sm">
                    <input type="radio" name="group_by" value="merek">
                    By Merek
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded font-bold">Cetak</button>
                <a href="{{ route('dashboard') }}" class="px-5 py-2 bg-gray-100 rounded">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function toggleBranches(status) {
            document.querySelectorAll('.branch-checkbox').forEach(checkbox => checkbox.checked = status);
        }
        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
        });
    </script>
@endpush
