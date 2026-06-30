@extends('layouts.app')

@section('title', 'Edit - Master Group Product')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        <form action="{{ route('groupproduct.update', $groupproduct->fgroupid) }}" method="POST" id="formGroupproduct">
            @csrf
            @method('PATCH')

            {{-- ─── CARD 1: Identitas Group Product ──────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Group Product</p>
                </div>
                <div class="p-4 space-y-3">

                    {{-- Kode & Nama Group (2 kolom) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Kode Group <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fgroupcode" id="fgroupcode"
                                value="{{ old('fgroupcode', $groupproduct->fgroupcode) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-100 @error('fgroupcode') border-red-400 @enderror {{ $isUsedInTransactions ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'focus:border-blue-500' }}"
                                {{ $isUsedInTransactions ? 'readonly' : '' }}
                                maxlength="10" placeholder="cth. GRP-001" autofocus>
                            @if ($isUsedInTransactions)
                                <p class="text-[11px] text-amber-600 mt-1 font-medium">Kode group tidak bisa diubah karena produk dalam group ini sudah digunakan dalam transaksi.</p>
                            @endif
                            @error('fgroupcode')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Nama Group <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fgroupname" id="fgroupname"
                                value="{{ old('fgroupname', $groupproduct->fgroupname) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fgroupname') border-red-400 @enderror"
                                maxlength="50" placeholder="cth. Group Makanan">
                            @error('fgroupname')
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
                    <div x-data="{ active: {{ old('fnonactive', $groupproduct->fnonactive) == '1' ? 'false' : 'true' }} }">
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                            @click="active = !active; $el.closest('[x-data]').querySelector('input[name=fnonactive]').value = active ? '0' : '1'">
                            <div>
                                <p class="text-sm text-gray-800">Group product aktif</p>
                                <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan group product dari transaksi baru</p>
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
                        onclick="window.location.href='{{ route('groupproduct.index') }}'"
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
            $lastUpdate = $groupproduct->fupdatedat ?: $groupproduct->fcreatedat;
            $updatedBy = $groupproduct->fupdatedby ?: ($groupproduct->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

</div>
@endsection
