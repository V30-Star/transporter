@extends('layouts.app')

@section('title', 'Edit - Master Type Pembayaran')

@section('content')
<div>
    <div class="max-w-4xl mx-auto py-8 px-6">
        <form action="{{ route('typepembayaran.update', $typePembayaran->ftypepembayaranid) }}" method="POST" id="formTypePembayaran">
            @csrf
            @method('PATCH')

            {{-- ─── CARD: Identitas Type Pembayaran ────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden shadow-sm">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Type Pembayaran</p>
                </div>

                <div class="p-4 space-y-4">
                    {{-- Kode & Nama (2 kolom) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Kode Type Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="ftypepembayarankode" id="ftypepembayarankode"
                                value="{{ old('ftypepembayarankode', $typePembayaran->ftypepembayarankode) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftypepembayarankode') border-red-400 @enderror"
                                maxlength="10" placeholder="cth. CASH, BCA, QRIS">
                            @error('ftypepembayarankode')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Nama Type Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="ftypepembayaranname" id="ftypepembayaranname"
                                value="{{ old('ftypepembayaranname', $typePembayaran->ftypepembayaranname) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftypepembayaranname') border-red-400 @enderror"
                                maxlength="50" placeholder="cth. TUNAI / KAS, TRANSFER BCA" autofocus>
                            @error('ftypepembayaranname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Account Kas/Bank --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">
                            Account Kas/Bank <span class="text-red-500">*</span>
                        </label>
                        <select name="faccount" id="faccount"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-white @error('faccount') border-red-400 @enderror">
                            <option value="">-- Pilih Account Kas/Bank --</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->faccount }}" {{ old('faccount', $typePembayaran->faccount) == $acc->faccount ? 'selected' : '' }}>
                                    {{ $acc->faccount }} - {{ $acc->faccname }}
                                </option>
                            @endforeach
                        </select>
                        @error('faccount')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
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
            $lastUpdate = $typePembayaran->fupdatedat ?: $typePembayaran->fcreatedat;
            $updatedBy = $typePembayaran->fupdatedby ?: ($typePembayaran->fcreateby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('formTypePembayaran')?.addEventListener('submit', function() {
        sessionStorage.setItem('app.pendingSuccessMessage', 'Type Pembayaran berhasil diupdate.');
    });
</script>
@endpush
