@extends('layouts.app')

@section('title', 'Tambah Customer')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true, selected: 'surat' }">
        <div x-show="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
            <div class="bg-white w-full max-w-5xl p-6 rounded shadow relative overflow-y-auto max-h-screen">
                <!-- Tombol X -->
                <button type="button" @click="window.location.href='{{ route('customer.index') }}'"
                    class="absolute top-4 right-6 text-gray-500 hover:text-red-600 text-xl font-bold">
                    &times;
                </button>
                <!-- Header -->
                <div class="mb-6 border-b pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                        <x-heroicon-o-user-plus class="w-6 h-6 text-blue-600" />
                        <span>Tambah Customer</span>
                    </h2>
                </div>
                <form action="{{ route('customer.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Kode Customer</label>
                            <input type="text" name="fcustomercode" class="w-full border rounded px-3 py-2"
                                placeholder="Masukkan Kode Customer">
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Nama Customer</label>
                            <input type="text" name="fcustomername" class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Group Customer</label>
                            <select name="fgroup"
                                class="w-full border rounded px-3 py-2 @error('fgroup') border-red-500 @enderror"
                                id="groupSelect">
                                <option value="">-- Pilih Group Produk --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupid }}"
                                        {{ old('fgroup') == $group->fgroupid ? 'selected' : '' }}>
                                        {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Salesman</label>
                                <select name="fsalesman"
                                    class="w-full border rounded px-3 py-2 @error('fsalesman') border-red-500 @enderror"
                                    id="salesmanSelect">
                                    <option value="">-- Pilih Salesman --</option>
                                    @foreach ($salesman as $sales)
                                        <option value="{{ $sales->fsalesmanid }}"
                                            {{ old('fsalesman') == $sales->fsalesmanid ? 'selected' : '' }}>
                                            {{ $sales->fsalesmanname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Wilayah</label>
                                <select name="fwilayah"
                                    class="w-full border rounded px-3 py-2 @error('fwilayah') border-red-500 @enderror"
                                    id="wilayahSelect">
                                    <option value="">-- Pilih Wilayah --</option>
                                    @foreach ($wilayah as $wil)
                                        <option value="{{ $wil->fwilayahid }}"
                                            {{ old('fwilayah') == $wil->fwilayahid ? 'selected' : '' }}>
                                            {{ $wil->fwilayahname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">NPWP</label>
                            <input type="text"
                                class="w-full border rounded px-3 py-2 @error('fnpwp') is-invalid @enderror" name="fnpwp"
                                id="fnpwp" placeholder="Masukkan NPWP" value="{{ old('fnpwp') }}">
                            @error('fnpwp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">No. NIK</label>
                            <input type="text"
                                class="w-full border rounded px-3 py-2 @error('fnik') is-invalid @enderror" name="fnik"
                                id="fnik" placeholder="Masukkan NIK" value="{{ old('fnik') }}">
                            @error('fnik')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Jadwal Tukar Faktur</label>
                            <select name="fjadwaltukarfaktur"
                                class="w-full border rounded px-3 py-2 @error('fjadwaltukarfaktur') border-red-500 @enderror">
                                <option value="Setiap Minggu">Setiap Minggu</option>
                                <option value="Setiap Bulan">Setiap Bulan</option>
                                <option value="Sesuai Permintaan">Sesuai Permintaan</option>
                            </select>
                            @error('fjadwaltukarfaktur')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Kode Faktur Pajak</label>
                            <input type="text"
                                class="w-full border rounded px-3 py-2 @error('fkodefp') is-invalid @enderror"
                                name="fkodefp" id="fkodefp" placeholder="010" value="{{ old('fkodefp', '010') }}"
                                maxlength="3">
                            @error('fkodefp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Alamat -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2">Alamat</label>
                            <div class="flex space-x-2 mb-4">
                                <button type="button" @click="selected = 'alamat1'"
                                    :class="selected === 'alamat1' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-4 py-2 rounded border">Alamat 1</button>
                                <button type="button" @click="selected = 'alamat2'"
                                    :class="selected === 'alamat2' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-4 py-2 rounded border">Alamat 2</button>
                                <button type="button" @click="selected = 'alamat3'"
                                    :class="selected === 'alamat3' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-4 py-2 rounded border">Alamat 3</button>
                            </div>

                            <div x-show="selected === 'alamat1'">
                                <textarea class="w-full border rounded px-3 py-2 @error('fkirimaddress1') is-invalid @enderror" name="fkirimaddress1"
                                    id="fkirimaddress1" placeholder="Masukkan Alamat Kirim 1" cols="10" rows="6">{{ old('fkirimaddress1') }}</textarea>
                                @error('fkirimaddress1')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div x-show="selected === 'alamat2'">
                                <textarea class="w-full border rounded px-3 py-2 @error('fkirimaddress2') is-invalid @enderror" name="fkirimaddress2"
                                    id="fkirimaddress2" placeholder="Masukkan Alamat Kirim 2" cols="10" rows="6">{{ old('fkirimaddress2') }}</textarea>
                                @error('fkirimaddress2')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div x-show="selected === 'alamat3'">
                                <textarea class="w-full border rounded px-3 py-2 @error('fkirimaddress3') is-invalid @enderror" name="fkirimaddress3"
                                    id="fkirimaddress3" placeholder="Masukkan Alamat Kirim 3" cols="10" rows="6">{{ old('fkirimaddress3') }}</textarea>
                                @error('fkirimaddress3')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Telp</label>
                            <input type="number"
                                class="w-full border rounded px-3 py-2 @error('ftelp') is-invalid @enderror"
                                name="ftelp" id="ftelp" placeholder="Masukkan Nomor Telepon"
                                value="{{ old('ftelp') }}">
                            @error('ftelp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Fax</label>
                            <input type="number"
                                class="w-full border rounded px-3 py-2 @error('ffax') is-invalid @enderror"
                                name="ffax" id="ffax" placeholder="Masukkan Nomor Fax"
                                value="{{ old('ffax') }}">
                            @error('ffax')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium">Email</label>
                            <input type="email"
                                class="w-full border rounded px-3 py-2 @error('femail') is-invalid @enderror"
                                name="femail" id="femail" placeholder="Masukkan Email" value="{{ old('femail') }}">
                            @error('femail')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Jatuh Tempo</label>
                            <input type="number"
                                class="w-full border rounded px-3 py-2 @error('ftempo') is-invalid @enderror"
                                name="ftempo" id="ftempo" value="{{ old('ftempo', 0) }}" maxlength="3">
                            @error('ftempo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Max JT Tempo</label>
                            <input type="number"
                                class="w-full border rounded px-3 py-2 @error('fmaxtempo') is-invalid @enderror"
                                name="fmaxtempo" id="fmaxtempo" value="{{ old('fmaxtempo', 0) }}">
                            @error('fmaxtempo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Limit Piutang</label>
                            <input type="text"
                                class="w-full border rounded px-3 py-2 @error('flimit') is-invalid @enderror"
                                name="flimit" id="flimit" placeholder="Masukkan Limit Piutang"
                                value="{{ old('flimit') }}">
                            @error('flimit')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Set Harga</label>
                            <select class="w-full border rounded px-3 py-2 @error('fhargalevel') is-invalid @enderror"
                                name="fhargalevel" id="fhargalevel">
                                <option value="0" selected> Harga Level 1 </option>
                                <option value="1"> Harga Level 2 </option>
                                <option value="2"> Harga Level 3 </option>
                            </select>
                            @error('fhargalevel')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Kontak Person</label>
                            <input type="number"
                                class="w-full border rounded px-3 py-2 @error('fkontakperson') is-invalid @enderror"
                                name="fkontakperson" id="fkontakperson" placeholder="Masukkan Nama Kontak Person"
                                value="{{ old('fkontakperson') }}">
                            @error('fkontakperson')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Jabatan</label>
                            <input type="text"
                                class="w-full border rounded px-3 py-2 @error('fjabatan') is-invalid @enderror"
                                name="fjabatan" id="fjabatan" placeholder="Masukkan Jabatan"
                                value="{{ old('fjabatan') }}">
                            @error('fjabatan')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium">Kode Rekening</label>
                            <select class="w-full border rounded px-3 py-2 @error('frekening') is-invalid @enderror"
                                name="frekening" id="frekening">
                                <option value="" selected> Pilih Kode Rekening </option>
                                @foreach ($rekening as $rek)
                                    <option value="{{ $rek->frekeningcode }}" {{ old('frekening') ? 'selected' : '' }}>
                                        {{ $rek->frekeningcode }} - {{ $rek->frekeningname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('frekening')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium">Memo</label>
                            <textarea class="w-full border rounded px-3 py-2 @error('fmemo') is-invalid @enderror" name="fmemo" id="fmemo"
                                placeholder="Masukkan Memo" cols="10" rows="6">{{ old('fmemo') }}</textarea>
                            @error('fmemo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-6 flex justify-center space-x-4">
                        <!-- Simpan -->
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" />
                            Simpan
                        </button>

                        <!-- Keluar -->
                        <button type="button" @click="window.location.href='{{ route('customer.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                            Keluar
                        </button>
                    </div>
            </div>
            </form>
        </div>
    </div>
    </div>
@endsection

<!-- Include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

<!-- Include jQuery (required by Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#groupSelect').select2({
            placeholder: '-- Pilih Group Produk --',
            allowClear: true
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#salesmanSelect').select2({
            placeholder: '-- Pilih Salesman --',
            allowClear: true
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#wilayahSelect').select2({
            placeholder: '-- Pilih Wilayah --',
            allowClear: true
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#frekening').select2({
            placeholder: '-- Pilih Rekening --',
            allowClear: true
        });
    });
</script>
