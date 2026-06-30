@extends('layouts.app')

@section('title', 'Master Customer')

@section('content')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>

<div>

    <div class="max-w-4xl mx-auto py-8 px-6" x-data="{ showModal: false, selectedAlamat: 'alamatsurat', frekening: '' }">

        <form id="customerForm"
            @submit.prevent="
                frekening = $el.querySelector('#frekening').value;
                if (!frekening) {
                    showModal = true;
                } else {
                    $el.submit();
                }"
            action="{{ route('customer.store') }}" method="POST">
            @csrf

            {{-- ─── CARD 1: Identitas Customer ────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden" x-data="{ autoCode: true }">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas Customer</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Kode Customer --}}
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Kode Customer
                                </label>
                                <input type="text" name="fcustomercode" id="fcustomercode"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    placeholder="Masukkan Kode Customer" :disabled="autoCode"
                                    :value="autoCode ? '' : '{{ old('fcustomercode') }}'"
                                    :class="autoCode ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : 'bg-white'">
                            </div>
                            <label class="inline-flex items-center mt-5 font-medium text-xs text-gray-700 cursor-pointer">
                                <input type="checkbox" x-model="autoCode" class="form-checkbox h-4 w-4 rounded text-blue-600 border-gray-300 focus:ring-blue-100">
                                <span class="ml-1.5">Auto</span>
                            </label>
                        </div>

                        {{-- Nama Customer --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Nama Customer <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fcustomername" id="fcustomername"
                                value="{{ old('fcustomername') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fcustomername') border-red-400 @enderror"
                                placeholder="Masukkan Nama Customer" autofocus>
                            @error('fcustomername')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Group Customer --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Group Customer</label>
                            <select name="fgroup" id="groupSelect" class="w-full">
                                <option value="">-- Pilih Group Customer --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupid }}" {{ old('fgroup') == $group->fgroupid ? 'selected' : '' }}>
                                        {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Set Harga --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Set Harga</label>
                            <select name="fhargalevel" id="fhargalevel" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="0" {{ old('fhargalevel', 0) == 0 ? 'selected' : '' }}>Harga Level 1</option>
                                <option value="1" {{ old('fhargalevel') == 1 ? 'selected' : '' }}>Harga Level 2</option>
                                <option value="2" {{ old('fhargalevel') == 2 ? 'selected' : '' }}>Harga Level 3</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Salesman --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Salesman</label>
                            <select name="fsalesman" id="salesmanSelect" class="w-full">
                                <option value="">-- Pilih Salesman --</option>
                                @foreach ($salesman as $sales)
                                    <option value="{{ $sales->fsalesmancode }}" {{ old('fsalesman') == $sales->fsalesmancode ? 'selected' : '' }}>
                                        {{ $sales->fsalesmanname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Wilayah --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Wilayah</label>
                            <select name="fwilayah" id="wilayahSelect" class="w-full">
                                <option value="">-- Pilih Wilayah --</option>
                                @foreach ($wilayah as $wil)
                                    <option value="{{ $wil->fwilayahid }}" {{ old('fwilayah') == $wil->fwilayahid ? 'selected' : '' }}>
                                        {{ $wil->fwilayahname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── CARD 2: Perpajakan & Identitas ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Perpajakan & Identitas</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- NPWP --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">NPWP</label>
                            <input type="text" name="fnpwp" id="fnpwp" value="{{ old('fnpwp') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fnpwp') border-red-400 @enderror"
                                placeholder="Masukkan NPWP">
                            @error('fnpwp')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- NIK --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">No. NIK</label>
                            <input type="text" name="fnik" id="fnik" value="{{ old('fnik') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fnik') border-red-400 @enderror"
                                placeholder="Masukkan NIK">
                            @error('fnik')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Kode Faktur Pajak --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kode Faktur Pajak</label>
                        <input type="text" name="fkodefp" id="fkodefp" value="{{ old('fkodefp', '010') }}" maxlength="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fkodefp') border-red-400 @enderror"
                            placeholder="010">
                        @error('fkodefp')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- ─── CARD 3: Kontak & Alamat ─────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Kontak & Alamat</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {{-- Telp --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Telp</label>
                            <input type="text" name="ftelp" id="ftelp" value="{{ old('ftelp') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftelp') border-red-400 @enderror"
                                placeholder="Masukkan Nomor Telepon">
                            @error('ftelp')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Fax --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fax</label>
                            <input type="text" name="ffax" id="ffax" value="{{ old('ffax') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ffax') border-red-400 @enderror"
                                placeholder="Masukkan Nomor Fax">
                            @error('ffax')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" name="femail" id="femail" value="{{ old('femail') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('femail') border-red-400 @enderror"
                                placeholder="Masukkan Email">
                            @error('femail')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Kontak Person --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Kontak Person</label>
                            <input type="text" name="fkontakperson" id="fkontakperson" value="{{ old('fkontakperson') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fkontakperson') border-red-400 @enderror"
                                placeholder="Nama Kontak Person">
                            @error('fkontakperson')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Jabatan --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jabatan</label>
                            <input type="text" name="fjabatan" id="fjabatan" value="{{ old('fjabatan') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fjabatan') border-red-400 @enderror"
                                placeholder="Jabatan">
                            @error('fjabatan')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Alamat Tabs --}}
                    <div class="mt-4 border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Alamat Lengkap</label>
                        <div class="flex space-x-1.5 mb-3">
                            <button type="button" @click="selectedAlamat = 'alamatsurat'"
                                :class="selectedAlamat === 'alamatsurat' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors">
                                Alamat Surat
                            </button>
                            <button type="button" @click="selectedAlamat = 'alamatkirim'"
                                :class="selectedAlamat === 'alamatkirim' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors">
                                Alamat Kirim
                            </button>
                            <button type="button" @click="selectedAlamat = 'alamatpajak'"
                                :class="selectedAlamat === 'alamatpajak' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors">
                                Alamat Pajak
                            </button>
                        </div>

                        <div x-show="selectedAlamat === 'alamatsurat'" class="space-y-2">
                            <textarea name="faddress" id="faddress" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan Alamat Surat">{{ old('faddress') }}</textarea>
                        </div>

                        <div x-show="selectedAlamat === 'alamatkirim'" class="space-y-2">
                            <textarea name="fkirimaddress1" id="fkirimaddress1" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan Alamat Kirim 1">{{ old('fkirimaddress1') }}</textarea>
                            <textarea name="fkirimaddress2" id="fkirimaddress2" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan Alamat Kirim 2">{{ old('fkirimaddress2') }}</textarea>
                            <textarea name="fkirimaddress3" id="fkirimaddress3" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan Alamat Kirim 3">{{ old('fkirimaddress3') }}</textarea>
                        </div>

                        <div x-show="selectedAlamat === 'alamatpajak'" class="space-y-2">
                            <textarea name="ftaxaddress" id="ftaxaddress" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan Alamat Pajak">{{ old('ftaxaddress') }}</textarea>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── CARD 4: Kredit & Pembayaran ──────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Kredit & Pembayaran</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {{-- Jatuh Tempo --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jatuh Tempo (Hari)</label>
                            <input type="number" name="ftempo" id="ftempo" value="{{ old('ftempo', 0) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftempo') border-red-400 @enderror">
                            @error('ftempo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Max JT Tempo --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Max JT Tempo</label>
                            <input type="number" name="fmaxtempo" id="fmaxtempo" value="{{ old('fmaxtempo', 0) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fmaxtempo') border-red-400 @enderror">
                            @error('fmaxtempo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Limit Piutang --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Limit Piutang</label>
                            <input type="text" name="flimit" id="flimit" value="{{ old('flimit') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('flimit') border-red-400 @enderror"
                                placeholder="Masukkan Limit Piutang">
                            @error('flimit')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Jadwal Tukar Faktur --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jadwal Tukar Faktur</label>
                            <select name="fjadwaltukarfakturmingguan" id="fjadwaltukarfakturmingguan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="1" {{ old('fjadwaltukarfakturmingguan') == '1' ? 'selected' : '' }}>Setiap Minggu</option>
                                <option value="2" {{ old('fjadwaltukarfakturmingguan') == '2' ? 'selected' : '' }}>Minggu Ganjil</option>
                                <option value="3" {{ old('fjadwaltukarfakturmingguan') == '3' ? 'selected' : '' }}>Minggu Genap</option>
                            </select>
                        </div>

                        {{-- Hari Tukar Faktur --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Hari Tukar Faktur</label>
                            <select name="fjadwaltukarfakturhari" id="fjadwaltukarfakturhari" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                <option value="">-- Pilih Hari --</option>
                                <option value="1" {{ old('fjadwaltukarfakturhari') == '1' ? 'selected' : '' }}>Senin</option>
                                <option value="2" {{ old('fjadwaltukarfakturhari') == '2' ? 'selected' : '' }}>Selasa</option>
                                <option value="3" {{ old('fjadwaltukarfakturhari') == '3' ? 'selected' : '' }}>Rabu</option>
                                <option value="4" {{ old('fjadwaltukarfakturhari') == '4' ? 'selected' : '' }}>Kamis</option>
                                <option value="5" {{ old('fjadwaltukarfakturhari') == '5' ? 'selected' : '' }}>Jumat</option>
                                <option value="6" {{ old('fjadwaltukarfakturhari') == '6' ? 'selected' : '' }}>Sabtu</option>
                                <option value="7" {{ old('fjadwaltukarfakturhari') == '7' ? 'selected' : '' }}>Minggu</option>
                            </select>
                        </div>
                    </div>

                    {{-- Kode Rekening --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kode Rekening</label>
                        <select name="frekening" id="frekening" class="w-full">
                            <option value="">Pilih Kode Rekening</option>
                            @foreach ($rekening as $rek)
                                <option value="{{ $rek->frekeningid }}" {{ old('frekening') == $rek->frekeningid ? 'selected' : '' }}>
                                    {{ $rek->frekeningname }}
                                </option>
                            @endforeach
                        </select>
                        @error('frekening')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- ─── CARD 5: Memo & Status ───────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Memo & Status</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Memo --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Memo</label>
                        <textarea name="fmemo" id="fmemo" rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Masukkan Memo">{{ old('fmemo') }}</textarea>
                    </div>

                    {{-- Toggles --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Status Blokir --}}
                        <div x-data="{ blocked: {{ old('fblokir') == '1' ? 'true' : 'false' }} }">
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                                @click="blocked = !blocked; $el.closest('[x-data]').querySelector('input[name=fblokir]').value = blocked ? '1' : '0'">
                                <div>
                                    <p class="text-sm text-gray-800">Blokir Customer</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Mencegah customer dari membuat transaksi baru</p>
                                </div>
                                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                    :class="blocked ? 'bg-red-500' : 'bg-gray-300'">
                                    <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                        :class="blocked ? 'translate-x-4 left-0.5' : 'left-0.5'"></div>
                                </div>
                            </div>
                            <input type="hidden" name="fblokir" :value="blocked ? '1' : '0'">
                        </div>

                        {{-- Status Aktif --}}
                        <div x-data="{ active: {{ old('fnonactive') == '1' ? 'false' : 'true' }} }">
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                                @click="active = !active; $el.closest('[x-data]').querySelector('input[name=fnonactive]').value = active ? '0' : '1'">
                                <div>
                                    <p class="text-sm text-gray-800">Customer aktif</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan customer dari daftar aktif</p>
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

                </div>

                {{-- Footer Buttons --}}
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <button type="button"
                        onclick="window.location.href='{{ route('customer.index') }}'"
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

        {{-- modal --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Rekening Kosong</h3>
                <p class="text-gray-600 mb-6">
                    Anda belum memilih rekening. Apakah yakin ingin menyimpan data tanpa rekening?
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium">
                        Tidak
                    </button>
                    <button type="button" @click="showModal = false; document.getElementById('customerForm').submit()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Ya, Simpan
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#groupSelect, #salesmanSelect, #wilayahSelect, #frekening').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || '-- Pilih --';
            },
            dropdownAutoWidth: true
        });
        $(function() {
            const $inp = $("#fcustomername");
            let lastXHR = null;
            const localCache = {};

            $inp.autocomplete({
                source: function(request, response) {
                    const term = request.term || "";

                    if (localCache[term]) {
                        response(localCache[term]);
                        return;
                    }

                    if (lastXHR && lastXHR.readyState !== 4) lastXHR.abort();

                    lastXHR = $.getJSON("{{ route('customer.name.suggest') }}", {
                        term
                    }, function(data) {
                        localCache[term] = data;
                        response(data);
                    });
                },

                minLength: 0,
                delay: 400,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    return false;
                },
                open: function() {
                    $(".ui-autocomplete").css("width", $inp.outerWidth());
                }
            });

            $inp.on("focus", function() {
                if (!$(".ui-autocomplete:visible").length) {
                    $(this).autocomplete("search", $(this).val() || "");
                }
            });

            $inp.on("keydown", function(e) {
                if (e.key === "ArrowDown" && !$(".ui-autocomplete:visible").length) {
                    $(this).autocomplete("search", $(this).val() || "");
                }
            });
        });
    });
</script>
@endsection
