@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Produk' : 'Edit Produk')

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

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

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

        input:checked+.slider {
            background-color: #4CAF50;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        /* Select2 satuan warna */
        #fsatuankecil+.select2-container .select2-selection--single {
            background-color: #eff6ff !important;
            border-color: #93c5fd !important;
        }

        #fsatuanbesar+.select2-container .select2-selection--single {
            background-color: #fefce8 !important;
            border-color: #fde047 !important;
        }

        #fsatuanbesar2+.select2-container .select2-selection--single {
            background-color: #faf5ff !important;
            border-color: #d8b4fe !important;
        }

        .satuan-kecil-display {
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }

        input:focus,
        select:focus,
        textarea:focus {
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

        /* ─── Layout sections ─── */
        .section-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .field-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 13px;
            background: #fff;
            color: #111827;
        }

        .field-input:disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .field-input.blue {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .field-input.yellow {
            background: #fefce8;
            border-color: #fde047;
        }

        .field-input.purple {
            background: #faf5ff;
            border-color: #d8b4fe;
        }

        /* satuan badge */
        .satuan-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 99px;
            margin-bottom: 6px;
        }

        .satuan-badge.blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .satuan-badge.yellow {
            background: #fef9c3;
            color: #92400e;
        }

        .satuan-badge.purple {
            background: #ede9fe;
            color: #6d28d9;
        }

        /* Harga jual matrix table */
        .hj-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .hj-table th {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-align: center;
        }

        .hj-table th:first-child {
            text-align: left;
        }

        .hj-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }

        .hj-table td.row-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
            background: #f9fafb;
        }

        .hj-table input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 13px;
            text-align: right;
        }

        .hj-table input.blue {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .hj-table input.yellow {
            background: #fefce8;
        }

        .hj-table input.purple {
            background: #faf5ff;
            border-color: #d8b4fe;
        }

        /* Image sidebar */
        .img-upload-box {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #9ca3af;
            transition: border-color 0.15s;
            padding: 12px 8px;
            text-align: center;
            gap: 4px;
        }

        .img-upload-box:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .img-upload-box svg {
            width: 22px;
            height: 22px;
        }

        .img-upload-box span {
            font-size: 10px;
        }
    </style>


    <div x-data="{ open: false, keyword: '', rows: [], page: 1, lastPage: 1, total: 0 }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1800px] w-full mx-auto">

            {{-- ============================================ --}}
            {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
            {{-- ============================================ --}}
            @if ($action === 'delete')
                @php
                    $isApproved = \App\Support\ApprovalState::isApprovedRecord($product);
                @endphp
                <div class="space-y-4">
                    <div>
                        <!-- Group Produk Dropdown -->
                        <div class="mt-2 w-1/2" x-data="{ isEditable: false }">
                            <label class="block text-sm font-bold">Group Produk</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select disabled class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed" id="groupSelect">
                                        <option value=""></option>
                                        @foreach ($groups as $group)
                                            <option value="{{ $group->fgroupid }}"
                                                {{ old('fgroupcode', $product->fgroupcode) == $group->fgroupid ? 'selected' : '' }}>
                                                {{ $group->fgroupname }} ({{ $group->fgroupcode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Group"
                                        @click="if (isEditable) window.dispatchEvent(new CustomEvent('group-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fgroupcode"
                                    value="{{ old('fgroupcode', $product->fgroupcode) }}">
                                <button disabled type="button"
                                    @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none cursor-not-allowed"
                                    title="Browse Group Produk">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400" />
                                </button>
                                <button disabled type="button" @click="isEditable = true; $dispatch('open-group-modal')"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50 cursor-not-allowed"
                                    title="Tambah Group Produk">
                                    <x-heroicon-o-plus class="w-5 h-5 text-gray-400" />
                                </button>
                            </div>
                            @error('fgroupcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Merek Dropdown -->
                        <div class="mt-2 w-1/2" x-data="{ isMerekEditable: false }">
                            <label class="block text-sm font-bold">Merek</label>
                            <div class="flex">
                                <div class="relative flex-1">
                                    <select disabled id="merkSelect"
                                        class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed">
                                        <option value=""></option>
                                        @foreach ($merks as $merk)
                                            <option value="{{ $merk->fmerekcode }}"
                                                {{ old('fmerek', $product->fmerek) == $merk->fmerekcode ? 'selected' : '' }}>
                                                {{ $merk->fmerekname }} ({{ $merk->fmerekcode }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-0" role="button" aria-label="Browse Merek"
                                        @click="if (isMerekEditable) window.dispatchEvent(new CustomEvent('merek-browse-open'))"></div>
                                </div>
                                <input type="hidden" name="fmerek" id="fmerek" value="{{ old('fmerek') }}">
                                <button type="button" disabled
                                    @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"
                                    class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none cursor-not-allowed"
                                    title="Browse Merek">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400" />
                                </button>
                                <button type="button" disabled
                                    @click="isMerekEditable = true; $dispatch('open-merk-modal')"
                                    class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50 cursor-not-allowed"
                                    title="Tambah Merek">
                                    <x-heroicon-o-plus class="w-5 h-5 text-gray-400" />
                                </button>
                            </div>
                            @error('fmerek')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kode Produk -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-bold">Kode Produk</label>
                            <input type="text" name="fprdcode" id="fprdcode" readonly
                                value="{{ old('fprdcode', $product->fprdcode) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fprdcode') border-red-500 @enderror">
                            @error('fprdcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="open" x-transition.opacity x-cloak
                            class="fixed inset-0 z-50 flex items-center justify-center">
                            <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                            <div
                                class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
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

                        <!-- Nama Produk -->
                        <div class="mt-2 w-1/2">
                            <label class="block text-sm font-bold">Nama Produk</label>
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
                            <label class="block text-sm font-bold">Barcode</label>
                            <input readonly type="text" name="fbarcode"
                                value="{{ old('fbarcode', $product->fbarcode) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fbarcode') border-red-500 @enderror">
                            @error('fbarcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="satuan-container">

                            {{-- Satuan Kecil --}}
                            <div class="mt-2 flex items-end gap-4">
                                <div class="w-1/3">

                                    <label class="block text-sm font-bold">Satuan 1</label>
                                    <select
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
                                    <label class="block text-sm font-bold">HPP Satuan Kecil</label>
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
                                <div class="flex items-start gap-4">
                                    <div class="w-1/3">
                                        <label class="block text-sm font-bold">Satuan 2</label>
                                        <select
                                            class="w-full border rounded px-3 py-2 bg-yellow-50 @error('fsatuanbesar') border-red-500 @enderror"
                                            name="fsatuanbesar" id="fsatuanbesar" disabled
                                            onchange="updateSatuanLogic();">
                                            <option value="" selected>Pilih Satuan 2</option>
                                            @foreach ($satuan as $satu)
                                                <option value="{{ $satu->fsatuancode }}"
                                                    data-name="{{ $satu->fsatuanname }}"
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

                                    <div class="w-1/4 min-h-[96px]"> {{-- Kita lebarkan sedikit dari 1/6 ke 1/4 agar ruang teks lebih lega --}}
                                        <label class="block text-sm font-bold">Isi</label>
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
                                        @error('fqtykecil')
                                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="w-1/6">
                                        <label class="block text-sm font-bold">HPP Satuan 2</label>
                                        <input type="text" name="fhpp2" id="fhpp2"
                                            value="{{ old('fhpp2', $product->fhpp2) }}"
                                            class="autonumeric w-full border border-yellow-300 rounded px-3 py-2 bg-yellow-50 font-semibold"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            {{-- Satuan 3 --}}
                            <div id="satuan3-block" style="display: none;">
                                <div class="flex items-start gap-4">
                                    <div class="w-1/3">
                                        <label class="block text-sm font-bold">Satuan 3</label>
                                        <select
                                            class="w-full border rounded px-3 py-2 bg-purple-50 @error('fsatuanbesar2') border-red-500 @enderror"
                                            name="fsatuanbesar2" id="fsatuanbesar2"
                                            data-select2-id="select2-data-fsatuanbesar2" tabindex="-1"
                                            aria-hidden="true">
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

                                    <div class="w-1/4 min-h-[96px]"> {{-- Lebar dinaikkan ke 1/4 agar ruang teks lebih lega --}}
                                        <label class="block text-sm font-bold">Isi</label>
                                        <div
                                            class="flex items-center border border-purple-300 rounded bg-purple-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-purple-400 transition-all">
                                            {{-- Input tanpa border agar menyatu dengan container --}}
                                            <input type="text" name="fqtykecil2" id="fqtykecil2"
                                                value={{ old('fqtykecil2', $product->fqtykecil2) }}
                                                class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right"
                                                disabled>

                                            {{-- Span sebagai prefix/suffix di dalam kotak --}}
                                            <span
                                                class="satuan-kecil-display text-gray-500 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-purple-200 ml-2 pl-2">
                                            </span>
                                        </div>
                                        @error('fqtykecil2')
                                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="w-1/6">
                                        <label class="block text-sm font-bold">HPP Satuan 3</label>
                                        <input type="text" name="fhpp3" id="fhpp3"
                                            value="{{ old('fhpp3', $product->fhpp3) }}"
                                            class="autonumeric w-full border border-purple-300 rounded px-3 py-2 bg-purple-50 font-semibold"
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

                        <div class="flex flex-col md:flex-row gap-4 mt-2 max-w-2xl">

                            <div class="flex-1">
                                <label class="block text-sm font-bold mb-1">Satuan Default Transaksi</label>
                                <select name="fsatuandefault"
                                    class="w-full border rounded px-3 py-2 @error('fsatuandefault') border-red-500 @enderror">
                                    <option value="1"
                                        {{ old('fsatuandefault', $product->fsatuandefault) == '1' ? 'selected' : '' }}>
                                        Satuan 1
                                    </option>
                                    <option value="2"
                                        {{ old('fsatuandefault', $product->fsatuandefault) == '2' ? 'selected' : '' }}>
                                        Satuan 2
                                    </option>
                                    <option value="3"
                                        {{ old('fsatuandefault', $product->fsatuandefault) == '3' ? 'selected' : '' }}>
                                        Satuan 3
                                    </option>
                                </select>
                                @error('fsatuandefault')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="flex-1">
                                <label class="block text-sm font-bold mb-1">Satuan Default Laporan</label>
                                <select name="fsatuandefaultlaporan" disabled
                                    class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-500 cursor-not-allowed @error('fsatuandefaultlaporan') border-red-500 @enderror">
                                    <option value="1"
                                        {{ old('fsatuandefaultlaporan', $product->fsatuandefaultlaporan) == '1' ? 'selected' : '' }}>
                                        Satuan 1
                                    </option>
                                    <option value="2"
                                        {{ old('fsatuandefaultlaporan', $product->fsatuandefaultlaporan) == '2' ? 'selected' : '' }}>
                                        Satuan 2
                                    </option>
                                    <option value="3"
                                        {{ old('fsatuandefaultlaporan', $product->fsatuandefaultlaporan) == '3' ? 'selected' : '' }}>
                                        Satuan 3
                                    </option>
                                </select>
                                @error('fsatuandefaultlaporan')
                                    <div class="text-red-600 text-sm mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Harga Satuan 3 Level 1 -->
                            <div>
                                <label for="fhargajuallevel1" class="block text-sm font-bold">Harga Jual Satuan 1
                                    1</label>
                                <div class="d-flex">
                                    <input type="text"
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
                                <label for="fhargajuallevel2" class="block text-sm font-bold">Harga Jual Satuan 1
                                    2</label>
                                <div class="d-flex">
                                    <input type="text"
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
                                <label for="fhargajuallevel3" class="block text-sm font-bold">Harga Jual Satuan 1
                                    3</label>
                                <div class="d-flex">
                                    <input type="text"
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
                                    <label class="block text-sm font-bold">Harga Jual Satuan 2</label>
                                    <div class="d-flex">
                                        <input type="text" disabled
                                            class="w-1/10 border rounded px-3 py-2 bg-yellow-50"
                                            value="{{ $product->fhargajual2level1 ?? 0 }}">
                                    </div>
                                </div>

                                <!-- HJ. Besar Level 2 -->
                                <div>
                                    <label class="block text-sm font-bold">Harga Jual Satuan 2</label>
                                    <div class="d-flex">
                                        <input type="text" disabled
                                            class="w-1/10 border rounded px-3 py-2 bg-yellow-50"
                                            value="{{ $product->fhargajual2level2 ?? 0 }}">
                                    </div>
                                </div>

                                <!-- HJ. Besar Level 3 -->
                                <div>
                                    <label class="block text-sm font-bold">Harga Jual Satuan 2</label>
                                    <div class="d-flex">
                                        <input type="text" disabled
                                            class="w-1/10 border rounded px-3 py-2 bg-yellow-50"
                                            value="{{ $product->fhargajual2level3 ?? 0 }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- HJ Dynamic Columns (Delete Mode) --}}
                        <div id="hj-level2-block" style="display: none;">

                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- HJ <PCS> Level 1 -->
                                <div>
                                    <label class="block text-sm font-bold">
                                        Harga Jual 3 <span class="uppercase">{{ $product->fsatuankecil ?? '-' }}</span>
                                        Level 1
                                    </label>
                                    <div class="d-flex">
                                        <input type="text" disabled
                                            class="w-1/10 border rounded px-3 py-2 bg-purple-50"
                                            value="{{ $product->fhargajual3level1 ?? 0 }}">
                                    </div>
                                </div>

                                <!-- HJ <CTN> Level 1 -->
                                <div>
                                    <label class="block text-sm font-bold">
                                        Harga Jual 3 <span class="uppercase">{{ $product->fsatuanbesar ?? '-' }}</span>
                                        Level 1
                                    </label>
                                    <div class="d-flex">
                                        <input type="text" disabled
                                            class="w-1/10 border rounded px-3 py-2 bg-purple-50"
                                            value="{{ $product->fhargajual3level2 ?? 0 }}">
                                    </div>
                                </div>

                                <!-- HJ <DUS> Level 1 -->
                                <div>
                                    <label class="block text-sm font-bold">
                                        Harga Jual 3 <span class="uppercase">{{ $product->fsatuanbesar2 ?? '-' }}</span>
                                        Level 1
                                    </label>
                                    <div class="d-flex">
                                        <input type="text" disabled
                                            class="w-1/10 border rounded px-3 py-2 bg-purple-50"
                                            value="{{ $product->fhargajual3level3 ?? 0 }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Min Stok --}}
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-bold mb-1">Min.Stok</label>

                            {{-- Container yang membungkus input dan satuan --}}
                            <div
                                class="flex items-center border border-gray-300 rounded bg-gray-50 focus-within:bg-white focus-within:ring-1 focus-within:ring-blue-400 transition-all @error('fminstock') border-red-500 @enderror">

                                {{-- Input tanpa border agar menyatu dengan container --}}
                                <input type="text" name="fminstock" id="fminstock" readonly
                                    value="{{ number_format((float) old('fminstock', $product->fminstock ?? 0), 2, ',', '.') }}"
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
                            <label class="block text-sm font-bold">Jenis</label>
                            <select name="ftype" disabled
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('ftype') border-red-500 @enderror">
                                <option value="Produk" {{ old('ftype', $product->ftype) == 'Produk' ? 'selected' : '' }}>
                                    Produk</option>
                                <option value="Jasa" {{ old('ftype', $product->ftype) == 'Jasa' ? 'selected' : '' }}>
                                    Jasa</option>
                            </select>
                            @error('ftype')
                                <div class="text-red-600 text-sm mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        @php
                            $canApproval = in_array(
                                'approveProduct',
                                explode(',', session('user_restricted_permissions', '')),
                            );
                        @endphp
                        @if ($canApproval)
                            <div class="md:col-span-2 flex justify-center items-center space-x-2">
                                <fieldset {{ $isApproved ? 'disabled' : '' }}>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm font-bold">Status Persetujuan</label>
                                        <label class="switch">
                                            <input type="checkbox" name="approve_now" id="approvalToggle"
                                                {{ $isApproved ? 'checked' : '' }} disabled>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>
                            <br>
                        @endif

                    </div>
                </div>

                <div class="mt-6 flex justify-center space-x-4">
                    <button type="button" onclick="window.location.href='{{ route('rekening.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
                </div>

                {{-- ============================================ --}}
                {{-- MODE EDIT: FORM EDITABLE                    --}}
                {{-- ============================================ --}}
            @else
                <form action="{{ route('product.update', $product->fprdid) }}" method="POST"
                    enctype="multipart/form-data" data-form-draft="true" data-draft-key="product:edit">
                    @csrf
                    @method('PATCH')
                    @php
                        $isApproved = \App\Support\ApprovalState::isApprovedRecord($product);
                        $isUsedProduct = $usageInfo['is_used'] ?? false;
                        $usedByLabels  = $usageInfo['used_by'] ?? [];
                        $lockSatuan1   = $isUsedProduct;
                        $lockSatuan2   = $isUsedProduct && !empty($product->fsatuanbesar);
                        $lockSatuan3   = $isUsedProduct && !empty($product->fsatuanbesar2);
                        $lockQty2      = $lockSatuan2;
                        $lockQty3      = $lockSatuan3;
                    @endphp

                    @if ($isUsedProduct)
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            PRODUK INI SUDAH DIREFERENSI DI TRANSAKSI {{ implode(', ', $usedByLabels) }}.
                            KODE PRODUK, QTY KONVERSI, DAN SATUAN YANG SUDAH TERPAKAI DIKUNCI.
                            ANDA MASIH BISA UBAH FIELD LAIN.
                            Satuan 2 dan Satuan 3 masih boleh diisi atau diupdate jika slotnya masih kosong.
                        </div>
                    @endif

                    {{-- ═══ MAIN GRID: sidebar image + form ═══ --}}
                    <div class="flex gap-5 items-start">

                        {{-- ── LEFT: Gambar Produk ── --}}
                        @if (!empty($enabledImageNumbers))
                            <div class="flex-shrink-0 w-48">
                                <div class="section-card" style="padding:1rem;">
                                    <div class="section-title" style="margin-bottom:0.75rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Foto Produk
                                    </div>
                                    <div class="space-y-3">
                                        @foreach ($enabledImageNumbers as $imgNo)
                                            @php
                                                $field       = 'fimage' . $imgNo;
                                                $imageRaw    = (string) ($product->{$field} ?? '');
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
                                                $photoVersion   = !empty($product->fupdatedat) ? strtotime((string)$product->fupdatedat) : null;
                                                $drivePreviewUrl = $driveFileId
                                                    ? route('product.photo', ['fprdid' => $product->fprdid, 'field' => $field, 'v' => $photoVersion ?: time()])
                                                    : null;
                                            @endphp
                                            <div>
                                                <span class="field-label">Foto {{ $imgNo }}</span>
                                                <input type="file" name="fimage{{ $imgNo }}"
                                                    id="fimage{{ $imgNo }}" accept="image/*" class="hidden"
                                                    onchange="previewImage(this, {{ $imgNo }})">

                                                {{-- Preview box --}}
                                                <div id="imagePreviewContainer{{ $imgNo }}"
                                                    class="{{ $drivePreviewUrl ? '' : 'hidden' }} mb-2">
                                                    <img id="imagePreview{{ $imgNo }}"
                                                        src="{{ $drivePreviewUrl ?? '' }}"
                                                        alt="Preview {{ $imgNo }}"
                                                        class="w-full rounded border cursor-zoom-in hover:opacity-90 transition"
                                                        style="object-fit:cover;height:130px;"
                                                        onclick="openModal({{ $imgNo }})"
                                                        @if($driveFileId) onerror="this.onerror=null;this.src='https://drive.google.com/thumbnail?id={{ $driveFileId }}&sz=w1000';" @endif>
                                                </div>

                                                {{-- Upload box (shown when no preview) --}}
                                                <div id="uploadBox{{ $imgNo }}" class="img-upload-box"
                                                    style="{{ $drivePreviewUrl ? 'display:none;' : '' }}"
                                                    onclick="document.getElementById('fimage{{ $imgNo }}').click()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>Klik untuk pilih</span>
                                                </div>

                                                <button type="button" id="btnRemoveImage{{ $imgNo }}"
                                                    class="{{ $drivePreviewUrl ? '' : 'hidden' }} mt-1 w-full text-xs text-red-500 border border-red-200 rounded py-1 hover:bg-red-50"
                                                    onclick="removeImage({{ $imgNo }})">
                                                    Hapus foto
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">JPG, PNG · maks 2MB</p>
                                </div>
                            </div>
                        @endif

                        {{-- ── RIGHT: Form fields ── --}}
                        <div class="flex-1 min-w-0">

                            {{-- ═══ SECTION 1: Identitas Produk ═══ --}}
                            <div class="section-card">
                                <div class="section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Identitas Produk
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    {{-- Group Produk --}}
                                    <div x-data="{ isEditable: false }">
                                        <label class="field-label">Group Produk</label>
                                        <div class="flex">
                                            <div class="relative flex-1">
                                                <select disabled class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed" id="groupSelect">
                                                    <option value="">-- Pilih Group Produk --</option>
                                                    @foreach ($groups as $group)
                                                        <option value="{{ $group->fgroupid }}"
                                                            {{ old('fgroupcode', $product->fgroupcode) == $group->fgroupid ? 'selected' : '' }}>
                                                            {{ $group->fgroupcode }} - {{ $group->fgroupname }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="absolute inset-0" role="button" aria-label="Browse Group"
                                                    @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"></div>
                                            </div>
                                            <input type="hidden" name="fgroupcode" id="groupCodeHidden"
                                                value="{{ old('fgroupcode', $product->fgroupcode) }}">
                                            <button type="button"
                                                @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"
                                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                                title="Browse Group Produk">
                                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                            </button>
                                            <button type="button" @click="isEditable = true; $dispatch('open-group-modal')"
                                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                                title="Tambah Group Produk">
                                                <x-heroicon-o-plus class="w-5 h-5" />
                                            </button>
                                        </div>
                                        @error('fgroupcode')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Merek --}}
                                    <div x-data="{ isMerekEditable: false }">
                                        <label class="field-label">Merek</label>
                                        <div class="flex">
                                            <div class="relative flex-1">
                                                <select disabled id="merkSelect"
                                                    class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed">
                                                    <option value="">-- Pilih Merek --</option>
                                                    @foreach ($merks as $merk)
                                                        <option value="{{ $merk->fmerekcode }}"
                                                            {{ old('fmerek', $product->fmerek) == $merk->fmerekcode ? 'selected' : '' }}>
                                                            {{ $merk->fmerekcode }} - {{ $merk->fmerekname }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="absolute inset-0" role="button" aria-label="Browse Merek"
                                                    @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"></div>
                                            </div>
                                            <input type="hidden" name="fmerek" id="fmerek"
                                                value="{{ old('fmerek', $product->fmerek) }}">
                                            <button type="button"
                                                @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"
                                                class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-none"
                                                title="Browse Merek">
                                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                            </button>
                                            <button type="button" @click="isMerekEditable = true; $dispatch('open-merk-modal')"
                                                class="border -ml-px rounded-r px-3 py-2 bg-white hover:bg-gray-50"
                                                title="Tambah Merek">
                                                <x-heroicon-o-plus class="w-5 h-5" />
                                            </button>
                                        </div>
                                        @error('fmerek')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4 mb-4">
                                    {{-- Kode Produk --}}
                                    <div>
                                        <label class="field-label">Kode Produk</label>
                                        <input type="text" name="fprdcode" id="fprdcode"
                                            value="{{ old('fprdcode', $product->fprdcode) }}"
                                            class="field-input bg-gray-100 cursor-not-allowed uppercase" readonly>
                                    </div>

                                    {{-- Nama Produk --}}
                                    <div>
                                        <label class="field-label">Nama Produk</label>
                                        <input type="text" name="fprdname" id="fprdname"
                                            value="{{ old('fprdname', $product->fprdname) }}"
                                            class="field-input uppercase @error('fprdname') border-red-500 bg-red-50 @enderror"
                                            autofocus>
                                        @error('fprdname')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Barcode --}}
                                    <div>
                                        <label class="field-label">Barcode</label>
                                        <input type="text" name="fbarcode"
                                            value="{{ old('fbarcode', $product->fbarcode) }}"
                                            class="field-input @error('fbarcode') border-red-500 @enderror">
                                        @error('fbarcode')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4">
                                    {{-- Jenis --}}
                                    <div>
                                        <label class="field-label">Jenis</label>
                                        <select name="ftype" class="field-input @error('ftype') border-red-500 @enderror">
                                            <option value="Produk" {{ old('ftype', $product->ftype) == 'Produk' ? 'selected' : '' }}>Produk</option>
                                            <option value="Jasa"   {{ old('ftype', $product->ftype) == 'Jasa'   ? 'selected' : '' }}>Jasa</option>
                                        </select>
                                    </div>

                                    {{-- Non Aktif --}}
                                    <div class="flex items-end pb-0.5">
                                        <label class="inline-flex items-center gap-2 border-2 border-red-200 bg-red-50 text-red-700 rounded-lg px-3 py-2 cursor-pointer hover:bg-red-100 text-sm font-semibold transition-colors duration-200">
                                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                                class="h-4 w-4 text-red-600 rounded focus:ring-red-500 border-red-300"
                                                value="1"
                                                {{ old('fnonactive', $product->fnonactive) == '1' ? 'checked' : '' }}>
                                            Non Aktif
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══ SECTION 2: Satuan & HPP ═══ --}}
                            <div class="section-card" id="satuan-container">
                                <div class="section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                    Satuan &amp; HPP
                                </div>

                                {{-- Satuan 1 --}}
                                <div class="mb-4">
                                    <span class="satuan-badge blue">Satuan 1 — utama</span>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="field-label">Jenis Satuan</label>
                                            <select class="field-input blue" name="fsatuankecil" id="fsatuankecil"
                                                {{ $lockSatuan1 ? 'disabled' : '' }}
                                                onchange="updateSatuanLogic();">
                                                <option value="">Pilih Satuan 1</option>
                                                @foreach ($satuan as $satu)
                                                    <option value="{{ $satu->fsatuancode }}"
                                                        {{ old('fsatuankecil', $product->fsatuankecil) == $satu->fsatuancode ? 'selected' : '' }}>
                                                        {{ $satu->fsatuancode }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($lockSatuan1)
                                                <input type="hidden" name="fsatuankecil" value="{{ $product->fsatuankecil }}">
                                            @endif
                                            @error('fsatuankecil')
                                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div></div>
                                        <div>
                                            <label class="field-label">HPP Satuan Kecil</label>
                                            <input type="text" name="fhpp" id="fhpp"
                                                class="autonumeric field-input blue text-right"
                                                value="{{ old('fhpp', $product->fhpp ?? 0) }}">
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-100 my-3">

                                {{-- Satuan 2 --}}
                                <div id="satuan2-block" style="display:none;" class="mb-4">
                                    <span class="satuan-badge yellow">Satuan 2</span>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="field-label">Jenis Satuan</label>
                                            <select class="field-input yellow" name="fsatuanbesar" id="fsatuanbesar"
                                                {{ $lockSatuan2 ? 'disabled' : '' }}
                                                onchange="updateSatuanLogic();">
                                                <option value="">Pilih Satuan 2</option>
                                                @foreach ($satuan as $satu)
                                                    <option value="{{ $satu->fsatuancode }}"
                                                        {{ old('fsatuanbesar', $product->fsatuanbesar) == $satu->fsatuancode ? 'selected' : '' }}>
                                                        {{ $satu->fsatuancode }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($lockSatuan2)
                                                <input type="hidden" name="fsatuanbesar" value="{{ $product->fsatuanbesar }}">
                                            @endif
                                            @error('fsatuanbesar')
                                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="field-label">Isi</label>
                                            <div class="flex items-center border border-yellow-300 rounded bg-yellow-50 focus-within:ring-1 focus-within:ring-yellow-400">
                                                <input type="text" name="fqtykecil" id="fqtykecil"
                                                    value="{{ old('fqtykecil', $product->fqtykecil ?? 0) }}"
                                                    class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm"
                                                    {{ $lockQty2 ? 'disabled' : '' }}>
                                                @if ($lockQty2)
                                                    <input type="hidden" name="fqtykecil" value="{{ $product->fqtykecil }}">
                                                @endif
                                                <span class="satuan-kecil-display text-gray-500 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-yellow-200 ml-2 pl-2"></span>
                                            </div>
                                            @error('fqtykecil')
                                                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="field-label">HPP Satuan 2</label>
                                            <input type="text" name="fhpp2" id="fhpp2"
                                                value="{{ old('fhpp2', $product->fhpp2 ?? 0) }}"
                                                class="autonumeric field-input yellow text-right" readonly>
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-100 my-3" id="br-satuan2" style="display:none;">

                                {{-- Satuan 3 --}}
                                <div id="satuan3-block" style="display:none;" class="mb-4">
                                    <span class="satuan-badge purple">Satuan 3</span>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="field-label">Jenis Satuan</label>
                                            <select class="field-input purple" name="fsatuanbesar2" id="fsatuanbesar2"
                                                {{ $lockSatuan3 ? 'disabled' : '' }}
                                                onchange="updateSatuanLogic();">
                                                <option value="">Pilih Satuan 3</option>
                                                @foreach ($satuan as $satu)
                                                    <option value="{{ $satu->fsatuancode }}"
                                                        {{ old('fsatuanbesar2', $product->fsatuanbesar2) == $satu->fsatuancode ? 'selected' : '' }}>
                                                        {{ $satu->fsatuancode }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($lockSatuan3)
                                                <input type="hidden" name="fsatuanbesar2" value="{{ $product->fsatuanbesar2 }}">
                                            @endif
                                            @error('fsatuanbesar2')
                                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="field-label">Isi</label>
                                            <div class="flex items-center border border-purple-300 rounded bg-purple-50 focus-within:ring-1 focus-within:ring-purple-400">
                                                <input type="text" name="fqtykecil2" id="fqtykecil2"
                                                    value="{{ old('fqtykecil2', $product->fqtykecil2 ?? 0) }}"
                                                    class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm"
                                                    {{ $lockQty3 ? 'disabled' : '' }}>
                                                @if ($lockQty3)
                                                    <input type="hidden" name="fqtykecil2" value="{{ $product->fqtykecil2 }}">
                                                @endif
                                                <span class="satuan-kecil-display text-purple-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-purple-200 ml-2 pl-2 uppercase"></span>
                                            </div>
                                            @error('fqtykecil2')
                                                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="field-label">HPP Satuan 3</label>
                                            <input type="text" name="fhpp3" id="fhpp3"
                                                value="{{ old('fhpp3', $product->fhpp3 ?? 0) }}"
                                                class="autonumeric field-input purple text-right" readonly>
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-100 my-3">

                                {{-- Satuan Default --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="field-label">Satuan Default Transaksi</label>
                                        <select name="fsatuandefault" class="field-input @error('fsatuandefault') border-red-500 @enderror">
                                            <option value="1" {{ old('fsatuandefault', $product->fsatuandefault) == '1' ? 'selected' : '' }}>Satuan 1</option>
                                            <option value="2" {{ old('fsatuandefault', $product->fsatuandefault) == '2' ? 'selected' : '' }}>Satuan 2</option>
                                            <option value="3" {{ old('fsatuandefault', $product->fsatuandefault) == '3' ? 'selected' : '' }}>Satuan 3</option>
                                        </select>
                                        @error('fsatuandefault')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="field-label">Satuan Default Laporan</label>
                                        <select name="fsatuandefaultlaporan" class="field-input @error('fsatuandefaultlaporan') border-red-500 @enderror">
                                            <option value="1" {{ old('fsatuandefaultlaporan', $product->fsatuandefaultlaporan) == '1' ? 'selected' : '' }}>Satuan 1</option>
                                            <option value="2" {{ old('fsatuandefaultlaporan', $product->fsatuandefaultlaporan) == '2' ? 'selected' : '' }}>Satuan 2</option>
                                            <option value="3" {{ old('fsatuandefaultlaporan', $product->fsatuandefaultlaporan) == '3' ? 'selected' : '' }}>Satuan 3</option>
                                        </select>
                                        @error('fsatuandefaultlaporan')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- ═══ SECTION 3: Harga Jual (Matrix) ═══ --}}
                            <div class="section-card">
                                <div class="section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 8V4a1 1 0 011-1z" />
                                    </svg>
                                    Harga Jual per Level
                                </div>

                                <table class="hj-table">
                                    <thead>
                                        <tr>
                                            <th style="width:30%">Satuan</th>
                                            <th>Level 1</th>
                                            <th>Level 2</th>
                                            <th>Level 3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Row Satuan 1 (Kecil) --}}
                                        <tr>
                                            <td class="row-label">
                                                <span class="satuan-badge blue" style="margin:0;">S1</span>&nbsp;
                                                <span id="hj-satuan-kecil-level1-label-row" class="uppercase text-xs text-blue-700">-</span>
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric blue" name="fhargajuallevel1" id="fhargajuallevel1"
                                                    value="{{ old('fhargajuallevel1', $product->fhargajuallevel1 ?? 0) }}">
                                                @error('fhargajuallevel1')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric blue" name="fhargajuallevel2" id="fhargajuallevel2"
                                                    value="{{ old('fhargajuallevel2', $product->fhargajuallevel2 ?? 0) }}">
                                                @error('fhargajuallevel2')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric blue" name="fhargajuallevel3" id="fhargajuallevel3"
                                                    value="{{ old('fhargajuallevel3', $product->fhargajuallevel3 ?? 0) }}">
                                                @error('fhargajuallevel3')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                        </tr>

                                        {{-- Row Satuan 2 --}}
                                        <tr id="hj-level1-block" style="display:none;">
                                            <td class="row-label">
                                                <span class="satuan-badge yellow" style="margin:0;">S2</span>&nbsp;
                                                <span id="hj-satuan-besar-level1-label" class="uppercase text-xs text-yellow-700">-</span>
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric yellow" name="fhargajual2level1" id="fhargajual2level1"
                                                    value="{{ old('fhargajual2level1', $product->fhargajual2level1 ?? 0) }}">
                                                @error('fhargajual2level1')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric yellow" name="fhargajual2level2" id="fhargajual2level2"
                                                    value="{{ old('fhargajual2level2', $product->fhargajual2level2 ?? 0) }}">
                                                @error('fhargajual2level2')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric yellow" name="fhargajual2level3" id="fhargajual2level3"
                                                    value="{{ old('fhargajual2level3', $product->fhargajual2level3 ?? 0) }}">
                                                @error('fhargajual2level3')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                        </tr>

                                        {{-- Row Satuan 3 --}}
                                        <tr id="hj-level2-block" style="display:none;">
                                            <td class="row-label">
                                                <span class="satuan-badge purple" style="margin:0;">S3</span>&nbsp;
                                                <span id="hj-satuan-kecil-label" class="uppercase text-xs text-purple-700">-</span>
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric purple" name="fhargajual3level1" id="fhargajual3level1"
                                                    value="{{ old('fhargajual3level1', $product->fhargajual3level1 ?? 0) }}">
                                                @error('fhargajual3level1')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric purple" name="fhargajual3level2" id="fhargajual3level2"
                                                    value="{{ old('fhargajual3level2', $product->fhargajual3level2 ?? 0) }}">
                                                @error('fhargajual3level2')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <input type="text" class="autonumeric purple" name="fhargajual3level3" id="fhargajual3level3"
                                                    value="{{ old('fhargajual3level3', $product->fhargajual3level3 ?? 0) }}">
                                                @error('fhargajual3level3')<div class="text-red-600 text-xs">{{ $message }}</div>@enderror
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                {{-- Hidden label spans required by JS --}}
                                <span id="hj-satuan-kecil-level1-label"  class="hidden"></span>
                                <span id="hj-satuan-kecil-level2-label"  class="hidden"></span>
                                <span id="hj-satuan-kecil-level3-label"  class="hidden"></span>
                                <span id="hj-satuan-besar-level2-label"  class="hidden"></span>
                                <span id="hj-satuan-besar-level3-label"  class="hidden"></span>
                                <span id="hj-satuan-besar-label"         class="hidden"></span>
                                <span id="hj-satuan-besar2-label"        class="hidden"></span>

                                <p class="text-xs text-gray-400 mt-2">Level 1 = retail &middot; Level 2 = grosir &middot; Level 3 = distributor</p>
                            </div>

                            {{-- ═══ SECTION 4: Stok & Lainnya ═══ --}}
                            <div class="section-card">
                                <div class="section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    Stok &amp; Lainnya
                                </div>

                                <div class="grid grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="field-label">Min. Stok</label>
                                        <div class="flex items-center border border-gray-300 rounded bg-gray-50 focus-within:ring-1 focus-within:ring-blue-400 @error('fminstock') border-red-500 @enderror">
                                            <input type="text" name="fminstock" id="fminstock"
                                                value="{{ number_format((float) old('fminstock', $product->fminstock ?? 0), 2, ',', '.') }}"
                                                class="flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm">
                                            <span class="satuan-kecil-display text-gray-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-gray-200 ml-2 pl-2 uppercase"></span>
                                        </div>
                                        @error('fminstock')
                                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Approve --}}
                                @php $canApproval = in_array('approveProduct', explode(',', session('user_restricted_permissions', ''))); @endphp
                                @if ($canApproval)
                                    <div class="flex items-center justify-center gap-2 mb-4">
                                        <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer border rounded-lg px-3 py-2 hover:bg-gray-50">
                                            <span>Approve</span>
                                            <label class="switch" style="margin:0">
                                                <input type="checkbox" name="approve_now" id="approvalToggle"
                                                    {{ old('approve_now', $product->fapproval) == '1' || $product->fapproval == '1' ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </label>
                                    </div>
                                @endif

                                {{-- Tombol Aksi --}}
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" onclick="window.location.href='{{ route('product.index') }}'"
                                        class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1">
                                        <x-heroicon-o-arrow-left class="w-4 h-4" /> Keluar
                                    </button>
                                    <button type="submit"
                                        class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-1">
                                        <x-heroicon-o-check class="w-4 h-4" /> Simpan
                                    </button>
                                </div>
                            </div>

                        </div>{{-- end right column --}}

                    </div>{{-- end main grid --}}

                </form>

                {{-- ═══ IMAGE MODAL ═══ --}}
                <div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-90 flex items-center justify-center p-4"
                    onclick="closeModal()">
                    <span class="absolute top-5 right-10 text-white text-4xl font-bold cursor-pointer">&times;</span>
                    <img id="modalContent" class="max-w-full max-h-full rounded shadow-2xl">
                </div>
            @endif
            <br>
            <hr><br>
            <span class="text-sm text-gray-600 flex justify-between items-center">
                <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
                <span>{{ \Carbon\Carbon::parse($product->fupdatedat ?: $product->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
            </span>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus product ini?</h3>
                <form id="deleteForm" action="{{ route('product.destroy', $product->fprdid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div id="toast" class="hidden fixed top-4 right-4 z-50 max-w-sm">
            <div id="toastContent" class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center">
                <span id="toastMessage"></span>
                <button onclick="closeToast()" class="ml-4 text-white hover:text-gray-200">&times;</button>
            </div>
        </div>

        <script>
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            function closeToast() {
                document.getElementById('toast').classList.add('hidden');
            }

            function showToast(message, isSuccess = true) {
                const toast = document.getElementById('toast');
                const toastContent = document.getElementById('toastContent');
                const toastMessage = document.getElementById('toastMessage');

                toastMessage.textContent = message;
                toastContent.className = isSuccess ?
                    'bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center' :
                    'bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center';

                toast.classList.remove('hidden');
            }

            function confirmDelete() {
                const btnYa = document.getElementById('btnYa');
                const btnTidak = document.getElementById('btnTidak');

                btnYa.disabled = true;
                btnTidak.disabled = true;
                btnYa.textContent = 'Menghapus...';

                fetch('{{ route('product.destroy', $product->fprdid) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'DELETE'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        closeDeleteModal();
                        showToast(data.message || 'Data berhasil dihapus.', true);

                        setTimeout(() => {
                            window.location.href = '{{ route('product.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat hapus data.', false);
                    });
            }
        </script>
    @endif

    <div x-data="{
        open: false,
        loading: false,
        isEditable: false,
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
                        const opt = new Option(res.name, res.id, true, true);
                        $('#groupSelect').append(opt).trigger('change');
    
                        const hidCode = document.getElementById('groupCodeHidden');
                        if (hidCode) {
                            hidCode.value = res.id;
                        }
    
                        this.open = false;
                        this.form = { fgroupcode: '', fgroupname: '', fnonactive: false };
                        this.errors = {};
                        this.isEditable = true;
                    } else {
                        window.showAppErrorAlert('TERJADI KESALAHAN', 'FORMAT RESPON SERVER SALAH.');
                    }
                    this.loading = false;
                })
                .fail((xhr) => {
                    this.loading = false;
                    if (xhr.status === 422) {
                        this.errors = xhr.responseJSON?.errors || {};
                    } else {
                        window.showAppErrorAlert('TERJADI KESALAHAN', 'GAGAL MENYIMPAN GROUP PRODUK.');
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
                    <label class="block text-sm font-bold text-gray-700">Kode Group</label>
                    <input type="text" x-model="form.fgroupcode" class="w-full border rounded px-3 py-2 uppercase"
                        maxlength="10" :class="errors.fgroupcode ? 'border-red-500' : 'border-gray-300'">
                    <template x-if="errors.fgroupcode">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fgroupcode[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700">Nama Group</label>
                    <input type="text" x-model="form.fgroupname" class="w-full border rounded px-3 py-2 uppercase"
                        :class="errors.fgroupname ? 'border-red-500' : 'border-gray-300'">
                    <template x-if="errors.fgroupname">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fgroupname[0]"></p>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_group_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_group_fnonactive" class="text-sm font-bold text-gray-700">Non Aktif</label>
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
                    if (res && res.code && res.name) {
                        // 1. Buat opsi baru dan masukkan ke Select
                        const opt = new Option(res.name, res.code, true, true);
                        $('#merkSelect').append(opt).trigger('change');
    
                        // 2. SINKRONISASI KE INPUT HIDDEN (PENTING!)
                        // Tanpa ini, server akan tetap menerima data lama dari input hidden
                        const hidMerek = document.getElementById('fmerek');
                        if (hidMerek) {
                            hidMerek.value = res.code;
                        }
    
                        // 3. TUTUP MODAL & RESET
                        this.open = false;
                        this.form = { fmerekcode: '', fmerekname: '', fnonactive: false };
                        this.errors = {};
                    }
                    this.loading = false;
                })
                .fail((xhr) => {
                    this.loading = false;
                    if (xhr.status === 422) {
                        this.errors = xhr.responseJSON?.errors || {};
                    } else {
                        window.showAppErrorAlert('TERJADI KESALAHAN', 'GAGAL MENYIMPAN MEREK.');
                    }
                });
        }
    }" x-on:open-merk-modal.window="open = true; errors = {}; loading = false;" x-show="open"
        style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center">

        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <!-- card -->
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Tambah Merek</h3>

            <div class="space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-bold">Kode Merek</label>
                    <input type="text" x-model="form.fmerekcode" class="w-full border rounded px-3 py-2 uppercase"
                        maxlength="10" :class="errors.fmerekcode ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekcode">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekcode[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-bold">Nama Merek</label>
                    <input type="text" x-model="form.fmerekname" class="w-full border rounded px-3 py-2 uppercase"
                        :class="errors.fmerekname ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekname">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekname[0]"></p>
                    </template>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_fnonactive" class="block text-sm font-bold">Non Aktif</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open=false"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>

                <!-- FIXED BUTTON - Single button with all elements inside -->
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

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
            style="height: 85vh;">
            <!-- Header -->
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Group Produk</h3>
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

    <!-- MODAL BROWSE MEREK -->
    <div x-data="merekBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
            style="height: 85vh;">

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
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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
        $('#fsatuankecil, #fsatuanbesar, #fsatuanbesar2').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || '-- Pilih --';
            },
        });


        // let fhargasatuankecillevel1 = new AutoNumeric('#fhargasatuankecillevel1',
        //     'commaDecimalCharDotSeparator');
        // let hargasatuankecillevel2 = new AutoNumeric('#fhargasatuankecillevel2',
        //     'commaDecimalCharDotSeparator');
        // let hargasatuankecillevel3 = new AutoNumeric('#fhargasatuankecillevel3',
        //     'commaDecimalCharDotSeparator');
        let hargajuallevel1 = new AutoNumeric('#fhargajuallevel1', 'commaDecimalCharDotSeparator');
        let hargajuallevel2 = new AutoNumeric('#fhargajuallevel2', 'commaDecimalCharDotSeparator');
        let hargajuallevel3 = new AutoNumeric('#fhargajuallevel3', 'commaDecimalCharDotSeparator');
        let hargajual2level1 = new AutoNumeric('#fhargajual2level1', 'commaDecimalCharDotSeparator');
        let hargajual2level2 = new AutoNumeric('#fhargajual2level2', 'commaDecimalCharDotSeparator');
        let hargajual2level3 = new AutoNumeric('#fhargajual2level3', 'commaDecimalCharDotSeparator');
        let hargajual3level1 = new AutoNumeric('#fhargajual3level1', 'commaDecimalCharDotSeparator');
        let hargajual3level2 = new AutoNumeric('#fhargajual3level2', 'commaDecimalCharDotSeparator');
        let hargajual3level3 = new AutoNumeric('#fhargajual3level3', 'commaDecimalCharDotSeparator');

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

<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fsatuankecil = document.getElementById('fsatuankecil');
        const fsatuanbesar = document.getElementById('fsatuanbesar');
        const fsatuanbesar2 = document.getElementById('fsatuanbesar2');
        const fqtykecil = document.getElementById('fqtykecil');
        const fqtykecil2 = document.getElementById('fqtykecil2');
        const isLocked = (field) => field?.dataset?.usageLocked === '1';

        function toggleFields() {
            if (fsatuankecil.value !== "") {
                if (!isLocked(fsatuanbesar)) fsatuanbesar.disabled = false;
                if (!isLocked(fsatuanbesar2)) fsatuanbesar2.disabled = false;
                if (!isLocked(fqtykecil)) fqtykecil.disabled = false;
                if (!isLocked(fqtykecil2)) fqtykecil2.disabled = false;
            } else {
                if (!isLocked(fsatuanbesar)) fsatuanbesar.disabled = true;
                if (!isLocked(fsatuanbesar2)) fsatuanbesar2.disabled = true;
                if (!isLocked(fqtykecil)) fqtykecil.disabled = true;
                if (!isLocked(fqtykecil2)) fqtykecil2.disabled = true;
            }
        }

        fsatuankecil.addEventListener('change', toggleFields);

        toggleFields();
    });

    function chooseMerek(merek) {
        // Set value to the select dropdown
        document.querySelector('#merkSelect').value = merek.fmerekcode;

        // Optionally trigger 'change' event if needed
        $(document).find('#merkSelect').trigger('change');

        // Close the modal
        this.open = false;
    }

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
                            width: '400px',
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
                sel.value = fmerekcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hid) {
                hid.value = fmerekcode || '';
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
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
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
            const alpineData = Alpine.$data(sel.closest('[x-data]'));
            if (alpineData) {
                alpineData.isEditable = true;
            }
        });
    });
</script>

<script>
    /**
     * Fungsi utama untuk mengelola visibilitas field satuan dan pembaruan label.
     * Dipanggil saat ada perubahan pada Satuan Kecil atau Satuan 2.
     */
    let isUpdating = false;
    let isInitialSatuanRender = true;

    function isUsageLockedField(field) {
        return field?.dataset?.usageLocked === '1';
    }

    function syncSatuanQtyFieldLayout() {
        ['fqtykecil', 'fqtykecil2'].forEach(function(id) {
            const field = document.getElementById(id);
            if (!field) return;

            field.style.setProperty('text-align', 'right', 'important');
            field.style.setProperty('padding-left', '0.75rem', 'important');
            field.style.setProperty('padding-right', '0.75rem', 'important');
            field.style.setProperty('background', 'transparent', 'important');
            field.style.setProperty('border', '0', 'important');
            field.style.setProperty('outline', '0', 'important');
            field.style.setProperty('box-shadow', 'none', 'important');
            field.style.setProperty('min-width', '0', 'important');
            field.style.setProperty('width', '100%', 'important');
        });
    }

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

        const shouldShowSatuan2 = !!smallSatuanValue;

        // --- 2. Logika Satuan 2 & Satuan Kecil Display ---
        if (shouldShowSatuan2) {
            // Tampilkan block Satuan 2 dan elemen <br>
            if (block2) block2.style.display = 'block';
            if (br2) br2.style.display = 'block';

            // Aktifkan field Satuan 2 (Select dan Input Isi)
            if (largeSatuan1 && !isUsageLockedField(largeSatuan1)) largeSatuan1.disabled = false;
            if (qty1 && !isUsageLockedField(qty1)) qty1.disabled = false;

            // Aktifkan HJ Satuan Kecil input
            if (hjSatuanKecilInput) hjSatuanKecilInput.disabled = false;

        } else {
            // Sembunyikan block Satuan 2, nonaktifkan, dan reset nilai
            if (block2) block2.style.display = 'none';
            if (br2) br2.style.display = 'none';

            if (largeSatuan1 && !isUsageLockedField(largeSatuan1)) {
                largeSatuan1.disabled = true;
                largeSatuan1.value = "";
            }
            if (qty1 && !isUsageLockedField(qty1)) {
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
        const shouldShowSatuan3 = isSatuan2Visible && !!largeSatuan1Value;

        if (shouldShowSatuan3) {
            // Tampilkan block Satuan 3
            if (block3) block3.style.display = 'block';

            // Aktifkan field Satuan 3
            if (largeSatuan2 && !isUsageLockedField(largeSatuan2)) largeSatuan2.disabled = false;
            if (qty2 && !isUsageLockedField(qty2)) qty2.disabled = !largeSatuan2Value;

            // Aktifkan HJ Satuan Besar input
            if (hjSatuanBesarInput) hjSatuanBesarInput.disabled = false;

        } else {
            // Sembunyikan block Satuan 3, nonaktifkan, dan reset nilai
            if (block3) block3.style.display = 'none';

            if (largeSatuan2 && !isUsageLockedField(largeSatuan2)) {
                largeSatuan2.disabled = true;
                largeSatuan2.value = "";
            }
            if (qty2 && !isUsageLockedField(qty2)) {
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
            if (!isUsageLockedField(document.getElementById('fsatuanbesar'))) {
                $('#fsatuanbesar').prop('disabled', false);
            }
            if (!isUsageLockedField(document.getElementById('fqtykecil'))) {
                $('#fqtykecil').prop('disabled', false);
            }

            $('.satuan-kecil-display').text(satuanKecil);
            $('#hj-satuan-kecil-level1-label, #hj-satuan-kecil-level2-label, #hj-satuan-kecil-level3-label').text(
                satuanKecil);
        } else {
            $('#satuan2-block').hide();
            $('#hj-level1-block').hide();
            // Reset tanpa memicu loop yang parah
            if (!isUsageLockedField(document.getElementById('fsatuanbesar')) && $('#fsatuanbesar').val() !== "") {
                $('#fsatuanbesar').val('').trigger('change.select2'); // Gunakan namespace select2 agar lebih spesifik
            }
            if (!isUsageLockedField(document.getElementById('fsatuanbesar'))) {
                $('#fsatuanbesar').prop('disabled', true);
            }
        }

        // --- LOGIKA SATUAN 2 ---
        if (satuanKecil !== "" && satuanKecil !== null && satuan2 !== "" && satuan2 !== null) {
            $('#satuan3-block').show();
            $('#hj-level2-block').show();
            if (!isUsageLockedField(document.getElementById('fsatuanbesar2'))) {
                $('#fsatuanbesar2').prop('disabled', false);
            }
            $('#hj-satuan-besar-level1-label, #hj-satuan-besar-level2-label, #hj-satuan-besar-level3-label').text(
                satuan2);
        } else {
            $('#satuan3-block').hide();
            $('#hj-level2-block').hide();
            if (!isUsageLockedField(document.getElementById('fsatuanbesar2')) && $('#fsatuanbesar2').val() !== "") {
                $('#fsatuanbesar2').val('').trigger('change.select2');
            }
            if (!isUsageLockedField(document.getElementById('fsatuanbesar2'))) {
                $('#fsatuanbesar2').prop('disabled', true);
            }
        }

        // --- LOGIKA SATUAN 3 ---
        if (satuan3 !== "" && satuan3 !== null) {
            if (!isUsageLockedField(document.getElementById('fqtykecil2'))) {
                $('#fqtykecil2').prop('disabled', false);
            }
        } else {
            if (!isUsageLockedField(document.getElementById('fqtykecil2'))) {
                $('#fqtykecil2').prop('disabled', true);
            }
        }

        isInitialSatuanRender = false;
        isUpdating = false;
        syncSatuanQtyFieldLayout();
    }

    // --- Pemasangan Event Listener ---

    // Panggil fungsi ini saat dokumen dimuat untuk inisialisasi awal (kasus halaman Create)
    document.addEventListener('DOMContentLoaded', function() {
        updateSatuanLogic();
        syncSatuanQtyFieldLayout();
    });

    // Event listener untuk Satuan Kecil (Sudah dipasang melalui onchange="updateSatuanLogic()" di HTML)
    // Event listener untuk Satuan 2 (Sudah dipasang melalui onchange="updateSatuanLogic()" di HTML)

    // --- Image Preview Functions ---
    function previewImage(input, imageNo = 1) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                document.getElementById(`imagePreview${imageNo}`).src = e.target.result;
                document.getElementById(`imagePreviewContainer${imageNo}`).classList.remove('hidden');
                document.getElementById(`btnRemoveImage${imageNo}`).classList.remove('hidden');
                const uploadBox = document.getElementById(`uploadBox${imageNo}`);
                if (uploadBox) uploadBox.style.display = 'none';
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage(imageNo = 1) {
        document.getElementById(`fimage${imageNo}`).value = '';
        document.getElementById(`imagePreview${imageNo}`).src = '';
        document.getElementById(`imagePreviewContainer${imageNo}`).classList.add('hidden');
        document.getElementById(`btnRemoveImage${imageNo}`).classList.add('hidden');
        const uploadBox = document.getElementById(`uploadBox${imageNo}`);
        if (uploadBox) uploadBox.style.display = '';
    }

    function openModal(imageNo = 1) {
        const modal = document.getElementById('imageModal');
        const previewImg = document.getElementById(`imagePreview${imageNo}`);
        const modalImg = document.getElementById('modalContent');

        if (previewImg.src) {
            modal.classList.remove('hidden');
            modalImg.src = previewImg.src;
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") closeModal();
    });
</script>
