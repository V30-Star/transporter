@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Product' : 'Edit Product')

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


    <div x-data="{ open: false, keyword: '', rows: [], page: 1, lastPage: 1, total: 0 }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1500px] w-full mx-auto">

            {{-- ============================================ --}}
            {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
            {{-- ============================================ --}}
            @if ($action === 'delete')
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
                                <input type="hidden" name="fgroupcode"
                                    value="{{ old('fgroupcode', $product->fgroupcode) }}">

                                <!-- Add Group Produk (Icon Button) -->
                                <button disabled type="button" @click="isEditable = true; $dispatch('open-group-modal')"
                                    class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                    <i class="fa fa-plus"></i>
                                </button>

                                <!-- Browse Group (Icon Button) -->
                                <button disabled type="button"
                                    @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"
                                    class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>

                            @error('fgroupcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Merek Dropdown -->
                        <div class="mt-2 w-1/2" x-data="{ isMerekEditable: false }">
                            <label class="block text-sm font-medium">Merek</label>
                            <div class="flex items-center gap-2">
                                <select name="fmerekSelect" :disabled="!isMerekEditable"
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

                                <input type="hidden" name="fmerek" id="fmerek" value="{{ old('fmerek') }}">

                                <!-- Button Tambah Merek -->
                                <button type="button" disabled @click="isMerekEditable = true; $dispatch('open-merk-modal')"
                                    class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                    <i class="fa fa-plus"></i>

                                    <!-- Button Browse Merek -->
                                    <button type="button" disabled
                                        @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"
                                        class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                        <i class="fa fa-search"></i>
                                    </button>
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
                            <input readonly type="text" name="fbarcode"
                                value="{{ old('fbarcode', $product->fbarcode) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fbarcode') border-red-500 @enderror">
                            @error('fbarcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="satuan-container">

                            {{-- Satuan Kecil --}}
                            <div class="mt-2 w-1/4">
                                <label class="block text-sm font-medium">Satuan Kecil</label>
                                <select disabled
                                    class="w-full border rounded px-3 py-2 bg-gray-100 @error('fsatuankecil') border-red-500 @enderror"
                                    name="fsatuankecil" id="fsatuankecil" onchange="updateSatuanLogic();">
                                    <option value="" selected> Pilih Satuan 1</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}"
                                            {{ old('fsatuankecil', $product->fsatuankecil) == $satu->fsatuancode ? 'selected' : '' }}>
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
                            <div id="satuan2-block" style="display: none;">
                                <div class="flex items-end gap-4">
                                    <div class="w-1/3">
                                        <label class="block text-sm font-medium">Satuan 2</label>
                                        <select disabled
                                            class="w-full border rounded px-3 py-2 bg-gray-100 @error('fsatuanbesar') border-red-500 @enderror"
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

                                    <div class="w-1/6">
                                        <label class="block text-sm font-medium">Isi</label>
                                        <div class="flex items-baseline gap-2">
                                            <input type="number" readonly
                                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fqtykecil') border-red-500 @enderror"
                                                name="fqtykecil" id="fqtykecil"
                                                value="{{ old('fqtykecil', $product->fqtykecil) }}">
                                            <span id="satuanKecilTarget"
                                                class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap">
                                            </span>
                                        </div>
                                        @error('fqtykecil')
                                            <div class="text-red-600 text-sm mt-1">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="w-1/6">
                                        <label id="fsatuanname-label"
                                            class="block text-sm font-medium text-red-500 font-bold"></label>
                                    </div>
                                </div>
                            </div>

                            {{-- Satuan 3 --}}
                            <div id="satuan3-block" style="display: none;">
                                <div class="flex items-end gap-4">
                                    <div class="w-1/3">
                                        <label class="block text-sm font-medium">Satuan 3</label>
                                        <select disabled
                                            class="w-full border rounded px-3 py-2 bg-gray-100 @error('fsatuanbesar2') border-red-500 @enderror"
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

                                    <div class="w-1/6">
                                        <label class="block text-sm font-medium">Isi</label>
                                        <div class="flex items-baseline gap-2">
                                            <input type="number" readonly
                                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fqtykecil2') border-red-500 @enderror"
                                                name="fqtykecil2" id="fqtykecil2"
                                                value="{{ old('fqtykecil2', $product->fqtykecil2) }}">
                                            <span id="satuanKecilTarget"
                                                class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap">
                                            </span>
                                        </div>
                                        @error('fqtykecil2')
                                            <div class="text-red-600 text-sm mt-1">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="w-1/6">
                                        <label id="fsatuanname-label-2"
                                            class="block text-sm font-medium text-red-500 font-bold"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

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

                        <!-- Harga Pokok Produksi -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Harga Pokok Produksi</label>
                            <input type="text" name="fhpp" id="fhpp" readonly
                                value="{{ old('fhpp', $product->fhpp) }}"
                                class="w-full border rounded px-3 py-2 bg-gray-100 @error('fhpp') border-red-500 @enderror">
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
                                    <input type="text" readonly
                                        class="w-1/10 border rounded px-3 py-2 bg-gray-100 @error('fhargasatuankecillevel1') is-invalid @enderror"
                                        name="fhargasatuankecillevel1" id="fhargasatuankecillevel1"
                                        value="{{ old('fhargasatuankecillevel1', $product->fhargasatuankecillevel1) }}">
                                    @error('fhargasatuankecillevel1')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="text" readonly
                                        class="w-1/10 border rounded px-3 py-2 bg-gray-100 @error('fhargasatuankecillevel2') is-invalid @enderror"
                                        name="fhargasatuankecillevel2" id="fhargasatuankecillevel2"
                                        value="{{ old('fhargasatuankecillevel2', $product->fhargasatuankecillevel2) }}">
                                    @error('fhargasatuankecillevel2')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="text" readonly
                                        class="w-1/10 border rounded px-3 py-2 bg-gray-100 @error('fhargasatuankecillevel3') is-invalid @enderror"
                                        name="fhargasatuankecillevel3" id="fhargasatuankecillevel3"
                                        value="{{ old('fhargasatuankecillevel3', $product->fhargasatuankecillevel3) }}">
                                    @error('fhargasatuankecillevel3')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="text" readonly
                                        class="w-1/10 border rounded px-3 py-2 bg-gray-100 @error('fhargajuallevel1') is-invalid @enderror"
                                        name="fhargajuallevel1" id="fhargajuallevel1"
                                        value="{{ old('fhargajuallevel1', $product->fhargajuallevel1) }}">
                                    @error('fhargajuallevel1')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="text" readonly
                                        class="w-1/10 border rounded px-3 py-2 bg-gray-100 @error('fhargajuallevel2') is-invalid @enderror"
                                        name="fhargajuallevel2" id="fhargajuallevel2"
                                        value="{{ old('fhargajuallevel2', $product->fhargajuallevel2) }}">
                                    @error('fhargajuallevel2')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="text" readonly
                                        class="w-1/10 border rounded px-3 py-2 bg-gray-100 @error('fhargajuallevel3') is-invalid @enderror"
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

                        <!-- Min.Stok -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Min.Stok</label>
                            <div class="flex items-baseline">
                                <input type="text" name="fminstock" readonly
                                    value="{{ old('fminstock', $product->fminstock) }}"
                                    class="w-full border rounded px-3 py-2 bg-gray-100 @error('fminstock') border-red-500 @enderror">
                                <span id="satuanKecilTarget"
                                    class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap">
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
                    <button type="button" onclick="showDeleteModal()"
                        class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                        <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                        Hapus
                    </button>
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
                <form action="{{ route('product.update', $product->fprdid) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    @php
                        $isApproved = !empty($product->fapproval);
                    @endphp
                    <div>
                        <!-- Group Produk Dropdown -->
                        <div class="mt-2 w-1/2" x-data="{ isEditable: false }">
                            <label class="block text-sm font-medium">Group Produk</label>
                            <div class="flex items-center gap-2">
                                <select name="fgroupcodeSelect" :disabled="!isEditable"
                                    class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror"
                                    id="groupSelect">
                                    <option value=""></option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->fgroupid }}"
                                            {{ old('fgroupcode', $product->fgroupcode) == $group->fgroupid ? 'selected' : '' }}>
                                            {{ $group->fgroupname }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="fgroupcode"
                                    value="{{ old('fgroupcode', $product->fgroupcode) }}">

                                <!-- Add Group Produk (Icon Button) -->
                                <button type="button" @click="isEditable = true; $dispatch('open-group-modal')"
                                    class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                    <i class="fa fa-plus"></i>
                                </button>

                                <!-- Browse Group (Icon Button) -->
                                <button type="button" @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"
                                    class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>

                            @error('fgroupcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Merek Dropdown -->
                        <div class="mt-2 w-1/2" x-data="{ isMerekEditable: false }">
                            <label class="block text-sm font-medium">Merek</label>
                            <div class="flex items-center gap-2">
                                <select name="fmerekSelect" :disabled="!isMerekEditable"
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

                                <input type="hidden" name="fmerek" id="fmerek" value="{{ old('fmerek') }}">

                                <!-- Button Tambah Merek -->
                                <button type="button" @click="isMerekEditable = true; $dispatch('open-merk-modal')"
                                    class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                    <i class="fa fa-plus"></i>

                                    <!-- Button Browse Merek -->
                                    <button type="button"
                                        @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"
                                        class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                        <i class="fa fa-search"></i>
                                    </button>
                            </div>
                            @error('fmerek')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kode Product -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Kode Product</label>
                            <input type="text" name="fprdcode" id="fprdcode"
                                value="{{ old('fprdcode', $product->fprdcode) }}"
                                class="w-full border rounded px-3 py-2 uppercase @error('fprdcode') border-red-500 @enderror">
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

                        <!-- Nama Product -->
                        <div class="mt-2 w-1/2">
                            <label class="block text-sm font-medium">Nama Product</label>
                            <input type="text" name="fprdname" id="fprdname"
                                value="{{ old('fprdname', $product->fprdname) }}"
                                class="w-full border rounded px-3 py-2 uppercase @error('fprdname') border-red-500 @enderror"
                                autofocus>
                            @error('fprdname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Barcode -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Barcode</label>
                            <input type="text" name="fbarcode" value="{{ old('fbarcode', $product->fbarcode) }}"
                                class="w-full border rounded px-3 py-2 @error('fbarcode') border-red-500 @enderror">
                            @error('fbarcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="satuan-container">

                            {{-- Satuan Kecil --}}
                            <div class="mt-2 w-1/4">
                                <label class="block text-sm font-medium">Satuan Kecil</label>
                                <select
                                    class="w-full border rounded px-3 py-2 @error('fsatuankecil') border-red-500 @enderror"
                                    name="fsatuankecil" id="fsatuankecil" onchange="updateSatuanLogic();">
                                    <option value="" selected> Pilih Satuan 1</option>
                                    @foreach ($satuan as $satu)
                                        <option value="{{ $satu->fsatuancode }}"
                                            {{ old('fsatuankecil', $product->fsatuankecil) == $satu->fsatuancode ? 'selected' : '' }}>
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
                            <div id="satuan2-block" style="display: none;">
                                <div class="flex items-end gap-4">
                                    <div class="w-1/3">
                                        <label class="block text-sm font-medium">Satuan 2</label>
                                        <select
                                            class="w-full border rounded px-3 py-2 @error('fsatuanbesar') border-red-500 @enderror"
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

                                    <div class="w-1/6">
                                        <label class="block text-sm font-medium">Isi</label>
                                        <div class="flex items-baseline gap-2">
                                            <input type="number"
                                                class="w-full border rounded px-3 py-2 @error('fqtykecil') border-red-500 @enderror"
                                                name="fqtykecil" id="fqtykecil"
                                                value="{{ old('fqtykecil', $product->fqtykecil) }}">
                                            <span id="satuanKecilTarget"
                                                class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap">
                                            </span>
                                        </div>
                                        @error('fqtykecil')
                                            <div class="text-red-600 text-sm mt-1">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="w-1/6">
                                        <label id="fsatuanname-label"
                                            class="block text-sm font-medium text-red-500 font-bold"></label>
                                    </div>
                                </div>
                            </div>

                            {{-- Satuan 3 --}}
                            <div id="satuan3-block" style="display: none;">
                                <div class="flex items-end gap-4">
                                    <div class="w-1/3">
                                        <label class="block text-sm font-medium">Satuan 3</label>
                                        <select
                                            class="w-full border rounded px-3 py-2 @error('fsatuanbesar2') border-red-500 @enderror"
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

                                    <div class="w-1/6">
                                        <label class="block text-sm font-medium">Isi</label>
                                        <div class="flex items-baseline gap-2">
                                            <input type="number"
                                                class="w-full border rounded px-3 py-2 @error('fqtykecil2') border-red-500 @enderror"
                                                name="fqtykecil2" id="fqtykecil2"
                                                value="{{ old('fqtykecil2', $product->fqtykecil2) }}">
                                            <span id="satuanKecilTarget"
                                                class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap">
                                            </span>
                                        </div>
                                        @error('fqtykecil2')
                                            <div class="text-red-600 text-sm mt-1">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="w-1/6">
                                        <label id="fsatuanname-label-2"
                                            class="block text-sm font-medium text-red-500 font-bold"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Satuan Default Dropdown -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Satuan Default</label>
                            <select name="fsatuandefault"
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

                        <!-- Harga Pokok Produksi -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Harga Pokok Produksi</label>
                            <input type="text" name="fhpp" id="fhpp"
                                value="{{ old('fhpp', $product->fhpp) }}"
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
                                        class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel1') is-invalid @enderror"
                                        name="fhargasatuankecillevel1" id="fhargasatuankecillevel1"
                                        value="{{ old('fhargasatuankecillevel1', $product->fhargasatuankecillevel1) }}">
                                    @error('fhargasatuankecillevel1')
                                        <div class="text-red-600 text-sm mt-1">
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
                                        value="{{ old('fhargasatuankecillevel2', $product->fhargasatuankecillevel2) }}">
                                    @error('fhargasatuankecillevel2')
                                        <div class="text-red-600 text-sm mt-1">
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
                                        value="{{ old('fhargasatuankecillevel3', $product->fhargasatuankecillevel3) }}">
                                    @error('fhargasatuankecillevel3')
                                        <div class="text-red-600 text-sm mt-1">
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
                                        name="fhargajuallevel1" id="fhargajuallevel1"
                                        value="{{ old('fhargajuallevel1', $product->fhargajuallevel1) }}">
                                    @error('fhargajuallevel1')
                                        <div class="text-red-600 text-sm mt-1">
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
                                        name="fhargajuallevel2" id="fhargajuallevel2"
                                        value="{{ old('fhargajuallevel2', $product->fhargajuallevel2) }}">
                                    @error('fhargajuallevel2')
                                        <div class="text-red-600 text-sm mt-1">
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

                        <!-- Min.Stok -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Min.Stok</label>
                            <div class="flex items-baseline">
                                <input type="text" name="fminstock"
                                    value="{{ old('fminstock', $product->fminstock) }}"
                                    class="w-full border rounded px-3 py-2 @error('fminstock') border-red-500 @enderror">
                                <span id="satuanKecilTarget"
                                    class="satuan-kecil-display text-gray-700 font-semibold whitespace-nowrap">
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

                        <div class="md:col-span-2 flex justify-center items-center space-x-2">
                            <fieldset {{ $isApproved ? 'disabled' : '' }}>
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium">Approval</label>
                                    <label class="switch">
                                        <input type="checkbox" name="approve_now" id="approvalToggle"
                                            {{ !empty($product->fapproval) ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </fieldset>
                        </div>
                        <br>
                        <div class="flex justify-center mt-4">
                            <label for="statusToggle"
                                class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <span class="text-sm font-medium">Non Aktif</span>
                                <input type="checkbox" name="fnonactive" id="statusToggle"
                                    class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                                    {{ old('fnonactive', $product->fnonactive) == '1' ? 'checked' : '' }}>
                            </label>
                        </div>
                    </div>
                    <br>
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
                    @php
                        $lastUpdate = $product->fupdatedat ?: $product->fcreatedat;
                        $isUpdated = !empty($product->fupdatedat);
                    @endphp
                </form>
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

                <div class="flex justify-end space-x-2">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        id="btnTidak">
                        Tidak
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                        id="btnYa">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div id="toast" class="hidden fixed top-4 right-4 z-50 max-w-sm">
            <div id="toastContent" class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center">
                <span id="toastMessage"></span>
                <button onclick="closeToast()" class="ml-4 text-white hover:text-gray-200">×</button>
            </div>
        </div>

        <script>
            // Tampilkan Modal
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            // Tutup Modal
            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            // Tutup Toast
            function closeToast() {
                document.getElementById('toast').classList.add('hidden');
            }

            // Tampilkan Toast
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

            // Konfirmasi Delete
            function confirmDelete() {
                const btnYa = document.getElementById('btnYa');
                const btnTidak = document.getElementById('btnTidak');

                // Disable buttons
                btnYa.disabled = true;
                btnTidak.disabled = true;
                btnYa.textContent = 'Menghapus...';

                // Kirim request delete
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
                        showToast(data.message || 'Data berhasil dihapus', true);

                        // Redirect ke index setelah 1.5 detik
                        setTimeout(() => {
                            window.location.href = '{{ route('product.index') }}';
                        }, 1500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat menghapus data', false);
                    });
            }
        </script>
    @endif

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
                    if (res && res.name && res.id) {
                        const opt = new Option(res.name, res.id, true, true);
                        $('#merkSelect').append(opt).trigger('change');
                        this.open = false;
                        this.form.fmerekcode = '';
                        this.form.fmerekname = '';
                        this.form.fnonactive = false;
                    } else {
                        alert('Response format is incorrect or missing expected data.');
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

        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <!-- card -->
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Tambah Merek</h3>

            <div class="space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-medium">Kode Merek</label>
                    <input type="text" x-model="form.fmerekcode" class="w-full border rounded px-3 py-2"
                        maxlength="10" :class="errors.fmerekcode ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekcode">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekcode[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Merek</label>
                    <input type="text" x-model="form.fmerekname" class="w-full border rounded px-3 py-2"
                        :class="errors.fmerekname ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekname">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekname[0]"></p>
                    </template>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_fnonactive" class="block text-sm font-medium">Non Aktif</label>
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

    <!-- MODAL BROWSE MEREK -->
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.8.1/autoNumeric.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#groupSelect').select2({
            placeholder: '',
            allowClear: true
        });

        $('#merkSelect').select2({
            placeholder: '',
            allowClear: true
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

        function toggleFields() {
            if (fsatuankecil.value !== "") {
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

        fsatuankecil.addEventListener('change', toggleFields);

        toggleFields();
    });

    function chooseMerek(merek) {
        // Set value to the select dropdown
        document.querySelector('#merkSelect').value = merek.fmerekid;

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
                fgroupcode,
                fgroupid,
                fgroupname
            } = ev.detail || {};

            const sel = document.getElementById('groupSelect');
            const hidId = document.getElementById('groupIdHidden');
            const hidCode = document.getElementById('groupCodeHidden');

            if (sel) {
                sel.value = fgroupcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            if (hidId) {
                hidId.value = fgroupid || '';
            }

            if (hidCode) {
                hidCode.value = fgroupcode || '';
            }
        });
    });

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
