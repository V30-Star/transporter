{{-- Modal Konfirmasi Pembayaran Retail --}}
<div id="retailPaymentModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-2 sm:p-4 overflow-y-auto" style="display: none;">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/60 transition-opacity" onclick="window.closeRetailPaymentModal()"></div>

    <!-- Modal Dialog -->
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl sm:max-w-2xl my-auto flex flex-col max-h-[94vh] overflow-hidden border border-gray-100 z-10">
        <!-- Header -->
        <div class="px-6 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center shadow-inner">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight">Konfirmasi Pembayaran</h3>
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
        <div class="px-6 py-4 space-y-3.5 text-sm text-gray-700 bg-gray-50/50 overflow-y-auto">
            <!-- Total Nota -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-semibold text-gray-700 text-sm">Total Nota:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold text-sm">Rp</span>
                        <input type="text" id="modal_total_nota" readonly
                            class="w-full pl-10 pr-3 py-2 bg-gray-100 border border-gray-300 rounded-xl text-right font-bold text-gray-800 cursor-not-allowed text-base">
                    </div>
                </div>
            </div>

            <!-- Pembayaran -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-semibold text-gray-700 text-sm">Pembayaran: <span class="text-red-500">*</span></label>
                <div class="col-span-8">
                    <select id="modal_payment_type" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-gray-800 font-semibold bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Pembayaran --</option>
                        @php
                            $defaultTypePembayaranId = $selectedTypePembayaranId ?? ($typePembayarans->first()->ftypepembayaranid ?? null);
                        @endphp
                        @foreach($typePembayarans ?? [] as $tp)
                            @php
                                $tpMasterName = trim((string) ($tp->fmastername ?? $tp->ftypepembayaranname ?? ''));
                                $tpCode = trim((string) ($tp->ftypepembayarankode ?? $tpMasterName));
                                $tpName = trim((string) ($tp->ftypepembayaranname ?? $tpMasterName));
                                $tpAcc = trim((string) ($tp->faccount ?? ''));
                                $tpNumValue = (float) ($tp->fnumvalue ?? 0);
                                $isSelected = isset($defaultTypePembayaranId) && ($defaultTypePembayaranId == $tp->ftypepembayaranid);
                            @endphp
                            <option value="{{ $tp->ftypepembayaranid }}" data-faccount="{{ $tpAcc }}" data-kode="{{ $tpCode }}" data-name="{{ $tpMasterName }}" data-fnumvalue="{{ $tpNumValue }}"
                                {{ $isSelected ? 'selected' : '' }}>
                                {{ !empty($tpCode) && $tpCode !== $tpName ? $tpCode . ' - ' : '' }}{{ $tpName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Biaya / Charge -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-semibold text-gray-700 text-sm">Biaya / Charge:</label>
                <div class="col-span-8 flex items-center gap-2">
                    <div class="relative w-24 shrink-0">
                        <input type="text" id="modal_biaya_charge_persen" value="0" disabled
                            class="w-full pr-7 pl-3 py-2 bg-gray-100 border border-gray-200 rounded-xl text-right font-semibold text-gray-500 cursor-not-allowed text-sm">
                        <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 font-bold text-sm pointer-events-none">%</span>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-semibold text-sm">Rp</span>
                        <input type="text" id="modal_biaya_charge_nominal" value="0,00" disabled
                            class="w-full pl-10 pr-3 py-2 bg-gray-100 border border-gray-200 rounded-xl text-right font-semibold text-gray-600 cursor-not-allowed text-sm">
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
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-blue-500 font-bold text-base">Rp</span>
                        <input type="text" id="modal_grand_total" readonly
                            class="w-full pl-10 pr-3 py-2 bg-blue-50 border border-blue-300 rounded-xl text-right font-black text-xl text-blue-700 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-bold text-gray-900 text-base">Total Pembayaran:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-600 font-bold text-base">Rp</span>
                        <input type="text" id="modal_total_bayar" value="0,00"
                            class="w-full pl-10 pr-3 py-2 border-2 border-emerald-500 rounded-xl text-right font-black text-xl text-emerald-700 bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-600">
                    </div>
                </div>
            </div>

            <!-- Kembalian -->
            <div class="grid grid-cols-12 items-center gap-3">
                <label class="col-span-4 font-bold text-gray-900 text-base">Kembalian:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold text-base">Rp</span>
                        <input type="text" id="modal_kembalian" readonly
                            class="w-full pl-10 pr-3 py-2 bg-gray-100 border border-gray-300 rounded-xl text-right font-black text-xl text-emerald-600 cursor-not-allowed">
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3.5 bg-gray-100 border-t border-gray-200 flex items-center justify-end gap-3 shrink-0">
            <button type="button" onclick="window.closeRetailPaymentModal()"
                class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-400 transition">
                Batal
            </button>
            <button type="button" onclick="window.confirmRetailPaymentOk()"
                class="px-8 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-base font-bold shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        function updateBiayaFromSelect() {
            const sel = document.getElementById('modal_payment_type');
            const opt = sel?.options[sel.selectedIndex];
            const fnumvalue = parseFloat(opt?.getAttribute('data-fnumvalue') || '0') || 0;
            const persenEl = document.getElementById('modal_biaya_charge_persen');
            if (persenEl) {
                persenEl.value = fnumvalue > 0
                    ? (Number.isInteger(fnumvalue) ? fnumvalue.toString() : fnumvalue.toString().replace('.', ','))
                    : '0';
            }
            recalculateRetailTotals();
        }

        function recalculateRetailTotals() {
            const totalNota = parseRetailMoney(document.getElementById('modal_total_nota')?.dataset?.raw || '0');
            const persen = parsePercent(document.getElementById('modal_biaya_charge_persen')?.value || '0');
            const biaya = Math.round((totalNota * persen) / 100);

            const biayaNominalEl = document.getElementById('modal_biaya_charge_nominal');
            if (biayaNominalEl) {
                biayaNominalEl.value = formatRetailMoney(biaya);
            }

            const grandTotal = Math.max(0, totalNota + biaya);
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
            const totalSebelumBiayaInput = form.querySelector('input[name="total_sebelum_biaya"]');
            const amountInput = form.querySelector('input[name="famountso"]');
            const totalNota = parseFloat(totalSebelumBiayaInput?.value || amountInput?.value || '0') || 0;

            const totalNotaEl = document.getElementById('modal_total_nota');
            if (totalNotaEl) {
                totalNotaEl.value = formatRetailMoney(totalNota);
                totalNotaEl.dataset.raw = totalNota;
            }

            const persenVal = parseFloat(form.querySelector('input[name="fbiayapersen"]')?.value || '0') || 0;
            const persenEl = document.getElementById('modal_biaya_charge_persen');
            if (persenEl) {
                persenEl.value = persenVal > 0 ? (Number.isInteger(persenVal) ? persenVal.toString() : persenVal.toString().replace('.', ',')) : '0';
            }
            const biayaNominalEl = document.getElementById('modal_biaya_charge_nominal');
            if (biayaNominalEl) biayaNominalEl.value = '0,00';

            const curPaymentName = form.querySelector('[name="fpembayaran"]')?.value?.trim();
            const paySelect = document.getElementById('modal_payment_type');
            if (paySelect) {
                let opt = null;
                if (curPaymentName) {
                    opt = Array.from(paySelect.options).find(o => 
                        (o.getAttribute('data-name') || '').toUpperCase() === curPaymentName.toUpperCase() ||
                        (o.getAttribute('data-kode') || '').toUpperCase() === curPaymentName.toUpperCase() ||
                        o.text.trim().toUpperCase() === curPaymentName.toUpperCase()
                    );
                }
                if (!opt) {
                    opt = Array.from(paySelect.options).find(o => o.value !== '');
                }
                if (opt) paySelect.value = opt.value;
            }

            // Sync Biaya/Charge % from selected payment type
            updateBiayaFromSelect();

            // Default Total Pembayaran diisi sebesar Grand Total
            const grandTotal = parseRetailMoney(document.getElementById('modal_grand_total')?.dataset?.raw || totalNota);
            const bayarEl = document.getElementById('modal_total_bayar');
            if (bayarEl) {
                bayarEl.value = formatRetailMoney(grandTotal);
            }

            recalculateRetailKembalian();

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

            // Kirim nilai biaya (Rp) ke tranmt.fongkosangkut
            const biayaNominal = parseRetailMoney(document.getElementById('modal_biaya_charge_nominal')?.value || '0');
            let inputFongkos = retailActiveForm.querySelector('input[name="fongkosangkut"]');
            if (!inputFongkos) {
                inputFongkos = document.createElement('input');
                inputFongkos.type = 'hidden';
                inputFongkos.name = 'fongkosangkut';
                retailActiveForm.appendChild(inputFongkos);
            }
            inputFongkos.value = biayaNominal;

            const modalGrandTotal = parseRetailMoney(document.getElementById('modal_grand_total')?.dataset?.raw || '0');
            let inputGrandTotal = retailActiveForm.querySelector('input[name="famountso"]');
            if (!inputGrandTotal) {
                inputGrandTotal = document.createElement('input');
                inputGrandTotal.type = 'hidden';
                inputGrandTotal.name = 'famountso';
                retailActiveForm.appendChild(inputGrandTotal);
            }
            inputGrandTotal.value = modalGrandTotal;

            let inputFpembayaran = retailActiveForm.querySelector('[name="fpembayaran"]');
            if (!inputFpembayaran) {
                inputFpembayaran = document.createElement('input');
                inputFpembayaran.type = 'hidden';
                inputFpembayaran.name = 'fpembayaran';
                retailActiveForm.appendChild(inputFpembayaran);
            }
            inputFpembayaran.value = fkode;

            window.closeRetailPaymentModal();

            // Lanjut ke submission dengan konfirmasi stok jika ada minus
            window.submitFormWithStockMinusConfirmation?.(retailActiveForm);
        };

        // Attach input listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Update biaya when payment type changes
            const paySelect = document.getElementById('modal_payment_type');
            if (paySelect) {
                paySelect.addEventListener('change', updateBiayaFromSelect);
            }

            const attachMoneyEvents = function(id, isTotalBayar = false) {
                const el = document.getElementById(id);
                if (!el) return;

                el.addEventListener('focus', function() {
                    this.select();
                });

                el.addEventListener('input', function() {
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
                        if (id === 'modal_total_bayar') {
                            window.confirmRetailPaymentOk();
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        window.closeRetailPaymentModal();
                    }
                });
            };

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
                        document.getElementById('modal_total_bayar')?.focus();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        window.closeRetailPaymentModal();
                    }
                });
            }

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
