@extends('layouts.app')

@section('title', 'Master Type Pembayaran - View')

@section('content')
<div>
    <div class="max-w-4xl mx-auto py-8 px-6">
        {{-- ─── CARD: Identitas Type Pembayaran ────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Type Pembayaran</p>
            </div>

            <div class="p-4 space-y-4">
                {{-- No. Urut --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">No. Urut</label>
                    <input type="text" value="{{ $typePembayaran->fmasternum ?? '-' }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly>
                </div>

                {{-- Nama Type Pembayaran --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Nama Type Pembayaran</label>
                    <input type="text" value="{{ $typePembayaran->fmastername }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly>
                </div>

                {{-- Biaya/Charge (%) --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Biaya/Charge (%)</label>
                    <input type="text" value="{{ number_format((float) ($typePembayaran->fnumvalue ?? 0), 2, ',', '.') }}%"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly>
                </div>

                {{-- Account --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Account</label>
                    <input type="text"
                        value="{{ $typePembayaran->account ? $typePembayaran->account->faccount . ' - ' . $typePembayaran->account->faccname : ($typePembayaran->fnote1 ?: '-') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly>
                </div>
            </div>

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                <button type="button"
                    onclick="window.location.href='{{ route('typepembayaran.index') }}'"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Kembali
                </button>
            </div>
        </div>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $typePembayaran->fdatetime;
            $updatedBy = $typePembayaran->fuserid ?: '—';
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>
    </div>
</div>
@endsection
