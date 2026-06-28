@extends('layouts.app')

@section('title', 'View Produk')

@section('content')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>

    <style>
        .ui-autocomplete { z-index: 9999; max-height: 240px; overflow-y: auto; overflow-x: hidden; }

        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; border-radius: 50%; left: 4px; bottom: 4px; background-color: white; transition: 0.4s; }
        input:checked+.slider { background-color: #4CAF50; }
        input:checked+.slider:before { transform: translateX(26px); }
        .slider.round { border-radius: 34px; }
        .slider.round:before { border-radius: 50%; }

        .satuan-kecil-display { white-space: nowrap; display: inline-block; vertical-align: middle; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,0.2); }

        /* ─── Layout sections ─── */
        .section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; }
        .section-title { font-size: 11px; font-weight: 600; letter-spacing: 0.07em; text-transform: uppercase; color: #6b7280; margin-bottom: 1rem; display: flex; align-items: center; gap: 6px; }
        .field-label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .field-input { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px; background: #fff; color: #111827; }
        .field-input:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
        .field-input.blue   { background: #eff6ff; border-color: #93c5fd; }
        .field-input.yellow { background: #fefce8; border-color: #fde047; }
        .field-input.purple { background: #faf5ff; border-color: #d8b4fe; }

        /* satuan badge */
        .satuan-badge { display: inline-block; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 99px; margin-bottom: 6px; }
        .satuan-badge.blue   { background: #dbeafe; color: #1d4ed8; }
        .satuan-badge.yellow { background: #fef9c3; color: #92400e; }
        .satuan-badge.purple { background: #ede9fe; color: #6d28d9; }

        /* Harga jual matrix table */
        .hj-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .hj-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 7px 10px; font-size: 11px; font-weight: 600; color: #6b7280; text-align: center; }
        .hj-table th:first-child { text-align: left; }
        .hj-table td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        .hj-table td.row-label { font-size: 12px; font-weight: 600; color: #374151; white-space: nowrap; background: #f9fafb; }
        .hj-table input { width: 100%; border: 1px solid #d1d5db; border-radius: 4px; padding: 5px 8px; font-size: 13px; text-align: right; }
        .hj-table input.blue   { background: #eff6ff; border-color: #93c5fd; }
        .hj-table input.yellow { background: #fefce8; }
        .hj-table input.purple { background: #faf5ff; border-color: #d8b4fe; }

        /* Image sidebar */
        .img-view-box { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #f9fafb; display: flex; align-items: center; justify-content: center; height: 130px; }
    </style>

    <div x-data="{ open: false, keyword: '', rows: [], page: 1, lastPage: 1, total: 0 }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1800px] w-full mx-auto">

            @php $isApproved = \App\Support\ApprovalState::isApprovedRecord($product); @endphp
            @if (!empty($approvalLockMessage))
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $approvalLockMessage }}
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
                                        $photoVersion    = !empty($product->fupdatedat) ? strtotime((string)$product->fupdatedat) : null;
                                        $drivePreviewUrl = $driveFileId
                                            ? route('product.photo', ['fprdid' => $product->fprdid, 'field' => $field, 'v' => $photoVersion ?: time()])
                                            : null;
                                    @endphp
                                    <div>
                                        <span class="field-label">Foto {{ $imgNo }}</span>
                                        @if ($drivePreviewUrl)
                                            <img src="{{ $drivePreviewUrl }}"
                                                alt="Foto {{ $imgNo }}"
                                                class="w-full rounded border cursor-zoom-in hover:opacity-90 transition"
                                                style="object-fit:cover;height:130px;"
                                                onclick="openImageModal(this.src)"
                                                onerror="this.onerror=null;this.src='https://drive.google.com/thumbnail?id={{ $driveFileId }}&sz=w1000';">
                                        @else
                                            <div class="img-view-box text-gray-400 text-xs italic">Belum ada foto</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Klik gambar untuk zoom</p>
                        </div>
                    </div>
                @endif

                {{-- ── RIGHT: Form fields (read-only) ── --}}
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
                            <div>
                                <label class="field-label">Group Produk</label>
                                <select disabled class="field-input bg-gray-100 text-gray-700 cursor-not-allowed" id="groupSelect">
                                    <option value="">-- Pilih Group Produk --</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->fgroupid }}"
                                            {{ $product->fgroupcode == $group->fgroupid ? 'selected' : '' }}>
                                            {{ $group->fgroupcode }} - {{ $group->fgroupname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Merek --}}
                            <div>
                                <label class="field-label">Merek</label>
                                <select disabled class="field-input bg-gray-100 text-gray-700 cursor-not-allowed" id="merkSelect">
                                    <option value="">-- Pilih Merek --</option>
                                    @foreach ($merks as $merk)
                                        <option value="{{ $merk->fmerekcode }}"
                                            {{ $product->fmerek == $merk->fmerekcode ? 'selected' : '' }}>
                                            {{ $merk->fmerekcode }} - {{ $merk->fmerekname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-4">
                            {{-- Kode Produk --}}
                            <div>
                                <label class="field-label">Kode Produk</label>
                                <input type="text" readonly value="{{ $product->fprdcode }}"
                                    class="field-input bg-gray-100 cursor-not-allowed uppercase">
                            </div>

                            {{-- Nama Produk --}}
                            <div>
                                <label class="field-label">Nama Produk</label>
                                <input type="text" readonly value="{{ $product->fprdname }}"
                                    class="field-input bg-gray-100 uppercase">
                            </div>

                            {{-- Barcode --}}
                            <div>
                                <label class="field-label">Barcode</label>
                                <input type="text" readonly value="{{ $product->fbarcode }}"
                                    class="field-input bg-gray-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            {{-- Jenis --}}
                            <div>
                                <label class="field-label">Jenis</label>
                                <select disabled class="field-input bg-gray-100 cursor-not-allowed">
                                    <option value="Produk" {{ $product->ftype == 'Produk' ? 'selected' : '' }}>Produk</option>
                                    <option value="Jasa"   {{ $product->ftype == 'Jasa'   ? 'selected' : '' }}>Jasa</option>
                                </select>
                            </div>

                            {{-- Non Aktif --}}
                            <div class="flex items-end pb-0.5">
                                <label class="inline-flex items-center gap-2 border-2 border-red-200 bg-red-50 text-red-700 rounded-lg px-3 py-2 text-sm font-semibold {{ $product->fnonactive == '1' ? 'opacity-100' : 'opacity-40' }}">
                                    <input type="checkbox" disabled class="h-4 w-4 text-red-600 rounded border-red-300"
                                        {{ $product->fnonactive == '1' ? 'checked' : '' }}>
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
                                    <select disabled class="field-input blue cursor-not-allowed" name="fsatuankecil" id="fsatuankecil">
                                        <option value="">Pilih Satuan 1</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                {{ $product->fsatuankecil == $satu->fsatuancode ? 'selected' : '' }}>
                                                {{ $satu->fsatuancode }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div></div>
                                <div>
                                    <label class="field-label">HPP Satuan Kecil</label>
                                    <input type="text" disabled id="fhpp"
                                        class="autonumeric field-input blue text-right"
                                        value="{{ $product->fhpp ?? 0 }}">
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
                                    <select disabled class="field-input yellow cursor-not-allowed" name="fsatuanbesar" id="fsatuanbesar">
                                        <option value="">Pilih Satuan 2</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                {{ $product->fsatuanbesar == $satu->fsatuancode ? 'selected' : '' }}>
                                                {{ $satu->fsatuancode }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Isi</label>
                                    <div class="flex items-center border border-yellow-300 rounded bg-yellow-50">
                                        <input type="text" disabled id="fqtykecil"
                                            value="{{ $product->fqtykecil ?? 0 }}"
                                            class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm">
                                        <span class="satuan-kecil-display text-gray-500 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-yellow-200 ml-2 pl-2"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">HPP Satuan 2</label>
                                    <input type="text" disabled id="fhpp2"
                                        value="{{ $product->fhpp2 ?? 0 }}"
                                        class="autonumeric field-input yellow text-right">
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
                                    <select disabled class="field-input purple cursor-not-allowed" name="fsatuanbesar2" id="fsatuanbesar2">
                                        <option value="">Pilih Satuan 3</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                {{ $product->fsatuanbesar2 == $satu->fsatuancode ? 'selected' : '' }}>
                                                {{ $satu->fsatuancode }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Isi</label>
                                    <div class="flex items-center border border-purple-300 rounded bg-purple-50">
                                        <input type="text" disabled id="fqtykecil2"
                                            value="{{ $product->fqtykecil2 ?? 0 }}"
                                            class="autonumeric flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm">
                                        <span class="satuan-kecil-display text-purple-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-purple-200 ml-2 pl-2 uppercase"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label">HPP Satuan 3</label>
                                    <input type="text" disabled id="fhpp3"
                                        value="{{ $product->fhpp3 ?? 0 }}"
                                        class="autonumeric field-input purple text-right">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100 my-3">

                        {{-- Satuan Default --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Satuan Default Transaksi</label>
                                <select disabled class="field-input bg-gray-100 cursor-not-allowed">
                                    <option value="1" {{ $product->fsatuandefault == '1' ? 'selected' : '' }}>Satuan 1</option>
                                    <option value="2" {{ $product->fsatuandefault == '2' ? 'selected' : '' }}>Satuan 2</option>
                                    <option value="3" {{ $product->fsatuandefault == '3' ? 'selected' : '' }}>Satuan 3</option>
                                </select>
                            </div>
                            <div>
                                <label class="field-label">Satuan Default Laporan</label>
                                <select disabled class="field-input bg-gray-100 cursor-not-allowed">
                                    <option value="1" {{ $product->fsatuandefaultlaporan == '1' ? 'selected' : '' }}>Satuan 1</option>
                                    <option value="2" {{ $product->fsatuandefaultlaporan == '2' ? 'selected' : '' }}>Satuan 2</option>
                                    <option value="3" {{ $product->fsatuandefaultlaporan == '3' ? 'selected' : '' }}>Satuan 3</option>
                                </select>
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
                                    <td><input type="text" disabled class="autonumeric blue" value="{{ $product->fhargajuallevel1 ?? 0 }}"></td>
                                    <td><input type="text" disabled class="autonumeric blue" value="{{ $product->fhargajuallevel2 ?? 0 }}"></td>
                                    <td><input type="text" disabled class="autonumeric blue" value="{{ $product->fhargajuallevel3 ?? 0 }}"></td>
                                </tr>

                                {{-- Row Satuan 2 --}}
                                <tr id="hj-level1-block" style="display:none;">
                                    <td class="row-label">
                                        <span class="satuan-badge yellow" style="margin:0;">S2</span>&nbsp;
                                        <span id="hj-satuan-besar-level1-label" class="uppercase text-xs text-yellow-700">-</span>
                                    </td>
                                    <td><input type="text" disabled class="autonumeric yellow" value="{{ $product->fhargajual2level1 ?? 0 }}"></td>
                                    <td><input type="text" disabled class="autonumeric yellow" value="{{ $product->fhargajual2level2 ?? 0 }}"></td>
                                    <td><input type="text" disabled class="autonumeric yellow" value="{{ $product->fhargajual2level3 ?? 0 }}"></td>
                                </tr>

                                {{-- Row Satuan 3 --}}
                                <tr id="hj-level2-block" style="display:none;">
                                    <td class="row-label">
                                        <span class="satuan-badge purple" style="margin:0;">S3</span>&nbsp;
                                        <span id="hj-satuan-kecil-label" class="uppercase text-xs text-purple-700">-</span>
                                    </td>
                                    <td><input type="text" disabled class="autonumeric purple" value="{{ $product->fhargajual3level1 ?? 0 }}"></td>
                                    <td><input type="text" disabled class="autonumeric purple" value="{{ $product->fhargajual3level2 ?? 0 }}"></td>
                                    <td><input type="text" disabled class="autonumeric purple" value="{{ $product->fhargajual3level3 ?? 0 }}"></td>
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

                    {{-- ═══ SECTION 4: Stok & Info ═══ --}}
                    <div class="section-card">
                        <div class="section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            Stok &amp; Info
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="field-label">Min. Stok</label>
                                <div class="flex items-center border border-gray-300 rounded bg-gray-50">
                                    <input type="text" disabled id="fminstock"
                                        value="{{ number_format((float)($product->fminstock ?? 0), 2, ',', '.') }}"
                                        class="flex-1 bg-transparent border-none focus:ring-0 px-3 py-2 text-right text-sm">
                                    <span id="satuanKecilTarget" class="satuan-kecil-display text-gray-700 font-bold text-[10px] pr-3 flex-shrink-0 border-l border-gray-200 ml-2 pl-2 uppercase"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Approve status --}}
                        @php $canApproval = in_array('approveProduct', explode(',', session('user_restricted_permissions', ''))); @endphp
                        @if ($canApproval)
                            <div class="flex items-center justify-center gap-2 mb-4">
                                <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer border rounded-lg px-3 py-2">
                                    <span>Approve</span>
                                    <label class="switch" style="margin:0">
                                        <input type="checkbox" disabled {{ $isApproved ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </label>
                            </div>
                        @endif

                        {{-- Tombol Aksi --}}
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" onclick="window.location.href='{{ route('product.index') }}'"
                                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1">
                                <x-heroicon-o-arrow-left class="w-4 h-4" /> Kembali
                            </button>
                        </div>

                        <br>
                        <hr style="border:0;border-top:2px dashed #000;margin:16px 0;">
                        <span class="text-sm text-gray-600 flex justify-between items-center">
                            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
                            <span>{{ \Carbon\Carbon::parse($product->fupdatedat ?: $product->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
                        </span>
                    </div>

                </div>{{-- end right column --}}

            </div>{{-- end main grid --}}

        </div>
    </div>

    {{-- ═══ IMAGE MODAL ═══ --}}
    <div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-90 flex items-center justify-center p-4"
        onclick="closeModal()">
        <span class="absolute top-5 right-10 text-white text-4xl font-bold cursor-pointer">&times;</span>
        <img id="modalContent" class="max-w-full max-h-full rounded shadow-2xl">
    </div>

@endsection

<script>
    /**
     * View mode — show/hide satuan blocks and update labels.
     * All inputs remain disabled.
     */
    function initViewSatuanDisplay() {
        const satuanKecil = document.getElementById('fsatuankecil')?.value || '';
        const satuan2     = document.getElementById('fsatuanbesar')?.value  || '';
        const satuan3     = document.getElementById('fsatuanbesar2')?.value || '';

        // Update unit display spans
        document.querySelectorAll('.satuan-kecil-display').forEach(el => el.textContent = satuanKecil);

        // Update HJ Labels
        ['hj-satuan-kecil-level1-label','hj-satuan-kecil-level2-label','hj-satuan-kecil-level3-label',
         'hj-satuan-kecil-level1-label-row'].forEach(id => {
            const el = document.getElementById(id); if (el) el.textContent = satuanKecil || '-';
        });
        ['hj-satuan-besar-level1-label','hj-satuan-besar-level2-label','hj-satuan-besar-level3-label',
         'hj-satuan-besar-label'].forEach(id => {
            const el = document.getElementById(id); if (el) el.textContent = satuan2 || '-';
        });
        ['hj-satuan-kecil-label','hj-satuan-besar2-label'].forEach(id => {
            const el = document.getElementById(id); if (el) el.textContent = satuan3 || '-';
        });

        // Show/hide satuan 2 block
        const show2 = satuan2 !== '';
        document.getElementById('satuan2-block').style.display  = show2 ? '' : 'none';
        document.getElementById('br-satuan2').style.display     = show2 ? '' : 'none';
        document.getElementById('hj-level1-block').style.display = show2 ? '' : 'none';

        // Show/hide satuan 3 block
        const show3 = satuan3 !== '';
        document.getElementById('satuan3-block').style.display  = show3 ? '' : 'none';
        document.getElementById('hj-level2-block').style.display = show3 ? '' : 'none';
    }

    // Also update satuanKecilTarget (min stok)
    function updateMinStokSatuan() {
        const satuanKecil = document.getElementById('fsatuankecil')?.value || '';
        const target = document.getElementById('satuanKecilTarget');
        if (target) target.textContent = satuanKecil;
    }

    document.addEventListener('DOMContentLoaded', function() {
        initViewSatuanDisplay();
        updateMinStokSatuan();
    });

    function openImageModal(src) {
        const modal   = document.getElementById('imageModal');
        const content = document.getElementById('modalContent');
        if (!src || !modal || !content) return;
        content.src = src;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        const modal = document.getElementById('imageModal');
        if (modal) modal.classList.add('hidden');
    }
</script>
