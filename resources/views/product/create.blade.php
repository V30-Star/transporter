@extends('layouts.app')

@section('title', 'Tambah Product')

@section('content')
    <style>
        /* The switch - the outer box */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        /* Hide the default checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        /* The slider circle */
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
        }

        /* When the checkbox is checked, change the background color */
        input:checked+.slider {
            background-color: #4CAF50;
        }

        /* Move the slider circle when checked */
        input:checked+.slider:before {
            transform: translateX(26px);
        }

        /* Add a border when checked */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
    <style>
        input:focus,
        select:focus,
        textarea:focus,
        .select2-container--default .select2-selection--single:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #000000 !important;
            border-radius: 0.375rem;
            height: 42px;
            padding: 0.5rem 0.75rem;
            width: 100% !important;
            background-color: white;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        .select2-dropdown {
            border: 1px solid #000000 !important;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .select2-results__option {
            padding: 8px 12px;
        }

        .select2-results__option--highlighted {
            background-color: #2563eb !important;
            color: white !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000000 !important;
        }
    </style>

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                <x-heroicon-o-plus-circle class="w-6 h-6 text-blue-600" />
                <span>Tambah Product</span>
            </h2>
            <form action="{{ route('product.store') }}" method="POST">
                @csrf

                <div>
                    <!-- Group Produk Dropdown -->
                    <div class="mt-4 w-1/4">
                        <label class="block text-sm font-medium">Group Produk</label>
                        <select name="fgroupcode"
                            class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror"
                            id="groupSelect">
                            <option value="">-- Pilih Group Produk --</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->fgroupid }}"
                                    {{ old('fgroupcode') == $group->fgroupid ? 'selected' : '' }}>
                                    {{ $group->fgroupname }}
                                </option>
                            @endforeach
                        </select>
                        @error('fgroupcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <!-- Input Kode Product -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Kode Product</label>
                            <input type="text" name="fproductcode" class="w-full border rounded px-3 py-2"
                                placeholder="Masukkan Kode Product" :disabled="autoCode"
                                :value="autoCode ? '{{ $newProductCode }}' : '{{ old('fproductcode') }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>

                        <!-- Checkbox Auto Generate -->
                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <!-- Nama Product -->
                    <div class="mt-2 w-1/2">
                        <label class="block text-sm font-medium">Nama Product</label>
                        <input type="text" name="fproductname" value="{{ old('fproductname') }}"
                            class="w-full border rounded px-3 py-2 @error('fproductname') border-red-500 @enderror">
                        @error('fproductname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Barcode -->
                    <div class="mt-2 w-1/3">
                        <label class="block text-sm font-medium">Barcode</label>
                        <input type="text" name="fbarcode" value="{{ old('fbarcode') }}"
                            class="w-full border rounded px-3 py-2 @error('fbarcode') border-red-500 @enderror">
                        @error('fbarcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Merek Dropdown -->
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Merek</label>
                        <select name="fmerek"
                            class="w-full border rounded px-3 py-2 @error('fmerek') border-red-500 @enderror"
                            id="merkSelect">
                            <option value="">-- Pilih Merek --</option>
                            @foreach ($merks as $merk)
                                <option value="{{ $merk->fmerekid }}"
                                    {{ old('fmerek') == $merk->fmerekid ? 'selected' : '' }}>
                                    {{ $merk->fmerekname }}
                                </option>
                            @endforeach
                        </select>
                        @error('fmerek')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Satuan Kecil --}}
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Satuan Kecil</label>
                        <select class="w-full border rounded px-3 py-2 @error('fsatuankecil') border-red-500 @enderror"
                            name="fsatuankecil" id="fsatuankecil">
                            <option value="" selected> Pilih Satuan 1</option>
                            @foreach ($satuan as $satu)
                                <option value="{{ $satu->fsatuancode }}">
                                    {{ $satu->fsatuancode }}
                                </option>
                            @endforeach
                        </select>
                        @error('fsatuankecil')
                            <div class="text-red-600 text-sm mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Satuan 2 --}}
                    <div class="mt-2">
                        <div class="flex items-end gap-4">
                            <!-- Satuan 2 Select -->
                            <div class="w-1/3">
                                <label class="block text-sm font-medium">Satuan 2</label>
                                <select
                                    class="w-full border rounded px-3 py-2 @error('fsatuanbesar') border-red-500 @enderror"
                                    name="fsatuanbesar" id="fsatuanbesar">
                                    <option value="" selected>Pilih Satuan 2</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}">
                                            {{ $satu->fsatuancode }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fsatuanbesar')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Isi Input -->
                            <div class="w-1/6">
                                <label class="block text-sm font-medium">Isi</label>
                                <input type="number"
                                    class="w-full border rounded px-3 py-2 @error('fqtykecil') border-red-500 @enderror"
                                    name="fqtykecil" id="fqtykecil" value="{{ old('fqtykecil', 0) }}">
                                @error('fqtykecil')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Satuan 3 --}}
                    <div class="mt-2">
                        <div class="flex items-end gap-4">
                            <!-- Satuan 3 Select -->
                            <div class="w-1/3">
                                <label class="block text-sm font-medium">Satuan 3</label>
                                <select
                                    class="w-full border rounded px-3 py-2 @error('fsatuanbesar2') border-red-500 @enderror"
                                    name="fsatuanbesar2" id="fsatuanbesar2">
                                    <option value="" selected>Pilih Satuan 3</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}">
                                            {{ $satu->fsatuancode }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fsatuanbesar2')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Isi Input -->
                            <div class="w-1/6">
                                <label class="block text-sm font-medium">Isi</label>
                                <input type="number"
                                    class="w-full border rounded px-3 py-2 @error('fqtykecil2') border-red-500 @enderror"
                                    name="fqtykecil2" id="fqtykecil2" value="{{ old('fqtykecil2', 0) }}">
                                @error('fqtykecil2')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Satuan Default Dropdown -->
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Satuan Default</label>
                        <select name="fsatuandefault"
                            class="w-full border rounded px-3 py-2 @error('fsatuandefault') border-red-500 @enderror">
                            <option value="1"> Satuan 1 </option>
                            <option value="2"> Satuan 2 </option>
                            <option value="3"> Satuan 3 </option>
                        </select>
                        @error('fsatuandefault')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Harga Pokok Penjualan -->
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Harga Pokok Penjualan</label>
                        <input type="text" name="fhpp" id="fhpp" value="{{ old('fhpp', 0) }}"
                            class="w-full border rounded px-3 py-2 @error('fhpp') border-red-500 @enderror">
                        @error('fhpp')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Harga Satuan 3 Level 1 -->
                        <div>
                            <label for="fhargasatuankecillevel1" class="block text-sm font-medium">HJ. Kecil Level
                                1</label>
                            <div class="d-flex">
                                <input type="text"
                                    class="w-1/2 border rounded px-3 py-2 @error('fhargasatuankecillevel1') is-invalid @enderror"
                                    name="fhargasatuankecillevel1" id="fhargasatuankecillevel1"
                                    value="{{ old('fhargasatuankecillevel1', 0) }}">
                                @error('fhargasatuankecillevel1')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan 3 Level 2 -->
                        <div>
                            <label for="fhargasatuankecillevel2" class="block text-sm font-medium">HJ. Kecil Level
                                2</label>
                            <div class="d-flex">
                                <input type="text"
                                    class="w-1/2 border rounded px-3 py-2 @error('fhargasatuankecillevel2') is-invalid @enderror"
                                    name="fhargasatuankecillevel2" id="fhargasatuankecillevel2"
                                    value="{{ old('fhargasatuankecillevel2', 0) }}">
                                @error('fhargasatuankecillevel2')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan 3 Level 3 -->
                        <div>
                            <label for="fhargasatuankecillevel3" class="block text-sm font-medium">HJ. Kecil Level
                                3</label>
                            <div class="d-flex">
                                <input type="text"
                                    class="w-1/2 border rounded px-3 py-2 @error('fhargasatuankecillevel3') is-invalid @enderror"
                                    name="fhargasatuankecillevel3" id="fhargasatuankecillevel3"
                                    value="{{ old('fhargasatuankecillevel3', 0) }}">
                                @error('fhargasatuankecillevel3')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Harga Satuan 2 Level 1 -->
                        <div>
                            <label for="fhargajuallevel1" class="block text-sm font-medium">HJ. Besar Level
                                1</label>
                            <div class="d-flex">
                                <input type="text"
                                    class="w-1/2 border rounded px-3 py-2 @error('fhargajuallevel1') is-invalid @enderror"
                                    name="fhargajuallevel1" id="fhargajuallevel1"
                                    value="{{ old('fhargajuallevel1', 0) }}">
                                @error('fhargajuallevel1')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan 2 Level 2 -->
                        <div>
                            <label for="fhargajuallevel2" class="block text-sm font-medium">HJ. Besar Level
                                2</label>
                            <div class="d-flex">
                                <input type="text"
                                    class="w-1/2 border rounded px-3 py-2 @error('fhargajuallevel2') is-invalid @enderror"
                                    name="fhargajuallevel2" id="fhargajuallevel2"
                                    value="{{ old('fhargajuallevel2', 0) }}">
                                @error('fhargajuallevel2')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan 2 Level 3 -->
                        <div>
                            <label for="fhargajuallevel3" class="block text-sm font-medium">HJ. Besar Level
                                3</label>
                            <div class="d-flex">
                                <input type="text"
                                    class="w-1/2 border rounded px-3 py-2 @error('fhargajuallevel3') is-invalid @enderror"
                                    name="fhargajuallevel3" id="fhargajuallevel3"
                                    value="{{ old('fhargajuallevel3', 0) }}">
                                @error('fhargajuallevel3')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Min.Stok -->
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Min.Stok</label>
                        <input type="text" name="fminstock" value="{{ old('fminstock', 0) }}"
                            class="w-full border rounded px-3 py-2 @error('fminstock') border-red-500 @enderror">
                        @error('fminstock')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Jenis --}}
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Jenis</label>
                        <select name="ftype"
                            class="w-full border rounded px-3 py-2 @error('ftype') border-red-500 @enderror">
                            <option value="Produk">Product</option>
                            <option value="Jasa"> Jasa </option>
                        </select>
                        @error('ftype')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <label class="block text-sm font-medium">Approval</label>
                        <label class="switch">
                            <input type="checkbox" name="fapproval" id="approvalToggle"
                                {{ session('fapproval') ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
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
                    <button type="button" @click="window.location.href='{{ route('product.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.8.1/autoNumeric.min.js"></script>

<script>
    $(document).ready(function() {
        $('#groupSelect, #merkSelect, #fsatuankecil, #fsatuanbesar, #fsatuanbesar2').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || '-- Pilih --';
            },
        });

        $('#groupSelect').select2({
            placeholder: '-- Pilih Group Produk --'
        });

        $('#merkSelect').select2({
            placeholder: '-- Pilih Merek --'
        });

        let fhpp = new AutoNumeric('#fhpp', 'commaDecimalCharDotSeparator');
        let fhargasatuankecillevel1 = new AutoNumeric('#fhargasatuankecillevel1',
            'commaDecimalCharDotSeparator');
        let hargasatuankecillevel2 = new AutoNumeric('#fhargasatuankecillevel2',
            'commaDecimalCharDotSeparator');
        let hargasatuankecillevel3 = new AutoNumeric('#fhargasatuankecillevel3',
            'commaDecimalCharDotSeparator');
        let hargajuallevel1 = new AutoNumeric('#fhargajuallevel1', 'commaDecimalCharDotSeparator');
        let hargajuallevel2 = new AutoNumeric('#fhargajuallevel2', 'commaDecimalCharDotSeparator');
        let hargajuallevel3 = new AutoNumeric('#fhargajuallevel3', 'commaDecimalCharDotSeparator');
        let hargajual2level1 = new AutoNumeric('#fhargajual2level1', 'commaDecimalCharDotSeparator');
        let hargajual2level2 = new AutoNumeric('#fhargajual2level2', 'commaDecimalCharDotSeparator');
        let hargajual2level3 = new AutoNumeric('#fhargajual2level3', 'commaDecimalCharDotSeparator');

    });
</script>
