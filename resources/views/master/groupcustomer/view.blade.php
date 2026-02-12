@extends('layouts.app')

@section('title', 'View Group Customer')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-2xl mx-auto">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Group Customer</label>
                    <input type="text" value="{{ $groupcustomer->fgroupcode }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Group Customer</label>
                    <input type="text" value="{{ $groupcustomer->fgroupname }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
                </div>

                <div class="flex justify-center mt-4">
                    <label class="flex items-center justify-between w-40 p-3 border rounded-lg bg-gray-100">
                        <span class="text-sm font-medium">Non Active</span>
                        <input type="checkbox" class="h-5 w-5 text-green-600 rounded"
                            {{ $groupcustomer->fnonactive == '1' ? 'checked' : '' }} disabled>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="showDeleteModal()"
                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Hapus
                </button>
                <button type="button" onclick="window.location.href='{{ route('groupcustomer.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
@endsection

<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>
