@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 border border-gray-100">
            <div class="flex justify-between items-start mb-6 border-b pb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">{{ $pageTitle }}</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ 'Isi periode dalam format YYYYMM. Semua transaksi dengan tanggal sebelum periode ini tidak bisa create/edit/delete sesuai aturan posting periode.' }}
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors ml-4" title="Kembali ke Dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>

            <form action="{{ route('editperiode.update') }}" method="POST" class="space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="fyrmth" class="block text-sm font-medium text-gray-700 mb-2">{{ 'Periode' }}</label>
                    <input type="text" id="fyrmth" name="fyrmth" value="{{ old('fyrmth', $fyrmth) }}"
                        maxlength="6"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 @error('fyrmth') border-red-500 @enderror"
                        placeholder="202601">
                    @error('fyrmth')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        {{ 'Simpan' }}
                    </button>
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-lg bg-gray-100 border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>

            {{-- FOOTER INFO --}}
            @php
                $setini = \Illuminate\Support\Facades\DB::table('setini')->first();
                $lastUpdate = $setini->fupdatedat ?? ($setini->fcreatedat ?? null);
                $updatedBy = $setini->fupdatedby ?? ($setini->fuserupdate ?? ($setini->fcreatedby ?? ($setini->fusercreate ?? '—')));
            @endphp
            <div class="mt-6 pt-4 border-t px-2 flex justify-between items-center text-xs text-gray-400">
                <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
                <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
            </div>
        </div>
    </div>
@endsection
