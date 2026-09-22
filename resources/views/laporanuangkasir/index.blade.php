@extends('layouts.app')

@section('title', 'Laporan Uang Kasir')

@section('content')
    <div id="filterModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Laporan Uang Kasir</h3>
                    <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</a>
                </div>

                <form method="GET" action="{{ route('laporanuangkasir.print') }}" target="_blank">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-xs font-bold uppercase">Cabang</label>
                                @if ($isAuthorized)
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selectBranches(true)" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded">Select All</button>
                                        <button type="button" onclick="selectBranches(false)" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded">Unselect All</button>
                                    </div>
                                @endif
                            </div>
                            <div class="border rounded p-3 bg-gray-50 max-h-40 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($branches as $branch)
                                        @php($checked = $isAuthorized || $userBranchCode === $branch->fcabangkode)
                                        <label class="flex items-center text-sm">
                                            @if (!$isAuthorized && $checked)
                                                <input type="hidden" name="branch_codes[]" value="{{ $branch->fcabangkode }}">
                                            @endif
                                            <input type="checkbox" name="branch_codes[]" value="{{ $branch->fcabangkode }}" class="branch-checkbox mr-2" {{ $checked ? 'checked' : '' }} {{ !$isAuthorized ? 'disabled' : '' }}>
                                            <span>{{ $branch->fcabangkode }} - {{ $branch->fcabangname }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Tanggal</label>
                            <input type="date" name="date" value="{{ $date }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase mb-1">Kasir</label>
                            <input type="text" name="kasir" class="w-full border rounded px-3 py-2 text-sm" placeholder="fuserid">
                        </div>

                        <label class="flex items-center text-sm font-semibold bg-gray-50 p-3 rounded border">
                            <input type="checkbox" name="only_cash" value="1" class="mr-2 w-4 h-4">
                            Hanya Uang Tunai Saja
                        </label>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <a href="{{ route('dashboard') }}" class="px-5 py-2 bg-gray-100 text-gray-600 rounded-lg">Batal</a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg">Cetak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function selectBranches(checked) {
            document.querySelectorAll('.branch-checkbox').forEach((checkbox) => checkbox.checked = checked);
        }
    </script>
@endsection
