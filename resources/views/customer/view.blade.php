@extends('layouts.app')

@section('title', 'View Customer')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6" x-data="{ selectedAlamat: 'alamatsurat' }">

        {{-- ─── CARD 1: Identitas Customer ────────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden" x-data="{ autoCode: {{ $customer->fcustomercode ? 'true' : 'false' }} }">
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
                            <input type="text" value="{{ $customer->fcustomercode }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                                readonly>
                        </div>
                        <label class="inline-flex items-center mt-5 font-medium text-xs text-gray-400 cursor-not-allowed">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox h-4 w-4 rounded text-blue-500/60 border-gray-300 cursor-not-allowed" disabled>
                            <span class="ml-1.5">Auto</span>
                        </label>
                    </div>

                    {{-- Nama Customer --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Customer</label>
                        <input type="text" value="{{ $customer->fcustomername }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Group Customer --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Group Customer</label>
                        <select name="fgroup" id="groupSelect" class="w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                            <option value="">-- Pilih Group Customer --</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->fgroupid }}" {{ $customer->fgroup == $group->fgroupid ? 'selected' : '' }}>
                                    {{ $group->fgroupname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Set Harga --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Set Harga</label>
                        <select name="fhargalevel" id="fhargalevel" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                            <option value="0" {{ $customer->fhargalevel == 0 ? 'selected' : '' }}>Harga Level 1</option>
                            <option value="1" {{ $customer->fhargalevel == 1 ? 'selected' : '' }}>Harga Level 2</option>
                            <option value="2" {{ $customer->fhargalevel == 2 ? 'selected' : '' }}>Harga Level 3</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Salesman --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Salesman</label>
                        <select name="fsalesman" id="salesmanSelect" class="w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                            <option value="">-- Pilih Salesman --</option>
                            @foreach ($salesman as $sales)
                                <option value="{{ $sales->fsalesmancode }}" {{ $customer->fsalesman == $sales->fsalesmancode ? 'selected' : '' }}>
                                    {{ $sales->fsalesmanname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Wilayah --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Wilayah</label>
                        <select name="fwilayah" id="wilayahSelect" class="w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                            <option value="">-- Pilih Wilayah --</option>
                            @foreach ($wilayah as $wil)
                                <option value="{{ $wil->fwilayahid }}" {{ $customer->fwilayah == $wil->fwilayahid ? 'selected' : '' }}>
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
                        <input type="text" value="{{ $customer->fnpwp }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- NIK --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">No. NIK</label>
                        <input type="text" value="{{ $customer->fnik }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>
                </div>

                {{-- Kode Faktur Pajak --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kode Faktur Pajak</label>
                    <input type="text" value="{{ $customer->fkodefp }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                        readonly>
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
                        <input type="text" value="{{ $customer->ftelp }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- Fax --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fax</label>
                        <input type="text" value="{{ $customer->ffax }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input type="email" value="{{ $customer->femail }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Kontak Person --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kontak Person</label>
                        <input type="text" value="{{ $customer->fkontakperson }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- Jabatan --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jabatan</label>
                        <input type="text" value="{{ $customer->fjabatan }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
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
                        <textarea rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>{{ $customer->faddress }}</textarea>
                    </div>

                    <div x-show="selectedAlamat === 'alamatkirim'" class="space-y-2">
                        <textarea rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>{{ $customer->fkirimaddress1 }}</textarea>
                        <textarea rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>{{ $customer->fkirimaddress2 }}</textarea>
                        <textarea rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>{{ $customer->fkirimaddress3 }}</textarea>
                    </div>

                    <div x-show="selectedAlamat === 'alamatpajak'" class="space-y-2">
                        <textarea rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                            readonly>{{ $customer->ftaxaddress }}</textarea>
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
                        <input type="number" value="{{ $customer->ftempo }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- Max JT Tempo --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Max JT Tempo</label>
                        <input type="number" value="{{ $customer->fmaxtempo }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- Limit Piutang --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Limit Piutang</label>
                        <input type="text" value="{{ $customer->flimit }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Jadwal Tukar Faktur --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jadwal Tukar Faktur</label>
                        <select name="fjadwaltukarfakturmingguan" id="fjadwaltukarfakturmingguan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                            <option value="1" {{ $customer->fjadwaltukarfakturmingguan == '1' ? 'selected' : '' }}>Setiap Minggu</option>
                            <option value="2" {{ $customer->fjadwaltukarfakturmingguan == '2' ? 'selected' : '' }}>Minggu Ganjil</option>
                            <option value="3" {{ $customer->fjadwaltukarfakturmingguan == '3' ? 'selected' : '' }}>Minggu Genap</option>
                        </select>
                    </div>

                    {{-- Hari Tukar Faktur --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hari Tukar Faktur</label>
                        <select name="fjadwaltukarfakturhari" id="fjadwaltukarfakturhari" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                            <option value="">-- Pilih Hari --</option>
                            <option value="1" {{ $customer->fjadwaltukarfakturhari == '1' ? 'selected' : '' }}>Senin</option>
                            <option value="2" {{ $customer->fjadwaltukarfakturhari == '2' ? 'selected' : '' }}>Selasa</option>
                            <option value="3" {{ $customer->fjadwaltukarfakturhari == '3' ? 'selected' : '' }}>Rabu</option>
                            <option value="4" {{ $customer->fjadwaltukarfakturhari == '4' ? 'selected' : '' }}>Kamis</option>
                            <option value="5" {{ $customer->fjadwaltukarfakturhari == '5' ? 'selected' : '' }}>Jumat</option>
                            <option value="6" {{ $customer->fjadwaltukarfakturhari == '6' ? 'selected' : '' }}>Sabtu</option>
                            <option value="7" {{ $customer->fjadwaltukarfakturhari == '7' ? 'selected' : '' }}>Minggu</option>
                        </select>
                    </div>
                </div>

                {{-- Kode Rekening --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kode Rekening</label>
                    <select name="frekening" id="frekening" class="w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                        <option value="">Pilih Kode Rekening</option>
                        @foreach ($rekening as $rek)
                            <option value="{{ $rek->frekeningid }}" {{ $customer->frekening == $rek->frekeningid ? 'selected' : '' }}>
                                {{ $rek->frekeningname }}
                            </option>
                        @endforeach
                    </select>
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
                    <textarea rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                        readonly>{{ $customer->fmemo }}</textarea>
                </div>

                {{-- Toggles --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Status Blokir --}}
                    <div>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                            <div>
                                <p class="text-sm text-gray-800">Blokir Customer</p>
                                <p class="text-xs text-gray-400 mt-0.5">Mencegah customer dari membuat transaksi baru</p>
                            </div>
                            <div class="relative w-9 h-5 rounded-full duration-200 flex-shrink-0 cursor-not-allowed {{ $customer->fblokir == '1' ? 'bg-red-500/60' : 'bg-gray-200' }}">
                                <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $customer->fblokir == '1' ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Aktif --}}
                    <div>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                            <div>
                                <p class="text-sm text-gray-800">Customer aktif</p>
                                <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan customer dari daftar aktif</p>
                            </div>
                            <div class="relative w-9 h-5 rounded-full duration-200 flex-shrink-0 cursor-not-allowed {{ $customer->fnonactive == '0' ? 'bg-blue-500/60' : 'bg-gray-200' }}">
                                <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $customer->fnonactive == '0' ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                            </div>
                        </div>
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
            </div>
        </div>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $customer->fupdatedat ?: $customer->fcreatedat;
            $updatedBy = $customer->fupdatedby ?: ($customer->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
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
    });
</script>
@endsection
