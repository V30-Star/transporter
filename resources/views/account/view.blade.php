@extends('layouts.app')

@section('title', 'View - Master Account')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        {{-- ─── CARD 1: Identitas Akun ─────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 pt-3 pb-0">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas akun</p>
            </div>
            <div class="p-4 space-y-3">

                {{-- Account Header --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Account Header</label>
                    <input type="text" id="headerDisplay"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly
                        value="{{ $account->faccupline ? ($selectedHeader ? $selectedHeader->faccount . ' — ' . $selectedHeader->faccname : $account->faccupline) : '—' }}">
                </div>

                {{-- Kode & Nama Account (2 kolom) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kode Account</label>
                        <input type="text" value="{{ $account->faccount }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Account</label>
                        <input type="text" value="{{ $account->faccname }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── CARD 2: Konfigurasi ────────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 pt-3 pb-0">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Konfigurasi</p>
            </div>
            <div class="p-4 space-y-4">

                {{-- Saldo Normal --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Saldo Normal</label>
                    <div class="flex gap-2">
                        <button type="button" disabled
                            class="px-4 py-1.5 rounded-full text-xs border cursor-not-allowed {{ $account->fnormal === 'D' ? 'bg-blue-50 border-blue-200 text-blue-600 font-medium' : 'bg-gray-50 border-gray-200 text-gray-400' }}">Debit</button>
                        <button type="button" disabled
                            class="px-4 py-1.5 rounded-full text-xs border cursor-not-allowed {{ $account->fnormal === 'K' ? 'bg-blue-50 border-blue-200 text-blue-600 font-medium' : 'bg-gray-50 border-gray-200 text-gray-400' }}">Kredit</button>
                    </div>
                </div>

                {{-- Type Account --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Type Account</label>
                    <div class="flex gap-2">
                        <button type="button" disabled
                            class="px-4 py-1.5 rounded-full text-xs border cursor-not-allowed {{ $account->fend == '1' ? 'bg-blue-50 border-blue-200 text-blue-600 font-medium' : 'bg-gray-50 border-gray-200 text-gray-400' }}">Detil</button>
                        <button type="button" disabled
                            class="px-4 py-1.5 rounded-full text-xs border cursor-not-allowed {{ $account->fend == '0' ? 'bg-blue-50 border-blue-200 text-blue-600 font-medium' : 'bg-gray-50 border-gray-200 text-gray-400' }}">Header</button>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Sub Account Toggle --}}
                <div>
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                        <div>
                            <p class="text-sm text-gray-800">Ada Sub Account?</p>
                            <p class="text-xs text-gray-400 mt-0.5">Aktifkan jika akun ini memiliki turunan</p>
                        </div>
                        <div class="relative w-9 h-5 rounded-full duration-200 flex-shrink-0 cursor-not-allowed {{ $account->fhavesubaccount ? 'bg-blue-500/60' : 'bg-gray-200' }}">
                            <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $account->fhavesubaccount ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                        </div>
                    </div>

                    @if($account->fhavesubaccount)
                    @php
                        $subType = 'Sub Account';
                        if (($account->ftypesubaccount ?? '') === 'C') {
                            $subType = 'Customer';
                        } elseif (($account->ftypesubaccount ?? '') === 'P') {
                            $subType = 'Supplier';
                        }
                    @endphp
                    <div class="mt-2 pl-1">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Type Sub Account</label>
                        <div class="flex gap-2 flex-wrap">
                            @foreach (['Sub Account', 'Customer', 'Supplier'] as $opt)
                            <button type="button" disabled
                                class="px-4 py-1.5 rounded-full text-xs border cursor-not-allowed {{ $subType === $opt ? 'bg-blue-50 border-blue-200 text-blue-600 font-medium' : 'bg-gray-50 border-gray-200 text-gray-400' }}">{{ $opt }}</button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <hr class="border-gray-100">

                {{-- Initial Jurnal --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Initial Jurnal</label>
                    <input type="text" value="{{ $account->finitjurnal ?: '—' }}"
                        class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase text-center tracking-widest bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly>
                </div>
            </div>
        </div>

        {{-- ─── CARD 3: Akses & Status ─────────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 pt-3 pb-0">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Akses & status</p>
            </div>
            <div class="p-4 space-y-4">

                {{-- User Level --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">User Level</label>
                    <div class="flex gap-2">
                        @foreach (['1' => 'User', '2' => 'Supervisor', '3' => 'Admin'] as $k => $label)
                        <button type="button" disabled
                            class="px-4 py-1.5 rounded-full text-xs border cursor-not-allowed {{ $account->fuserlevel == $k ? 'bg-blue-50 border-blue-200 text-blue-600 font-medium' : 'bg-gray-50 border-gray-200 text-gray-400' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Status Aktif --}}
                <div>
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                        <div>
                            <p class="text-sm text-gray-800">Akun aktif</p>
                            <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan akun dari transaksi baru</p>
                        </div>
                        <div class="relative w-9 h-5 rounded-full duration-200 flex-shrink-0 cursor-not-allowed {{ $account->fnonactive == '0' ? 'bg-blue-500/60' : 'bg-gray-200' }}">
                            <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $account->fnonactive == '0' ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                <button type="button"
                    onclick="window.location.href='{{ route('account.index') }}'"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Kembali
                </button>
            </div>
        </div>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $account->fupdatedat ?: $account->fcreatedat;
            $updatedBy = $account->fupdatedby ?: ($account->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

</div>
@endsection
