@extends('layouts.app')

@section('title', 'Master Product')

@section('content')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>

    <style>
        .ui-autocomplete {
            z-index: 9999;
            max-height: 240px;
            overflow-y: auto;
            overflow-x: hidden;
        }

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

        /* Warna untuk Satuan Kecil (Blue) */
        #fsatuankecil+.select2-container .select2-selection--single {
            background-color: #eff6ff !important;
            /* bg-blue-50 */
            border-color: #93c5fd !important;
            /* border-blue-300 */
        }

        /* Warna untuk Satuan 2 (Yellow) */
        #fsatuanbesar+.select2-container .select2-selection--single {
            background-color: #fefce8 !important;
            /* bg-yellow-50 */
            border-color: #fde047 !important;
            /* border-yellow-300 */
        }

        /* Warna untuk Satuan 3 (Purple) */
        #fsatuanbesar2+.select2-container .select2-selection--single {
            background-color: #faf5ff !important;
            /* bg-purple-50 */
            border-color: #d8b4fe !important;
            /* border-purple-300 */
        }

        /* Tambahan: Mencegah teks span (Isi) turun ke bawah */
        .satuan-kecil-display {
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
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

    <div x-data="{ open: false, keyword: '', rows: [], page: 1, lastPage: 1, total: 0 }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1500px] w-full mx-auto">
            <form action="{{ route('product.store') }}" method="POST">
                @csrf

                <div>
                    <!-- Group Produk Dropdown -->
                    <div class="mt-2 w-1/2" x-data="{ isEditable: false }">
                        <label class="block text-sm font-medium">Group Produk</label>
                        <div class="flex items-center gap-2">
                            <select :disabled="!isEditable"
                                class="w-full border rounded px-3 py-2 @error('fgroupid') border-red-500 @enderror"
                                id="groupSelect">
                                <option value="">-- Pilih Group Produk --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupid }}"
                                        {{ old('fgroupid') == $group->fgroupid ? 'selected' : '' }}>
                                        {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="fgroupid" id="groupIdHidden"
                                value="{{ old('fgroupid', $tr_prh->fgroupid ?? null) }}">

                            <input type="hidden" name="fgroupcode" id="groupCodeHidden"
                                value="{{ old('fgroupcode', $product->fgroupcode ?? '') }}">

                            <button type="button" @click="isEditable = true; $dispatch('open-group-modal')"
                                class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                <i class="fa fa-plus"></i>
                            </button>
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"
                                class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>

                        @error('fgroupid')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Merek Dropdown + Button Create -->
                    <div class="mt-2 w-1/2" x-data="{ isMerekEditable: false }">
                        <label class="block text-sm font-medium">Merek</label>
                        <div class="flex items-center gap-2">
                            <!-- Merek Dropdown -->
                            <select name="fmerek" id="merkSelect" :disabled="!isMerekEditable"
                                class="w-full border rounded px-3 py-2 @error('fmerek') border-red-500 @enderror">
                                <option value="">-- Pilih Merek --</option>
                                @foreach ($merks as $merk)
                                    <option value="{{ $merk->fmerekid }}"
                                        {{ old('fmerek') == $merk->fmerekid ? 'selected' : '' }}>
                                        {{ $merk->fmerekname }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="hidden" name="fmerek" id="fmerek" value="{{ old('fmerek') }}">

                            <!-- Button to Add Merek -->
                            <button type="button" @click="isMerekEditable = true; $dispatch('open-merk-modal')"
                                class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                <i class="fa fa-plus"></i>
                            </button>

                            <!-- Button to Browse Merek -->
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"
                                class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>

                        <!-- Validation error for fmerek -->
                        @error('fmerek')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <!-- Input Kode Product -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Kode Product</label>
                            <input type="text" name="fprdcode" id="fprdcode"
                                class="w-full border rounded px-3 py-2 uppercase" placeholder="Masukkan Kode Product"
                                :disabled="autoCode" :value="autoCode ? '' : '{{ old('fprdcode') }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>

                        <!-- Checkbox Auto Generate -->
                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <div x-show="open" x-transition.opacity x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Merek</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>

                            <div class="p-3 border-t flex items-center gap-2">
                                <div class="text-sm text-gray-600"><span
                                        x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span></div>
                                <div class="ml-auto flex items-center gap-2">
                                    <button type="button" @click="prev()" :disabled="page <= 1"
                                        class="px-3 py-1 rounded border"
                                        :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                            'bg-gray-100 hover:bg-gray-200'">Prev</button>
                                    <button type="button" @click="next()" :disabled="page >= lastPage"
                                        class="px-3 py-1 rounded border"
                                        :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                            'bg-gray-100 hover:bg-gray-200'">Next</button>
                                    <button type="button" @click="open = false"
                                        class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nama Product -->
                <div class="mt-2 w-1/2">
                    <label class="block text-sm font-medium">Nama Product</label>
                    <input type="text" name="fprdname" id="fprdname" value="{{ old('fprdname') }}"
                        class="w-full border rounded px-3 py-2 uppercase @error('fprdname') border-red-500 @enderror"
                        autofocus>
                    @error('fprdname')
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

                <div id="satuan-container" class="space-y-4">
                    {{-- 1. Satuan Kecil (Field Biru) --}}
                    <div class="mt-2 flex items-end gap-4">
                        <div class="w-1/3">
                            <label class="block text-sm font-medium">Satuan Kecil</label>
                            <select
                                class="w-full border rounded px-3 py-2 bg-blue-50 border-blue-300 focus:ring-blue-500 @error('fsatuankecil') border-red-500 @enderror"
                                name="fsatuankecil" id="fsatuankecil" onchange="updateSatuanLogic();">
                                <option value="" selected>Pilih Satuan 1</option>
                                @foreach ($satuan as $satu)
                                    <option value="{{ $satu->fsatuancode }}"
                                        {{ old('fsatuankecil') == $satu->fsatuancode ? 'selected' : '' }}>
                                        {{ $satu->fsatuancode }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-1/4 invisible"></div>

                        <div class="w-1/6">
                            <label class="block text-sm font-medium">HPP Satuan Kecil</label>
                            <input type="text" name="fhpp" id="fhpp"
                                class="autonumeric w-full border border-blue-300 rounded px-3 py-2 bg-blue-50 focus:bg-white transition-colors"
                                value="0">
                        </div>
                    </div>

                    {{-- 2. Satuan 2 (Field Kuning) --}}
                    <div id="satuan2-block" style="display: none;" class="mt-4">
                        <div class="flex items-end gap-4">
                            <div class="w-1/3">
                                <label class="block text-sm font-medium">Satuan 2</label>
                                <select class="w-full border border-yellow-300 rounded px-3 py-2 bg-yellow-50"
                                    name="fsatuanbesar" id="fsatuanbesar" disabled onchange="updateSatuanLogic();">
                                    <option value="" selected>Pilih Satuan 2</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}"
                                            {{ old('fsatuanbesar') == $satu->fsatuancode ? 'selected' : '' }}>
                                            {{ $satu->fsatuancode }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fsatuanbesar')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="w-1/4"> {{-- Kita lebarkan sedikit dari 1/6 ke 1/4 agar ruang teks lebih lega --}}
                                <label class="block text-sm font-medium">Isi</label>
                                <div
                                    class="flex items-center border border-yellow-300 rounded bg-yellow-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-yellow-400 transition-all">
                                    {{-- Input tanpa border agar menyatu dengan container --}}
                                    <input type="text" name="fqtykecil" id="fqtykecil" value="0"
                                        class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right"
                                        disabled>

                                    {{-- Span sebagai prefix/suffix di dalam kotak --}}
                                    <span
                                        class="satuan-kecil-display text-gray-500 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-yellow-200 ml-2 pl-2">
                                    </span>
                                </div>
                            </div>

                            <div class="w-1/6">
                                <label class="block text-sm font-medium">HPP Satuan 2</label>
                                <input type="text" name="fhpp2" id="fhpp2"
                                    class="autonumeric w-full border border-yellow-300 rounded px-3 py-2 bg-yellow-100 font-semibold"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Satuan 3 (Field Ungu) --}}
                    <div id="satuan3-block" style="display: none;" class="mt-4">
                        <div class="flex items-end gap-4">
                            <div class="w-1/3">
                                <label class="block text-sm font-medium">Satuan 3</label>
                                <select class="w-full border border-purple-300 rounded px-3 py-2 bg-purple-50"
                                    name="fsatuanbesar2" id="fsatuanbesar2" disabled onchange="updateSatuanLogic();">
                                    <option value="" selected>Pilih Satuan 3</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}"
                                            {{ old('fsatuanbesar2') == $satu->fsatuancode ? 'selected' : '' }}>
                                            {{ $satu->fsatuancode }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fsatuanbesar2')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="w-1/4"> {{-- Lebar dinaikkan ke 1/4 agar ruang teks lebih lega --}}
                                <label class="block text-sm font-medium">Isi</label>
                                <div
                                    class="flex items-center border border-purple-300 rounded bg-purple-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-purple-400 transition-all">
                                    {{-- Input tanpa border agar menyatu dengan container --}}
                                    <input type="text" name="fqtykecil2" id="fqtykecil2" value="0"
                                        class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right"
                                        disabled>

                                    {{-- Span sebagai teks di dalam kotak --}}
                                    <span
                                        class="satuan-kecil-display text-purple-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-purple-200 ml-2 pl-2 uppercase">
                                    </span>
                                </div>
                            </div>

                            <div class="w-1/6">
                                <label class="block text-sm font-medium">HPP Satuan 3</label>
                                <input type="text" name="fhpp3" id="fhpp3"
                                    class="autonumeric w-full border border-purple-300 rounded px-3 py-2 bg-purple-100 font-semibold"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        // 1. Inisialisasi AutoNumeric untuk semua class .autonumeric
                        if (typeof AutoNumeric !== 'undefined') {
                            AutoNumeric.multiple('.autonumeric', {
                                digitGroupSeparator: '.',
                                decimalCharacter: ',',
                                decimalPlaces: 2,
                                unformatOnSubmit: true
                            });
                        }

                        function calculateHPPRows() {
                            // Ambil instance AutoNumeric
                            const anHppKecil = AutoNumeric.getAutoNumericElement('#fhpp');
                            const anQty2 = AutoNumeric.getAutoNumericElement('#fqtykecil');
                            const anQty3 = AutoNumeric.getAutoNumericElement('#fqtykecil2');
                            const anHpp2 = AutoNumeric.getAutoNumericElement('#fhpp2');
                            const anHpp3 = AutoNumeric.getAutoNumericElement('#fhpp3');

                            const valHppKecil = anHppKecil ? anHppKecil.getNumber() : 0;

                            if (anQty2 && anHpp2) {
                                // Gunakan .getNumber() untuk kalkulasi
                                const result2 = valHppKecil * anQty2.getNumber();
                                // Gunakan .set() untuk update nilai agar format tetap terjaga
                                anHpp2.set(result2);
                            }

                            if (anQty3 && anHpp3) {
                                const result3 = valHppKecil * anQty3.getNumber();
                                anHpp3.set(result3);
                            }
                        }

                        // Jalankan kalkulasi setiap kali nilai AutoNumeric berubah
                        $('#fhpp, #fqtykecil, #fqtykecil2').on('autoNumeric:rawValueModified', function() {
                            calculateHPPRows();
                        });

                        // Delay sedikit saat load pertama untuk sinkronisasi data
                        setTimeout(calculateHPPRows, 500);
                    });
                </script>

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

                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Harga Satuan 3 Level 1 -->
                    <div>
                        <label for="fhargasatuankecillevel1" class="block text-sm font-medium">HJ. Kecil Level
                            1</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel1') is-invalid @enderror"
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
                                class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel2') is-invalid @enderror"
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
                                class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel3') is-invalid @enderror"
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
                                class="w-1/10 border rounded px-3 py-2 @error('fhargajuallevel1') is-invalid @enderror"
                                name="fhargajuallevel1" id="fhargajuallevel1" value="{{ old('fhargajuallevel1', 0) }}">
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
                                class="w-1/10 border rounded px-3 py-2 @error('fhargajuallevel2') is-invalid @enderror"
                                name="fhargajuallevel2" id="fhargajuallevel2" value="{{ old('fhargajuallevel2', 0) }}">
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
                                class="w-1/10 border rounded px-3 py-2 @error('fhargajuallevel3') is-invalid @enderror"
                                name="fhargajuallevel3" id="fhargajuallevel3" value="{{ old('fhargajuallevel3', 0) }}">
                            @error('fhargajuallevel3')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-2 w-1/4">
                    <label class="block text-sm font-medium mb-1">Min.Stok</label>
                    <div class="flex items-baseline">
                        <input type="text" name="fminstock" value="{{ old('fminstock', 0) }}"
                            class="flex-1 border rounded px-3 py-2 @error('fminstock') border-red-500 @enderror">
                        <span class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap ml-2">
                        </span>
                    </div>
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
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                    </label>
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
        </div>

        <br>

        </form>
    </div>

    <div x-data="{
        open: false,
        loading: false,
        errors: {},
        form: { fgroupcode: '', fgroupname: '', fnonactive: false },
        saveData() {
            this.loading = true;
            this.errors = {};
            $.ajax({
                    url: '{{ route('groupproduct.store') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        fgroupcode: this.form.fgroupcode,
                        fgroupname: this.form.fgroupname,
                        fnonactive: this.form.fnonactive ? 1 : 0
                    }
                })
                .done((res) => {
                    if (res && res.id && res.name) {
                        // 1. Tambahkan ke Dropdown Select
                        const opt = new Option(res.name, res.id, true, true);
                        $('#groupSelect').append(opt).trigger('change');
    
                        // 2. SINKRONISASI KE INPUT HIDDEN (Menggunakan fgroupid)
                        const hidId = document.getElementById('groupIdHidden');
                        if (hidId) {
                            hidId.value = res.id; // res.id dari controller adalah fgroupid
                        }
    
                        this.open = false;
                        this.form = { fgroupcode: '', fgroupname: '', fnonactive: false };
                        this.errors = {};
                    } else {
                        alert('Format respon server salah.');
                    }
                    this.loading = false;
                })
                .fail((xhr) => {
                    this.loading = false;
                    if (xhr.status === 422) {
                        this.errors = xhr.responseJSON?.errors || {};
                    } else {
                        alert('Gagal menyimpan group produk.');
                    }
                });
        }
    }" x-on:open-group-modal.window="open = true; errors = {}; loading = false;" x-show="open"
        style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center">

        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Tambah Group Produk</h3>
            <div class="space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Group</label>
                    <input type="text" x-model="form.fgroupcode" class="w-full border rounded px-3 py-2 uppercase"
                        maxlength="10" :class="errors.fgroupcode ? 'border-red-500' : 'border-gray-300'">
                    <template x-if="errors.fgroupcode">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fgroupcode[0]"></p>
                    </template>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Group</label>
                    <input type="text" x-model="form.fgroupname" class="w-full border rounded px-3 py-2 uppercase"
                        :class="errors.fgroupname ? 'border-red-500' : 'border-gray-300'">
                    <template x-if="errors.fgroupname">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fgroupname[0]"></p>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_group_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_group_fnonactive" class="text-sm font-medium text-gray-700">Non Aktif</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open=false"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">Batal</button>
                <button type="button" @click="saveData()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 disabled:opacity-60 transition"
                    :disabled="loading">
                    <svg x-show="loading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            opacity=".25"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" opacity=".75"></path>
                    </svg>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Group'"></span>
                </button>
            </div>
        </div>
    </div>

    <div x-data="{
        open: false,
        loading: false,
        errors: {},
        form: { fmerekcode: '', fmerekname: '', fnonactive: false },
        saveData() {
            this.loading = true;
            this.errors = {};
            $.ajax({
                    url: '{{ route('merek.store') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        fmerekcode: this.form.fmerekcode,
                        fmerekname: this.form.fmerekname,
                        fnonactive: this.form.fnonactive ? 1 : 0
                    }
                })
                .done((res) => {
                    if (res && res.id && res.name) {
                        const opt = new Option(res.name, res.id, true, true);
                        $('#merkSelect').append(opt).trigger('change');
                        const hidMerek = document.getElementById('fmerek');
                        if (hidMerek) { hidMerek.value = res.id; }
                        this.open = false;
                        this.form = { fmerekcode: '', fmerekname: '', fnonactive: false };
                        this.errors = {};
                    } else {
                        alert('Format respon server salah.');
                    }
                    this.loading = false;
                })
                .fail((xhr) => {
                    this.loading = false;
                    if (xhr.status === 422) {
                        this.errors = xhr.responseJSON?.errors || {};
                    } else {
                        alert('Gagal menyimpan merek.');
                    }
                });
        }
    }" x-on:open-merk-modal.window="open = true; errors = {}; loading = false;" x-show="open"
        style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center">

        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Tambah Merek</h3>
            <div class="space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-medium">Kode Merek</label>
                    <input type="text" x-model="form.fmerekcode" class="w-full border rounded px-3 py-2 uppercase"
                        maxlength="10" :class="errors.fmerekcode ? 'border-red-500' : 'border-gray-300'">
                    <template x-if="errors.fmerekcode">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekcode[0]"></p>
                    </template>
                </div>
                <div>
                    <label class="block text-sm font-medium">Nama Merek</label>
                    <input type="text" x-model="form.fmerekname" class="w-full border rounded px-3 py-2 uppercase"
                        :class="errors.fmerekname ? 'border-red-500' : 'border-gray-300'">
                    <template x-if="errors.fmerekname">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekname[0]"></p>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_fnonactive" class="block text-sm font-medium">Non Aktif</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open=false"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>
                <button type="button" @click="saveData()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 disabled:opacity-60"
                    :disabled="loading">
                    <svg x-show="loading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            opacity=".25"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" opacity=".75"></path>
                    </svg>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL BROWSE GROUP PRODUCT -->
    <div x-data="groupBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden"
            style="height: 650px;">
            <!-- Header -->
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Group Product</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Pilih group product yang diinginkan</p>
                </div>
                <button type="button" @click="close()"
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                    Tutup
                </button>
            </div>

            <!-- Search & Length Menu -->
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="tableControls"></div>
            </div>

            <!-- Table with fixed height and scroll -->
            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                <div class="bg-white">
                    <table id="groupTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode Group
                                </th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama Group
                                </th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination & Info -->
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div id="tablePagination"></div>
            </div>
        </div>
    </div>

    {{-- MODAL MEREK dengan DataTables --}}
    <div x-data="merekBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl flex flex-col overflow-hidden"
            style="height: 550px;">

            <!-- Header (Disamakan dengan Supplier/PR) -->
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Merek (Brand)</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Pilih merek yang diinginkan</p>
                </div>
                <button type="button" @click="close()"
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                    Tutup
                </button>
            </div>

            <!-- Search & Length Menu (Area untuk kontrol DataTables) -->
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <!-- DataTables akan merender kontrol f (filter) dan l (length) di sini karena setting dom. -->
            </div>

            <!-- Table with fixed height and scroll -->
            <div class="flex-1 overflow-y-auto px-6" style="min-height: 0;">
                <div class="bg-white">
                    <table id="merekTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                    Kode Merek</th>
                                <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                    Nama Merek</th>
                                <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination & Info -->
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <!-- DataTables info dan paginate akan dirender di sini -->
            </div>
        </div>
    </div>

@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.8.1/autoNumeric.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<style>
    /* Lebarkan dropdown tampilkan data */
    #merekTable_wrapper .dt-length select,
    #merekTable_wrapper .dataTables_length select {
        min-width: 80px !important;
        width: auto !important;
        padding-right: 30px !important;
    }

    /* Pastikan wrapper length cukup lebar */
    #merekTable_wrapper .dt-length,
    #merekTable_wrapper .dataTables_length {
        min-width: 180px;
        white-space: nowrap;
    }

    /* Styling untuk select agar lebih rapi */
    #merekTable_wrapper .dt-length select,
    #merekTable_wrapper .dataTables_length select {
        padding: 6px 30px 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background-position: right 8px center;
        background-size: 16px;
    }

    /* Lebarkan dropdown tampilkan data */
    #groupTable_wrapper .dt-length select,
    #groupTable_wrapper .dataTables_length select {
        min-width: 80px !important;
        width: auto !important;
        padding-right: 30px !important;
    }

    /* Pastikan wrapper length cukup lebar */
    #groupTable_wrapper .dt-length,
    #groupTable_wrapper .dataTables_length {
        min-width: 180px;
        white-space: nowrap;
    }

    /* Styling untuk select agar lebih rapi */
    #groupTable_wrapper .dt-length select,
    #groupTable_wrapper .dataTables_length select {
        padding: 6px 30px 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background-position: right 8px center;
        background-size: 16px;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize Select2
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

        // Initialize AutoNumeric
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

        // Product Name Autocomplete
        $(function() {
            const $inp = $("#fprdname");
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

                    lastXHR = $.getJSON("{{ route('product.name.suggest') }}", {
                        term
                    }, function(data) {
                        localCache[term] = data;
                        response(data);
                    });
                },
                minLength: 0,
                delay: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    return false;
                },
                open: function() {
                    $(".ui-autocomplete").css("width", $inp.outerWidth());
                }
            });

            $('#fprdcode').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "{{ route('product.suggest-codes') }}",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 1, // Minimal 1 karakter untuk kode
                select: function(event, ui) {
                    // Isi input dengan nilai yang dipilih
                    $(this).val(ui.item.value);
                    return false;
                },
                // Disable autocomplete saat checkbox "Auto" dicentang
                disabled: true
            });

            const fprdcodeInput = document.getElementById('fprdcode');

            setInterval(function() {
                const isDisabled = fprdcodeInput.disabled;

                if (isDisabled) {
                    // Jika Auto dicentang, disable autocomplete
                    $('#fprdcode').autocomplete('disable');
                } else {
                    // Jika Auto tidak dicentang, enable autocomplete
                    $('#fprdcode').autocomplete('enable');
                }
            }, 100);

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

<script>
    $(document).ready(function() {
        $('#fsatuanbesar').on('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var fsatuanname = selectedOption.getAttribute('data-name');

            if (fsatuanname) {
                $('#fsatuanname-label').text(fsatuanname);
            } else {
                $('#fsatuanname-label').text('Tidak ada pilihan');
            }
        });

        $('#fsatuanbesar2').on('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var fsatuanname = selectedOption.getAttribute('data-name');

            if (fsatuanname) {
                $('#fsatuanname-label-2').text(fsatuanname);
            } else {
                $('#fsatuanname-label-2').text('Tidak ada pilihan');
            }
        });
    });
</script>

<script>
    function checkSatuan() {
        const fsatuankecil = document.getElementById('fsatuankecil').value;
        const fsatuanbesar = document.getElementById('fsatuanbesar');
        const fsatuanbesar2 = document.getElementById('fsatuanbesar2');
        const fqtykecil = document.getElementById('fqtykecil');
        const fqtykecil2 = document.getElementById('fqtykecil2');

        if (fsatuankecil !== "") {
            fsatuanbesar.disabled = false;
            fsatuanbesar2.disabled = false;
            fqtykecil.disabled = false;
            fqtykecil2.disabled = false;
        } else {
            fsatuanbesar.disabled = true;
            fsatuanbesar2.disabled = true;
            fqtykecil.disabled = true;
            fqtykecil2.disabled = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkSatuan();
    });
</script>

<script>
    window.merekBrowser = function() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#merekTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('merek.browse') }}",
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                // Menggunakan parameter sorting standar DataTables
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            };
                        },
                        // Tidak perlu dataSrc jika backend merespons dalam format DataTables standar
                        // Jika backend masih merespons dengan struktur Laravel pagination, dataSrc diperlukan
                    },
                    columns: [{
                            data: 'fmerekcode',
                            name: 'fmerekcode',
                            className: 'font-mono text-sm', // Styling konsisten
                            width: '30%'
                        },
                        {
                            data: 'fmerekname',
                            name: 'fmerekname',
                            className: 'text-sm', // Styling konsisten
                            width: '55%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '15%',
                            render: function(data, type, row) {
                                // Menggunakan styling button yang seragam (biru)
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    // Menggunakan DOM custom yang sudah diseragamkan
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',

                    language: {
                        processing: "Memuat data...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Tidak ada data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ada data yang ditemukan",
                        emptyTable: "Tidak ada data tersedia",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    order: [
                        [1, 'asc'] // Default order by Merek Name
                    ],
                    autoWidth: false,
                    // Tambahkan initComplete untuk styling
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Style search input (disamakan dengan Supplier/PR)
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();

                        // Style length select (disamakan dengan Supplier/PR)
                        $container.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });

                // Handle button click
                $('#merekTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    this.choose(data);
                });
            },

            openModal() {
                this.open = true;
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            close() {
                this.open = false;
                if (this.table) {
                    this.table.search('').draw();
                }
            },

            choose(m) {
                window.dispatchEvent(new CustomEvent('merek-picked', {
                    detail: {
                        fmerekid: m.fmerekid,
                        fmerekcode: m.fmerekcode,
                        fmerekname: m.fmerekname
                    }
                }));
                this.close();
            },

            init() {
                // Menggunakan passive: true untuk performa
                window.addEventListener('merek-browse-open', () => this.openModal(), {
                    passive: true
                });
            }
        }
    };

    // Helper: update field saat merek-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('merek-picked', (ev) => {
            const {
                fmerekcode,
                fmerekid,
                fmerekname
            } = ev.detail || {};

            const sel = document.getElementById('merkSelect');
            const hid = document.getElementById('fmerek');

            if (sel) {
                sel.value = fmerekid || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hid) {
                hid.value = fmerekid || '';
            }

            // Optional: Enable select after picking
            const alpineData = Alpine.$data(sel.closest('[x-data]'));
            if (alpineData) {
                alpineData.isMerekEditable = true;
            }
        });
    });

    window.groupBrowser = function() {
        return {
            open: false,
            table: null,

            initDataTable() {
                if (this.table) {
                    this.table.destroy();
                }

                this.table = $('#groupTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('group.browse') }}", // Sesuaikan route Anda
                        type: 'GET',
                        data: function(d) {
                            return {
                                draw: d.draw,
                                page: (d.start / d.length) + 1,
                                per_page: d.length,
                                search: d.search.value
                            };
                        },
                        dataSrc: function(json) {
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'fgroupcode',
                            name: 'fgroupcode',
                            className: 'font-mono'
                        },
                        {
                            data: 'fgroupname',
                            name: 'fgroupname'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',

                    language: {
                        processing: "Memuat...",
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ada data yang ditemukan",
                        emptyTable: "Tidak ada data tersedia",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    order: [
                        [0, 'asc']
                    ], // Sort by kode group
                    autoWidth: false,
                    initComplete: function() {
                        const api = this.api();
                        const $container = $(api.table().container());

                        // Lebarkan search input
                        $container.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '400px',
                            maxWidth: '100%',
                            minWidth: '300px'
                        });

                        // Opsional: lebarkan wrapper search juga
                        $container.find('.dt-search, .dataTables_filter').css({
                            minWidth: '420px'
                        });

                        $container.find('.dt-search .dt-input, .dataTables_filter input').focus();
                    }
                });

                // Handle button click
                $('#groupTable').on('click', '.btn-choose', (e) => {
                    const data = this.table.row($(e.target).closest('tr')).data();
                    this.choose(data);
                });
            },

            openModal() {
                this.open = true;
                // Initialize DataTable setelah modal terbuka
                this.$nextTick(() => {
                    this.initDataTable();
                });
            },

            close() {
                this.open = false;
                if (this.table) {
                    this.table.search('').draw();
                }
            },

            choose(g) {
                window.dispatchEvent(new CustomEvent('group-picked', {
                    detail: {
                        fgroupid: g.fgroupid,
                        fgroupcode: g.fgroupcode,
                        fgroupname: g.fgroupname
                    }
                }));
                this.close();
            },

            init() {
                window.addEventListener('group-browse-open', () => this.openModal());
            }
        }
    };

    // Helper: update field saat group-picked
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('group-picked', (ev) => {
            const {
                fgroupid
            } = ev.detail || {};
            const sel = document.getElementById('groupSelect');
            const hidCode = document.getElementById('groupCodeHidden');

            if (sel) {
                sel.value = fgroupid || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hidCode) {
                hidCode.value = fgroupid || ''; // Update nilai tersembunyi agar masuk ke database
            }
        });
    });
</script>

<script>
    /**
     * Fungsi utama untuk mengelola visibilitas field satuan dan pembaruan label.
     * Dipanggil saat ada perubahan pada Satuan Kecil atau Satuan 2.
     */
    function updateSatuanLogic() {
        // --- 1. Ambil Elemen Utama ---
        const smallSatuan = document.getElementById('fsatuankecil');
        const largeSatuan1 = document.getElementById('fsatuanbesar');
        const largeSatuan2 = document.getElementById('fsatuanbesar2');

        const qty1 = document.getElementById('fqtykecil');
        const qty2 = document.getElementById('fqtykecil2');

        const block2 = document.getElementById('satuan2-block');
        const br2 = document.getElementById('br-satuan2');
        const block3 = document.getElementById('satuan3-block');

        // Target span untuk menampilkan kode Satuan Kecil
        const targets = document.querySelectorAll('.satuan-kecil-display');

        // Ambil nilai yang dipilih
        const smallSatuanValue = smallSatuan ? smallSatuan.value : '';
        const largeSatuan1Value = largeSatuan1 ? largeSatuan1.value : '';

        // --- 2. Logika Satuan 2 & Satuan Kecil Display ---
        if (smallSatuanValue) {
            // Tampilkan block Satuan 2 dan elemen <br>
            if (block2) block2.style.display = 'block';
            if (br2) br2.style.display = 'block';

            // Aktifkan field Satuan 2 (Select dan Input Isi)
            if (largeSatuan1) largeSatuan1.disabled = false;
            if (qty1) qty1.disabled = false;

        } else {
            // Sembunyikan block Satuan 2, nonaktifkan, dan reset nilai
            if (block2) block2.style.display = 'none';
            if (br2) br2.style.display = 'none';

            if (largeSatuan1) {
                largeSatuan1.disabled = true;
                largeSatuan1.value = "";
            }
            if (qty1) {
                qty1.disabled = true;
                qty1.value = 0;
            }
        }

        // Tampilkan kode Satuan Kecil di samping field Isi untuk semua target
        targets.forEach(function(target) {
            target.textContent = smallSatuanValue;
        });

        // --- 3. Logika Satuan 3 ---
        // Satuan 3 muncul jika Satuan 2 sedang terlihat DAN Satuan 2 memiliki nilai yang dipilih
        const isSatuan2Visible = block2 ? block2.style.display !== 'none' : false;

        if (isSatuan2Visible && largeSatuan1Value) {
            // Tampilkan block Satuan 3
            if (block3) block3.style.display = 'block';

            // Aktifkan field Satuan 3
            if (largeSatuan2) largeSatuan2.disabled = false;
            if (qty2) qty2.disabled = false;
        } else {
            // Sembunyikan block Satuan 3, nonaktifkan, dan reset nilai
            if (block3) block3.style.display = 'none';

            if (largeSatuan2) {
                largeSatuan2.disabled = true;
                largeSatuan2.value = "";
            }
            if (qty2) {
                qty2.disabled = true;
                qty2.value = 0;
            }
        }
    }

    // --- Pemasangan Event Listener ---

    // Panggil fungsi ini saat dokumen dimuat untuk inisialisasi awal (kasus halaman Create)
    document.addEventListener('DOMContentLoaded', updateSatuanLogic);

    // Event listener untuk Satuan Kecil (Sudah dipasang melalui onchange="updateSatuanLogic()" di HTML)
    // Event listener untuk Satuan 2 (Sudah dipasang melalui onchange="updateSatuanLogic()" di HTML)
</script>
