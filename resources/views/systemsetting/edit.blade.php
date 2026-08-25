@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="max-w-5xl mx-auto pb-10">
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            {{-- Header --}}
            <div class="flex justify-between items-center px-6 py-5 border-b bg-gray-50/50">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-blue-600"></i>
                        {{ $pageTitle }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Kelola data identitas perusahaan, NPWP, penandatangan dokumen (Invoice & PO), dan parameter sistem.
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors" title="Kembali ke Dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>

            @if(session('success'))
                <div class="mx-6 mt-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-green-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('systemsetting.update') }}" method="POST" class="p-6 space-y-8">
                @csrf
                @method('PATCH')

                {{-- 1. ALAMAT & KONTAK PERUSAHAAN --}}
                <div class="border rounded-xl p-5 bg-white shadow-sm space-y-4">
                    <div class="border-b pb-3 flex items-center gap-2 text-blue-900 font-semibold text-base">
                        <i class="fa-solid fa-building text-blue-600"></i>
                        <span>Alamat & Kontak Perusahaan</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label for="falamat1" class="block text-sm font-medium text-gray-700 mb-1">Alamat Kop Surat Baris 1</label>
                            <input type="text" id="falamat1" name="falamat1" value="{{ old('falamat1', $setting->falamat1 ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Alamat Kop Surat Baris 1">
                            @error('falamat1') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="falamat2" class="block text-sm font-medium text-gray-700 mb-1">Alamat Kop Surat Baris 2</label>
                            <input type="text" id="falamat2" name="falamat2" value="{{ old('falamat2', $setting->falamat2 ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Alamat Kop Surat Baris 1">
                            @error('falamat2') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                    </div>
                    <div>
                       <label for="fcity" class="block text-sm font-medium text-gray-700 mb-1">
                            Alamat Singkat <span class="text-gray-500 font-bold">(Khusus untuk Laporan)</span>
                        </label>
                        <input type="text" id="fcity" name="fcity" value="{{ old('fcity', $setting->fcity ?? '') }}"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="Alamat Singkat (Khusus untuk Laporan)">
                        @error('fcity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- 2. NPWP PERUSAHAAN --}}
                <div class="border rounded-xl p-5 bg-white shadow-sm space-y-4">
                    <div class="border-b pb-3 flex items-center gap-2 text-blue-900 font-semibold text-base">
                        <i class="fa-solid fa-file-invoice text-blue-600"></i>
                        <span>Informasi NPWP Perusahaan</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="fnpwp" class="block text-sm font-medium text-gray-700 mb-1">Nomor NPWP</label>
                            <input type="text" id="fnpwp" name="fnpwp" value="{{ old('fnpwp', $setting->fnpwp ?? '') }}"
                                class="w-full md:w-1/2 rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="00.000.000.0-000.000">
                            @error('fnpwp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="falamat1npwp" class="block text-sm font-medium text-gray-700 mb-1">Alamat NPWP 1</label>
                            <input type="text" id="falamat1npwp" name="falamat1npwp" value="{{ old('falamat1npwp', $setting->falamat1npwp ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Alamat sesuai NPWP (baris 1)">
                            @error('falamat1npwp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="falamat2npwp" class="block text-sm font-medium text-gray-700 mb-1">Alamat NPWP 2</label>
                            <input type="text" id="falamat2npwp" name="falamat2npwp" value="{{ old('falamat2npwp', $setting->falamat2npwp ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Alamat sesuai NPWP (baris 2)">
                            @error('falamat2npwp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- 3. NAMA TANDA TANGAN (TTD) --}}
                <div class="border rounded-xl p-5 bg-white shadow-sm space-y-4">
                    <div class="border-b pb-3 flex items-center gap-2 text-blue-900 font-semibold text-base">
                        <i class="fa-solid fa-signature text-blue-600"></i>
                        <span>Nama Penandatangan Dokumen</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="fnamattdfakturpenjualan" class="block text-sm font-medium text-gray-700 mb-1">Nama TTD Invoice 1</label>
                            <input type="text" id="fnamattdfakturpenjualan" name="fnamattdfakturpenjualan" value="{{ old('fnamattdfakturpenjualan', $setting->fnamattdfakturpenjualan ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Nama penandatangan invoice 1">
                            @error('fnamattdfakturpenjualan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="fnamattdfakturpenjualan2" class="block text-sm font-medium text-gray-700 mb-1">Nama TTD Invoice 2</label>
                            <input type="text" id="fnamattdfakturpenjualan2" name="fnamattdfakturpenjualan2" value="{{ old('fnamattdfakturpenjualan2', $setting->fnamattdfakturpenjualan2 ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Nama penandatangan invoice 2 (opsional)">
                            @error('fnamattdfakturpenjualan2') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="fnamattdpo" class="block text-sm font-medium text-gray-700 mb-1">Nama TTD PO 1</label>
                            <input type="text" id="fnamattdpo" name="fnamattdpo" value="{{ old('fnamattdpo', $setting->fnamattdpo ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Nama penandatangan purchase order 1">
                            @error('fnamattdpo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="fnamattdpo2" class="block text-sm font-medium text-gray-700 mb-1">Nama TTD PO 2</label>
                            <input type="text" id="fnamattdpo2" name="fnamattdpo2" value="{{ old('fnamattdpo2', $setting->fnamattdpo2 ?? '') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                placeholder="Nama penandatangan purchase order 2 (opsional)">
                            @error('fnamattdpo2') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- 4. PARAMETER SISTEM TAMBAHAN --}}
                <div class="border rounded-xl p-5 bg-white shadow-sm space-y-4">
                    <div class="border-b pb-3 flex items-center gap-2 text-blue-900 font-semibold text-base">
                        <i class="fa-solid fa-gears text-blue-600"></i>
                        <span>Parameter Sistem Lainnya</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="fppntarif" class="block text-sm font-medium text-gray-700 mb-1">Tarif PPN (%)</label>
                            <input type="number" step="0.01" id="fppntarif" name="fppntarif" value="{{ old('fppntarif', $setting->fppntarif ?? '11.00') }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            @error('fppntarif') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end items-center gap-3 pt-4 border-t">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-lg bg-gray-100 border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan Setting
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
