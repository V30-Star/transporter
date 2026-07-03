@extends('layouts.app')

@section('title', 'New - Master Gudang')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        <form action="{{ route('gudang.store') }}" method="POST" data-form-draft="true" data-draft-key="gudang:create">
            @csrf

            {{-- ─── CARD 1: Identitas Gudang ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                 <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Gudang</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Cabang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Cabang <span class="text-red-500">*</span>
                            </label>
                            <select name="fbranchcode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fbranchcode') border-red-400 @enderror">
                                <option value="">Pilih Cabang</option>
                                @foreach ($cabangOptions as $cabang)
                                    <option value="{{ $cabang->fbranchcode }}" {{ old('fbranchcode') == $cabang->fbranchcode ? 'selected' : '' }}>
                                        {{ $cabang->fcabangname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fbranchcode')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Kode Gudang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Kode Gudang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fwhcode" value="{{ old('fwhcode') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fwhcode') border-red-400 @enderror"
                                placeholder="Masukkan Kode Gudang" autofocus>
                            @error('fwhcode')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Nama Gudang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Nama Gudang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fwhname" value="{{ old('fwhname') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fwhname') border-red-400 @enderror"
                                placeholder="Masukkan Nama Gudang">
                            @error('fwhname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Alamat --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Alamat <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="faddress" value="{{ old('faddress') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('faddress') border-red-400 @enderror"
                                placeholder="Masukkan Alamat Gudang">
                            @error('faddress')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── CARD 2: Status ────────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden"
                x-data="{ active: {{ old('fnonactive') == '1' ? 'false' : 'true' }} }">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Status</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Status Aktif --}}
                    <div>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                            @click="active = !active; $el.querySelector('input[name=fnonactive]').value = active ? '0' : '1'">
                            <div>
                                <p class="text-sm text-gray-800">Gudang aktif</p>
                                <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan gudang dari transaksi baru</p>
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
                        onclick="window.location.href='{{ route('gudang.index') }}'"
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
