@extends('layouts.app')

@section('title', 'View Group Customer')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1800px] w-full mx-auto">
            <div class="space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-bold">Kode Group</label>
                    <input type="text" value="{{ old('fgroupcode', $groupcustomer->fgroupcode) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
                </div>

                <div>
                    <label class="block text-sm font-bold">Nama Group</label>
                    <input type="text" value="{{ old('fgroupname', $groupcustomer->fgroupname) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
                </div>

                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label class="flex items-center justify-between w-40 p-3 border rounded-lg bg-gray-100 font-bold">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" class="h-5 w-5 text-green-600 rounded"
                            {{ old('fnonactive', $groupcustomer->fnonactive) == '1' ? 'checked' : '' }} disabled>
                    </label>
                </div>
            </div>
            <br>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="window.location.href='{{ route('groupcustomer.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
        </div>
    </div>
@endsection
