@extends('layouts.app')

@section('title', 'View - Master Rekening')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        {{-- ─── CARD 1: Identitas Rekening ───────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Rekening</p>
            </div>
            <div class="p-4 space-y-3">

                {{-- Nama Rekening --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Nama Rekening</label>
                    <textarea id="frekeningname" rows="3" readonly
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                        placeholder="Tidak ada nama rekening">{{ $rekening->frekeningname }}</textarea>
                </div>

            </div>
        </div>

        {{-- ─── CARD 2: Status ────────────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Status</p>
            </div>
            <div class="p-4 space-y-4">

                {{-- Status Aktif --}}
                <div>
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                        <div>
                            <p class="text-sm text-gray-800">Rekening aktif</p>
                            <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan rekening dari transaksi baru</p>
                        </div>
                        <div class="relative w-9 h-5 rounded-full duration-200 flex-shrink-0 cursor-not-allowed {{ $rekening->fnonactive == '0' ? 'bg-blue-500/60' : 'bg-gray-200' }}">
                            <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $rekening->fnonactive == '0' ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                <button type="button"
                    onclick="window.location.href='{{ route('rekening.index') }}'"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Kembali
                </button>
            </div>
        </div>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $rekening->fupdatedat ?: $rekening->fcreatedat;
            $updatedBy = $rekening->fupdatedby ?: ($rekening->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

</div>
@endsection
