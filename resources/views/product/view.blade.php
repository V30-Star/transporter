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
                    @if($product->fimage1)
                    <div class="mt-4 w-1/2">
                        <label class="block text-sm font-medium">Foto Product</label>
                        <div class="mt-2">
                            <img id="productImage" src="https://drive.google.com/uc?id={{ $product->fimage1 }}&export=view" 
                                 alt="Product Image" 
                                 class="max-w-xs max-h-64 border rounded shadow cursor-pointer hover:opacity-90 transition-opacity"
                                 onclick="openImageModal(this.src)">
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Klik gambar untuk melihat lebih besar</p>
                    </div>
                    @endif

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
     * Fungsi utama untuk mengelola visibilitas field satuan dan pembaruan label.
     * Dipanggil saat ada perubahan pada Satuan Kecil atau Satuan 2.
     */
    let isUpdating = false;

    function updateSatuanLogic() {
        if (isUpdating) return;

        isUpdating = true; // Set flag sedang update
        // --- 1. Ambil Elemen Utama ---
        const smallSatuan = document.getElementById('fsatuankecil');
        const largeSatuan1 = document.getElementById('fsatuanbesar');
        const largeSatuan2 = document.getElementById('fsatuanbesar2');

        const qty1 = document.getElementById('fqtykecil');
        const qty2 = document.getElementById('fqtykecil2');

        const block2 = document.getElementById('satuan2-block');
        const br2 = document.getElementById('br-satuan2');
        const block3 = document.getElementById('satuan3-block');

        const satuanKecil = $('#fsatuankecil').val();
        const satuan2 = $('#fsatuanbesar').val();
        const satuan3 = $('#fsatuanbesar2').val();

        // Target span untuk menampilkan kode Satuan Kecil
        const targets = document.querySelectorAll('.satuan-kecil-display');

        // HJ Labels
        const hjSatuanKecilLabel = document.getElementById('hj-satuan-kecil-label');
        const hjSatuanBesarLabel = document.getElementById('hj-satuan-besar-label');
        const hjSatuanBesar2Label = document.getElementById('hj-satuan-besar2-label');

        const hjSatuanKecilLevel1Label = document.getElementById('hj-satuan-kecil-level1-label');
        const hjSatuanKecilLevel2Label = document.getElementById('hj-satuan-kecil-level2-label');
        const hjSatuanKecilLevel3Label = document.getElementById('hj-satuan-kecil-level3-label');

        const hjSatuanBesarLevel1Label = document.getElementById('hj-satuan-besar-level1-label');
        const hjSatuanBesarLevel2Label = document.getElementById('hj-satuan-besar-level2-label');
        const hjSatuanBesarLevel3Label = document.getElementById('hj-satuan-besar-level3-label');

        // HJ Input Fields
        const hjSatuanKecilInput = document.getElementById('fhargajual3level1');
        const hjSatuanBesarInput = document.getElementById('fhargajual3level2');
        const hjSatuanBesar2Input = document.getElementById('fhargajual3level3');

        // Ambil nilai yang dipilih
        const smallSatuanValue = smallSatuan ? smallSatuan.value : '';
        const largeSatuan1Value = largeSatuan1 ? largeSatuan1.value : '';
        const largeSatuan2Value = largeSatuan2 ? largeSatuan2.value : '';

        // --- 2. Logika Satuan 2 & Satuan Kecil Display ---
        if (smallSatuanValue) {
            // Tampilkan block Satuan 2 dan elemen <br>
            if (block2) block2.style.display = 'block';
            if (br2) br2.style.display = 'block';

            // Aktifkan field Satuan 2 (Select dan Input Isi)
            if (largeSatuan1) largeSatuan1.disabled = false;
            if (qty1) qty1.disabled = false;

            // Aktifkan HJ Satuan Kecil input
            if (hjSatuanKecilInput) hjSatuanKecilInput.disabled = false;

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

            // Nonaktifkan dan reset HJ Satuan Kecil input
            if (hjSatuanKecilInput) {
                hjSatuanKecilInput.disabled = true;
                hjSatuanKecilInput.value = 0;
            }
        }

        // Tampilkan kode Satuan Kecil di samping field Isi untuk semua target
        targets.forEach(function(target) {
            target.textContent = smallSatuanValue;
        });

        // --- 3. Update HJ Labels ---

        if (hjSatuanKecilLevel1Label) {
            hjSatuanKecilLevel1Label.textContent = smallSatuanValue || '-';
        }
        if (hjSatuanKecilLevel2Label) {
            hjSatuanKecilLevel2Label.textContent = smallSatuanValue || '-';
        }
        if (hjSatuanKecilLevel3Label) {
            hjSatuanKecilLevel3Label.textContent = smallSatuanValue || '-';
        }

        if (hjSatuanBesarLevel1Label) {
            hjSatuanBesarLevel1Label.textContent = largeSatuan1Value || '-';
        }
        if (hjSatuanBesarLevel2Label) {
            hjSatuanBesarLevel2Label.textContent = largeSatuan1Value || '-';
        }
        if (hjSatuanBesarLevel3Label) {
            hjSatuanBesarLevel3Label.textContent = largeSatuan1Value || '-';
        }

        if (hjSatuanKecilLabel) {
            hjSatuanKecilLabel.textContent = largeSatuan2Value || '-';
        }
        if (hjSatuanBesarLabel) {
            hjSatuanBesarLabel.textContent = largeSatuan2Value || '-';
        }
        if (hjSatuanBesar2Label) {
            hjSatuanBesar2Label.textContent = largeSatuan2Value || '-';
        }

        // --- 4. Logika Satuan 3 ---
        // Satuan 3 muncul jika Satuan 2 sedang terlihat DAN Satuan 2 memiliki nilai yang dipilih
        const isSatuan2Visible = block2 ? block2.style.display !== 'none' : false;

        if (isSatuan2Visible && largeSatuan1Value) {
            // Tampilkan block Satuan 3
            if (block3) block3.style.display = 'block';

            // Aktifkan field Satuan 3
            if (largeSatuan2) largeSatuan2.disabled = false;
            if (qty2) qty2.disabled = false;

            // Aktifkan HJ Satuan Besar input
            if (hjSatuanBesarInput) hjSatuanBesarInput.disabled = false;

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

            // Nonaktifkan dan reset HJ Satuan Besar input
            if (hjSatuanBesarInput) {
                hjSatuanBesarInput.disabled = true;
                hjSatuanBesarInput.value = 0;
            }
        }

        // --- 5. HJ Satuan 3 ---
        const isSatuan3Visible = block3 ? block3.style.display !== 'none' : false;

        if (isSatuan3Visible && largeSatuan2Value) {
            // Aktifkan HJ Satuan Besar 2 input
            if (hjSatuanBesar2Input) hjSatuanBesar2Input.disabled = false;
        } else {
            // Nonaktifkan dan reset HJ Satuan Besar 2 input
            if (hjSatuanBesar2Input) {
                hjSatuanBesar2Input.disabled = true;
                hjSatuanBesar2Input.value = 0;
            }
        }

        if (satuanKecil !== "" && satuanKecil !== null) {
            $('#satuan2-block').show();
            $('#hj-level1-block').show();
            $('#fsatuanbesar').prop('disabled', false);

            $('.satuan-kecil-display').text(satuanKecil);
            $('#hj-satuan-kecil-level1-label, #hj-satuan-kecil-level2-label, #hj-satuan-kecil-level3-label').text(
                satuanKecil);
        } else {
            $('#satuan2-block').hide();
            $('#hj-level1-block').hide();
            // Reset tanpa memicu loop yang parah
            if ($('#fsatuanbesar').val() !== "") {
                $('#fsatuanbesar').val('').trigger('change.select2'); // Gunakan namespace select2 agar lebih spesifik
            }
            $('#fsatuanbesar').prop('disabled', true);
        }

        // --- LOGIKA SATUAN 2 ---
        if (satuan2 !== "" && satuan2 !== null && satuanKecil !== "") {
            $('#satuan3-block').show();
            $('#hj-level2-block').show();
            $('#fsatuanbesar2').prop('disabled', false);
            $('#fqtykecil').prop('disabled', false);

            $('#hj-satuan-besar-level1-label, #hj-satuan-besar-level2-label, #hj-satuan-besar-level3-label').text(
                satuan2);
        } else {
            $('#satuan3-block').hide();
            $('#hj-level2-block').hide();
            if ($('#fsatuanbesar2').val() !== "") {
                $('#fsatuanbesar2').val('').trigger('change.select2');
            }
            $('#fsatuanbesar2').prop('disabled', true);
            $('#fqtykecil').prop('disabled', true);
        }

        // --- LOGIKA SATUAN 3 ---
        if (satuan3 !== "" && satuan3 !== null) {
            $('#fqtykecil2').prop('disabled', false);
        } else {
            $('#fqtykecil2').prop('disabled', true);
        }

        isUpdating = false;
    }

    // --- Pemasangan Event Listener ---

    // Panggil fungsi ini saat dokumen dimuat untuk inisialisasi awal (kasus halaman Create)
    document.addEventListener('DOMContentLoaded', updateSatuanLogic);

    // Event listener untuk Satuan Kecil (Sudah dipasang melalui onchange="updateSatuanLogic()" di HTML)
    // Event listener untuk Satuan 2 (Sudah dipasang melalui onchange="updateSatuanLogic()" di HTML)

    // --- Image Modal Functions ---
    function openImageModal(src) {
        if (!src) return;
        
        var modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:10000;display:flex;align-items:center;justify-content:center;cursor:pointer;';
        modal.innerHTML = '<img src="' + src + '" style="max-width:90%;max-height:90%;border-radius:8px;box-shadow:0 0 20px rgba(255,255,255,0.3);" />';
        modal.onclick = function() { this.remove(); };
        document.body.appendChild(modal);
    }
</script>
