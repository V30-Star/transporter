@php
    $section = $section ?? '';
    $includeInitItems = $includeInitItems ?? false;
    $includeForEdit = $includeForEdit ?? false;
@endphp

@if ($section === 'form_styles')
    @include('components.transaction.form-base-styles')
    <style>
        #supplierSelect,
        #supplierSelect:disabled {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
        }

        #supplierSelect::-ms-expand {
            display: none
        }

        .item-row-active {
            background-color: #f0fdf4;
        }

        .desc-inline-field {
            display: flex !important;
            width: 100%;
            min-width: 0;
            align-items: stretch;
            flex-wrap: nowrap !important;
        }

        .desc-inline-field__text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .desc-inline-field__button {
            flex: 0 0 auto;
            width: 2.5rem;
            justify-content: center;
        }
    </style>
@elseif ($section === 'datatables_length_styles')
    <style>
        div#productTable_length select,
        .dataTables_wrapper #productTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#productTable_length,
        .dataTables_wrapper #productTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#productTable_length label,
        .dataTables_wrapper #productTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        div#supplierTable_length select,
        .dataTables_wrapper #supplierTable_length select,
        table#supplierBrowseTable+.dataTables_wrapper .dataTables_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#supplierTable_length,
        .dataTables_wrapper #supplierTable_length,
        .dataTables_wrapper .dataTables_length {
            min-width: 250px !important;
        }

        div#supplierTable_length label,
        .dataTables_wrapper #supplierTable_length label,
        .dataTables_wrapper .dataTables_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
    </style>
@elseif ($section === 'browser_globals')
window.PRODUCT_MAP = @json($productMap ?? []);

@if (!empty($includeInitItems))
window.INIT_ITEMS = @json($savedItems ?? []);
@endif

window.cryptoRandom = window.cryptoRandom || function() {
    try {
        if (window.crypto?.getRandomValues) {
            const arr = new Uint32Array(1);
            window.crypto.getRandomValues(arr);
            return 'r' + arr[0].toString(16);
        }
    } catch (e) {}

    return 'r' + (Date.now().toString(16) + Math.random().toString(16).slice(2));
};
@elseif ($section === 'draft_unit_dom_helpers')
function getDraftUnitSelect() {
    return document.getElementById('draftUnitSelect');
}

function populateDraftUnitSelect(units) {
    const sel = getDraftUnitSelect();
    if (!sel) return;
    sel.innerHTML = '';
    units.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        sel.appendChild(opt);
    });
}

function getDraftUnitValue() {
    const sel = getDraftUnitSelect();
    return sel ? sel.value : '';
}

function clearDraftUnitSelect() {
    const sel = getDraftUnitSelect();
    if (sel) sel.innerHTML = '';
}
@elseif ($section === 'supplier_browser_standard')
function supplierBrowser() {
    return {
        open: false,
        dataTable: null,

        initDataTable() {
            if (this.dataTable) this.dataTable.destroy();
            this.dataTable = $('#supplierBrowseTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('suppliers.browse') }}",
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
                        data: 'fsuppliercode',
                        name: 'fsuppliercode',
                        className: 'font-mono text-sm',
                        width: '15%'
                    },
                    {
                        data: 'fsuppliername',
                        name: 'fsuppliername',
                        className: 'text-sm',
                        width: '25%'
                    },
                    {
                        data: 'faddress',
                        name: 'faddress',
                        className: 'text-sm',
                        defaultContent: '-',
                        orderable: false,
                        width: '30%'
                    },
                    {
                        data: 'ftelp',
                        name: 'ftelp',
                        className: 'text-sm',
                        defaultContent: '-',
                        orderable: false,
                        width: '15%'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        width: '15%',
                        render: () =>
                            '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
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
            $('#supplierBrowseTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                const data = this.dataTable.row($(e.target).closest('tr')).data();
                if (!data) return;
                this.chooseSupplier(data);
            });
        },

        openBrowse() {
            this.open = true;
            this.$nextTick(() => this.initDataTable());
        },
        close() {
            this.open = false;
            if (this.dataTable) this.dataTable.search('').draw();
        },

        chooseSupplier(supplier) {
            const sel = document.getElementById('modal_filter_supplier_id');
            const hid = document.getElementById('supplierCodeHidden');
            if (!sel) {
                this.close();
                return;
            }
            let opt = [...sel.options].find(o => o.value == String(supplier.fsuppliercode));
            const label = `${supplier.fsuppliername} (${supplier.fsuppliercode})`;
            if (!opt) {
                opt = new Option(label, supplier.fsuppliercode, true, true);
                sel.add(opt);
            } else {
                opt.text = label;
                opt.selected = true;
            }
            sel.dispatchEvent(new Event('change'));
            if (hid) hid.value = supplier.fsuppliercode;
            this.close();
        },

        init() {
            window.addEventListener('supplier-browse-open', () => this.openBrowse(), {
                passive: true
            });
        }
    }
}
@elseif ($section === 'items_noacak_methods')
normalizeNoAcak(value) {
    return (value || '').toString().replace(/\D/g, '').slice(0, 3);
},

generateUniqueNoAcak() {
    const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
    let candidate = '';

    do {
        candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
    } while (used.has(candidate));

    return candidate;
},
@elseif ($section === 'items_product_meta_rich_methods')
productMeta(code) {
    const key = (code || '').trim();
    const meta = window.PRODUCT_MAP?.[key];
    if (!meta) {
        return {
            name: '',
            units: [],
            stock: 0,
            unit_ratios: {
                satuankecil: 1,
                satuanbesar: 1,
                satuanbesar2: 1
            }
        };
    }
    return meta;
},

formatStockLimit(code, qty, satuan) {
    const meta = this.productMeta(code);
    if (!code || !meta.stock) return '';

    const entered = Number(qty) || 0;
    const remaining = Math.max(0, meta.stock - entered);
    const units = meta.units || [];
    const ratios = meta.unit_ratios || {
        satuankecil: 1,
        satuanbesar: 1,
        satuanbesar2: 1
    };

    if (!units.length || !satuan) return '';

    const satKecil = units[0] || 'pcs';
    const satBesar = units[1] || '';
    const satBesar2 = units[2] || '';

    let ratio = 1;
    if (satuan === satBesar2 && ratios.satuanbesar2 > 0) {
        ratio = ratios.satuanbesar2;
    } else if (satuan === satBesar && ratios.satuanbesar > 0) {
        ratio = ratios.satuanbesar;
    } else if (satuan === satKecil) {
        ratio = 1;
    }

    const limitValue = Math.floor(remaining / ratio);
    return '<span class="font-medium">limit:</span> ' + limitValue + ' ' + satuan;
},
@elseif ($section === 'items_desc_basic_methods')
hasDesc(value) {
    return String(value ?? '').trim() !== '';
},

descButtonClass(value) {
    return this.hasDesc(value)
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
        : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100';
},

getDescRow(target = 'draft', index = null) {
    if (target === 'saved' && index !== null) {
        return this.savedItems[index] || null;
    }
    return this.draft || null;
},

openDesc(target = 'draft', index = null) {
    const row = this.getDescRow(target, index);
    const itemCode = (row?.fitemcode || '').toString().trim();
    if (!itemCode) return;
    this.descTarget = target;
    this.descSavedIndex = index;
    this.descItemCode = itemCode;
    this.descItemName = (row?.fitemname || '').toString().trim();
    this.descValue = (row?.fdesc || '').toString();
    this.showDescModal = true;
},

closeDesc() {
    this.showDescModal = false;
    this.descTarget = 'draft';
    this.descSavedIndex = null;
    this.descValue = '';
    this.descItemCode = '';
    this.descItemName = '';
    this.descCopied = false;
},

applyDesc() {
    const val = (this.descValue || '').trim();
    if (this.descTarget === 'saved' && this.descSavedIndex !== null) {
        this.savedItems[this.descSavedIndex].fdesc = val;
    } else {
        this.draft.fdesc = val;
    }
    this.closeDesc();
},
@elseif ($section === 'items_enforce_qty_method')
enforceQtyRow(row) {
    return;
},
@elseif ($section === 'items_format_qty_method')
formatQtyValue(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) return '0,00';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
},
@elseif ($section === 'items_sanitize_number_method')
sanitizeNumber(v, d = 0) {
    const n = +v;
    return Number.isFinite(n) ? n : d;
},
@elseif ($section === 'items_desc_preview_methods')
labelOf(row) {
    return [row.fitemcode, row.fitemname].filter(Boolean).join(' - ');
},

syncDescList() {
    Alpine.store('prh').descList = this.savedItems
        .map((it, i) => ({
            uid: it.uid,
            index: i + 1,
            label: this.labelOf(it),
            text: it.fdesc || ''
        }))
        .filter(x => x.text);
},
@elseif ($section === 'product_browser_standard')
function productBrowser() {
    return {
        open: false,
        @if(!empty($includeForEdit))
        forEdit: false,
        @endif
        table: null,

        initDataTable() {
            if (this.table) this.table.destroy();
            this.table = $('#productTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('products.browse') }}",
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
                        data: 'fprdcode',
                        name: 'fprdcode',
                        className: 'font-mono text-sm'
                    },
                    {
                        data: 'fprdname',
                        name: 'fprdname',
                        className: 'text-sm'
                    },
                    {
                        data: 'fsatuanbesar',
                        name: 'fsatuanbesar',
                        className: 'text-sm',
                        render: d => d || '-'
                    },
                    {
                        data: 'fmerekname',
                        name: 'fmerekname',
                        className: 'text-center text-sm',
                        render: d => d || '-'
                    },
                    {
                        data: 'fminstock',
                        name: 'fminstock',
                        className: 'text-center text-sm'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        width: '100px',
                        render: () =>
                            '<button type="button" class="btn-choose px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150">Pilih</button>'
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
            $('#productTable').off('click', '.btn-choose').on('click', '.btn-choose', (e) => {
                const product = this.table.row($(e.target).closest('tr')).data();
                if (product) this.choose(product);
            });
        },

        close() {
            this.open = false;
            if (this.table) this.table.search('').draw();
        },
        choose(product) {
            window.dispatchEvent(new CustomEvent('product-chosen', {
                detail: {
                    @if(!empty($includeForEdit))
                    product: product,
                    forEdit: this.forEdit
                    @else
                    product
                    @endif
                }
            }));
            this.close();
        },
        init() {
            window.addEventListener('browse-open', (e) => {
                this.open = true;
                @if(!empty($includeForEdit))
                this.forEdit = !!(e.detail && e.detail.forEdit);
                @endif
                this.$nextTick(() => this.initDataTable());
            }, {
                passive: true
            });
        }
    }
}
@elseif ($section === 'desc_store')
document.addEventListener('alpine:init', () => {
    let existingStore = null;

    try {
        existingStore = Alpine.store('prh');
    } catch (e) {
        existingStore = null;
    }

    if (existingStore) {
        return;
    }

    Alpine.store('prh', {
        descPreview: {
            uid: null,
            index: null,
            label: '',
            text: ''
        },
        descList: []
    });
});
@endif
