@extends('layouts.app')

@section('title', 'Master Produk')

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

        <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data" data-form-draft="true"
            data-draft-key="product:create">
            @csrf

            {{-- ═══ PAGE HEADER ═══ --}}
            {{-- <div class="flex items-center justify-between mb-5">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Master Produk</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Buat produk baru</p>
                </div>
            </div> --}}

            {{-- ═══ MAIN GRID: sidebar image + form ═══ --}}
            <div class="flex gap-5 items-start">

                {{-- ── LEFT: Gambar Produk ── --}}
                @if (!empty($enabledImageNumbers))
                    <div class="flex-shrink-0 w-48">
                        <div class="section-card" style="padding: 1rem;">
                            <div class="section-title" style="margin-bottom:0.75rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Foto Produk
                            </div>

                            <div class="space-y-3">
                                @foreach ($enabledImageNumbers as $imgNo)
                                    <div>
                                        <span class="field-label">Foto {{ $imgNo }}</span>
                                        <input type="file" name="fimage{{ $imgNo }}"
                                            id="fimage{{ $imgNo }}" accept="image/*" class="hidden"
                                            onchange="previewImage(this, {{ $imgNo }})">

                                        {{-- Preview box --}}
                                        <div id="imagePreviewContainer{{ $imgNo }}" class="hidden mb-2">
                                            <img id="imagePreview{{ $imgNo }}" src=""
                                                alt="Preview {{ $imgNo }}"
                                                class="w-full rounded border cursor-zoom-in hover:opacity-90 transition"
                                                style="object-fit:cover; height:130px;"
                                                onclick="openModal({{ $imgNo }})">
                                        </div>

                                        {{-- Upload box (shown when no preview) --}}
                                        <div id="uploadBox{{ $imgNo }}" class="img-upload-box"
                                            onclick="document.getElementById('fimage{{ $imgNo }}').click()">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>Klik untuk pilih</span>
                                        </div>

                                        <button type="button" id="btnRemoveImage{{ $imgNo }}"
                                            class="hidden mt-1 w-full text-xs text-red-500 border border-red-200 rounded py-1 hover:bg-red-50"
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
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
                                                    {{ old('fgroupid', old('fgroupcode')) == $group->fgroupid ? 'selected' : '' }}>
                                                    {{ $group->fgroupcode }} - {{ $group->fgroupname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Group"
                                            @click="window.dispatchEvent(new CustomEvent('group-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="fgroupid" id="groupIdHidden"
                                        value="{{ old('fgroupid', old('fgroupcode')) }}">
                                    <input type="hidden" name="fgroupcode" id="groupCodeHidden"
                                        value="{{ old('fgroupcode', '') }}">
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
                                                    {{ old('fmerek') == $merk->fmerekcode ? 'selected' : '' }}>
                                                    {{ $merk->fmerekcode }} - {{ $merk->fmerekname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-0" role="button" aria-label="Browse Merek"
                                            @click="window.dispatchEvent(new CustomEvent('merek-browse-open'))"></div>
                                    </div>
                                    <input type="hidden" name="fmerek" id="fmerek" value="{{ old('fmerek') }}">
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
                            <div x-data="{ autoCode: true }">
                                <label class="field-label">Kode Produk</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="fprdcode" id="fprdcode"
                                        class="field-input flex-1 uppercase" placeholder="Masukkan kode"
                                        :disabled="autoCode" :value="autoCode ? '' : '{{ old('fprdcode') }}'"
                                        :class="autoCode ? 'bg-gray-100 cursor-not-allowed' : ''">
                                    <label
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 cursor-pointer whitespace-nowrap">
                                        <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600"
                                            checked>
                                        Auto
                                    </label>
                                </div>
                            </div>

                            {{-- Nama Produk --}}
                            <div>
                                <label class="field-label">Nama Produk</label>
                                <input type="text" name="fprdname" id="fprdname" value="{{ old('fprdname') }}"
                                    class="field-input uppercase @error('fprdname') border-red-500 bg-red-50 @enderror"
                                    autofocus>
                                @error('fprdname')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Barcode --}}
                            <div>
                                <label class="field-label">Barcode</label>
                                <input type="text" name="fbarcode" value="{{ old('fbarcode') }}"
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
                                    <option value="Produk">Produk</option>
                                    <option value="Jasa">Jasa</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    {{-- ═══ SECTION 2: Satuan & HPP ═══ --}}
                    <div class="section-card" id="satuan-container">
                        <div class="section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
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
                                        onchange="updateSatuanLogic();">
                                        <option value="" selected>Pilih Satuan 1</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                {{ old('fsatuankecil') == $satu->fsatuancode ? 'selected' : '' }}>
                                                {{ $satu->fsatuancode }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fsatuankecil')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div></div>
                                <div>
                                    <label class="field-label">HPP Satuan Kecil</label>
                                    <input type="text" name="fhpp" id="fhpp"
                                        class="autonumeric field-input blue text-right" value="0">
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
                                    <select class="field-input yellow" name="fsatuanbesar" id="fsatuanbesar" disabled
                                        onchange="updateSatuanLogic();">
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
                                <div>
                                    <label class="field-label">Isi</label>
                                    <div
                                        class="flex items-center border border-yellow-300 rounded bg-yellow-50 focus-within:ring-1 focus-within:ring-yellow-400">
                                        <input type="text" name="fqtykecil" id="fqtykecil" value="0"
                                            class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm"
                                            disabled>
                                        <span
                                            class="satuan-kecil-display text-gray-500 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-yellow-200 ml-2 pl-2"></span>
                                    </div>
                                    @error('fqtykecil')
                                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="field-label">HPP Satuan 2</label>
                                    <input type="text" name="fhpp2" id="fhpp2"
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
                                    <select class="field-input purple" name="fsatuanbesar2" id="fsatuanbesar2" disabled
                                        onchange="updateSatuanLogic();">
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
                                <div>
                                    <label class="field-label">Isi</label>
                                    <div
                                        class="flex items-center border border-purple-300 rounded bg-purple-50 focus-within:ring-1 focus-within:ring-purple-400">
                                        <input type="text" name="fqtykecil2" id="fqtykecil2" value="0"
                                            class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm"
                                            disabled>
                                        <span
                                            class="satuan-kecil-display text-purple-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-purple-200 ml-2 pl-2 uppercase"></span>
                                    </div>
                                    @error('fqtykecil2')
                                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="field-label">HPP Satuan 3</label>
                                    <input type="text" name="fhpp3" id="fhpp3"
                                        class="autonumeric field-input purple text-right" readonly>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100 my-3">

                        {{-- Satuan Default --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Satuan Default Transaksi</label>
                                <select name="fsatuandefault"
                                    class="field-input @error('fsatuandefault') border-red-500 @enderror">
                                    <option value="1">Satuan 1</option>
                                    <option value="2">Satuan 2</option>
                                    <option value="3">Satuan 3</option>
                                </select>
                                @error('fsatuandefault')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Satuan Default Laporan</label>
                                <select name="fsatuandefaultlaporan"
                                    class="field-input @error('fsatuandefaultlaporan') border-red-500 @enderror">
                                    <option value="1">Satuan 1</option>
                                    <option value="2">Satuan 2</option>
                                    <option value="3">Satuan 3</option>
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
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
                                        <span id="hj-satuan-kecil-level1-label-row"
                                            class="uppercase text-xs text-blue-700">-</span>
                                    </td>
                                    <td>
                                        <input type="text" class="blue" name="fhargajuallevel1"
                                            id="fhargajuallevel1" value="{{ old('fhargajuallevel1', 0) }}">
                                        @error('fhargajuallevel1')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="blue" name="fhargajuallevel2"
                                            id="fhargajuallevel2" value="{{ old('fhargajuallevel2', 0) }}">
                                        @error('fhargajuallevel2')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="blue" name="fhargajuallevel3"
                                            id="fhargajuallevel3" value="{{ old('fhargajuallevel3', 0) }}">
                                        @error('fhargajuallevel3')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>

                                {{-- Row Satuan 2 --}}
                                <tr id="hj-level1-block" style="display:none;">
                                    <td class="row-label">
                                        <span class="satuan-badge yellow" style="margin:0;">S2</span>&nbsp;
                                        <span id="hj-satuan-besar-level1-label"
                                            class="uppercase text-xs text-yellow-700">-</span>
                                    </td>
                                    <td>
                                        <input type="text" class="yellow" name="fhargajual2level1"
                                            id="fhargajual2level1" value="{{ old('fhargajual2level1', 0) }}">
                                        @error('fhargajual2level1')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="yellow" name="fhargajual2level2"
                                            id="fhargajual2level2" value="{{ old('fhargajual2level2', 0) }}">
                                        @error('fhargajual2level2')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="yellow" name="fhargajual2level3"
                                            id="fhargajual2level3" value="{{ old('fhargajual2level3', 0) }}">
                                        @error('fhargajual2level3')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>

                                {{-- Row Satuan 3 --}}
                                <tr id="hj-level2-block" style="display:none;">
                                    <td class="row-label">
                                        <span class="satuan-badge purple" style="margin:0;">S3</span>&nbsp;
                                        <span id="hj-satuan-kecil-label"
                                            class="uppercase text-xs text-purple-700">-</span>
                                    </td>
                                    <td>
                                        <input type="text" class="purple" name="fhargajual3level1"
                                            id="fhargajual3level1" value="{{ old('fhargajual3level1', 0) }}">
                                        @error('fhargajual3level1')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="purple" name="fhargajual3level2"
                                            id="fhargajual3level2" value="{{ old('fhargajual3level2', 0) }}">
                                        @error('fhargajual3level2')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="purple" name="fhargajual3level3"
                                            id="fhargajual3level3" value="{{ old('fhargajual3level3', 0) }}">
                                        @error('fhargajual3level3')
                                            <div class="text-red-600 text-xs">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- Hidden label spans required by JS (kept for compatibility) --}}
                        <span id="hj-satuan-kecil-level1-label" class="hidden"></span>
                        <span id="hj-satuan-kecil-level2-label" class="hidden"></span>
                        <span id="hj-satuan-kecil-level3-label" class="hidden"></span>
                        <span id="hj-satuan-besar-level2-label" class="hidden"></span>
                        <span id="hj-satuan-besar-level3-label" class="hidden"></span>
                        <span id="hj-satuan-besar-label" class="hidden"></span>
                        <span id="hj-satuan-besar2-label" class="hidden"></span>

                        <p class="text-xs text-gray-400 mt-2">Level 1 = retail &middot; Level 2 = grosir &middot; Level 3 =
                            distributor</p>
                    </div>

                    {{-- ═══ SECTION 4: Stok & Lainnya ═══ --}}
                    <div class="section-card">
                        <div class="section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            Stok &amp; Lainnya
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="field-label">Min. Stok</label>
                                <div
                                    class="flex items-center border border-gray-300 rounded bg-gray-50 focus-within:ring-1 focus-within:ring-blue-400 @error('fminstock') border-red-500 @enderror">
                                    <input type="text" name="fminstock" id="fminstock"
                                        value="{{ number_format((float) old('fminstock', 0), 2, ',', '.') }}"
                                        class="flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm">
                                    <span
                                        class="satuan-kecil-display text-gray-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-gray-200 ml-2 pl-2 uppercase"></span>
                                </div>
                                @error('fminstock')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ═══ SECTION 4: Stok & Lainnya ═══ --}}
                    <div class="section-card">

                        {{-- BARIS 1: Kontrol Status (Non Aktif & Approve) --}}
                        <div class="flex items-center justify-center gap-2 mb-4">
                            @php $canApproval = in_array('approveProduct', explode(',', session('user_restricted_permissions', ''))); @endphp
                            @if ($canApproval)
                                <label
                                    class="flex items-center gap-2 text-sm font-semibold cursor-pointer border rounded-lg px-3 py-2 hover:bg-gray-50">
                                    <span>Approve</span>
                                    <label class="switch" style="margin:0">
                                        <input type="checkbox" name="fapproval" id="approvalToggle"
                                            {{ session('fapproval') ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </label>
                            @endif
                        </div>

                        {{-- BARIS 2: Aksi/Tombol (Keluar & Simpan) --}}
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
    </div>

    {{-- ═══ IMAGE MODAL ═══ --}}
    <div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-90 flex items-center justify-center p-4"
        onclick="closeModal()">
        <span class="absolute top-5 right-10 text-white text-4xl font-bold cursor-pointer">&times;</span>
        <img id="modalContent" class="max-w-full max-h-full rounded shadow-2xl">
    </div>

    {{-- ═══ MODAL TAMBAH GROUP ═══ --}}
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
                data: { fgroupcode: this.form.fgroupcode, fgroupname: this.form.fgroupname, fnonactive: this.form.fnonactive ? 1 : 0 }
            }).done((res) => {
                if (res && res.id && res.name) {
                    const opt = new Option(res.name, res.id, true, true);
                    $('#groupSelect').append(opt).trigger('change');
                    const hidId = document.getElementById('groupIdHidden');
                    if (hidId) hidId.value = res.id;
                    this.open = false;
                    this.form = { fgroupcode: '', fgroupname: '', fnonactive: false };
                    this.errors = {};
                } else { window.showAppErrorAlert('TERJADI KESALAHAN', 'FORMAT RESPON SERVER SALAH.'); }
                this.loading = false;
            }).fail((xhr) => {
                this.loading = false;
                if (xhr.status === 422) { this.errors = xhr.responseJSON?.errors || {}; } else { window.showAppErrorAlert('TERJADI KESALAHAN', 'GAGAL MENYIMPAN GROUP PRODUK.'); }
            });
        }
    }" x-on:open-group-modal.window="open = true; errors = {}; loading = false;" x-show="open"
        style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Tambah Group Produk</h3>
            <div class="space-y-4 mt-2">
                <div>
                    <label class="field-label">Kode Group</label>
                    <input type="text" x-model="form.fgroupcode" class="field-input uppercase" maxlength="10"
                        :class="errors.fgroupcode ? 'border-red-500' : ''">
                    <template x-if="errors.fgroupcode">
                        <p class="text-red-600 text-xs mt-1" x-text="errors.fgroupcode[0]"></p>
                    </template>
                </div>
                <div>
                    <label class="field-label">Nama Group</label>
                    <input type="text" x-model="form.fgroupname" class="field-input uppercase"
                        :class="errors.fgroupname ? 'border-red-500' : ''">
                    <template x-if="errors.fgroupname">
                        <p class="text-red-600 text-xs mt-1" x-text="errors.fgroupname[0]"></p>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_group_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_group_fnonactive" class="field-label" style="margin:0">Non Aktif</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open=false"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>
                <button type="button" @click="saveData()" :disabled="loading"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 disabled:opacity-60">
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

    {{-- ═══ MODAL TAMBAH MEREK ═══ --}}
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
                data: { fmerekcode: this.form.fmerekcode, fmerekname: this.form.fmerekname, fnonactive: this.form.fnonactive ? 1 : 0 }
            }).done((res) => {
                if (res && res.code && res.name) {
                    const opt = new Option(res.name, res.code, true, true);
                    $('#merkSelect').append(opt).trigger('change');
                    const hidMerek = document.getElementById('fmerek');
                    if (hidMerek) hidMerek.value = res.code;
                    this.open = false;
                    this.form = { fmerekcode: '', fmerekname: '', fnonactive: false };
                    this.errors = {};
                } else { window.showAppErrorAlert('TERJADI KESALAHAN', 'FORMAT RESPON SERVER SALAH.'); }
                this.loading = false;
            }).fail((xhr) => {
                this.loading = false;
                if (xhr.status === 422) { this.errors = xhr.responseJSON?.errors || {}; } else { window.showAppErrorAlert('TERJADI KESALAHAN', 'GAGAL MENYIMPAN MEREK.'); }
            });
        }
    }" x-on:open-merk-modal.window="open = true; errors = {}; loading = false;" x-show="open"
        style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Tambah Merek</h3>
            <div class="space-y-4 mt-2">
                <div>
                    <label class="field-label">Kode Merek</label>
                    <input type="text" x-model="form.fmerekcode" class="field-input uppercase" maxlength="10"
                        :class="errors.fmerekcode ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekcode">
                        <p class="text-red-600 text-xs mt-1" x-text="errors.fmerekcode[0]"></p>
                    </template>
                </div>
                <div>
                    <label class="field-label">Nama Merek</label>
                    <input type="text" x-model="form.fmerekname" class="field-input uppercase"
                        :class="errors.fmerekname ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekname">
                        <p class="text-red-600 text-xs mt-1" x-text="errors.fmerekname[0]"></p>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_fnonactive" class="field-label" style="margin:0">Non Aktif</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open=false"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>
                <button type="button" @click="saveData()" :disabled="loading"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 disabled:opacity-60">
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

    {{-- ═══ MODAL BROWSE GROUP ═══ --}}
    <div x-data="groupBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
            style="height:85vh;">
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Group Produk</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Pilih group product yang diinginkan</p>
                </div>
                <button type="button" @click="close()"
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">Tutup</button>
            </div>
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                <div id="tableControls"></div>
            </div>
            <div class="flex-1 overflow-y-auto px-6" style="min-height:0;">
                <table id="groupTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode Group
                            </th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama Group
                            </th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div id="tablePagination"></div>
            </div>
        </div>
    </div>

    {{-- ═══ MODAL BROWSE MEREK ═══ --}}
    <div x-data="merekBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
            style="height:85vh;">
            <div
                class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Browse Merek (Brand)</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Pilih merek yang diinginkan</p>
                </div>
                <button type="button" @click="close()"
                    class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">Tutup</button>
            </div>
            <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100"></div>
            <div class="flex-1 overflow-y-auto px-6" style="min-height:0;">
                <table id="merekTable" class="min-w-full text-sm display nowrap stripe hover" style="width:100%">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Kode Merek
                            </th>
                            <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Nama Merek
                            </th>
                            <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50"></div>
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
    #merekTable_wrapper .dt-length select,
    #merekTable_wrapper .dataTables_length select {
        min-width: 80px !important;
        width: auto !important;
        padding-right: 30px !important;
    }

    #merekTable_wrapper .dt-length,
    #merekTable_wrapper .dataTables_length {
        min-width: 180px;
        white-space: nowrap;
    }

    #merekTable_wrapper .dt-length select,
    #merekTable_wrapper .dataTables_length select {
        padding: 6px 30px 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: .375rem;
    }

    #groupTable_wrapper .dt-length select,
    #groupTable_wrapper .dataTables_length select {
        min-width: 80px !important;
        width: auto !important;
        padding-right: 30px !important;
    }

    #groupTable_wrapper .dt-length,
    #groupTable_wrapper .dataTables_length {
        min-width: 180px;
        white-space: nowrap;
    }

    #groupTable_wrapper .dt-length select,
    #groupTable_wrapper .dataTables_length select {
        padding: 6px 30px 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: .375rem;
    }
</style>

<script>
    $(document).ready(function() {
        $('#fsatuankecil, #fsatuanbesar, #fsatuanbesar2').select2({
            width: '100%'
        });

        if (typeof AutoNumeric !== 'undefined') {
            AutoNumeric.multiple('.autonumeric', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 2,
                unformatOnSubmit: true
            });
        }

        function calculateHPPRows() {
            const anHppKecil = AutoNumeric.getAutoNumericElement('#fhpp');
            const anQty2 = AutoNumeric.getAutoNumericElement('#fqtykecil');
            const anQty3 = AutoNumeric.getAutoNumericElement('#fqtykecil2');
            const anHpp2 = AutoNumeric.getAutoNumericElement('#fhpp2');
            const anHpp3 = AutoNumeric.getAutoNumericElement('#fhpp3');
            const valHppKecil = anHppKecil ? anHppKecil.getNumber() : 0;
            if (anQty2 && anHpp2) anHpp2.set(valHppKecil * anQty2.getNumber());
            if (anQty3 && anHpp3) anHpp3.set(valHppKecil * anQty3.getNumber());
        }

        $('#fhpp, #fqtykecil, #fqtykecil2').on('autoNumeric:rawValueModified', function() {
            calculateHPPRows();
        });
        setTimeout(calculateHPPRows, 500);

        let hargajuallevel1 = new AutoNumeric('#fhargajuallevel1', 'commaDecimalCharDotSeparator');
        let hargajuallevel2 = new AutoNumeric('#fhargajuallevel2', 'commaDecimalCharDotSeparator');
        let hargajuallevel3 = new AutoNumeric('#fhargajuallevel3', 'commaDecimalCharDotSeparator');
        let hargajual2level1 = new AutoNumeric('#fhargajual2level1', 'commaDecimalCharDotSeparator');
        let hargajual2level2 = new AutoNumeric('#fhargajual2level2', 'commaDecimalCharDotSeparator');
        let hargajual2level3 = new AutoNumeric('#fhargajual2level3', 'commaDecimalCharDotSeparator');
        let hargajual3level1 = new AutoNumeric('#fhargajual3level1', 'commaDecimalCharDotSeparator');
        let hargajual3level2 = new AutoNumeric('#fhargajual3level2', 'commaDecimalCharDotSeparator');
        let hargajual3level3 = new AutoNumeric('#fhargajual3level3', 'commaDecimalCharDotSeparator');

        // Produk Name Autocomplete
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
            minLength: 1,
            select: function(event, ui) {
                $(this).val(ui.item.value);
                return false;
            },
            disabled: true
        });

        const fprdcodeInput = document.getElementById('fprdcode');
        setInterval(function() {
            fprdcodeInput.disabled ?
                $('#fprdcode').autocomplete('disable') :
                $('#fprdcode').autocomplete('enable');
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

        // Select2 change events
        $('#fsatuankecil, #fsatuanbesar, #fsatuanbesar2').on('change', function() {
            updateSatuanLogic();
        });

        // fsatuanbesar2 label updates
        $('#fsatuanbesar').on('change', function() {
            const name = this.options[this.selectedIndex].getAttribute('data-name');
            $('#fsatuanname-label').text(name || 'Tidak ada pilihan');
        });
        $('#fsatuanbesar2').on('change', function() {
            const name = this.options[this.selectedIndex].getAttribute('data-name');
            $('#fsatuanname-label-2').text(name || 'Tidak ada pilihan');
        });
    });
</script>

<script>
    function checkSatuan() {
        const fsatuankecil = document.getElementById('fsatuankecil').value;
        ['fsatuanbesar', 'fsatuanbesar2', 'fqtykecil', 'fqtykecil2'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = fsatuankecil === "";
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        checkSatuan();
    });
</script>

<script>
    let isUpdating = false;

    function updateSatuanLogic() {
        if (isUpdating) return;
        isUpdating = true;

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

        const targets = document.querySelectorAll('.satuan-kecil-display');

        // HJ label spans
        const hjSatuanKecilLabel = document.getElementById('hj-satuan-kecil-label');
        const hjSatuanBesarLabel = document.getElementById('hj-satuan-besar-label');
        const hjSatuanBesar2Label = document.getElementById('hj-satuan-besar2-label');
        const hjSatuanKecilLevel1Label = document.getElementById('hj-satuan-kecil-level1-label');
        const hjSatuanKecilLevel2Label = document.getElementById('hj-satuan-kecil-level2-label');
        const hjSatuanKecilLevel3Label = document.getElementById('hj-satuan-kecil-level3-label');
        const hjSatuanBesarLevel1Label = document.getElementById('hj-satuan-besar-level1-label');
        const hjSatuanBesarLevel2Label = document.getElementById('hj-satuan-besar-level2-label');
        const hjSatuanBesarLevel3Label = document.getElementById('hj-satuan-besar-level3-label');

        // HJ input fields
        const hjSatuanKecilInput = document.getElementById('fhargajual3level1');
        const hjSatuanBesarInput = document.getElementById('fhargajual3level2');
        const hjSatuanBesar2Input = document.getElementById('fhargajual3level3');

        const smallSatuanValue = smallSatuan ? smallSatuan.value : '';
        const largeSatuan1Value = largeSatuan1 ? largeSatuan1.value : '';
        const largeSatuan2Value = largeSatuan2 ? largeSatuan2.value : '';

        // Satuan 2 visibility
        if (smallSatuanValue) {
            if (block2) block2.style.display = 'block';
            if (br2) br2.style.display = 'block';
            if (largeSatuan1) largeSatuan1.disabled = false;
            if (qty1) qty1.disabled = false;
            if (hjSatuanKecilInput) hjSatuanKecilInput.disabled = false;
        } else {
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
            if (hjSatuanKecilInput) {
                hjSatuanKecilInput.disabled = true;
                hjSatuanKecilInput.value = 0;
            }
        }

        targets.forEach(function(target) {
            target.textContent = smallSatuanValue;
        });

        if (hjSatuanKecilLevel1Label) hjSatuanKecilLevel1Label.textContent = smallSatuanValue || '-';
        if (hjSatuanKecilLevel2Label) hjSatuanKecilLevel2Label.textContent = smallSatuanValue || '-';
        if (hjSatuanKecilLevel3Label) hjSatuanKecilLevel3Label.textContent = smallSatuanValue || '-';
        if (hjSatuanBesarLevel1Label) hjSatuanBesarLevel1Label.textContent = largeSatuan1Value || '-';
        if (hjSatuanBesarLevel2Label) hjSatuanBesarLevel2Label.textContent = largeSatuan1Value || '-';
        if (hjSatuanBesarLevel3Label) hjSatuanBesarLevel3Label.textContent = largeSatuan1Value || '-';
        if (hjSatuanKecilLabel) hjSatuanKecilLabel.textContent = largeSatuan2Value || '-';
        if (hjSatuanBesarLabel) hjSatuanBesarLabel.textContent = largeSatuan2Value || '-';
        if (hjSatuanBesar2Label) hjSatuanBesar2Label.textContent = largeSatuan2Value || '-';

        // Update matrix row labels (new layout)
        const rowLabelS1 = document.getElementById('hj-satuan-kecil-level1-label-row');
        if (rowLabelS1) rowLabelS1.textContent = smallSatuanValue || '-';

        // Satuan 3 visibility
        const isSatuan2Visible = block2 ? block2.style.display !== 'none' : false;
        if (isSatuan2Visible && largeSatuan1Value) {
            if (block3) block3.style.display = 'block';
            if (largeSatuan2) largeSatuan2.disabled = false;
            if (qty2) qty2.disabled = false;
            if (hjSatuanBesarInput) hjSatuanBesarInput.disabled = false;
        } else {
            if (block3) block3.style.display = 'none';
            if (largeSatuan2) {
                largeSatuan2.disabled = true;
                largeSatuan2.value = "";
            }
            if (qty2) {
                qty2.disabled = true;
                qty2.value = 0;
            }
            if (hjSatuanBesarInput) {
                hjSatuanBesarInput.disabled = true;
                hjSatuanBesarInput.value = 0;
            }
        }

        const isSatuan3Visible = block3 ? block3.style.display !== 'none' : false;
        if (isSatuan3Visible && largeSatuan2Value) {
            if (hjSatuanBesar2Input) hjSatuanBesar2Input.disabled = false;
        } else {
            if (hjSatuanBesar2Input) {
                hjSatuanBesar2Input.disabled = true;
                hjSatuanBesar2Input.value = 0;
            }
        }

        // jQuery show/hide for HJ matrix rows (table rows)
        if (satuanKecil !== "" && satuanKecil !== null) {
            $('#hj-level1-block').show();
            $('#fsatuanbesar').prop('disabled', false);
            $('.satuan-kecil-display').text(satuanKecil);
            $('#hj-satuan-kecil-level1-label, #hj-satuan-kecil-level2-label, #hj-satuan-kecil-level3-label').text(
                satuanKecil);
        } else {
            $('#hj-level1-block').hide();
            if ($('#fsatuanbesar').val() !== "") $('#fsatuanbesar').val('').trigger('change.select2');
            $('#fsatuanbesar').prop('disabled', true);
        }

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
            if ($('#fsatuanbesar2').val() !== "") $('#fsatuanbesar2').val('').trigger('change.select2');
            $('#fsatuanbesar2').prop('disabled', true);
            $('#fqtykecil').prop('disabled', true);
        }

        if (satuan3 !== "" && satuan3 !== null) {
            $('#fqtykecil2').prop('disabled', false);
        } else {
            $('#fqtykecil2').prop('disabled', true);
        }

        isUpdating = false;
    }

    document.addEventListener('DOMContentLoaded', updateSatuanLogic);
</script>

<script>
    // ─── Image Preview ───
    function previewImage(input, imageNo = 1) {
        const container = document.getElementById(`imagePreviewContainer${imageNo}`);
        const preview = document.getElementById(`imagePreview${imageNo}`);
        const btnRemove = document.getElementById(`btnRemoveImage${imageNo}`);
        const uploadBox = document.getElementById(`uploadBox${imageNo}`);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                container.classList.remove('hidden');
                btnRemove.classList.remove('hidden');
                if (uploadBox) uploadBox.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage(imageNo = 1) {
        const input = document.getElementById(`fimage${imageNo}`);
        const container = document.getElementById(`imagePreviewContainer${imageNo}`);
        const preview = document.getElementById(`imagePreview${imageNo}`);
        const btnRemove = document.getElementById(`btnRemoveImage${imageNo}`);
        const uploadBox = document.getElementById(`uploadBox${imageNo}`);

        input.value = "";
        preview.src = "";
        container.classList.add('hidden');
        btnRemove.classList.add('hidden');
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

<script>
    // ─── Merek Browser ───
    window.merekBrowser = function() {
        return {
            open: false,
            table: null,
            initDataTable() {
                if (this.table) this.table.destroy();
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
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            };
                        }
                    },
                    columns: [{
                            data: 'fmerekcode',
                            name: 'fmerekcode',
                            className: 'font-mono text-sm',
                            width: '30%'
                        },
                        {
                            data: 'fmerekname',
                            name: 'fmerekname',
                            className: 'text-sm',
                            width: '55%'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            width: '15%',
                            render: function() {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white">Pilih</button>';
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
                        [1, 'asc']
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const $c = $(this.api().table().container());
                        $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '300px',
                            padding: '8px 12px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        }).focus();
                        $c.find('.dt-length select, .dataTables_length select').css({
                            padding: '6px 32px 6px 10px',
                            border: '2px solid #e5e7eb',
                            borderRadius: '8px',
                            fontSize: '14px'
                        });
                    }
                });
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
                if (this.table) this.table.search('').draw();
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
                window.addEventListener('merek-browse-open', () => this.openModal(), {
                    passive: true
                });
            }
        };
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('merek-picked', (ev) => {
            const {
                fmerekcode
            } = ev.detail || {};
            const sel = document.getElementById('merkSelect');
            const hid = document.getElementById('fmerek');
            if (sel) {
                sel.value = fmerekcode || '';
                sel.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
            if (hid) hid.value = fmerekcode || '';
            const alpineData = Alpine.$data(sel.closest('[x-data]'));
            if (alpineData) alpineData.isMerekEditable = true;
        });
    });

    // ─── Group Browser ───
    window.groupBrowser = function() {
        return {
            open: false,
            table: null,
            initDataTable() {
                if (this.table) this.table.destroy();
                this.table = $('#groupTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('group.browse') }}",
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
                            render: function() {
                                return '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white">Pilih</button>';
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
                    ],
                    autoWidth: false,
                    initComplete: function() {
                        const $c = $(this.api().table().container());
                        $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                            width: '400px',
                            maxWidth: '100%',
                            minWidth: '300px'
                        });
                        $c.find('.dt-search, .dataTables_filter').css({
                            minWidth: '420px'
                        });
                        $c.find('.dt-search .dt-input, .dataTables_filter input').focus();
                    }
                });
                $('#groupTable').on('click', '.btn-choose', (e) => {
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
                if (this.table) this.table.search('').draw();
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
        };
    };

    document.addEventListener('DOMContentLoaded', () => {
        updateSatuanLogic();
        $('#fsatuankecil, #fsatuanbesar, #fsatuanbesar2').on('change', function() {
            updateSatuanLogic();
        });

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
            if (hidCode) hidCode.value = fgroupid || '';
            const alpineData = Alpine.$data(sel.closest('[x-data]'));
            if (alpineData) alpineData.isEditable = true;
        });
    });
</script>
