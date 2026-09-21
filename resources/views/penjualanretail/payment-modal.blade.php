{{-- Modal Konfirmasi Pembayaran Retail --}}
<div id="retailPaymentModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-2 sm:p-4 overflow-y-auto" style="display: none;">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/60 transition-opacity" onclick="window.closeRetailPaymentModal()"></div>

    <!-- Modal Dialog -->
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md my-auto flex flex-col max-h-[94vh] overflow-hidden border border-gray-100 z-10">
        <!-- Header -->
        <div class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center shadow-inner">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold leading-tight">Konfirmasi Pembayaran</h3>
                    <p class="text-[10px] text-blue-100">Penjualan Retail</p>
                </div>
            </div>
            <button type="button" onclick="window.closeRetailPaymentModal()" class="text-white/80 hover:text-white rounded p-1 hover:bg-white/10 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="px-4 py-2.5 space-y-2 text-xs text-gray-700 bg-gray-50/50 overflow-y-auto">
            <!-- Total Nota -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-semibold text-gray-700 text-xs">Total Nota:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-gray-400 font-semibold text-xs">Rp</span>
                        <input type="text" id="modal_total_nota" readonly
                            class="w-full pl-8 pr-2.5 py-1 bg-gray-100 border border-gray-300 rounded-lg text-right font-bold text-gray-800 cursor-not-allowed text-xs">
                    </div>
                </div>
            </div>

            <!-- Pembayaran -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-semibold text-gray-700 text-xs">Pembayaran: <span class="text-red-500">*</span></label>
                <div class="col-span-8">
                    <select id="modal_payment_type" class="w-full border border-gray-300 rounded-lg px-2.5 py-1 text-gray-800 font-medium bg-white text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Pembayaran --</option>
                        @foreach($typePembayarans ?? [] as $tp)
                            @php
                                $tpCode = trim((string) ($tp->ftypepembayarankode ?? ''));
                                $tpName = trim((string) ($tp->ftypepembayaranname ?? ''));
                                $tpAcc = trim((string) ($tp->faccount ?? ''));
                            @endphp
                            <option value="{{ $tp->ftypepembayaranid }}" data-faccount="{{ $tpAcc }}" data-kode="{{ $tpCode }}"
                                {{ (isset($selectedTypePembayaranId) && $selectedTypePembayaranId == $tp->ftypepembayaranid) ? 'selected' : '' }}>
                                {{ !empty($tpCode) && $tpCode !== $tpName ? $tpCode . ' - ' : '' }}{{ $tpName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Biaya / Charge -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-semibold text-gray-700 text-xs">Biaya / Charge:</label>
                <div class="col-span-8 flex items-center gap-2">
                    <div class="relative w-20 shrink-0">
                        <input type="text" id="modal_biaya_charge_persen" value="0"
                            class="w-full pr-6 pl-2 py-1 border border-gray-300 rounded-lg text-right font-medium text-gray-800 bg-white text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-2 flex items-center text-gray-500 font-bold text-xs pointer-events-none">%</span>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 font-semibold text-xs">Rp</span>
                        <input type="text" id="modal_biaya_charge_nominal" value="0,00" disabled
                            class="w-full pl-7 pr-2.5 py-1 bg-gray-100 border border-gray-200 rounded-lg text-right font-medium text-gray-600 cursor-not-allowed text-xs">
                    </div>
                </div>
            </div>

            <!-- Nomor Retur -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-semibold text-gray-700 text-xs">Nomor Retur: <span class="text-[10px] text-gray-400 font-normal">(Opsional)</span></label>
                <div class="col-span-8">
                    <select id="modal_retur_select"
                        class="w-full border border-gray-300 rounded-lg px-2.5 py-1 text-xs font-medium text-gray-800 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Tanpa Retur --</option>
                    </select>
                </div>
            </div>

            <!-- Nilai RP Retur -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-semibold text-gray-700 text-xs">Nilai RP Retur:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-gray-400 font-semibold text-xs">Rp</span>
                        <input type="text" id="modal_kurangi_retur" value="0,00"
                            class="w-full pl-8 pr-2.5 py-1 border border-gray-300 rounded-lg text-right font-medium text-gray-800 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs">
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-0.5"></div>

            <!-- Grand Total -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-bold text-gray-900 text-xs">Grand Total:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-blue-500 font-bold text-xs">Rp</span>
                        <input type="text" id="modal_grand_total" readonly
                            class="w-full pl-8 pr-2.5 py-1 bg-blue-50 border border-blue-300 rounded-lg text-right font-bold text-sm text-blue-700 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-bold text-gray-900 text-xs">Total Pembayaran:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-emerald-600 font-bold text-xs">Rp</span>
                        <input type="text" id="modal_total_bayar" value="0,00"
                            class="w-full pl-8 pr-2.5 py-1 border-2 border-emerald-500 rounded-lg text-right font-bold text-sm text-emerald-700 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Kembalian -->
            <div class="grid grid-cols-12 items-center gap-2">
                <label class="col-span-4 font-bold text-gray-900 text-xs">Kembalian:</label>
                <div class="col-span-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-gray-500 font-bold text-xs">Rp</span>
                        <input type="text" id="modal_kembalian" readonly
                            class="w-full pl-8 pr-2.5 py-1 bg-gray-100 border border-gray-300 rounded-lg text-right font-bold text-sm text-emerald-600 cursor-not-allowed">
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 bg-gray-100 border-t border-gray-200 flex items-center justify-end gap-2 shrink-0">
            <button type="button" onclick="window.closeRetailPaymentModal()"
                class="px-4 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-xs font-medium hover:bg-gray-50 hover:border-gray-400 transition">
                Batal
            </button>
            <button type="button" onclick="window.confirmRetailPaymentOk()"
                class="px-5 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow hover:shadow-md transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            const curPaymentName = form.querySelector('[name="fpembayaran"]')?.value?.trim();
            const paySelect = document.getElementById('modal_payment_type');
            if (curPaymentName && paySelect) {
                const opt = Array.from(paySelect.options).find(o => (o.getAttribute('data-kode') || o.text.trim()) === curPaymentName || o.text.trim() === curPaymentName);
                if (opt) paySelect.value = opt.value;
            }

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
                const presetReturNo = (form.querySelector('[name="frefretur"]')?.value || '').trim();
                const returUrl = '{{ route('penjualanretail.customer-returs') }}?fcustno=' + encodeURIComponent(custNo) + '&current_retur=' + encodeURIComponent(presetReturNo);
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
                                let html = '<option value="">-- Tanpa Retur --</option>';
                                const initialReturNo = (form.querySelector('[name="frefretur"]')?.dataset?.initialRetur || '').trim();
                                const initialReturNominal = parseFloat(form.querySelector('[name="famountretur"]')?.dataset?.initialNominal || '0') || 0;

                                returs.forEach(r => {
                                    let remain = parseFloat(r.famountremain || 0);
                                    if (initialReturNo && r.fsono === initialReturNo) {
                                        remain += initialReturNominal;
                                    }
                                    html += `<option value="${r.fsono}" data-remain="${remain}">[${r.fsono}] Sisa: Rp ${formatRetailMoney(remain)}</option>`;
                                });
                                returSelect.innerHTML = html;
                            }
                            if (returEl) {
                                returEl.disabled = false;
                                const presetReturNo = (form.querySelector('[name="frefretur"]')?.value || '').trim();
                                const presetReturNominal = parseRetailMoney(form.querySelector('[name="famountretur"]')?.value || '0');
                                if (presetReturNo) {
                                    returSelect.value = presetReturNo;
                                    const selectedOpt = returSelect.options[returSelect.selectedIndex];
                                    const maxRemain = parseFloat(selectedOpt?.getAttribute('data-remain') || '0');
                                    returEl.dataset.maxRemain = maxRemain;
                                    returEl.value = formatRetailMoney(presetReturNominal > 0 ? presetReturNominal : maxRemain);
                                } else {
                                    returSelect.value = '';
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

            let inputFpembayaran = retailActiveForm.querySelector('[name="fpembayaran"]');
            if (!inputFpembayaran) {
                inputFpembayaran = document.createElement('input');
                inputFpembayaran.type = 'hidden';
                inputFpembayaran.name = 'fpembayaran';
                retailActiveForm.appendChild(inputFpembayaran);
            }
            inputFpembayaran.value = fkode;

            const returSelect = document.getElementById('modal_retur_select');
            const returFsono = returSelect?.value || '';
            const returNominal = returFsono ? parseRetailMoney(document.getElementById('modal_kurangi_retur')?.value || '0') : 0;
            const maxRemain = parseFloat(document.getElementById('modal_kurangi_retur')?.dataset?.maxRemain || '0');

            if (returFsono && maxRemain > 0 && returNominal > maxRemain + 0.0001) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nilai Retur Melebihi Batas',
                    text: 'Nilai RP Retur tidak boleh melebihi angka nota retur nya (maksimal Rp ' + formatRetailMoney(maxRemain) + ').',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700' }
                });
                return;
            }

            let inputReturNo = retailActiveForm.querySelector('[name="frefretur"]');
            if (!inputReturNo) {
                inputReturNo = document.createElement('input');
                inputReturNo.type = 'hidden';
                inputReturNo.name = 'frefretur';
                retailActiveForm.appendChild(inputReturNo);
            }
            if (inputReturNo.tagName === 'SELECT' && returFsono) {
                if (!Array.from(inputReturNo.options).some(o => o.value === returFsono)) {
                    const newOpt = document.createElement('option');
                    newOpt.value = returFsono;
                    newOpt.textContent = returFsono;
                    inputReturNo.appendChild(newOpt);
                }
            }
            inputReturNo.value = returFsono;

            let inputLegacyReturNo = retailActiveForm.querySelector('input[name="retur_fsono"]');
            if (!inputLegacyReturNo) {
                inputLegacyReturNo = document.createElement('input');
                inputLegacyReturNo.type = 'hidden';
                inputLegacyReturNo.name = 'retur_fsono';
                retailActiveForm.appendChild(inputLegacyReturNo);
            }
            inputLegacyReturNo.value = returFsono;

            let inputReturNominal = retailActiveForm.querySelector('[name="famountretur"]');
            if (!inputReturNominal) {
                inputReturNominal = document.createElement('input');
                inputReturNominal.type = 'hidden';
                inputReturNominal.name = 'famountretur';
                retailActiveForm.appendChild(inputReturNominal);
            }
            inputReturNominal.value = returNominal > 0 ? formatRetailMoney(returNominal) : '';

            let inputLegacyReturNominal = retailActiveForm.querySelector('input[name="fkurangiretur"]');
            if (!inputLegacyReturNominal) {
                inputLegacyReturNominal = document.createElement('input');
                inputLegacyReturNominal.type = 'hidden';
                inputLegacyReturNominal.name = 'fkurangiretur';
                retailActiveForm.appendChild(inputLegacyReturNominal);
            }
            inputLegacyReturNominal.value = returNominal;

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
