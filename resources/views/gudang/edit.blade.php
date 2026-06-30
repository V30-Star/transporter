@extends('layouts.app')

@section('title', 'Edit Gudang')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        <form action="{{ route('gudang.update', $gudang->fwhid) }}" method="POST" data-form-draft="true" data-draft-key="gudang:edit">
            @csrf
            @method('PATCH')

            {{-- ─── CARD 1: Identitas Gudang ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Gudang</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Cabang --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Cabang <span class="text-red-500">*</span>
                            </label>
                            <select name="fbranchcode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fbranchcode') border-red-400 @enderror">
                                <option value="">Pilih Cabang</option>
                                @foreach ($cabangOptions as $cabang)
                                    <option value="{{ $cabang->fbranchcode }}" {{ old('fbranchcode', $gudang->fbranchcode) == $cabang->fbranchcode ? 'selected' : '' }}>
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
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Kode Gudang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fwhcode" value="{{ old('fwhcode', $gudang->fwhcode) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fwhcode') border-red-400 @enderror {{ !empty($isTransactionLocked) ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : '' }}"
                                {{ !empty($isTransactionLocked) ? 'readonly' : '' }} autofocus>
                            @if (!empty($isTransactionLocked))
                                <p class="text-[11px] text-amber-600 mt-1 font-medium">Kode gudang dikunci karena sudah direferensi di transaksi.</p>
                            @endif
                            @error('fwhcode')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Nama Gudang --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Nama Gudang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fwhname" value="{{ old('fwhname', $gudang->fwhname) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fwhname') border-red-400 @enderror"
                                placeholder="Masukkan Nama Gudang">
                            @error('fwhname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Alamat --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Alamat <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="faddress" value="{{ old('faddress', $gudang->faddress) }}"
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
                x-data="{ active: {{ old('fnonactive', $gudang->fnonactive) == '1' ? 'false' : 'true' }} }">
                <div class="px-4 pt-3 pb-0">
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

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $gudang->fupdatedat ?: $gudang->fcreatedat;
            $updatedBy = $gudang->fupdatedby ?: ($gudang->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

</div>
@endsection
