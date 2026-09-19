{{-- Modal Konfirmasi Pembayaran Retail --}}
<div id="retailPaymentModal" class="fixed inset-0 z-[100] hidden items-center justify-center" style="display: none;">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/60 transition-opacity" onclick="window.closeRetailPaymentModal()"></div>

    <!-- Modal Dialog -->
    <div class="relative bg-white rounded-2xl shadow-2xl w-[94vw] max-w-lg overflow-hidden border border-gray-100 z-10">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shadow-inner">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight">Konfirmasi Pembayaran</h3>
                    <p class="text-xs text-blue-100">Penjualan Retail</p>
                </div>
            </div>
            <button type="button" onclick="window.closeRetailPaymentModal()" class="text-white/80 hover:text-white rounded-lg p-1.5 hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-3.5 text-sm text-gray-700 bg-gray-50/50">
            <!-- Total Nota -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-semibold text-gray-700">Total Nota:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-semibold text-xs">Rp</span>
                        <input type="text" id="modal_total_nota" readonly
                            class="w-full pl-9 pr-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-right font-bold text-gray-800 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Pembayaran -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-semibold text-gray-700">Pembayaran: <span class="text-red-500">*</span></label>
                <div class="col-span-8">
                    <select id="modal_payment_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-gray-800 font-medium bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($typePembayarans ?? [] as $tp)
                            <option value="{{ $tp->ftypepembayaranid }}" data-faccount="{{ $tp->faccount }}" data-kode="{{ $tp->ftypepembayarankode }}"
                                {{ (isset($selectedTypePembayaranId) && $selectedTypePembayaranId == $tp->ftypepembayaranid) ? 'selected' : '' }}>
                                {{ !empty($tp->ftypepembayarankode) && $tp->ftypepembayarankode !== $tp->ftypepembayaranname ? $tp->ftypepembayarankode . ' - ' : '' }}{{ $tp->ftypepembayaranname }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Biaya / Charge -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-semibold text-gray-700">Biaya / Charge:</label>
                <div class="col-span-8 flex items-center gap-2">
                    <div class="relative w-28 shrink-0">
                        <input type="text" id="modal_biaya_charge_persen" value="0"
                            class="w-full pr-7 pl-3 py-2 border border-gray-300 rounded-lg text-right font-medium text-gray-800 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-500 font-bold text-xs pointer-events-none">%</span>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-gray-400 font-semibold text-xs">Rp</span>
                        <input type="text" id="modal_biaya_charge_nominal" value="0,00" disabled
                            class="w-full pl-8 pr-3 py-2 bg-gray-100 border border-gray-200 rounded-lg text-right font-medium text-gray-600 cursor-not-allowed text-sm">
                    </div>
                </div>
            </div>

            <!-- Kurangi Retur -->
            <div class="grid grid-cols-12 items-start gap-3">
                <label class="col-span-4 font-semibold text-gray-700 pt-1.5">Kurangi Retur:</label>
                <div class="col-span-8 space-y-1.5">
                    <select id="modal_retur_select"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-800 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Tanpa Retur --</option>
                    </select>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-semibold text-xs">Rp</span>
                        <input type="text" id="modal_kurangi_retur" value="0,00"
                            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-right font-medium text-gray-800 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-1"></div>

            <!-- Grand Total -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-bold text-gray-900 text-base">Grand Total:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-blue-500 font-bold text-xs">Rp</span>
                        <input type="text" id="modal_grand_total" readonly
                            class="w-full pl-9 pr-3 py-2 bg-blue-50 border-2 border-blue-200 rounded-lg text-right font-extrabold text-lg text-blue-700 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-bold text-gray-900 text-base">Total Pembayaran:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-600 font-bold text-xs">Rp</span>
                        <input type="text" id="modal_total_bayar" value="0,00"
                            class="w-full pl-9 pr-3 py-2 border-2 border-emerald-500 rounded-lg text-right font-extrabold text-lg text-emerald-700 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Kembalian -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-bold text-gray-900 text-base">Kembalian:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold text-xs">Rp</span>
                        <input type="text" id="modal_kembalian" readonly
                            class="w-full pl-9 pr-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-right font-extrabold text-lg text-emerald-600 cursor-not-allowed">
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3.5 bg-gray-100 border-t border-gray-200 flex items-center justify-end gap-3">
            <button type="button" onclick="window.closeRetailPaymentModal()"
                class="px-5 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 hover:border-gray-400 transition">
                Batal
            </button>
            <button type="button" onclick="window.confirmRetailPaymentOk()"
                class="px-6 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-md hover:shadow-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
                Ok
            </button>
        </div>
    </div>
</div>

<script>
    (function() {
        let retailActiveForm = null;

        function formatRetailMoney(val) {
            const num = Number(val || 0);
            if (!Number.isFinite(num)) return '0,00';
            return num.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function parseRetailMoney(val) {
            if (val === null || val === undefined || val === '') return 0;
            let str = val.toString().trim();
            if (str.includes('.') && str.includes(',')) {
                if (str.lastIndexOf('.') > str.lastIndexOf(',')) {
                    str = str.replace(/,/g, '');
                } else {
                    str = str.replace(/\./g, '').replace(',', '.');
                }
            } else if (str.includes(',')) {
                str = str.replace(',', '.');
            } else if (str.includes('.')) {
                const parts = str.split('.');
                if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
                    str = parts.join('');
                }
            }
            const num = Number(str.replace(/[^0-9.-]+/g, ''));
            return Number.isFinite(num) ? num : 0;
        }

        function parsePercent(val) {
            if (val === null || val === undefined || val === '') return 0;
            let str = val.toString().trim().replace(/,/g, '.');
            const num = parseFloat(str);
            return Number.isFinite(num) ? Math.max(0, num) : 0;
        }

        function recalculateRetailTotals() {
            const totalNota = parseRetailMoney(document.getElementById('modal_total_nota')?.dataset?.raw || '0');
            const persen = parsePercent(document.getElementById('modal_biaya_charge_persen')?.value || '0');
            const biaya = Math.round((totalNota * persen) / 100);

            const biayaNominalEl = document.getElementById('modal_biaya_charge_nominal');
            if (biayaNominalEl) {
                biayaNominalEl.value = formatRetailMoney(biaya);
            }

            const retur = parseRetailMoney(document.getElementById('modal_kurangi_retur')?.value || '0');

            const grandTotal = Math.max(0, totalNota + biaya - retur);
            const grandTotalEl = document.getElementById('modal_grand_total');
            if (grandTotalEl) {
                grandTotalEl.value = formatRetailMoney(grandTotal);
                grandTotalEl.dataset.raw = grandTotal;
            }
            recalculateRetailKembalian();
        }

        function recalculateRetailKembalian() {
            const grandTotal = parseRetailMoney(document.getElementById('modal_grand_total')?.dataset?.raw || '0');
            const totalBayar = parseRetailMoney(document.getElementById('modal_total_bayar')?.value || '0');

            const kembalian = totalBayar - grandTotal;
            const kembalianEl = document.getElementById('modal_kembalian');
            if (kembalianEl) {
                kembalianEl.value = formatRetailMoney(kembalian);
                kembalianEl.dataset.raw = kembalian;
                if (kembalian < 0) {
                    kembalianEl.classList.remove('text-emerald-600');
                    kembalianEl.classList.add('text-red-600');
                } else {
                    kembalianEl.classList.remove('text-red-600');
                    kembalianEl.classList.add('text-emerald-600');
                }
            }
        }

        window.openRetailPaymentModal = function(form) {
            retailActiveForm = form;
            const amountInput = form.querySelector('input[name="famountso"]');
            const totalNota = parseFloat(amountInput?.value || '0') || 0;

            const totalNotaEl = document.getElementById('modal_total_nota');
            if (totalNotaEl) {
                totalNotaEl.value = formatRetailMoney(totalNota);
                totalNotaEl.dataset.raw = totalNota;
            }

            const persenEl = document.getElementById('modal_biaya_charge_persen');
            if (persenEl) persenEl.value = '0';
            const biayaNominalEl = document.getElementById('modal_biaya_charge_nominal');
            if (biayaNominalEl) biayaNominalEl.value = '0,00';

            const returSelect = document.getElementById('modal_retur_select');
            const returEl = document.getElementById('modal_kurangi_retur');
            if (returSelect) {
                returSelect.innerHTML = '<option value="">Memuat retur...</option>';
                returSelect.disabled = true;
            }
            if (returEl) {
                returEl.value = '0,00';
                returEl.dataset.maxRemain = '0';
                returEl.disabled = true;
            }

            recalculateRetailTotals();

            // Default Total Pembayaran diisi sebesar Grand Total
            const grandTotal = parseRetailMoney(document.getElementById('modal_grand_total')?.dataset?.raw || totalNota);
            const bayarEl = document.getElementById('modal_total_bayar');
            if (bayarEl) {
                bayarEl.value = formatRetailMoney(grandTotal);
            }

            recalculateRetailKembalian();

            // Ambil retur yg masih famountremain > 0 milik customer yang bersangkutan
            const custNo = (form.querySelector('input[name="fcustno"]')?.value || form.querySelector('[name="filter_customer_id"]')?.value || '').trim();
            if (custNo) {
                const returUrl = '{{ route('penjualanretail.customer-returs') }}?fcustno=' + encodeURIComponent(custNo);
                fetch(returUrl)
                    .then(res => res.json())
                    .then(returs => {
                        if (!Array.isArray(returs) || returs.length === 0) {
                            if (returSelect) {
                                returSelect.innerHTML = '<option value="">-- Tidak ada retur aktif --</option>';
                                returSelect.disabled = true;
                            }
                            if (returEl) {
                                returEl.value = '0,00';
                                returEl.dataset.maxRemain = '0';
                                returEl.disabled = true;
                            }
                        } else {
                            if (returSelect) {
                                returSelect.disabled = false;
                                let html = '';
                                if (returs.length > 1) {
                                    html += '<option value="">-- Pilih Retur / Tanpa Retur --</option>';
                                }
                                returs.forEach(r => {
                                    const remain = parseFloat(r.famountremain || 0);
                                    html += `<option value="${r.fsono}" data-remain="${remain}">[${r.fsono}] Sisa: Rp ${formatRetailMoney(remain)}</option>`;
                                });
                                returSelect.innerHTML = html;
                            }
                            if (returEl) {
                                returEl.disabled = false;
                                if (returs.length === 1) {
                                    returSelect.value = returs[0].fsono;
                                    const remain = parseFloat(returs[0].famountremain || 0);
                                    returEl.dataset.maxRemain = remain;
                                    const curNota = parseRetailMoney(document.getElementById('modal_total_nota')?.dataset?.raw || '0');
                                    const curPersen = parsePercent(document.getElementById('modal_biaya_charge_persen')?.value || '0');
                                    const curBiaya = Math.round((curNota * curPersen) / 100);
                                    const deduction = Math.min(curNota + curBiaya, remain);
                                    returEl.value = formatRetailMoney(deduction);
                                } else {
                                    returEl.value = '0,00';
                                    returEl.dataset.maxRemain = '0';
                                }
                            }
                        }
                        recalculateRetailTotals();
                        const curGrandTotal = parseRetailMoney(document.getElementById('modal_grand_total')?.dataset?.raw || '0');
                        const curBayarEl = document.getElementById('modal_total_bayar');
                        if (curBayarEl) {
                            curBayarEl.value = formatRetailMoney(curGrandTotal);
                        }
                        recalculateRetailKembalian();
                    })
                    .catch(err => {
                        console.error('Error loading retur:', err);
                        if (returSelect) {
                            returSelect.innerHTML = '<option value="">-- Gagal memuat retur --</option>';
                            returSelect.disabled = true;
                        }
                    });
            } else {
                if (returSelect) {
                    returSelect.innerHTML = '<option value="">-- Customer belum dipilih --</option>';
                    returSelect.disabled = true;
                }
            }

            const modal = document.getElementById('retailPaymentModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.style.display = 'flex';
                setTimeout(() => {
                    const payInput = document.getElementById('modal_total_bayar');
                    if (payInput) {
                        payInput.focus();
                        payInput.select();
                    }
                }, 150);
            }
        };

        window.closeRetailPaymentModal = function() {
            const modal = document.getElementById('retailPaymentModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.style.display = 'none';
            }
        };

        window.confirmRetailPaymentOk = function() {
            if (!retailActiveForm) return;

            const selectEl = document.getElementById('modal_payment_type');
            const paymentTypeId = selectEl?.value || '';
            const selectedOpt = selectEl?.options[selectEl.selectedIndex];
            const faccount = selectedOpt ? (selectedOpt.getAttribute('data-faccount') || '') : '';
            const fkode = selectedOpt ? (selectedOpt.getAttribute('data-kode') || '') : '';

            if (!paymentTypeId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pembayaran Wajib Dipilih',
                    text: 'Silakan pilih Type Pembayaran terlebih dahulu.',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700' }
                });
                return;
            }

            // Set hidden inputs in form
            let inputTypeId = retailActiveForm.querySelector('input[name="type_pembayaran_id"]');
            if (!inputTypeId) {
                inputTypeId = document.createElement('input');
                inputTypeId.type = 'hidden';
                inputTypeId.name = 'type_pembayaran_id';
                retailActiveForm.appendChild(inputTypeId);
            }
            inputTypeId.value = paymentTypeId;

            let inputFaccount = retailActiveForm.querySelector('input[name="faccount_pembayaran"]');
            if (!inputFaccount) {
                inputFaccount = document.createElement('input');
                inputFaccount.type = 'hidden';
                inputFaccount.name = 'faccount_pembayaran';
                retailActiveForm.appendChild(inputFaccount);
            }
            inputFaccount.value = faccount;

            let inputFpembayaran = retailActiveForm.querySelector('input[name="fpembayaran"]');
            if (!inputFpembayaran) {
                inputFpembayaran = document.createElement('input');
                inputFpembayaran.type = 'hidden';
                inputFpembayaran.name = 'fpembayaran';
                retailActiveForm.appendChild(inputFpembayaran);
            }
            inputFpembayaran.value = fkode;

            const returSelect = document.getElementById('modal_retur_select');
            const returFsono = returSelect?.value || '';
            const returNominal = parseRetailMoney(document.getElementById('modal_kurangi_retur')?.value || '0');

            let inputReturNo = retailActiveForm.querySelector('input[name="retur_fsono"]');
            if (!inputReturNo) {
                inputReturNo = document.createElement('input');
                inputReturNo.type = 'hidden';
                inputReturNo.name = 'retur_fsono';
                retailActiveForm.appendChild(inputReturNo);
            }
            inputReturNo.value = returFsono;

            let inputReturNominal = retailActiveForm.querySelector('input[name="fkurangiretur"]');
            if (!inputReturNominal) {
                inputReturNominal = document.createElement('input');
                inputReturNominal.type = 'hidden';
                inputReturNominal.name = 'fkurangiretur';
                retailActiveForm.appendChild(inputReturNominal);
            }
            inputReturNominal.value = returNominal;

            window.closeRetailPaymentModal();

            // Lanjut ke submission dengan konfirmasi stok jika ada minus
            window.submitFormWithStockMinusConfirmation?.(retailActiveForm);
        };

        // Attach input listeners
        document.addEventListener('DOMContentLoaded', function() {
            const attachMoneyEvents = function(id, isTotalBayar = false) {
                const el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('focus', function() {
                    this.select();
                });

                el.addEventListener('input', function() {
                    if (id === 'modal_kurangi_retur') {
                        let val = parseRetailMoney(this.value);
                        const maxRemain = parseFloat(this.dataset.maxRemain || '0');
                        if (maxRemain > 0 && val > maxRemain) {
                            val = maxRemain;
                            this.value = formatRetailMoney(val);
                        }
                        const totalNota = parseRetailMoney(document.getElementById('modal_total_nota')?.dataset?.raw || '0');
                        const persen = parsePercent(document.getElementById('modal_biaya_charge_persen')?.value || '0');
                        const biaya = Math.round((totalNota * persen) / 100);
                        if (val > totalNota + biaya) {
                            val = totalNota + biaya;
                            this.value = formatRetailMoney(val);
                        }
                    }
                    if (isTotalBayar) {
                        recalculateRetailKembalian();
                    } else {
                        recalculateRetailTotals();
                    }
                });

                el.addEventListener('blur', function() {
                    const parsed = parseRetailMoney(this.value);
                    this.value = formatRetailMoney(parsed);
                    if (isTotalBayar) {
                        recalculateRetailKembalian();
                    } else {
                        recalculateRetailTotals();
                    }
                });

                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (id === 'modal_kurangi_retur') {
                            document.getElementById('modal_total_bayar')?.focus();
                        } else if (id === 'modal_total_bayar') {
                            window.confirmRetailPaymentOk();
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        window.closeRetailPaymentModal();
                    }
                });
            };

            const returSelect = document.getElementById('modal_retur_select');
            if (returSelect) {
                returSelect.addEventListener('change', function() {
                    const returEl = document.getElementById('modal_kurangi_retur');
                    const selectedOpt = this.options[this.selectedIndex];
                    const remain = parseFloat(selectedOpt?.getAttribute('data-remain') || '0');
                    if (returEl) {
                        returEl.dataset.maxRemain = remain;
                        if (remain > 0) {
                            const totalNota = parseRetailMoney(document.getElementById('modal_total_nota')?.dataset?.raw || '0');
                            const persen = parsePercent(document.getElementById('modal_biaya_charge_persen')?.value || '0');
                            const biaya = Math.round((totalNota * persen) / 100);
                            const deduction = Math.min(totalNota + biaya, remain);
                            returEl.value = formatRetailMoney(deduction);
                        } else {
                            returEl.value = '0,00';
                        }
                    }
                    recalculateRetailTotals();
                    const grandTotal = parseRetailMoney(document.getElementById('modal_grand_total')?.dataset?.raw || '0');
                    const bayarEl = document.getElementById('modal_total_bayar');
                    if (bayarEl) {
                        bayarEl.value = formatRetailMoney(grandTotal);
                    }
                    recalculateRetailKembalian();
                });

                returSelect.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('modal_kurangi_retur')?.focus();
                    }
                });
            }

            const persenEl = document.getElementById('modal_biaya_charge_persen');
            if (persenEl) {
                persenEl.addEventListener('focus', function() {
                    this.select();
                });
                persenEl.addEventListener('input', function() {
                    recalculateRetailTotals();
                });
                persenEl.addEventListener('blur', function() {
                    const p = parsePercent(this.value);
                    this.value = p > 0 ? (Number.isInteger(p) ? p.toString() : p.toString().replace('.', ',')) : '0';
                    recalculateRetailTotals();
                });
                persenEl.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const sel = document.getElementById('modal_retur_select');
                        if (sel && !sel.disabled) {
                            sel.focus();
                        } else {
                            document.getElementById('modal_total_bayar')?.focus();
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        window.closeRetailPaymentModal();
                    }
                });
            }

            attachMoneyEvents('modal_kurangi_retur', false);
            attachMoneyEvents('modal_total_bayar', true);

            // Esc key listener globally when modal is open
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('retailPaymentModal');
                    if (modal && !modal.classList.contains('hidden') && modal.style.display !== 'none') {
                        window.closeRetailPaymentModal();
                    }
                }
            });
        });
    })();
</script>
