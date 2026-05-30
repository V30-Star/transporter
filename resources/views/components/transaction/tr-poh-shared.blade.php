@php
    $section = $section ?? '';
@endphp

@if ($section === 'datatables_length_styles')
    <style>
        div#productTable_length select,
        .dataTables_wrapper #productTable_length select,
        div#supplierBrowseTable_length select,
        .dataTables_wrapper #supplierBrowseTable_length select,
        div#prTable_length select,
        .dataTables_wrapper #prTable_length select {
            min-width: 140px !important;
            width: auto !important;
            padding: 8px 45px 8px 16px !important;
            font-size: 14px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
        }

        div#productTable_length,
        .dataTables_wrapper #productTable_length,
        div#supplierBrowseTable_length,
        .dataTables_wrapper #supplierBrowseTable_length,
        div#prTable_length,
        .dataTables_wrapper #prTable_length {
            min-width: 250px !important;
        }

        div#productTable_length label,
        .dataTables_wrapper #productTable_length label,
        div#supplierBrowseTable_length label,
        .dataTables_wrapper #supplierBrowseTable_length label,
        div#prTable_length label,
        .dataTables_wrapper #prTable_length label {
            font-size: 14px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
    </style>
@elseif ($section === 'browser_globals')
window.PRODUCT_MAP = @json($productMap ?? []);

window.CURRENCY_MAP = {
    @foreach ($currencies as $cur)
        {{ $cur->fcurrid }}: {
            id: {{ $cur->fcurrid }},
            code: @json($cur->fcurrcode),
            name: @json($cur->fcurrname),
            rate: {{ $cur->frate ?? 0 }}
        },
    @endforeach
};

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

window.fetchLastPrice = async function(fprdcode, fsupplier, fsatuan) {
    if (!fprdcode || !fsupplier || !fsatuan) return null;

    try {
        const url = new URL("{{ route('tr_poh.lastPrice') }}", window.location.origin);
        url.searchParams.set('fprdcode', fprdcode);
        url.searchParams.set('fsupplier', fsupplier);
        url.searchParams.set('fsatuan', fsatuan);

        const res = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!res.ok) return null;

        return await res.json();
    } catch (e) {
        return null;
    }
};
@elseif ($section === 'form_method_helpers')
window.trPohNoAcakMethods = {
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
    }
};

window.trPohSummaryMethods = {
    get totalHarga() {
        return this.savedItems.reduce((s, it) => s + (it.ftotal || 0), 0);
    },
    get ppnNominal() {
        if (!this.includePPN) return 0;
        const total = this.totalHarga;
        const rate = +this.ppnRate || 0;
        return this.ppnMode === 1 ? Math.round(total * rate / (100 + rate)) : Math.round(total * rate / 100);
    },
    get grandTotal() {
        if (!this.includePPN) return this.totalHarga;
        return this.ppnMode === 1 ? this.totalHarga : this.totalHarga + this.ppnNominal;
    },
    get grandTotalRp() {
        if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return this.grandTotal;
        return +(this.grandTotal * (+this.rateValue || 1)).toFixed(2);
    },

    fmtCurr(n) {
        const v = Number(n || 0);
        if (!isFinite(v)) return '-';
        return v.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    rupiah(n) {
        const v = Number(n || 0);
        if (!isFinite(v)) return '-';
        return v.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    itemTotalRp(value) {
        const total = Number(value || 0);
        if (!Number.isFinite(total)) return 0;
        if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return total;
        return +(total * (+this.rateValue || 1)).toFixed(2);
    }
};

window.trPohCoreItemMethods = {
    onCurrencyChange() {
        const id = parseInt(this.selectedCurrId);
        const cur = window.CURRENCY_MAP[id];
        if (cur) {
            this.selectedCurrCode = cur.code;
            this.rateValue = cur.rate;
        } else {
            this.selectedCurrCode = '';
            this.rateValue = 0;
        }
    },

    recalc(row) {
        const qty = Math.max(0, +row.fqty || 0);
        const price = Math.max(0, +row.fprice || 0);
        const disc = Math.min(100, Math.max(0, +row.fdisc || 0));
        row.fqty = qty;
        row.fprice = price;
        row.fdisc = disc;
        row.ftotal = +(qty * price * (1 - disc / 100)).toFixed(2);
    },

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

    formatPrRemainHint(row) {
        return '';
    },

    enforcePrQtyRow(row) {
        const n = +row.fqty;
        if (!Number.isFinite(n)) {
            row.fqty = 1;
            return;
        }
        if (n < 1) row.fqty = 1;
        if (!row.frefdtno) return;
        row.maxqty = this.calcMaxQty(row);
    },

    hydrateRowFromMeta(row, meta, keepMaxqty = false, forceDefaultUnit = false) {
        if (!meta) {
            row.fitemname = '';
            row.units = [];
            row.fsatuan = '';
            if (!keepMaxqty) row.maxqty = 0;
            if (row === this.draft) {
                clearDraftUnitSelect();
            }
            return;
        }
        row.fitemname = meta.name || '';
        const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
        const defaultUnit = (meta.default_unit || '').toString().trim();
        const resolvedDefaultUnit = defaultUnit && units.includes(defaultUnit) ? defaultUnit : (units[0] || '');
        const currentSatuan = (row.fsatuan || '').trim();
        if (currentSatuan && !units.includes(currentSatuan)) units.unshift(currentSatuan);
        row.units = units;
        row.fsatuan = forceDefaultUnit
            ? resolvedDefaultUnit
            : (!currentSatuan ? (resolvedDefaultUnit || '') : currentSatuan);
        if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
        if (!keepMaxqty) row.maxqty = 0;

        if (row === this.draft) {
            if (units.length > 1) {
                populateDraftUnitSelect(units);
            } else {
                clearDraftUnitSelect();
            }
        }
    },

    onCodeTypedRow(row) {
        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode), false, true);
        this.$nextTick(() => this.applyLastPrice(row));
    },
    onCodeTypedSaved(item) {
        this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode), false, true);
        this.$nextTick(() => this.applyLastPrice(item));
    },

    getSupplier() {
        return (document.getElementById('supplierCodeHidden')?.value || '').trim();
    },

    isComplete(row) {
        return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
    },

    calcMaxQty(row) {
        const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
        const satuanPO = (row.fsatuan || '').trim();
        const satuanPR = (row.fqtypr_satuan || '').trim();
        const satKecil = (row.fsatuankecil || '').trim();
        const satBesar = (row.fsatuanbesar || '').trim();
        const satBesar2 = (row.fsatuanbesar2 || '').trim();
        const rasio = Number(row.fqtykecil || 0);
        const rasio2 = Number(row.fqtykecil2 || 0);
        const sisaPrBaris = Number(row.fqtysisapr ?? 0);

        if (sisaPrBaris > 0 && (!satuanPR || eq(satuanPO, satuanPR))) {
            return sisaPrBaris;
        }

        const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row.fqtykecil_ref !== '';

        let sisaKecil = 0;
        if (hasRemainField) {
            sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);
        } else {
            const qtyPR = Number(row.fqtypr) || 0;
            const fqtypo = Number(row.fqtypo) || 0;
            const satuanPRRef = (row.fqtypr_satuan || '').trim();
            if (!satuanPRRef || !(qtyPR > 0)) return 0;
            let qtyPRInKecil = qtyPR;
            if (eq(satuanPRRef, satBesar) && rasio > 0) {
                qtyPRInKecil = qtyPR * rasio;
            } else if (eq(satuanPRRef, satBesar2) && rasio2 > 0) {
                qtyPRInKecil = qtyPR * rasio2;
            }
            sisaKecil = Math.max(0, qtyPRInKecil - fqtypo);
        }

        if (!satuanPO || eq(satuanPO, satKecil)) {
            return sisaKecil;
        }
        if (eq(satuanPO, satBesar) && rasio > 0) {
            return Math.floor(sisaKecil / rasio);
        }
        if (eq(satuanPO, satBesar2) && rasio2 > 0) {
            return Math.floor(sisaKecil / rasio2);
        }
        return sisaKecil;
    },

    focusSavedUnit(item, i) {
        if (item.units.length > 1) this.$nextTick(() => document.getElementById('unit_saved_' + i)?.focus());
        else this.focusSavedQty(i);
    },
    focusSavedQty(i) {
        this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
    },
    focusSavedPrice(i) {
        this.$nextTick(() => document.getElementById('price_saved_' + i)?.focus());
    },
    focusSavedDisc(i) {
        this.$nextTick(() => document.getElementById('disc_saved_' + i)?.focus());
    },
    focusDraftCode() {
        this.$nextTick(() => this.$refs.draftCode?.focus());
    }
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

function clearDraftUnitSelect() {
    const sel = getDraftUnitSelect();
    if (sel) sel.innerHTML = '';
}

@elseif ($section === 'format_date_helper')
function formatDate(s) {
    if (!s || s === 'No Date') return '-';
    const d = new Date(s);
    if (isNaN(d.getTime())) return '-';
    const pad = n => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
}

@elseif ($section === 'desc_store')
document.addEventListener('alpine:init', () => {
    let existingStore;
    try {
        existingStore = Alpine.store('prh');
    } catch (e) {
        existingStore = undefined;
    }

    if (existingStore === undefined) {
        Alpine.store('prh', {
            descPreview: {
                uid: null,
                index: null,
                label: '',
                text: ''
            },
            descList: []
        });
    }
});
@elseif ($section === 'switch_styles')
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 34px
        }

        .slider:before {
            content: "";
            position: absolute;
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background: #fff;
            transition: .4s
        }

        input:checked+.slider {
            background: #4CAF50
        }

        input:checked+.slider:before {
            transform: translateX(26px)
        }
    </style>
@endif
