@extends('layouts.app')

@section('title', 'New - Master Satuan')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        <form action="{{ route('satuan.store') }}" method="POST" id="formSatuan">
            @csrf

            {{-- ─── CARD 1: Identitas Satuan ────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Satuan</p>
                </div>
                <div class="p-4 space-y-3">

                    {{-- Kode & Nama Satuan (2 kolom) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Kode Satuan <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fsatuancode" id="fsatuancode"
                                value="{{ old('fsatuancode') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fsatuancode') border-red-400 @enderror"
                                maxlength="10" placeholder="cth. PCS" autofocus>
                            @error('fsatuancode')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Nama Satuan <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fsatuanname" id="fsatuanname"
                                value="{{ old('fsatuanname') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fsatuanname') border-red-400 @enderror"
                                maxlength="50" placeholder="cth. PCS / PIECE">
                            @error('fsatuanname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── CARD 2: Status ────────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Status</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Status Aktif --}}
                    <div x-data="{ active: {{ old('fnonactive') == '1' ? 'false' : 'true' }} }">
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                            @click="active = !active; $el.closest('[x-data]').querySelector('input[name=fnonactive]').value = active ? '0' : '1'">
                            <div>
                                <p class="text-sm text-gray-800">Satuan aktif</p>
                                <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan satuan dari transaksi baru</p>
                            </div>
                            <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                :class="active ? 'bg-blue-500' : 'bg-gray-300'">
                                <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                    :class="active ? 'translate-x-4 left-0.5' : 'left-0.5'"></div>
                            </div>
                        </div>
                        <input type="hidden" name="fnonactive" :value="active ? '0' : '1'">
                    </div>

                </div>

                {{-- Footer Buttons --}}
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <button type="button"
                        onclick="window.location.href='{{ route('satuan.index') }}'"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Kembali
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Simpan
                    </button>
                </div>
            </div>

        </form>
    </div>

</div>
@endsection
