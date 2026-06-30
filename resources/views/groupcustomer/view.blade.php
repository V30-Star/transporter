@extends('layouts.app')

@section('title', 'View Group Customer')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        {{-- ─── CARD 1: Identitas Group Customer ────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 pt-3 pb-0">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Group Customer</p>
            </div>
            <div class="p-4 space-y-3">

                {{-- Kode & Nama Group (2 kolom) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kode Group</label>
                        <input type="text" value="{{ $groupcustomer->fgroupcode }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Group</label>
                        <input type="text" value="{{ $groupcustomer->fgroupname }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>
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
                <div>
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                        <div>
                            <p class="text-sm text-gray-800">Group customer aktif</p>
                            <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan group customer dari transaksi baru</p>
                        </div>
                        <div class="relative w-9 h-5 duration-200 flex-shrink-0 cursor-not-allowed {{ $groupcustomer->fnonactive == '0' ? 'bg-blue-500/60' : 'bg-gray-200' }}">
                            <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $groupcustomer->fnonactive == '0' ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                <button type="button"
                    onclick="window.location.href='{{ route('groupcustomer.index') }}'"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Kembali
                </button>
            </div>
        </div>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $groupcustomer->fupdatedat ?: $groupcustomer->fcreatedat;
            $updatedBy = $groupcustomer->fupdatedby ?: ($groupcustomer->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

</div>
@endsection
