@extends('layouts.app')

@section('title', 'Cetak Barcode Label')

@section('content')
<div class="bp-container">

    {{-- Top Header Card --}}
    <div class="bp-header-card">
        <div class="bp-header-left">
            <div class="bp-icon-badge">
                <i class="fa-solid fa-barcode"></i>
            </div>
            <div>
                <div class="bp-breadcrumb">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="{{ route('product.index') }}">Master Barang</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span class="active">Cetak Barcode</span>
                </div>
                <h1 class="bp-page-title">Cetak Barcode & Label Produk</h1>
                <p class="bp-page-subtitle">Atur ukuran kertas printer & cetak label barcode produk.</p>
            </div>
        </div>

        <div class="bp-header-right">
            <a href="{{ route('product.index') }}" class="bp-btn-secondary">
                <i class="fa-solid fa-box-archive text-blue-500"></i>
                <span>Ke Master Produk</span>
            </a>
        </div>
    </div>

    <form id="barcodeForm" action="{{ route('barcode.print') }}" method="POST" target="_blank">
        @csrf
        <input type="hidden" name="items" id="itemsInput" value="[]">

        <div class="bp-workspace-grid">

            {{-- ==================== PANEL KIRI: PENGATURAN STIKER & LIVE PREVIEW (420px) ==================== --}}
            <div class="bp-sidebar-pane">

                {{-- CARD 1: UKURAN KERTAS --}}
                <div class="bp-card">
                    <div class="bp-card-header">
                        <div class="bp-step-badge">1</div>
                        <span class="bp-card-title">Ukuran Kertas Stiker</span>
                        <span class="bp-pill-tag">Dinamis</span>
                    </div>

                    <div class="bp-card-body">
                        {{-- Recent Setting Section --}}
                        <div id="recentSettingSection" class="bp-recent-section" style="display: none;">
                            <div class="bp-recent-header">
                                <span class="bp-recent-title">
                                    <i class="fa-solid fa-clock-rotate-left text-amber-500 mr-1.5"></i>
                                    Recent Setting
                                </span>
                                <button type="button" id="btnClearRecent" class="bp-recent-clear-btn" title="Hapus riwayat setting">
                                    Hapus
                                </button>
                            </div>
                            <div id="recentChipsList" class="bp-recent-chips"></div>
                        </div>

                        <div class="bp-form-group">
                            <label class="bp-label">Pilih Preset Ukuran Stiker Fisik</label>
                            <div class="bp-preset-grid">
                                <button type="button" data-preset="33x15_3col" class="bp-preset-btn active">
                                    <div class="bp-preset-name">33 × 15 mm</div>
                                    <div class="bp-preset-desc">3 Kol (Retail)</div>
                                </button>
                                <button type="button" data-preset="40x30_1col" class="bp-preset-btn">
                                    <div class="bp-preset-name">40 × 30 mm</div>
                                    <div class="bp-preset-desc">1 Kol (Standar)</div>
                                </button>
                                <button type="button" data-preset="50x20_1col" class="bp-preset-btn">
                                    <div class="bp-preset-name">50 × 20 mm</div>
                                    <div class="bp-preset-desc">1 Kol (Price Tag)</div>
                                </button>
                                <button type="button" data-preset="50x30_1col" class="bp-preset-btn">
                                    <div class="bp-preset-name">50 × 30 mm</div>
                                    <div class="bp-preset-desc">1 Kol (Sedang)</div>
                                </button>
                                <button type="button" data-preset="70x50_1col" class="bp-preset-btn">
                                    <div class="bp-preset-name">70 × 50 mm</div>
                                    <div class="bp-preset-desc">1 Kol (Box)</div>
                                </button>
                                <button type="button" data-preset="custom" class="bp-preset-btn">
                                    <div class="bp-preset-name">Custom</div>
                                    <div class="bp-preset-desc">Input Bebas</div>
                                </button>
                            </div>
                        </div>

                        {{-- Input Dimensi Dinamis (4 Kolom Rapi 1 Baris) --}}
                        <div class="bp-dim-row-4">
                            <div class="bp-dim-col">
                                <label class="bp-label">Lebar (mm)</label>
                                <div class="bp-input-unit">
                                    <input type="number" step="0.5" min="10" max="250" name="label_width" id="labelWidth" value="33" class="bp-input font-mono">
                                    <span class="unit">mm</span>
                                </div>
                            </div>
                            <div class="bp-dim-col">
                                <label class="bp-label">Tinggi (mm)</label>
                                <div class="bp-input-unit">
                                    <input type="number" step="0.5" min="8" max="250" name="label_height" id="labelHeight" value="15" class="bp-input font-mono">
                                    <span class="unit">mm</span>
                                </div>
                            </div>
                            <div class="bp-dim-col">
                                <label class="bp-label">Kolom</label>
                                <input type="number" min="1" max="5" name="columns" id="labelColumns" value="3" class="bp-input font-mono">
                            </div>
                            <div class="bp-dim-col">
                                <label class="bp-label">Gap (mm)</label>
                                <div class="bp-input-unit">
                                    <input type="number" step="0.5" min="0" max="20" name="gap_x" id="gapX" value="2" class="bp-input font-mono">
                                    <span class="unit">mm</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: KONTEN PADA LABEL --}}
                <div class="bp-card">
                    <div class="bp-card-header">
                        <div class="bp-step-badge">2</div>
                        <span class="bp-card-title">Informasi Pada Label</span>
                    </div>

                    <div class="bp-card-body">
                        <div class="bp-check-grid">
                            <div class="bp-check-item">
                                <label class="bp-checkbox-label">
                                    <input type="checkbox" name="show_company" id="showCompany" value="1" checked class="bp-chk">
                                    <span>Header Toko:</span>
                                </label>
                                <input type="text" name="company_name" id="companyName" value="{{ $companyName }}" class="bp-input bp-inline-input" placeholder="Nama Toko">
                            </div>

                            <div class="bp-check-item">
                                <label class="bp-checkbox-label">
                                    <input type="checkbox" name="show_name" id="showName" value="1" checked class="bp-chk">
                                    <span>Nama Produk</span>
                                </label>
                            </div>

                            <div class="bp-check-item">
                                <label class="bp-checkbox-label">
                                    <input type="checkbox" name="show_code" id="showCode" value="1" checked class="bp-chk">
                                    <span>Garis Barcode & Kode</span>
                                </label>
                            </div>

                            <div class="bp-check-item">
                                <label class="bp-checkbox-label">
                                    <input type="checkbox" name="show_price" id="showPrice" value="1" checked class="bp-chk">
                                    <span>Harga Jual (Rp)</span>
                                </label>
                            </div>
                        </div>

                        <div class="bp-dim-row pt-2.5 mt-2.5 border-t border-[var(--app-border)]">
                            <div class="bp-dim-col">
                                <label class="bp-label">Tinggi Barcode (px)</label>
                                <input type="number" min="15" max="80" name="barcode_height" id="barcodeHeight" value="20" class="bp-input font-mono">
                            </div>
                            <div class="bp-dim-col">
                                <label class="bp-label">Ukuran Font (pt)</label>
                                <input type="number" step="0.5" min="6" max="14" name="font_size" id="fontSize" value="7" class="bp-input font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD 3: LIVE PREVIEW ROLL STRIP --}}
                <div class="bp-card">
                    <div class="bp-card-header">
                        <div class="bp-step-badge">3</div>
                        <span class="bp-card-title">Live Preview Strip Label</span>
                        <div class="bp-zoom-controls">
                            <span class="text-xs text-[var(--app-text-muted)] mr-1">Zoom:</span>
                            <button type="button" onclick="setZoom(1)" class="bp-zoom-btn active">1x</button>
                            <button type="button" onclick="setZoom(1.5)" class="bp-zoom-btn">1.5x</button>
                            <button type="button" onclick="setZoom(2)" class="bp-zoom-btn">2x</button>
                        </div>
                    </div>

                    <div class="bp-preview-viewport">
                        <div id="previewRollStrip" class="bp-roll-strip">
                            <div id="previewLabelsContainer" class="bp-labels-row"></div>
                        </div>
                    </div>

                    <div class="bp-preview-footer">
                        <i class="fa-solid fa-circle-check text-green-500 mr-1"></i>
                        Simulasi 1 baris kertas stiker fisik. Saat dicetak, ukuran pas 100% dengan mm stiker.
                    </div>
                </div>

            </div>


            {{-- ==================== PANEL KANAN: PILIH PRODUK & ANTREAN CETAK (LEBAR PENUH) ==================== --}}
            <div class="bp-main-pane">

                {{-- CARD: PILIH & TAMBAH PRODUK --}}
                <div class="bp-card">
                    <div class="bp-card-header">
                        <div class="flex items-center gap-2">
                            <div class="bp-icon-circle text-blue-600 bg-blue-50 dark:bg-blue-950/40">
                                <i class="fa-solid fa-cart-plus"></i>
                            </div>
                            <div>
                                <span class="bp-card-title">Cari & Tambah Produk</span>
                                <span class="bp-card-hint">Pilih produk dari database untuk dimasukkan ke antrean cetak</span>
                            </div>
                        </div>

                        <button type="button" id="btnOpenBrowseModal" class="bp-btn-browse">
                            <i class="fa-solid fa-table-list"></i>
                            <span>Browse Daftar Produk</span>
                        </button>
                    </div>

                    <div class="bp-card-body space-y-3">
                        {{-- Baris 1: Pencarian Produk (Full Width) --}}
                        <div>
                            <label class="bp-label">Ketik Nama Produk, Kode Produk, atau Barcode:</label>
                            <div class="w-full">
                                <select id="productSearchSelect" class="w-full" style="width: 100%;">
                                    <option value="">Ketik untuk mencari produk...</option>
                                </select>
                            </div>
                        </div>

                        {{-- Baris 2: Qty Stiker & Tombol Tambah --}}
                        <div class="bp-add-action-row">
                            <div class="flex items-center gap-3">
                                <label class="bp-label mb-0" style="font-size: 13px; font-weight: 700;">Jumlah Stiker (Qty):</label>
                                <div class="bp-stepper bp-stepper-lg">
                                    <button type="button" onclick="stepQtyInput(-1)" title="Kurangi 1">-</button>
                                    <input type="number" id="productAddQty" value="1" min="1" max="9999" class="font-mono font-bold">
                                    <button type="button" onclick="stepQtyInput(1)" title="Tambah 1">+</button>
                                </div>
                            </div>

                            <button type="button" id="btnAddProduct" class="bp-btn-primary">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah ke Antrean</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- CARD: DAFTAR ANTREAN LABEL --}}
                <div class="bp-card">
                    <div class="bp-card-header">
                        <div class="flex items-center gap-2.5">
                            <div class="bp-icon-circle text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <span class="bp-card-title">Antrean Label Yang Akan Dicetak</span>
                                <span class="bp-card-hint" id="queueSummaryText">0 macam produk dipilih (Total 0 lembar label)</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="bp-bulk-qty-wrap">
                                <span class="label">Set Semua Qty:</span>
                                <input type="number" id="bulkQtyInput" value="5" min="1" max="999" class="bp-bulk-input font-mono font-bold">
                                <button type="button" id="btnApplyBulkQty" class="bp-btn-bulk">Terapkan</button>
                            </div>

                            <button type="button" id="btnClearQueue" class="bp-btn-clear" title="Kosongkan antrean">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Kosongkan</span>
                            </button>
                        </div>
                    </div>

                    {{-- Table Area --}}
                    <div class="bp-table-container">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">#</th>
                                    <th style="width: 165px;">Kode & Barcode</th>
                                    <th>Nama Produk</th>
                                    <th style="width: 125px; text-align: right;">Harga Jual</th>
                                    <th style="width: 175px; text-align: center;">Jumlah Label</th>
                                    <th style="width: 55px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="queueTableBody">
                                <tr id="emptyQueueRow">
                                    <td colspan="6" class="bp-empty-state">
                                        <div class="icon">
                                            <i class="fa-solid fa-tags"></i>
                                        </div>
                                        <div class="title">Antrean Masih Kosong</div>
                                        <div class="subtitle">Gunakan form pencarian di atas atau klik "Browse Daftar Produk" untuk memilih barang.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Bottom Action Footer --}}
                    <div class="bp-card-footer">
                        <div class="bp-footer-hint">
                            <i class="fa-solid fa-lightbulb text-amber-500"></i>
                            <span>Tips cetak: Pilih printer barcode Anda di dialog cetak, lalu atur opsi <strong>Margins = None (Tidak Ada)</strong>.</span>
                        </div>

                        <button type="submit" id="btnPrintSubmit" disabled class="bp-btn-print-cta">
                            <i class="fa-solid fa-print"></i>
                            <span>Cetak Barcode Sekarang</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>


{{-- ==================== MODAL BROWSE PRODUK ==================== --}}
<div id="browseProductModal" class="bp-modal-backdrop" style="display: none;">
    <div class="bp-modal-dialog">
        <div class="bp-modal-header">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                    <i class="fa-solid fa-table-list text-white"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Browse Daftar Produk</h3>
                    <p class="text-xs text-blue-100">Centang produk yang ingin dicetak labelnya</p>
                </div>
            </div>
            <button type="button" onclick="closeBrowseModal()" class="bp-modal-close-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="bp-modal-toolbar">
            <div class="relative w-full sm:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <input type="text" id="modalSearchInput" placeholder="Ketik nama, kode, atau barcode..." class="bp-input pl-8 text-xs">
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-[var(--app-text-muted)]" id="modalSelectedCount">0 dipilih</span>
                <button type="button" id="btnAddSelectedModal" disabled class="bp-btn-primary h-8 text-xs">
                    <i class="fa-solid fa-plus"></i> Tambahkan Yang Dipilih
                </button>
            </div>
        </div>

        <div class="bp-modal-table-wrap">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="modalSelectAll" class="bp-chk">
                        </th>
                        <th style="width: 130px;">Kode Produk</th>
                        <th style="width: 140px;">Barcode</th>
                        <th>Nama Produk</th>
                        <th style="width: 90px; text-align: right;">Stok</th>
                        <th style="width: 120px; text-align: right;">Harga Jual</th>
                    </tr>
                </thead>
                <tbody id="modalProductTableBody">
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400">
                            <i class="fa-solid fa-spinner fa-spin text-xl text-blue-500 mb-2"></i>
                            <div>Memuat data produk...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bp-modal-footer">
            <span class="text-xs text-[var(--app-text-muted)]">* Menampilkan hingga 100 produk aktif</span>
            <button type="button" onclick="closeBrowseModal()" class="bp-btn-secondary h-8 text-xs">Tutup</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* =========================================================
       PURE BULLETPROOF CSS UNTUK MENU BARCODE (BEBAS BUG TAILWIND)
       ========================================================= */

    .bp-container {
        width: 100%;
        max-width: 100%;
        padding: 0;
        box-sizing: border-box;
    }

    /* Header Card */
    .bp-header-card {
        background: var(--app-surface);
        border: 1px solid var(--app-border);
        border-radius: 10px;
        padding: 10px 16px;
        margin-bottom: 12px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .bp-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .bp-icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 2px 8px rgba(37, 99, 255, 0.22);
        flex-shrink: 0;
    }
    .bp-breadcrumb {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: var(--app-text-muted);
        margin-bottom: 2px;
        font-weight: 500;
    }
    .bp-breadcrumb a {
        color: var(--app-text-muted);
        text-decoration: none;
    }
    .bp-breadcrumb a:hover {
        color: #2563eb;
    }
    .bp-breadcrumb .active {
        color: #2563eb;
        font-weight: 700;
    }
    .bp-page-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--app-text);
        margin: 0;
        line-height: 1.2;
    }
    .bp-page-subtitle {
        font-size: 12px;
        color: var(--app-text-muted);
        margin: 2px 0 0 0;
    }

    /* Workspace 2-Pane Layout */
    .bp-workspace-grid {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 14px;
        width: 100%;
        box-sizing: border-box;
    }
    .bp-sidebar-pane {
        width: 440px;
        min-width: 400px;
        max-width: 470px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .bp-main-pane {
        flex: 1 1 0%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    @media (max-width: 1120px) {
        .bp-workspace-grid {
            flex-direction: column;
        }
        .bp-sidebar-pane {
            width: 100%;
            max-width: 100%;
        }
    }

    /* Card System */
    .bp-card {
        background: var(--app-surface);
        border: 1px solid var(--app-border);
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .bp-card-header {
        padding: 9px 14px;
        background: var(--app-surface-soft);
        border-bottom: 1px solid var(--app-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .bp-card-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--app-text);
    }
    .bp-card-hint {
        font-size: 11px;
        color: var(--app-text-muted);
        display: block;
        margin-top: 1px;
    }
    .bp-card-body {
        padding: 12px 14px;
    }
    .bp-step-badge {
        width: 20px;
        height: 20px;
        border-radius: 5px;
        background: #2563eb;
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 6px;
        flex-shrink: 0;
    }
    .bp-icon-circle {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    .bp-pill-tag {
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 14px;
        background: #dbeafe;
        color: #1e40af;
    }
    html[data-theme="dark"] .bp-pill-tag {
        background: rgba(37, 99, 235, 0.25);
        color: #93c5fd;
    }

    /* Preset Grid (3 Kolom x 2 Baris) */
    .bp-preset-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin-top: 4px;
    }
    .bp-preset-btn {
        background: var(--app-surface);
        border: 1px solid var(--app-border);
        border-radius: 6px;
        padding: 6px 8px;
        text-align: left;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .bp-preset-btn:hover {
        background: var(--app-surface-soft);
        border-color: #3b82f6;
    }
    .bp-preset-btn.active {
        background: #eff6ff !important;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 1px #2563eb;
    }
    html[data-theme="dark"] .bp-preset-btn.active {
        background: rgba(37, 99, 235, 0.2) !important;
        border-color: #3b82f6 !important;
    }
    .bp-preset-name {
        font-size: 11.5px;
        font-weight: 800;
        color: var(--app-text);
        line-height: 1.2;
    }
    .bp-preset-btn.active .bp-preset-name {
        color: #1d4ed8;
    }
    html[data-theme="dark"] .bp-preset-btn.active .bp-preset-name {
        color: #93c5fd;
    }
    .bp-preset-desc {
        font-size: 9.5px;
        color: var(--app-text-muted);
        margin-top: 1px;
        line-height: 1.1;
    }

    /* Recent Setting Section */
    .bp-recent-section {
        background: var(--app-surface-soft, #f8fafc);
        border: 1px dashed var(--app-border, #cbd5e1);
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 12px;
    }
    html[data-theme="dark"] .bp-recent-section {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.12);
    }
    .bp-recent-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .bp-recent-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--app-text);
        display: flex;
        align-items: center;
    }
    .bp-recent-clear-btn {
        font-size: 10px;
        color: var(--app-text-muted);
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0 4px;
        border-radius: 3px;
        transition: color 0.15s;
    }
    .bp-recent-clear-btn:hover {
        color: #ef4444;
    }
    .bp-recent-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .bp-recent-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--app-surface, #ffffff);
        border: 1px solid var(--app-border, #e2e8f0);
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 10.5px;
        font-weight: 600;
        color: var(--app-text);
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1.2;
    }
    .bp-recent-chip:hover {
        border-color: #3b82f6;
        background: var(--app-surface-soft);
        color: #2563eb;
    }
    .bp-recent-chip.active {
        border-color: #f59e0b !important;
        background: #fffbeb !important;
        color: #b45309 !important;
        box-shadow: 0 0 0 1px #f59e0b;
    }
    html[data-theme="dark"] .bp-recent-chip.active {
        background: rgba(245, 158, 11, 0.15) !important;
        color: #fde68a !important;
        border-color: #f59e0b !important;
    }
    .bp-recent-chip .chip-time {
        font-size: 9px;
        font-weight: 400;
        color: var(--app-text-muted);
    }

    /* Dimensions Inputs (4 Kolom Rapi 1 Baris) */
    .bp-dim-row-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-top: 8px;
    }
    .bp-dim-row {
        display: flex;
        gap: 10px;
        margin-top: 8px;
    }
    .bp-dim-col {
        flex: 1;
        min-width: 0;
    }
    .bp-input-unit {
        position: relative;
        display: flex;
        align-items: center;
    }
    .bp-input-unit .unit {
        position: absolute;
        right: 8px;
        font-size: 11px;
        color: var(--app-text-muted);
        pointer-events: none;
    }

    /* Form Controls */
    .bp-label {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--app-text);
        margin-bottom: 4px;
        display: block;
    }
    .bp-input {
        width: 100%;
        height: 33px;
        border: 1px solid var(--app-border);
        border-radius: 6px;
        padding: 0 10px;
        background: var(--app-surface);
        color: var(--app-text);
        font-size: 12.5px;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .bp-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    /* Checkbox Grid (2 Kolom) */
    .bp-check-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 12px;
    }
    .bp-check-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-height: 32px;
    }
    .bp-checkbox-label {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 600;
        color: var(--app-text);
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    .bp-chk {
        width: 15px;
        height: 15px;
        border-radius: 4px;
        border: 1px solid var(--app-border);
        accent-color: #2563eb;
        cursor: pointer;
    }
    .bp-inline-input {
        width: 105px;
        height: 28px;
        font-size: 11.5px;
        padding: 0 7px;
    }

    /* Buttons */
    .bp-btn-primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 0 18px;
        height: 36px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        transition: all 0.15s ease;
        box-shadow: 0 2px 5px rgba(37, 99, 235, 0.2);
        white-space: nowrap;
    }
    .bp-btn-primary:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
    }
    .bp-btn-primary:disabled {
        background: #9ca3af !important;
        cursor: not-allowed;
        box-shadow: none;
    }
    .bp-btn-secondary {
        background: var(--app-surface-soft);
        color: var(--app-text);
        border: 1px solid var(--app-border);
        border-radius: 6px;
        padding: 0 12px;
        height: 32px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .bp-btn-secondary:hover {
        background: var(--app-surface);
        border-color: #3b82f6;
    }
    .bp-btn-browse {
        background: #eef2ff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
        border-radius: 6px;
        padding: 5px 11px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    html[data-theme="dark"] .bp-btn-browse {
        background: rgba(79, 70, 229, 0.15);
        color: #a5b4fc;
        border-color: rgba(79, 70, 229, 0.4);
    }
    .bp-btn-browse:hover {
        background: #e0e7ff;
    }

    /* Stepper Qty (Table & General) */
    .bp-stepper {
        display: inline-flex;
        align-items: center;
        height: 34px;
        border: 1.5px solid var(--app-border);
        border-radius: 6px;
        overflow: hidden;
        background: var(--app-surface);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .bp-stepper button {
        width: 36px;
        height: 100%;
        background: var(--app-surface-soft);
        border: none;
        font-size: 16px;
        font-weight: bold;
        color: var(--app-text);
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bp-stepper button:hover {
        background: #2563eb;
        color: #ffffff;
    }
    .bp-stepper input {
        width: 65px;
        height: 100%;
        border: none;
        border-left: 1px solid var(--app-border);
        border-right: 1px solid var(--app-border);
        text-align: center;
        font-weight: 800;
        font-size: 15px;
        background: transparent;
        color: var(--app-text);
        outline: none;
    }

    /* Stepper Large (Form Tambah Qty) */
    .bp-stepper-lg {
        height: 38px;
        width: 230px;
    }
    .bp-stepper-lg button {
        width: 46px;
        font-size: 18px;
    }
    .bp-stepper-lg input {
        width: 138px;
        font-size: 16px;
        font-weight: 800;
    }

    .bp-add-action-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-top: 4px;
    }

    /* Live Preview Viewport */
    .bp-preview-viewport {
        background: #e2e8f0;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow-x: auto;
        min-height: 80px;
        max-height: 125px;
    }
    html[data-theme="dark"] .bp-preview-viewport {
        background: #090d16;
    }
    .bp-roll-strip {
        padding: 6px;
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 6px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.06);
        transition: transform 0.2s ease;
        transform-origin: center center;
    }
    html[data-theme="dark"] .bp-roll-strip {
        background: #1e293b;
        border-color: #334155;
    }
    .bp-labels-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
    }
    .bp-preview-footer {
        padding: 6px 12px;
        background: var(--app-surface-soft);
        border-top: 1px solid var(--app-border);
        font-size: 11px;
        color: var(--app-text-muted);
        text-align: center;
    }
    .bp-zoom-controls {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .bp-zoom-btn {
        padding: 2px 7px;
        font-size: 10px;
        font-weight: 700;
        border: 1px solid var(--app-border);
        background: var(--app-surface);
        color: var(--app-text);
        border-radius: 4px;
        cursor: pointer;
    }
    .bp-zoom-btn.active {
        background: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
    }

    /* Table Styles */
    .bp-table-container {
        overflow-x: auto;
        max-height: 290px;
        overflow-y: auto;
    }
    .bp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .bp-table th {
        background: var(--app-surface-soft);
        color: var(--app-text-muted);
        padding: 8px 12px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        border-bottom: 1px solid var(--app-border);
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .bp-table td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--app-border);
        color: var(--app-text);
        vertical-align: middle;
    }
    .bp-table tr:hover {
        background: rgba(37, 99, 235, 0.04);
    }
    .bp-empty-state {
        text-align: center;
        padding: 28px 16px !important;
        color: var(--app-text-muted);
    }
    .bp-empty-state .icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--app-surface-soft);
        color: var(--app-text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin: 0 auto 8px auto;
    }
    .bp-empty-state .title {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--app-text);
    }
    .bp-empty-state .subtitle {
        font-size: 11.5px;
        margin-top: 2px;
    }

    /* Bulk actions */
    .bp-bulk-qty-wrap {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        color: var(--app-text-muted);
    }
    .bp-bulk-input {
        width: 48px;
        height: 27px;
        text-align: center;
        border: 1px solid var(--app-border);
        border-radius: 5px;
        background: var(--app-surface);
        color: var(--app-text);
        font-size: 12px;
    }
    .bp-btn-bulk {
        height: 27px;
        padding: 0 9px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 5px;
        border: 1px solid var(--app-border);
        background: var(--app-surface);
        color: var(--app-text);
        cursor: pointer;
    }
    .bp-btn-bulk:hover {
        background: var(--app-surface-soft);
    }
    .bp-btn-clear {
        color: #ef4444;
        background: none;
        border: none;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .bp-btn-clear:hover {
        color: #dc2626;
    }

    /* Card Footer & Big Print CTA */
    .bp-card-footer {
        padding: 10px 14px;
        background: var(--app-surface-soft);
        border-top: 1px solid var(--app-border);
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .bp-footer-hint {
        font-size: 11px;
        color: var(--app-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .bp-btn-print-cta {
        background: linear-gradient(135deg, #2563eb, #4338ca);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 10px 22px;
        font-size: 13.5px;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.3);
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .bp-btn-print-cta:hover {
        background: linear-gradient(135deg, #1d4ed8, #3730a3);
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.4);
    }
    .bp-btn-print-cta:disabled {
        background: #9ca3af !important;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    /* Modal Backdrop & Dialog */
    .bp-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .bp-modal-dialog {
        background: var(--app-surface);
        border: 1px solid var(--app-border);
        border-radius: 12px;
        width: 100%;
        max-width: 840px;
        max-height: 86vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.25);
    }
    .bp-modal-header {
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #ffffff;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .bp-modal-close-btn {
        background: none;
        border: none;
        color: #ffffff;
        font-size: 18px;
        cursor: pointer;
        opacity: 0.8;
    }
    .bp-modal-close-btn:hover {
        opacity: 1;
    }
    .bp-modal-toolbar {
        padding: 10px 16px;
        background: var(--app-surface-soft);
        border-bottom: 1px solid var(--app-border);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .bp-modal-table-wrap {
        overflow-y: auto;
        max-height: 50vh;
    }
    .bp-modal-footer {
        padding: 10px 16px;
        background: var(--app-surface-soft);
        border-top: 1px solid var(--app-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Select2 Fix */
    .select2,
    .select2-container,
    .select2-container--default {
        width: 100% !important;
        display: block !important;
    }
    .select2-container--default .select2-selection--single {
        border: 1px solid var(--app-border) !important;
        border-radius: 6px !important;
        height: 36px !important;
        padding: 0 10px !important;
        width: 100% !important;
        background-color: var(--app-surface) !important;
        color: var(--app-text) !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--app-text) !important;
        line-height: normal !important;
        font-size: 13px !important;
        padding-left: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px !important;
        top: 1px !important;
        right: 8px !important;
    }
    .select2-dropdown {
        border: 1px solid var(--app-border) !important;
        border-radius: 6px !important;
        background-color: var(--app-surface) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
        z-index: 99999 !important;
    }
    .select2-results__option {
        padding: 7px 11px !important;
        font-size: 12.5px !important;
        color: var(--app-text) !important;
    }
    .select2-results__option--highlighted {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
let queueItems = [];
let selectedSearchData = null;
let currentZoom = 1;
let modalProductList = [];
let recentSettingsList = @json($recentSettings ?? []);

// Preset definitions
const presets = {
    '33x15_3col': { width: 33, height: 15, cols: 3, gapX: 2, barcodeH: 20, fontS: 7 },
    '40x30_1col': { width: 40, height: 30, cols: 1, gapX: 0, barcodeH: 28, fontS: 8 },
    '50x20_1col': { width: 50, height: 20, cols: 1, gapX: 0, barcodeH: 22, fontS: 8 },
    '50x30_1col': { width: 50, height: 30, cols: 1, gapX: 0, barcodeH: 30, fontS: 8.5 },
    '70x50_1col': { width: 70, height: 50, cols: 1, gapX: 0, barcodeH: 42, fontS: 10 },
    'custom':     { width: 33, height: 15, cols: 3, gapX: 2, barcodeH: 20, fontS: 7 }
};

$(document).ready(function() {
    // Check preloaded product
    const preloaded = @json($preloaded ?? null);
    if (preloaded) {
        queueItems.push(preloaded);
        renderQueue();
    }

    // Preset buttons click
    $('.bp-preset-btn').on('click', function() {
        $('.bp-preset-btn').removeClass('active');
        $(this).addClass('active');
        $('.bp-recent-chip').removeClass('active');

        const presetKey = $(this).data('preset');
        if (presets[presetKey] && presetKey !== 'custom') {
            const p = presets[presetKey];
            $('#labelWidth').val(p.width);
            $('#labelHeight').val(p.height);
            $('#labelColumns').val(p.cols);
            $('#gapX').val(p.gapX);
            $('#barcodeHeight').val(p.barcodeH);
            $('#fontSize').val(p.fontS);
            updatePreview();
        }
    });

    // Inputs change listeners for live preview & auto-save recent setting
    let recentSaveTimer = null;
    $('#labelWidth, #labelHeight, #labelColumns, #gapX, #barcodeHeight, #fontSize, #showCompany, #companyName, #showName, #showCode, #showPrice').on('input change', function() {
        updatePreview();
        $('.bp-recent-chip').removeClass('active');
        clearTimeout(recentSaveTimer);
        recentSaveTimer = setTimeout(saveCurrentAsRecent, 800);
    });

    // Auto-save on print submit
    $('#barcodeForm').on('submit', function() {
        saveCurrentAsRecent();
    });

    // Clear recent settings
    $('#btnClearRecent').on('click', function() {
        Swal.fire({
            title: 'Hapus Recent Setting?',
            text: 'Hapus semua riwayat recent setting?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('barcode.recent-settings.clear') }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function() {
                        recentSettingsList = [];
                        renderRecentSettings();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Semua riwayat recent setting telah dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan saat menghapus riwayat setting.'
                        });
                    }
                });
            }
        });
    });

    // Load recent settings on ready
    renderRecentSettings();

    // Select2 Product Search
    $('#productSearchSelect').select2({
        width: '100%',
        ajax: {
            url: '{{ route('barcode.search-products') }}',
            dataType: 'json',
            delay: 200,
            data: function (params) {
                return { q: params.term || '', limit: 30 };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.fprdcode + ' - ' + item.fprdname + (item.fbarcode ? ' [' + item.fbarcode + ']' : ''),
                            product: item
                        };
                    })
                };
            },
            cache: true
        },
        placeholder: 'Ketik nama, kode, atau barcode...',
        minimumInputLength: 1
    }).on('select2:select', function(e) {
        selectedSearchData = e.params.data.product;
        $('#productAddQty').focus();
    });

    // Add Product to Queue
    $('#btnAddProduct').on('click', function() {
        if (!selectedSearchData) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Produk',
                text: 'Silakan pilih produk terlebih dahulu.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        const qty = parseInt($('#productAddQty').val(), 10) || 1;
        addItemToQueue(selectedSearchData, qty);

        // Reset
        $('#productSearchSelect').val(null).trigger('change');
        $('#productAddQty').val(1);
        selectedSearchData = null;
    });

    // Clear Queue
    $('#btnClearQueue').on('click', function() {
        if (queueItems.length === 0) return;
        queueItems = [];
        renderQueue();
    });

    // Bulk Qty Apply
    $('#btnApplyBulkQty').on('click', function() {
        const bulkVal = parseInt($('#bulkQtyInput').val(), 10) || 1;
        queueItems.forEach(it => it.qty = bulkVal);
        renderQueue();
    });

    // Modal Browse Product
    $('#btnOpenBrowseModal').on('click', function() {
        openBrowseModal();
    });

    // Modal Search filter
    $('#modalSearchInput').on('input', function() {
        const term = $(this).val().toLowerCase().trim();
        filterModalProducts(term);
    });

    // Modal Select All
    $('#modalSelectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.modal-product-chk').prop('checked', isChecked);
        updateModalSelectedCount();
    });

    $(document).on('change', '.modal-product-chk', function() {
        updateModalSelectedCount();
    });

    // Modal Add Selected
    $('#btnAddSelectedModal').on('click', function() {
        const checkedBoxes = $('.modal-product-chk:checked');
        checkedBoxes.each(function() {
            const prodId = $(this).val();
            const prod = modalProductList.find(p => String(p.id) === String(prodId));
            if (prod) {
                addItemToQueue(prod, 1);
            }
        });
        closeBrowseModal();
    });

    // Initial Preview
    updatePreview();
});

// Add Item Helper
function addItemToQueue(product, qty) {
    const existingIndex = queueItems.findIndex(it => it.fprdcode === product.fprdcode);
    if (existingIndex >= 0) {
        queueItems[existingIndex].qty += qty;
    } else {
        queueItems.push({
            fprdid: product.id,
            fprdcode: product.fprdcode,
            fprdname: product.fprdname,
            fbarcode: product.fbarcode || product.fprdcode,
            price: product.price || 0,
            satuan: product.satuan || '',
            qty: qty
        });
    }
    renderQueue();
}

// Stepper for Qty Input
function stepQtyInput(delta) {
    const el = $('#productAddQty');
    let val = (parseInt(el.val(), 10) || 1) + delta;
    if (val < 1) val = 1;
    el.val(val);
}

// Table Stepper for Rows
function stepRowQty(idx, delta) {
    if (queueItems[idx]) {
        queueItems[idx].qty = Math.max(1, queueItems[idx].qty + delta);
        renderQueue();
    }
}

// Remove Item
function removeRow(idx) {
    queueItems.splice(idx, 1);
    renderQueue();
}

// Render Queue Table
function renderQueue() {
    const tbody = $('#queueTableBody');
    tbody.empty();

    if (queueItems.length === 0) {
        tbody.append(`
            <tr id="emptyQueueRow">
                <td colspan="6" class="bp-empty-state">
                    <div class="icon">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div class="title">Antrean Masih Kosong</div>
                    <div class="subtitle">Gunakan form pencarian di atas atau klik "Browse Daftar Produk" untuk memilih barang.</div>
                </td>
            </tr>
        `);
        $('#btnPrintSubmit').prop('disabled', true);
        $('#queueSummaryText').text('0 macam produk dipilih (Total 0 lembar label)');
        $('#itemsInput').val('[]');
        updatePreview();
        return;
    }

    let totalLabels = 0;

    queueItems.forEach(function(item, idx) {
        totalLabels += item.qty;
        const formattedPrice = 'Rp ' + Number(item.price).toLocaleString('id-ID');

        tbody.append(`
            <tr>
                <td style="text-align: center; color: var(--app-text-muted); font-size: 11px;">${idx + 1}</td>
                <td>
                    <div style="font-family: monospace; font-size: 12px; font-weight: 800; color: var(--app-text); line-height: 1.3;">
                        <i class="fa-solid fa-barcode text-blue-500 mr-1"></i>${item.fbarcode || item.fprdcode}
                    </div>
                    <div style="font-family: monospace; font-size: 11px; color: var(--app-text-muted);">${item.fprdcode}</div>
                </td>
                <td>
                    <div style="font-weight: 700; font-size: 12.5px; color: var(--app-text); line-height: 1.3;">${item.fprdname}</div>
                    ${item.satuan ? `<span style="font-size: 10px; color: var(--app-text-muted);">Satuan: ${item.satuan}</span>` : ''}
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 800; font-size: 12.5px; color: var(--app-text);">
                    ${formattedPrice}
                </td>
                <td style="text-align: center;">
                    <div class="bp-stepper" style="height: 34px;">
                        <button type="button" onclick="stepRowQty(${idx}, -1)" style="width: 36px; font-size: 16px;">-</button>
                        <input type="number" min="1" max="9999" value="${item.qty}" data-index="${idx}" class="row-qty-input font-mono font-bold" style="width: 65px; font-size: 15px;">
                        <button type="button" onclick="stepRowQty(${idx}, 1)" style="width: 36px; font-size: 16px;">+</button>
                    </div>
                </td>
                <td style="text-align: center;">
                    <button type="button" onclick="removeRow(${idx})" class="bp-btn-clear" style="font-size: 13px;" title="Hapus">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            </tr>
        `);
    });

    $('#btnPrintSubmit').prop('disabled', false);
    $('#queueSummaryText').text(`${queueItems.length} macam produk dipilih (Total ${totalLabels} lembar label)`);
    $('#itemsInput').val(JSON.stringify(queueItems));

    updatePreview();
}

// Row Qty Change Handler
$(document).on('change input', '.row-qty-input', function() {
    const idx = $(this).data('index');
    const val = Math.max(1, parseInt($(this).val(), 10) || 1);
    if (queueItems[idx]) {
        queueItems[idx].qty = val;
        let totalLabels = queueItems.reduce((acc, it) => acc + it.qty, 0);
        $('#queueSummaryText').text(`${queueItems.length} macam produk dipilih (Total ${totalLabels} lembar label)`);
        $('#itemsInput').val(JSON.stringify(queueItems));
    }
});

// Zoom controller
function setZoom(scale) {
    currentZoom = scale;
    $('.bp-zoom-btn').removeClass('active');
    $(`.bp-zoom-btn:contains('${scale}x')`).addClass('active');
    $('#previewRollStrip').css('transform', `scale(${scale})`);
}

// Live Multi-Column Preview
function updatePreview() {
    const container = $('#previewLabelsContainer');
    container.empty();

    const sampleItem = queueItems.length > 0 ? queueItems[0] : {
        fprdname: 'CONTOH NAMA PRODUK',
        fbarcode: '8991234567890',
        fprdcode: 'PRD-001',
        price: 15000
    };

    const widthMm = parseFloat($('#labelWidth').val()) || 33;
    const heightMm = parseFloat($('#labelHeight').val()) || 15;
    const columns = Math.max(1, Math.min(5, parseInt($('#labelColumns').val(), 10) || 3));
    const gapX = parseFloat($('#gapX').val()) || 2;
    const showCompany = $('#showCompany').is(':checked');
    const companyName = $('#companyName').val() || '';
    const showName = $('#showName').is(':checked');
    const showCode = $('#showCode').is(':checked');
    const showPrice = $('#showPrice').is(':checked');
    const barcodeH = parseInt($('#barcodeHeight').val(), 10) || 20;
    const fontS = parseFloat($('#fontSize').val()) || 7;

    // Scale mm to screen pixel representation (1mm ≈ 3.6px)
    const scalePx = 3.6;
    const cardWidthPx = Math.round(widthMm * scalePx);
    const cardHeightPx = Math.round(heightMm * scalePx);
    const gapXPx = Math.round(gapX * scalePx);

    container.css('gap', `${gapXPx}px`);

    for (let c = 0; c < columns; c++) {
        const svgId = `prevBarcodeSvg_${c}`;
        const labelHtml = `
            <div style="width: ${cardWidthPx}px; height: ${cardHeightPx}px; box-sizing: border-box; background: #ffffff; border: 1px solid #94a3b8; border-radius: 2px; padding: 3px 4px; display: flex; flex-direction: column; justify-content: space-between; align-items: center; text-align: center; overflow: hidden; flex-shrink: 0; font-family: Arial, sans-serif;">
                ${showCompany && companyName ? `<div style="font-weight: 800; text-transform: uppercase; font-size: ${fontS * 0.9}pt; color: #1e293b; line-height: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;">${companyName}</div>` : ''}
                ${showName ? `<div style="font-weight: 800; font-size: ${fontS * 0.85}pt; color: #020617; line-height: 1.1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; width: 100%; margin-top: 1px;">${sampleItem.fprdname}</div>` : ''}
                ${showCode ? `<div style="width: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden; margin: 1px 0;"><svg id="${svgId}" style="max-width: 100%;"></svg></div>` : ''}
                ${showPrice && sampleItem.price > 0 ? `<div style="font-weight: 900; font-size: ${fontS * 0.95}pt; color: #020617; line-height: 1; width: 100%;">Rp ${Number(sampleItem.price).toLocaleString('id-ID')}</div>` : ''}
            </div>
        `;
        container.append(labelHtml);

        if (showCode) {
            try {
                JsBarcode(`#${svgId}`, sampleItem.fbarcode || sampleItem.fprdcode, {
                    format: "CODE128",
                    width: 1.1,
                    height: barcodeH,
                    displayValue: true,
                    fontSize: Math.max(8, fontS * 1.1),
                    margin: 0,
                    textMargin: 1
                });
            } catch (e) {
                JsBarcode(`#${svgId}`, "12345678", {
                    format: "CODE128",
                    width: 1.1,
                    height: barcodeH,
                    displayValue: true,
                    fontSize: 8,
                    margin: 0
                });
            }
        }
    }
}

// Recent Settings Helpers
function getCurrentSettings() {
    return {
        labelWidth: parseFloat($('#labelWidth').val()) || 33,
        labelHeight: parseFloat($('#labelHeight').val()) || 15,
        columns: Math.max(1, parseInt($('#labelColumns').val(), 10) || 1),
        gapX: parseFloat($('#gapX').val()) || 0,
        barcodeHeight: parseInt($('#barcodeHeight').val(), 10) || 20,
        fontSize: parseFloat($('#fontSize').val()) || 7,
        showCompany: $('#showCompany').is(':checked'),
        companyName: $('#companyName').val() || '',
        showName: $('#showName').is(':checked'),
        showCode: $('#showCode').is(':checked'),
        showPrice: $('#showPrice').is(':checked'),
        preset: $('.bp-preset-btn.active').data('preset') || 'custom',
        timestamp: Date.now()
    };
}

let isSavingRecent = false;
function saveCurrentAsRecent() {
    if (isSavingRecent) return;
    const cur = getCurrentSettings();

    // Check if duplicate of first item to avoid needless DB writes
    if (recentSettingsList && recentSettingsList.length > 0) {
        const top = recentSettingsList[0];
        if (
            top.labelWidth === cur.labelWidth &&
            top.labelHeight === cur.labelHeight &&
            top.columns === cur.columns &&
            top.gapX === cur.gapX &&
            top.barcodeHeight === cur.barcodeHeight &&
            top.fontSize === cur.fontSize &&
            Boolean(top.showCompany) === Boolean(cur.showCompany) &&
            (top.companyName || '') === (cur.companyName || '') &&
            Boolean(top.showName) === Boolean(cur.showName) &&
            Boolean(top.showCode) === Boolean(cur.showCode) &&
            Boolean(top.showPrice) === Boolean(cur.showPrice)
        ) {
            return;
        }
    }

    isSavingRecent = true;

    $.ajax({
        url: '{{ route('barcode.recent-settings.save') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            label_width: cur.labelWidth,
            label_height: cur.labelHeight,
            columns: cur.columns,
            gap_x: cur.gapX,
            barcode_height: cur.barcodeHeight,
            font_size: cur.fontSize,
            show_company: cur.showCompany ? 1 : 0,
            company_name: cur.companyName,
            show_name: cur.showName ? 1 : 0,
            show_code: cur.showCode ? 1 : 0,
            show_price: cur.showPrice ? 1 : 0,
            preset: cur.preset
        },
        success: function(res) {
            if (res && res.recents) {
                recentSettingsList = res.recents;
                renderRecentSettings();
            }
        },
        complete: function() {
            isSavingRecent = false;
        }
    });
}

function applySettings(s, chipEl) {
    $('#labelWidth').val(s.labelWidth);
    $('#labelHeight').val(s.labelHeight);
    $('#labelColumns').val(s.columns);
    $('#gapX').val(s.gapX);
    $('#barcodeHeight').val(s.barcodeHeight);
    $('#fontSize').val(s.fontSize);
    $('#showCompany').prop('checked', !!s.showCompany);
    $('#companyName').val(s.companyName || '');
    $('#showName').prop('checked', !!s.showName);
    $('#showCode').prop('checked', !!s.showCode);
    $('#showPrice').prop('checked', !!s.showPrice);

    let matchedPreset = s.preset || 'custom';
    if (!presets[matchedPreset] || matchedPreset === 'custom') {
        matchedPreset = 'custom';
        for (const [key, p] of Object.entries(presets)) {
            if (key !== 'custom' && p.width == s.labelWidth && p.height == s.labelHeight && p.cols == s.columns) {
                matchedPreset = key;
                break;
            }
        }
    }
    $('.bp-preset-btn').removeClass('active');
    $(`.bp-preset-btn[data-preset="${matchedPreset}"]`).addClass('active');

    $('.bp-recent-chip').removeClass('active');
    if (chipEl) $(chipEl).addClass('active');

    updatePreview();
}

function renderRecentSettings() {
    const $container = $('#recentChipsList');
    const $section = $('#recentSettingSection');

    if (!recentSettingsList || recentSettingsList.length === 0) {
        $section.hide();
        return;
    }

    $container.empty();
    recentSettingsList.forEach((item, idx) => {
        const d = item.timestamp ? new Date(item.timestamp) : new Date();
        const timeStr = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        const tooltip = `${item.labelWidth}×${item.labelHeight} mm, ${item.columns} Kolom, Font ${item.fontSize}pt`;
        const $chip = $(`
            <button type="button" class="bp-recent-chip" data-index="${idx}" title="${tooltip}">
                <i class="fa-solid fa-tag text-[10px] text-amber-500"></i>
                <span>${item.labelWidth}×${item.labelHeight}mm (${item.columns}K)</span>
                <span class="chip-time">${timeStr}</span>
            </button>
        `);
        $chip.on('click', function() {
            applySettings(item, this);
        });
        $container.append($chip);
    });

    $section.show();
}

// Modal functions
function openBrowseModal() {
    $('#browseProductModal').css('display', 'flex');
    loadModalProducts('');
}

function closeBrowseModal() {
    $('#browseProductModal').css('display', 'none');
    $('#modalSelectAll').prop('checked', false);
    $('.modal-product-chk').prop('checked', false);
    updateModalSelectedCount();
}

function loadModalProducts(search) {
    const tbody = $('#modalProductTableBody');
    tbody.html(`
        <tr>
            <td colspan="6" class="p-8 text-center text-gray-400">
                <i class="fa-solid fa-spinner fa-spin text-xl text-blue-500 mb-2"></i>
                <div>Memuat data produk...</div>
            </td>
        </tr>
    `);

    $.ajax({
        url: '{{ route('barcode.search-products') }}',
        data: { q: search, limit: 100 },
        success: function(data) {
            modalProductList = data;
            renderModalProducts(data);
        },
        error: function() {
            tbody.html(`
                <tr>
                    <td colspan="6" class="p-8 text-center text-red-500">Gagal memuat data produk.</td>
                </tr>
            `);
        }
    });
}

function filterModalProducts(term) {
    if (!term) {
        renderModalProducts(modalProductList);
        return;
    }
    const filtered = modalProductList.filter(p =>
        (p.fprdcode || '').toLowerCase().includes(term) ||
        (p.fprdname || '').toLowerCase().includes(term) ||
        (p.fbarcode || '').toLowerCase().includes(term)
    );
    renderModalProducts(filtered);
}

function renderModalProducts(items) {
    const tbody = $('#modalProductTableBody');
    tbody.empty();

    if (items.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="p-6 text-center text-gray-400">Produk tidak ditemukan.</td>
            </tr>
        `);
        return;
    }

    items.forEach(function(item) {
        const formattedPrice = 'Rp ' + Number(item.price).toLocaleString('id-ID');
        tbody.append(`
            <tr style="cursor: pointer;" onclick="toggleModalRowCheckbox(this, event)">
                <td style="text-align: center;" onclick="event.stopPropagation()">
                    <input type="checkbox" value="${item.id}" class="modal-product-chk bp-chk">
                </td>
                <td style="font-family: monospace; font-weight: bold; color: var(--app-text);">${item.fprdcode}</td>
                <td style="font-family: monospace; color: var(--app-text-muted);">${item.fbarcode || '-'}</td>
                <td style="font-weight: 600; color: var(--app-text);">${item.fprdname}</td>
                <td style="text-align: right; font-family: monospace; color: var(--app-text);">${Number(item.stock || 0).toLocaleString('id-ID')}</td>
                <td style="text-align: right; font-family: monospace; font-weight: bold; color: var(--app-text);">${formattedPrice}</td>
            </tr>
        `);
    });
}

function toggleModalRowCheckbox(row, e) {
    if ($(e.target).is('input[type="checkbox"]')) return;
    const chk = $(row).find('.modal-product-chk');
    chk.prop('checked', !chk.is(':checked'));
    updateModalSelectedCount();
}

function updateModalSelectedCount() {
    const count = $('.modal-product-chk:checked').length;
    $('#modalSelectedCount').text(`${count} dipilih`);
    $('#btnAddSelectedModal').prop('disabled', count === 0);
}
</script>
@endpush
