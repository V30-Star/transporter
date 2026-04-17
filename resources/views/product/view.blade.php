@extends('layouts.app')

@section('title', 'View Product')

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
        .invalid-feedback {
            color: #f87171;
            font-size: 0.875rem;
            margin-top: 4px;
            padding-left: 10px;
        }

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
            /* Black border */
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

    <div x-data="{ showModal: false, open: true, selected: 'alamatsurat', frekening: '' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1500px] w-full mx-auto">
            @php
                $isApproved = !empty($product->fapproval);
            @endphp
            <div class="space-y-4">
                <div>
                    <!-- Group Produk Dropdown -->
                    <div class="mt-2 w-1/2" x-data="{ isEditable: false }">
                        <label class="block text-sm font-medium">Group Produk</label>
                        <div class="flex items-center gap-2">
                            <select disabled name="fgroupcodeSelect" :disabled="!isEditable"
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fgroupcode') border-red-500 @enderror"
                                id="groupSelect">
                                <option value=""></option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupid }}"
                                        {{ old('fgroupcode', $product->fgroupcode) == $group->fgroupid ? 'selected' : '' }}>
                                        {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="fgroupcode" value="{{ old('fgroupcode', $product->fgroupcode) }}">
                        </div>

                        @error('fgroupcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-2 w-1/2" x-data="{ isMerekEditable: false }">
                        <label class="block text-sm font-medium">Merek</label>
                        <div class="flex items-center gap-2">
                            <select name="fmerek" :disabled="!isMerekEditable" {{-- ✅ name langsung fmerek --}}
                                class="w-full border rounded px-3 py-2 @error('fmerek') border-red-500 @enderror"
                                id="merkSelect">
                                <option value=""></option>
                                @foreach ($merks as $merk)
                                    <option value="{{ $merk->fmerekid }}"
                                        {{ old('fmerek', $product->fmerek) == $merk->fmerekid ? 'selected' : '' }}>
                                        {{ $merk->fmerekname }}
                                    </option>
                                @endforeach
                            </select>
                            {{-- ❌ Hapus hidden input --}}
                        </div>
                        @error('fmerek')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kode Product -->
                    <div class="mt-2 w-1/3">
                        <label class="block text-sm font-medium">Kode Product</label>
                        <input type="text" name="fprdcode" id="fprdcode" readonly
                            value="{{ old('fprdcode', $product->fprdcode) }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fprdcode') border-red-500 @enderror">
                        @error('fprdcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nama Product -->
                    <div class="mt-2 w-1/2">
                        <label class="block text-sm font-medium">Nama Product</label>
                        <input type="text" name="fprdname" id="fprdname" readonly
                            value="{{ old('fprdname', $product->fprdname) }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fprdname') border-red-500 @enderror"
                            autofocus>
                        @error('fprdname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Barcode -->
                    <div class="mt-2 w-1/3">
                        <label class="block text-sm font-medium">Barcode</label>
                        <input readonly type="text" name="fbarcode" value="{{ old('fbarcode', $product->fbarcode) }}"
                            class="w-full border rounded px-3 py-2 bg-gray-100 @error('fbarcode') border-red-500 @enderror">
                        @error('fbarcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="satuan-container">

                        {{-- Satuan Kecil --}}
                        <div class="mt-2 flex items-end gap-4">
                            <div class="w-1/3">

                                <label class="block text-sm font-medium">Satuan Kecil</label>
                                <select disabled
                                    class="w-full border rounded px-3 py-2 bg-blue-50 border-blue-300 focus:ring-blue-500 @error('fsatuankecil') border-red-500 @enderror"
                                    name="fsatuankecil" id="fsatuankecil" onchange="updateSatuanLogic();">
                                    <option value="" selected> Pilih Satuan 1</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}"
                                            {{ old('fsatuankecil', $product->fsatuankecil) == $satu->fsatuancode ? 'selected' : '' }}>
                                            {{ $satu->fsatuancode }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="w-1/4 invisible"></div>

                            <div class="w-1/6">
                                <label class="block text-sm font-medium">HPP Satuan Kecil</label>
                                <input type="text" name="fhpp" id="fhpp" disabled
                                    class="autonumeric w-full border border-blue-300 rounded px-3 py-2 bg-blue-50 focus:bg-white transition-colors"
                                    {{-- Gunakan format murni angka tanpa ribuan agar AutoNumeric yang memformatnya --}} value="{{ old('fhpp', $product->fhpp ?? 0) }}">
                            </div>
                            @error('fsatuankecil')
                                <div class="text-red-600 text-sm mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Satuan 2 --}}
                        <div id="satuan2-block" style="display: none;">
                            <div class="flex items-end gap-4">
                                <div class="w-1/3">
                                    <label class="block text-sm font-medium">Satuan 2</label>
                                    <select
                                        class="w-full border rounded px-3 py-2 bg-yellow-50 @error('fsatuanbesar') border-red-500 @enderror"
                                        name="fsatuanbesar" id="fsatuanbesar" disabled onchange="updateSatuanLogic();">
                                        <option value="" selected>Pilih Satuan 2</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}" data-name="{{ $satu->fsatuanname }}"
                                                {{ old('fsatuanbesar', $product->fsatuanbesar) == $satu->fsatuancode ? 'selected' : '' }}>
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

                                <div class="w-1/4"> {{-- Kita lebarkan sedikit dari 1/6 ke 1/4 agar ruang teks lebih lega --}}
                                    <label class="block text-sm font-medium">Isi</label>
                                    <div
                                        class="flex items-center border border-yellow-300 rounded bg-yellow-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-yellow-400 transition-all">
                                        {{-- Input tanpa border agar menyatu dengan container --}}
                                        <input type="text" name="fqtykecil" id="fqtykecil"
                                            value={{ old('fqtykecil', $product->fqtykecil) }}
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
                                        value="{{ old('fhpp2', $product->fhpp2) }}"
                                        class="autonumeric w-full border border-yellow-300 rounded px-3 py-2 bg-yellow-50"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        {{-- Satuan 3 --}}
                        <div id="satuan3-block" style="display: none;">
                            <div class="flex items-end gap-4">
                                <div class="w-1/3">
                                    <label class="block text-sm font-medium">Satuan 3</label>
                                    <select
                                        class="w-full border rounded px-3 py-2 bg-purple-50 @error('fsatuanbesar2') border-red-500 @enderror"
                                        name="fsatuanbesar2" id="fsatuanbesar2"
                                        data-select2-id="select2-data-fsatuanbesar2" tabindex="-1" aria-hidden="true">
                                        <option value="" selected>Pilih Satuan 3</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                data-name="{{ $satu->fsatuanname }}"
                                                {{ old('fsatuanbesar2', $product->fsatuanbesar2) == $satu->fsatuancode ? 'selected' : '' }}>
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

                                <div class="w-1/4"> {{-- Lebar dinaikkan ke 1/4 agar ruang teks lebih lega --}}
                                    <label class="block text-sm font-medium">Isi</label>
                                    <div
                                        class="flex items-center border border-purple-300 rounded bg-purple-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-purple-400 transition-all">
                                        {{-- Input tanpa border agar menyatu dengan container --}}
                                        <input type="text" name="fqtykecil2" id="fqtykecil2"
                                            value={{ old('fqtykecil2', $product->fqtykecil2) }}
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
                                        value="{{ old('fhpp3', $product->fhpp3) }}"
                                        class="autonumeric w-full border border-purple-300 rounded px-3 py-2 bg-purple-50"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        $(document).ready(function() {
                            // 1. Inisialisasi AutoNumeric secara individual agar lebih stabil
                            const autoNumericOptions = {
                                digitGroupSeparator: '.',
                                decimalCharacter: ',',
                                decimalPlaces: 2,
                                unformatOnSubmit: true,
                                allowDecimalPadding: true,
                                outputFormat: "number"
                            };

                            // Inisialisasi dan simpan instance ke dalam variabel
                            const anHpp = new AutoNumeric('#fhpp', autoNumericOptions);
                            const anHpp2 = new AutoNumeric('#fhpp2', autoNumericOptions);
                            const anHpp3 = new AutoNumeric('#fhpp3', autoNumericOptions);
                            const anQty2 = new AutoNumeric('#fqtykecil', autoNumericOptions);
                            const anQty3 = new AutoNumeric('#fqtykecil2', autoNumericOptions);

                            function calculateHPPRows() {
                                const valHppKecil = anHpp.getNumber();
                                const valQty2 = anQty2.getNumber();
                                const valQty3 = anQty3.getNumber();

                                // Update HPP 2 & 3
                                if (valQty2 > 0) {
                                    anHpp2.set(valHppKecil * valQty2);
                                }
                                if (valQty3 > 0) {
                                    anHpp3.set(valHppKecil * valQty3);
                                }
                            }

                            // 2. Event Listener khusus AutoNumeric
                            // Gunakan event 'autoNumeric:newValue' agar kalkulasi akurat setelah format selesai
                            $('#fhpp, #fqtykecil, #fqtykecil2').on('autoNumeric:newValue', function() {
                                calculateHPPRows();
                            });

                            // 3. Jalankan kalkulasi pertama kali saat halaman terbuka
                            // Gunakan sedikit delay agar AutoNumeric selesai memformat nilai awal dari DB
                            setTimeout(() => {
                                calculateHPPRows();
                            }, 300);
                        });
                    </script>

                    <!-- Satuan Default Dropdown -->
                    <div class="mt-2 w-1/4 bg-gray-100">
                        <label class="block text-sm font-medium">Satuan Default</label>
                        <select name="fsatuandefault" disabled
                            class="w-full border rounded px-3 py-2 @error('fsatuandefault') border-red-500 @enderror">
                            <option value="1"
                                {{ old('fsatuandefault', $product->fsatuandefault) == '1' ? 'selected' : '' }}>Satuan 1
                            </option>
                            <option value="2"
                                {{ old('fsatuandefault', $product->fsatuandefault) == '2' ? 'selected' : '' }}>Satuan 2
                            </option>
                            <option value="3"
                                {{ old('fsatuandefault', $product->fsatuandefault) == '3' ? 'selected' : '' }}>Satuan 3
                            </option>
                        </select>
                        @error('fsatuandefault')
                            <div class="text-red-600 text-sm mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Harga Satuan 3 Level 1 -->
                        <div>
                            <label for="fhargajuallevel1" class="block text-sm font-medium">Harga Jual Satuan 1
                                <span id="hj-satuan-kecil-level1-label" class="uppercase">-</span> Level
                                1</label>
                            <div class="d-flex">
                                <input type="text" disabled
                                    class="w-1/10 border rounded px-3 py-2 bg-blue-50 border-blue-300 focus:ring-blue-500 @error('fhargajuallevel1') is-invalid @enderror"
                                    name="fhargajuallevel1" id="fhargajuallevel1"
                                    value="{{ old('fhargajuallevel1', $product->fhargajuallevel1) }}">
                                @error('fhargajuallevel1')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan 3 Level 2 -->
                        <div>
                            <label for="fhargajuallevel2" class="block text-sm font-medium">Harga Jual Satuan
                                1<span id="hj-satuan-kecil-level2-label" class="uppercase">-</span> Level
                                2</label>
                            <div class="d-flex">
                                <input type="text" disabled
                                    class="w-1/10 border rounded px-3 py-2 bg-blue-50 border-blue-300 focus:ring-blue-500 @error('fhargajuallevel2') is-invalid @enderror"
                                    name="fhargajuallevel2" id="fhargajuallevel2"
                                    value="{{ old('fhargajuallevel2', $product->fhargajuallevel2) }}">
                                @error('fhargajuallevel2')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan 3 Level 3 -->
                        <div>
                            <label for="fhargajuallevel3" class="block text-sm font-medium">Harga Jual Satuan 1
                                <span id="hj-satuan-kecil-level3-label" class="uppercase">-</span> Level
                                3</label>
                            <div class="d-flex">
                                <input type="text" disabled
                                    class="w-1/10 border rounded px-3 py-2 bg-blue-50 border-blue-300 focus:ring-blue-500 @error('fhargajuallevel3') is-invalid @enderror"
                                    name="fhargajuallevel3" id="fhargajuallevel3"
                                    value="{{ old('fhargajuallevel3', $product->fhargajuallevel3) }}">
                                @error('fhargajuallevel3')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="hj-level1-block" style="display: none;">
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- HJ. Besar Level 1 -->
                            <div>
                                <label for="fhargajual2level1" class="block text-sm font-medium">Harga Jual Satuan 2
                                    <span id="hj-satuan-besar-level1-label" class="uppercase">-</span> Level 1</label>
                                <div class="d-flex">
                                    <input type="text" disabled class="w-1/10 border rounded px-3 py-2 bg-yellow-50"
                                        value="{{ $product->fhargajual2level1 ?? 0 }}">
                                </div>
                            </div>

                            <!-- HJ. Besar Level 2 -->
                            <div>
                                <label for="fhargajual2level2" class="block text-sm font-medium">Harga Jual Satuan 2
                                    <span id="hj-satuan-besar-level2-label" class="uppercase">-</span> Level 2</label>
                                <div class="d-flex">
                                    <input type="text" disabled class="w-1/10 border rounded px-3 py-2 bg-yellow-50"
                                        value="{{ $product->fhargajual2level2 ?? 0 }}">
                                </div>
                            </div>

                            <!-- HJ. Besar Level 3 -->
                            <div>
                                <label for="fhargajual2level3" class="block text-sm font-medium">Harga Jual Satuan 2
                                    <span id="hj-satuan-besar-level3-label" class="uppercase">-</span> Level 3</label>
                                <div class="d-flex">
                                    <input type="text" disabled class="w-1/10 border rounded px-3 py-2 bg-yellow-50"
                                        value="{{ $product->fhargajual2level3 ?? 0 }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- HJ Dynamic Columns --}}
                    <div id="hj-level2-block" style="display: none;">
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- HJ <PCS> Level 1 -->
                            <div>
                                <label for="fhargajual3level1" class="block text-sm font-medium">
                                    Harga Jual Satuan 3 <span id="hj-satuan-kecil-label"
                                        class="uppercase">{{ $product->fsatuankecil ?? '-' }}</span> Level 1
                                </label>
                                <div class="d-flex">
                                    <input type="text" disabled class="w-1/10 border rounded px-3 py-2 bg-purple-50"
                                        value="{{ $product->fhargajual3level1 ?? 0 }}">
                                </div>
                            </div>

                            <!-- HJ <CTN> Level 1 -->
                            <div>
                                <label for="fhargajual3level2" class="block text-sm font-medium">
                                    Harga Jual Satuan 3 <span id="hj-satuan-besar2-label"
                                        class="uppercase">{{ $product->fsatuanbesar ?? '-' }}</span> Level 2
                                </label>
                                <div class="d-flex">
                                    <input type="text" disabled class="w-1/10 border rounded px-3 py-2 bg-purple-50"
                                        value="{{ $product->fhargajual3level2 ?? 0 }}">
                                </div>
                            </div>

                            <!-- HJ <DUS> Level 1 -->
                            <div>
                                <label for="fhargajual3level3" class="block text-sm font-medium">
                                    Harga Jual Satuan 3 <span id="hjSatuanBesar2Label"
                                        class="uppercase">{{ $product->fsatuanbesar2 ?? '-' }}</span> Level 3
                                </label>
                                <div class="d-flex">
                                    <input type="text" disabled class="w-1/10 border rounded px-3 py-2 bg-purple-50"
                                        value="{{ $product->fhargajual3level3 ?? 0 }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Min Stok --}}
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium mb-1">Min.Stok</label>

                        {{-- Container yang membungkus input dan satuan --}}
                        <div
                            class="flex items-center border border-gray-300 rounded bg-gray-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-blue-400 transition-all @error('fminstock') border-red-500 @enderror">

                            {{-- Input tanpa border agar menyatu dengan container --}}
                            <input type="text" name="fminstock" id="fminstock" disabled
                                value="{{ old('fminstock', $product->fminstock) }}"
                                class="flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right">

                            {{-- Garis vertikal (border-l) dan teks satuan --}}
                            <span id="satuanKecilTarget"
                                class="satuan-kecil-display text-gray-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-gray-200 ml-2 pl-2 uppercase">
                                {{-- Isi satuan muncul via JavaScript --}}
                            </span>
                        </div>

                        @error('fminstock')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Jenis --}}
                    <div class="mt-2 w-1/4">
                        <label class="block text-sm font-medium">Jenis</label>
                        <select name="ftype" disabled
                            class="w-full border rounded px-3 py-2 bg-gray-100 @error('ftype') border-red-500 @enderror">
                            <option value="Produk" {{ old('ftype', $product->ftype) == 'Produk' ? 'selected' : '' }}>
                                Product</option>
                            <option value="Jasa" {{ old('ftype', $product->ftype) == 'Jasa' ? 'selected' : '' }}>
                                Jasa</option>
                        </select>
                        @error('ftype')
                            <div class="text-red-600 text-sm mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Foto Product --}}
                    <div class="mt-4 w-full">
                        <label class="block text-sm font-medium mb-2">Foto Product</label>

                        <div class="flex flex-col gap-6">
                            @foreach ([1, 2, 3] as $imgNo)
                                @php
                                    $field = 'fimage' . $imgNo;
                                    $imageRaw = (string) ($product->{$field} ?? '');
                                    $driveFileId = null;
                                    if ($imageRaw !== '') {
                                        if (str_contains($imageRaw, 'http')) {
                                            if (preg_match('~/d/([a-zA-Z0-9_-]+)~', $imageRaw, $m)) {
                                                $driveFileId = $m[1];
                                            } elseif (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $imageRaw, $m)) {
                                                $driveFileId = $m[1];
                                            }
                                        } else {
                                            $driveFileId = $imageRaw;
                                        }
                                    }
                                    $drivePreviewUrl = $driveFileId
                                        ? route('product.photo', ['fprdid' => $product->fprdid, 'field' => $field])
                                        : null;
                                @endphp

                                <div class="max-w-md">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">Foto {{ $imgNo }}</p>
                                    @if ($driveFileId)
                                        <img src="{{ $drivePreviewUrl }}" alt="Product Image {{ $imgNo }}"
                                            class="w-full max-h-80 object-cover border rounded shadow cursor-pointer hover:opacity-90 transition-opacity"
                                            onclick="openImageModal(this.src)"
                                            onerror="this.onerror=null; this.src='https://drive.google.com/thumbnail?id={{ $driveFileId }}&sz=w1000';">
                                    @else
                                        <div
                                            class="w-full h-32 border rounded bg-gray-100 text-gray-400 flex items-center justify-center text-sm italic">
                                            Belum ada foto {{ $imgNo }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <p class="text-xs text-gray-500 mt-4 italic">Klik gambar untuk melihat lebih besar</p>
                    </div>
                    
                    <div class="md:col-span-2 flex justify-center items-center space-x-2">
                        <fieldset {{ $isApproved ? 'disabled' : '' }}>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-medium">Approval</label>
                                <label class="switch">
                                    <input type="checkbox" name="approve_now" id="approvalToggle"
                                        {{ !empty($product->fapproval) ? 'checked' : '' }} disabled>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </fieldset>
                    </div>
                    <br>
                    <div class="flex justify-center mt-4">
                        <label for="statusToggle"
                            class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition bg-gray-100">
                            <span class="text-sm font-medium">Non Aktif</span>
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                                {{ old('fnonactive', $product->fnonactive) == '1' ? 'checked' : '' }} disabled>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="window.location.href='{{ route('product.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
            <br>
            <hr><br>
            <span class="text-sm text-gray-600 flex justify-between items-center">
                <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
                <span>{{ \Carbon\Carbon::parse($product->fupdatedat ?: $product->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
            </span>
        </div>
    </div>
@endsection

<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
<script>
    /**
     * Versi VIEW — hanya untuk tampilkan/sembunyikan block dan update label.
     * Semua select/input tetap disabled, tidak ada enable/disable via JS.
     */
    function initViewSatuanDisplay() {
        const satuanKecil = $('#fsatuankecil').val();
        const satuan2 = $('#fsatuanbesar').val();
        const satuan3 = $('#fsatuanbesar2').val();

        // --- Update span display ---
        $('.satuan-kecil-display').text(satuanKecil || '');

        // --- Update HJ Labels ---
        $('#hj-satuan-kecil-level1-label, #hj-satuan-kecil-level2-label, #hj-satuan-kecil-level3-label')
            .text(satuanKecil || '-');
        $('#hj-satuan-besar-level1-label, #hj-satuan-besar-level2-label, #hj-satuan-besar-level3-label')
            .text(satuan2 || '-');
        $('#hj-satuan-kecil-label, #hj-satuan-besar-label, #hj-satuan-besar2-label')
            .text(satuan3 || '-');

        // --- Tampilkan/sembunyikan block Satuan 2 ---
        if (satuanKecil !== '' && satuanKecil !== null) {
            $('#satuan2-block').show();
            $('#hj-level1-block').show();
        } else {
            $('#satuan2-block').hide();
            $('#hj-level1-block').hide();
        }

        // --- Tampilkan/sembunyikan block Satuan 3 ---
        if (satuan2 !== '' && satuan2 !== null && satuanKecil !== '') {
            $('#satuan3-block').show();
            $('#hj-level2-block').show();
        } else {
            $('#satuan3-block').hide();
            $('#hj-level2-block').hide();
        }
    }

    $(document).ready(function() {
        // Pastikan semua select benar-benar disabled — tidak ada yang boleh diklik
        $('#fsatuankecil, #fsatuanbesar, #fsatuanbesar2').prop('disabled', true);

        // Jalankan hanya untuk update tampilan label & show/hide block
        initViewSatuanDisplay();
    });

    // --- Image Modal ---
    function openImageModal(src) {
        if (!src) return;
        var modal = document.createElement('div');
        modal.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:10000;display:flex;align-items:center;justify-content:center;cursor:pointer;';
        modal.innerHTML = '<img src="' + src +
            '" style="max-width:90%;max-height:90%;border-radius:8px;box-shadow:0 0 20px rgba(255,255,255,0.3);" />';
        modal.onclick = function() {
            this.remove();
        };
        document.body.appendChild(modal);
    }

    function deletePhoto() {
        if (!confirm('Hapus foto product ini?')) return;
        fetch('{{ route('product.delete-photo', $product->fprdid) }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal menghapus foto');
                alert(data.message || 'Foto berhasil dihapus');
                window.location.reload();
            })
            .catch((error) => {
                alert(error.message || 'Terjadi kesalahan saat menghapus foto');
            });
    }
</script>
