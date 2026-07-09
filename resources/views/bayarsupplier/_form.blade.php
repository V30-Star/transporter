@php
    $pageTitle = $pageTitle ?? 'Bayar Supplier';
    $formAction = $formAction ?? route('bayarsupplier.store');
    $formMethod = strtoupper($formMethod ?? 'POST');
    $isReadOnly = (bool) ($isReadOnly ?? false);
    $isDeleteMode = (bool) ($isDeleteMode ?? false);
    $submitLabel = $submitLabel ?? 'Simpan';
    $backRoute = $backRoute ?? route('bayarsupplier.index');
    $draftKey = $draftKey ?? 'bayarsupplier:create';
    $headerData = $headerData ?? null;

    $parseAmount = function ($value): float {
        if (is_string($value)) {
            $value = trim($value);
            if (str_contains($value, ',') && str_contains($value, '.')) {
                $value = strrpos($value, ',') > strrpos($value, '.')
                    ? str_replace(',', '.', str_replace('.', '', $value))
                    : str_replace(',', '', $value);
            } elseif (str_contains($value, ',')) {
                $value = str_replace(',', '.', str_replace('.', '', $value));
            } else {
                $value = str_replace(',', '', $value);
            }
        }

        return is_numeric($value) ? (float) $value : 0.0;
    };

    $oldDetails = old('details', []);
    $seedDetails = is_array($oldDetails) && count($oldDetails) > 0 ? $oldDetails : ($detailRows ?? [[]]);
    $initialDetailRows = collect($seedDetails)
        ->values()
        ->map(function ($detail, $index) use ($parseAmount) {
            return [
                'uid' => 'bs-' . $index . '-' . substr(md5((string) $index), 0, 8),
                'frefno' => trim((string) ($detail['frefno'] ?? '')),
                'ftrcode' => trim((string) ($detail['ftrcode'] ?? $detail['freftype'] ?? 'BUY')),
                'fsupplier' => trim((string) ($detail['fsupplier'] ?? '')),
                'fsuppliername' => trim((string) ($detail['fsuppliername'] ?? '')),
                'ftempo' => (int) ($detail['ftempo'] ?? 0),
                'fnilai_order' => $parseAmount($detail['fnilai_order'] ?? 0),
                'fsisa_hutang' => $parseAmount($detail['fsisa_hutang'] ?? 0),
                'fdiscpersen' => $parseAmount($detail['fdiscpersen'] ?? 0),
                'fdiscount' => $parseAmount($detail['fdiscount'] ?? 0),
                'fkasdtvalue' => $parseAmount($detail['fkasdtvalue'] ?? 0),
            ];
        })
        ->all();

    $selectedSupplierCode = trim((string) old('fsupplier', $selectedSupplier->fsuppliercode ?? ''));
    $selectedSupplierName = trim((string) ($selectedSupplier->fsuppliername ?? ''));
    $selectedSupplierTempo = (int) old('fsupplier_tempo', $selectedSupplier->ftempo ?? 0);
    $selectedSupplierLabel = $selectedSupplierCode !== ''
        ? trim($selectedSupplierName . ' (' . $selectedSupplierCode . ')')
        : '';
    $selectedAccountCode = trim((string) old('faccountheader', $selectedAccount->faccount ?? ''));
    $selectedAccountName = trim((string) ($selectedAccount->faccname ?? ''));
    $selectedAdminAccount = $selectedAdminAccount ?? null;
@endphp

<div class="max-w-[1600px] mx-auto py-8 px-6"
    x-data="bayarSupplierForm(@js($initialDetailRows), @js($selectedSupplierTempo))" x-init="init()">
    <form action="{{ $formAction }}" method="POST" @submit="handleFormSubmit($event)"
        @if (!$isReadOnly && !empty($draftKey)) data-form-draft="true" data-draft-key="{{ $draftKey }}" @endif>
        @csrf
        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <input type="hidden" name="fsupplier_tempo" x-model="supplierTempo">
        <fieldset @disabled($isReadOnly)>

        {{-- ─── CARD 1: Identitas ────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <x-heroicon-o-identification class="w-5 h-5 text-blue-600" />
                <h2 class="font-semibold text-gray-800">Identitas Pembayaran</h2>
            </div>
            <div class="p-4 space-y-3">

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'Cabang' }}</label>
                    <input type="text" value="{{ $currentBranchLabel ?? old('fbranchcode', $currentBranchCode) }}"
                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 cursor-not-allowed text-gray-700" readonly>
                    <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $currentBranchCode) }}">
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'No. Voucher' }}</label>
                    <input type="text" name="fkasmtno" value="{{ old('fkasmtno', $voucherNo) }}"
                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fkasmtno') border-red-500 @enderror"
                        placeholder="Kosongkan untuk auto number">
                    @error('fkasmtno')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'Tanggal' }}</label>
                    <input type="date" name="fkasmtdate" x-model="transactionDate"
                        value="{{ old('fkasmtdate', $transactionDate) }}"
                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fkasmtdate') border-red-500 @enderror">
                    @error('fkasmtdate')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Row 2: Supplier, Account, No.Giro --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'Supplier' }}</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                class="w-full border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 text-gray-700 cursor-not-allowed" disabled>
                                <option value="{{ $selectedSupplierCode }}">{{ $selectedSupplierLabel }}</option>
                            </select>
                            @if (!$isReadOnly)
                                <div class="absolute inset-0" role="button" aria-label="Browse Supplier"
                                    @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"></div>
                            @endif
                        </div>
                        <input type="hidden" name="fsupplier" id="supplierCodeHidden" x-model="supplierCode">
                        @if (!$isReadOnly)
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('supplier-browse-open'))"
                                class="border border-gray-300 -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" title="Browse Supplier">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                        @endif
                    </div>
                    @error('fsupplier')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'Account' }}</label>
                    @if ($isReadOnly)
                        <input type="text" value="{{ $selectedAccountCode !== '' ? trim($selectedAccountCode . ' - ' . $selectedAccountName) : '' }}"
                            class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 cursor-not-allowed text-gray-700" readonly>
                        <input type="hidden" name="faccountheader" value="{{ $selectedAccountCode }}">
                    @else
                        <div>
                            <select name="faccountheader" x-model="accountCode" class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-white text-gray-900 @error('faccountheader') border-red-500 @enderror">
                                <option value="">{{ 'Pilih account' }}</option>
                                @foreach ($headerAccounts as $account)
                                    <option value="{{ $account->faccount }}">
                                        {{ $account->faccount }} - {{ $account->faccname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @error('faccountheader')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'No.Giro' }}</label>
                    <input type="text" name="fnogiro" value="{{ old('fnogiro', $giroNo ?? '') }}"
                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fnogiro') border-red-500 @enderror">
                    @error('fnogiro')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Row 3: Giro Mundur, Tgl.Jatuh Tempo --}}
            <div class="grid grid-cols-3 gap-3 items-end">
                <div>
                    <label class="inline-flex items-center gap-2 h-9 px-3 border border-gray-300 rounded-lg w-full bg-white text-sm">
                        <input type="checkbox" x-model="isGiroMundur" class="rounded">{{ 'Giro Mundur' }}
                    </label>
                    <input type="hidden" name="fgiromundur" :value="isGiroMundur ? '1' : '0'">
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-600 mb-1">{{ 'Tgl.Jatuh Tempo' }}</label>
                    <input type="date" name="ftgljatuhtempo" x-model="dueDate"
                        :readonly="!isGiroMundur" :disabled="!isGiroMundur"
                        :class="!isGiroMundur ? 'bg-gray-100 cursor-not-allowed text-gray-400' : 'bg-white'"
                        class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('ftgljatuhtempo') border-red-500 @enderror">
                    @error('ftgljatuhtempo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div></div>
            </div>

            {{-- Row 4: Keterangan --}}
            <div>
                <label class="text-xs font-bold text-gray-600 mb-1">{{ 'Keterangan' }}</label>
                <input type="text" name="fket" value="{{ old('fket', $noteValue ?? '') }}"
                    class="w-full border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fket') border-red-500 @enderror">
                @error('fket')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            </div>
        </div>

        {{-- ─── CARD 2: Detail Item ─────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <x-heroicon-o-list-bullet class="w-5 h-5 text-blue-600" />
                <h2 class="font-semibold text-gray-800">Detail Item</h2>
            </div>
            <div class="p-4 space-y-3">

            <div>
                <div class="overflow-auto border rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-12">{{ 'No' }}</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase min-w-[12rem]">{{ 'No.Penerimaan' }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase min-w-[10rem]">{{ 'Nilai Order' }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase min-w-[10rem]">{{ 'Sisa Hutang' }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase min-w-[8rem]">{{ 'Disc.%' }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase min-w-[10rem]">{{ 'Discount' }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase min-w-[10rem]">{{ 'Total Bayar' }}</th>
                                @if (!$isReadOnly)
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-16">{{ 'Aksi' }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="row.uid">
                                <tr>
                                    <td class="border-b border-gray-200 px-3 py-2 text-center text-sm" x-text="index + 1"></td>
                                    <td class="border-b border-gray-200 px-3 py-2">
                                        <input type="text" :name="`details[${index}][frefno]`" x-model="row.frefno"
                                            @input.debounce.500ms="handleManualPblInput(row); resolveManualPbl(row, true)"
                                            @blur="resolveManualPbl(row)"
                                            :class="referenceTextClass(row)"
                                            class="w-full border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <input type="hidden" :name="`details[${index}][ftrcode]`" :value="row.ftrcode || 'BUY'">
                                        <input type="hidden" :name="`details[${index}][fsupplier]`" :value="row.fsupplier">
                                        <input type="hidden" :name="`details[${index}][fsuppliername]`" :value="row.fsuppliername">
                                        <input type="hidden" :name="`details[${index}][ftempo]`" :value="row.ftempo">
                                    </td>
                                    <td class="border-b border-gray-200 px-3 py-2 text-sm">
                                        <input type="text" :value="formatNumber(row.fnilai_order)"
                                            :class="referenceTextClass(row)"
                                            class="w-full border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right bg-gray-100 cursor-not-allowed" readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fnilai_order]`" :value="row.fnilai_order">
                                    </td>
                                    <td class="border-b border-gray-200 px-3 py-2 text-sm">
                                        <input type="text" :value="formatNumber(row.fsisa_hutang)"
                                            :class="referenceTextClass(row)"
                                            class="w-full border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right bg-gray-100 cursor-not-allowed" readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fsisa_hutang]`" :value="row.fsisa_hutang">
                                    </td>
                                    <td class="border-b border-gray-200 px-3 py-2 text-sm">
                                        <input type="number" min="0" max="100" step="0.01"
                                            :name="`details[${index}][fdiscpersen]`" x-model="row.fdiscpersen"
                                            @input="syncDiscountFromPercent(row, $event)"
                                            :disabled="isDiscPercentDisabled(row)"
                                            :class="referenceTextClass(row)"
                                            class="w-full border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                        <input type="hidden" x-show="isDiscPercentDisabled(row)"
                                            :name="`details[${index}][fdiscpersen]`" :value="row.fdiscpersen">
                                    </td>
                                    <td class="border-b border-gray-200 px-3 py-2 text-sm">
                                        <input type="text" x-init="$el.value = formatNumber(row.fdiscount)"
                                            x-effect="if (document.activeElement !== $el) $el.value = formatNumber(row.fdiscount)"
                                            @focus="showRawNumber($event, row, 'fdiscount')"
                                            @input="syncDiscountFromRp(row, $event.target.value)"
                                            @blur="formatNumericField($event, row, 'fdiscount')"
                                            :disabled="isDiscountDisabled(row)"
                                            :class="referenceTextClass(row)"
                                            class="w-full border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                        <input type="hidden" :name="`details[${index}][fdiscount]`" :value="row.fdiscount">
                                    </td>
                                    <td class="border-b border-gray-200 px-3 py-2 text-sm">
                                        <input type="text" x-init="$el.value = formatNumber(row.fkasdtvalue)"
                                            x-effect="if (document.activeElement !== $el) $el.value = formatNumber(row.fkasdtvalue)"
                                            @focus="showRawNumber($event, row, 'fkasdtvalue')"
                                            @input="syncTotalBayarInput(row, $event.target.value)"
                                            @blur="formatNumericField($event, row, 'fkasdtvalue')"
                                            :class="referenceTextClass(row)"
                                            class="w-full border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right">
                                        <input type="hidden" :name="`details[${index}][fkasdtvalue]`" :value="row.fkasdtvalue">
                                    </td>
                                    @if (!$isReadOnly)
                                        <td class="border-b border-gray-200 px-3 py-2 text-center text-sm">
                                            <button type="button" @click="removeRow(index)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 text-lg font-bold">-</button>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 flex justify-start">
                    <button type="button" @click="openPblModal()" @disabled($isReadOnly)
                        class="inline-flex items-center justify-center gap-2 border-gray-300 rounded-lg px-3 py-2 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span>{{ 'Add Faktur' }}</span>
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <div class="w-full max-w-2xl">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-xs font-bold text-gray-600">{{ 'Biaya Admin Bank (-)' }}</label>
                            <div class="w-52">
                                <input type="number" min="0" step="0.01" name="fbiayaadminbank" x-model="bankAdminFee"
                                    @input="recalcTotals()"
                                    class="w-full border rounded px-3 py-2 text-right @error('fbiayaadminbank') border-red-500 @enderror">
                            </div>
                        </div>
                        @error('fbiayaadminbank')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="flex">
                                    <input type="text" x-model="adminAccountLabel"
                                        class="w-full border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 cursor-not-allowed" readonly>
                                    <input type="hidden" name="faccountadmin" x-model="adminAccountCode">
                                    @if (!$isReadOnly)
                                        <button type="button" @click="activeAccountField = 'admin'; window.dispatchEvent(new CustomEvent('admin-account-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Account Admin">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                        </button>
                                    @endif
                                </div>
                                @error('faccountadmin')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <input type="number" min="0" step="0.01" name="fhargaadmin" x-model="hargaAdmin"
                                    @input="recalcTotals()"
                                    class="w-52 border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right text-sm @error('fhargaadmin') border-red-500 @enderror">
                                @error('fhargaadmin')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="flex">
                                    <input type="text" x-model="adminAccount2Label"
                                        class="w-full border-gray-300 rounded-l-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 cursor-not-allowed" readonly>
                                    <input type="hidden" name="faccountadmin2" x-model="adminAccount2Code">
                                    @if (!$isReadOnly)
                                        <button type="button" @click="activeAccountField = 'admin2'; window.dispatchEvent(new CustomEvent('admin-account-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Account Admin 2">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                        </button>
                                    @endif
                                </div>
                                @error('faccountadmin2')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <input type="number" min="0" step="0.01" name="fhargaadmin2" x-model="hargaAdmin2"
                                    @input="recalcTotals()"
                                    class="w-52 border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-right text-sm @error('fhargaadmin2') border-red-500 @enderror">
                                @error('fhargaadmin2')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t pt-3">
                            <span class="text-xs font-bold text-gray-600">{{ 'Total Bayar' }}</span>
                            <input type="text" x-model="totalBayarDisplay"
                                class="w-52 border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 bg-gray-100 text-right font-semibold cursor-not-allowed"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
        </fieldset>

        {{-- ─── CARD 3: Approval & Aksi ────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                <a href="{{ $backRoute }}"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    {{ 'Keluar' }}
                </a>
                @if ($isDeleteMode)
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        {{ $submitLabel }}
                    </button>
                @elseif (!$isReadOnly && !empty($submitLabel))
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        {{ $submitLabel }}
                    </button>
                @endif
            </div>
        </div>

        @if (!$isReadOnly)
            <div x-cloak x-show="pblModalOpen" x-transition.opacity
                class="fixed inset-0 z-[95] flex items-center justify-center overflow-hidden p-3 md:p-6">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePblModal()"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
                    style="height: min(760px, calc(100vh - 1.5rem));">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Browse Faktur Supplier</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Pilih PBL yang ingin dibayar</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="submitSelectedPbls()" :disabled="!tempSelectedPbls.length"
                                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-medium text-sm">
                                Submit (<span x-text="tempSelectedPbls.length"></span>)
                            </button>
                            <button type="button" @click="closePblModal()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 font-medium text-gray-700 text-sm">Tutup</button>
                        </div>
                    </div>

                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <span class="font-medium">Cari:</span>
                                <input type="text" x-model="pblSearch" @input.debounce.400ms="pblPage = 1; fetchPblRecords()"
                                    class="w-full md:w-[320px] rounded-lg border-2 border-gray-200 px-3 py-2 text-sm"
                                    placeholder="No.PBL atau supplier">
                            </label>
                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <span class="font-medium">Tampilkan</span>
                                    <select x-model.number="pblLength" @change="pblPage = 1; fetchPblRecords()"
                                        class="rounded-lg border-2 border-gray-200 px-3 py-2 text-sm">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </label>
                                <button type="button" @click="fetchPblRecords()"
                                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Refresh</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-auto px-6" style="min-height: 0;">
                        <div x-show="pblError" x-text="pblError" class="my-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
                        <div x-show="pblLoading" class="py-10 text-center text-sm text-gray-500">Memuat data faktur...</div>
                        <div x-show="!pblLoading" class="bg-white min-w-max py-4">
                            <table class="min-w-full text-sm border border-gray-200">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200 w-16">
                                            <input type="checkbox" :checked="isAllOnPageSelected()" @change="toggleAllOnPage()" class="rounded border-gray-300 text-blue-600 w-4 h-4 cursor-pointer">
                                        </th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">No.PBL</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Tanggal</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Supplier</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Nilai Order</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Sisa Hutang</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="!pblRecords.length">
                                        <tr><td colspan="7" class="p-6 text-center text-gray-500 border-b border-gray-200">Tidak ada data faktur.</td></tr>
                                    </template>
                                    <template x-for="record in pblRecords" :key="record.fstockmtid">
                                        <tr class="bg-white hover:bg-gray-50 cursor-pointer" @click="togglePblSelection(record)">
                                            <td class="p-3 text-center border-b border-r border-gray-200" @click.stop>
                                                <input type="checkbox" :checked="isPblSelected(record)" @change="togglePblSelection(record)" class="rounded border-gray-300 text-blue-600 w-4 h-4 cursor-pointer">
                                            </td>
                                            <td class="p-3 border-b border-r border-gray-200 font-mono" x-text="record.fstockmtno || '-'"></td>
                                            <td class="p-3 border-b border-r border-gray-200" x-text="formatDateDisplay(record.fstockmtdate)"></td>
                                            <td class="p-3 border-b border-r border-gray-200">
                                                <div class="font-medium" x-text="record.fsuppliername || '-'"></div>
                                                <div class="text-xs text-gray-500" x-text="record.fsupplier || '-'"></div>
                                            </td>
                                            <td class="p-3 border-b border-r border-gray-200 text-right" x-text="formatNumber(record.famountmt)"></td>
                                            <td class="p-3 border-b border-r border-gray-200 text-right" x-text="formatNumber(record.famountremain)"></td>
                                            <td class="p-3 border-b" x-text="formatDateDisplay(record.ftgljatuhtempo)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between gap-3 flex-shrink-0 bg-gray-50 text-sm text-gray-600">
                        <div x-text="pblInfoText"></div>
                        <div class="flex items-center gap-4">
                            <button type="button" @click="submitSelectedPbls()" :disabled="!tempSelectedPbls.length"
                                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-medium text-sm">
                                Submit (<span x-text="tempSelectedPbls.length"></span>)
                            </button>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="prevPblPage()" :disabled="pblPage <= 1" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400">Sebelumnya</button>
                                <span class="font-medium" x-text="`Hal. ${pblPage}`"></span>
                                <button type="button" @click="nextPblPage()" :disabled="!canNextPblPage" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400">Selanjutnya</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>

@if (!$isReadOnly)
    <x-transaction.browse-supplier-modal />
    <x-transaction.browse-account-modal
        table-id="adminAccountTable"
        controls-id="adminAccountTableControls"
        pagination-id="adminAccountTablePagination"
        event-name="admin-account-browse-open"
        :fend="1"
        show-controls="true"
        show-pagination="true"
    />
@endif

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        .transaction-code-red:disabled {
            color: #dc2626 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        function bayarSupplierForm(initialRows, initialTempo) {
            return {
                rows: [],
                supplierCode: @js($selectedSupplierCode),
                supplierTempo: Number(initialTempo || 0),
                accountCode: @js($selectedAccountCode),
                accountLabel: @js($selectedAccountCode !== '' ? trim($selectedAccountCode . ' - ' . $selectedAccountName) : ''),
                adminAccountCode: @js($selectedAdminAccount->faccount ?? ''),
                adminAccountLabel: @js(isset($selectedAdminAccount) ? trim($selectedAdminAccount->faccount . ' - ' . $selectedAdminAccount->faccname) : ''),
                adminAccount2Code: @js($selectedAdminAccount2->faccount ?? ''),
                adminAccount2Label: @js(isset($selectedAdminAccount2) ? trim($selectedAdminAccount2->faccount . ' - ' . $selectedAdminAccount2->faccname) : ''),
                activeAccountField: null,
                transactionDate: @js(old('fkasmtdate', $transactionDate)),
                dueDate: @js(old('ftgljatuhtempo', $dueDate ?? '')),
                isGiroMundur: @js(old('fgiromundur', ($giroMundur ?? false) ? '1' : '0') === '1'),
                bankAdminFee: @js((float) old('fbiayaadminbank', $bankAdminFee ?? 0)),
                hargaAdmin: @js((float) old('fhargaadmin', $hargaAdminValue ?? 0)),
                hargaAdmin2: @js((float) old('fhargaadmin2', $hargaAdmin2Value ?? 0)),
                totalBayarDisplay: '0.00',
                pblModalOpen: false,
                pblLoading: false,
                pblSearch: '',
                pblError: '',
                pblRecords: [],
                pblLength: 10,
                pblPage: 1,
                pblRecordsFiltered: 0,
                pblRecordsTotal: 0,
                tempSelectedPbls: [],

                init() {
                    const isEdit = @js(isset($headerData));
                    this.rows = (Array.isArray(initialRows) && initialRows.length ? initialRows : [])
                        .map((row, index) => {
                            const normalized = this.normalizeRow(row, index);
                            if (isEdit) {
                                normalized.originalSisa = Math.abs(normalized.fsisa_hutang) + Math.abs(normalized.fkasdtvalue) + Math.abs(normalized.fdiscount);
                            } else {
                                normalized.originalSisa = normalized.fsisa_hutang;
                            }
                            return normalized;
                        });

                    this.ensureMinimumRows();
                    this.recalcTotals();

                    this.$watch('rows', () => {
                        this.ensureMinimumRows();
                        this.ensureTrailingRow();
                    }, { deep: true });

                    window.addEventListener('supplier-picked', (event) => {
                        if (this.hasSelectedPbls) return;
                        const detail = event.detail || {};
                        const code = String(detail.fsuppliercode || '').trim();
                        const name = String(detail.fsuppliername || '').trim();

                        this.supplierCode = code;
                        this.supplierTempo = Number(detail.ftempo || 0);
                        if (this.isGiroMundur) this.syncDueDate();

                        const select = document.getElementById('modal_filter_supplier_id');
                        if (select) {
                            const label = name ? `${name} (${code})` : code;
                            let option = Array.from(select.options).find((item) => item.value === code);
                            if (!option) {
                                option = new Option(label, code, true, true);
                                select.add(option);
                            } else {
                                option.text = label;
                                option.selected = true;
                            }
                        }
                    });

                    window.addEventListener('account-picked', (event) => {
                        const detail = event.detail || {};
                        const code = String(detail.faccount || '').trim();
                        const name = String(detail.faccname || '').trim();
                        if (this.activeAccountField === 'admin') {
                            this.adminAccountCode = code;
                            this.adminAccountLabel = code && name ? `${code} - ${name}` : code;
                        } else if (this.activeAccountField === 'admin2') {
                            this.adminAccount2Code = code;
                            this.adminAccount2Label = code && name ? `${code} - ${name}` : code;
                        } else {
                            this.accountCode = code;
                            this.accountLabel = code && name ? `${code} - ${name}` : code;
                        }
                    });

                    this.$watch('transactionDate', () => { if (this.isGiroMundur) this.syncDueDate(); });
                    this.$watch('isGiroMundur', (value) => value ? this.syncDueDate() : this.dueDate = '');
                    this.$watch('bankAdminFee', () => this.recalcTotals());
                    this.$watch('hargaAdmin', () => this.recalcTotals());
                    this.$watch('hargaAdmin2', () => this.recalcTotals());
                },

                get hasSelectedPbls() {
                    return this.rows.some(row => String(row?.frefno || '').trim() !== '');
                },

                emptyRow() {
                    return { uid: this.makeUid(), frefno: '', ftrcode: 'BUY', fsupplier: '', fsuppliername: '', ftempo: 0, fnilai_order: 0, fsisa_hutang: 0, originalSisa: 0, fdiscpersen: 0, fdiscount: 0, fkasdtvalue: 0 };
                },

                normalizeRow(row = {}, index = 0) {
                    const sisa = this.toNumber(row.fsisa_hutang);
                    const absSisa = Math.abs(sisa);
                    return {
                        uid: row.uid || `bs-row-${index}-${this.makeUid()}`,
                        frefno: String(row.frefno || '').trim(),
                        ftrcode: String(row.ftrcode || 'BUY').trim() || 'BUY',
                        fsupplier: String(row.fsupplier || '').trim(),
                        fsuppliername: String(row.fsuppliername || '').trim(),
                        ftempo: Number(row.ftempo || 0),
                        fnilai_order: this.toNumber(row.fnilai_order),
                        fsisa_hutang: sisa,
                        originalSisa: row.originalSisa !== undefined ? Math.abs(this.toNumber(row.originalSisa)) : absSisa,
                        fdiscpersen: this.toNumber(row.fdiscpersen),
                        fdiscount: this.toNumber(row.fdiscount),
                        fkasdtvalue: this.toNumber(row.fkasdtvalue),
                    };
                },

                ensureMinimumRows() { while (this.rows.length < 5) this.rows.push(this.emptyRow()); },
                ensureTrailingRow() { if (this.rows.length && String(this.rows[this.rows.length - 1].frefno || '').trim() !== '') this.rows.push(this.emptyRow()); },
                makeUid() { return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`; },
                toNumber(value) {
                    if (typeof value === 'string') {
                        value = value.replace(/,/g, '').trim();
                    }
                    const number = Number(value);
                    return Number.isFinite(number) ? number : 0;
                },
                formatNumber(value) { return this.toNumber(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                showRawNumber(event, row, field) {
                    event.target.value = this.toNumber(row?.[field]).toFixed(2);
                    event.target.select();
                },
                setNumericField(row, field, value) {
                    row[field] = this.toNumber(value);
                },
                formatNumericField(event, row, field) {
                    row[field] = this.toNumber(row?.[field]);
                    event.target.value = this.formatNumber(row[field]);
                },
                formatDateDisplay(value) {
                    if (!value) return '-';
                    const date = new Date(`${value}T00:00:00`);
                    if (Number.isNaN(date.getTime())) return value;
                    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
                },

                isRebRow(row) {
                    const code = String(row?.ftrcode || '').trim().toUpperCase();
                    const refNo = String(row?.frefno || '').trim().toUpperCase();
                    return code === 'REB' || refNo.startsWith('REB.');
                },

                isDiscPercentDisabled(row) {
                    if (this.isRebRow(row)) return true;
                    // Disc% disabled when Discount (Rp) has been manually filled
                    return this.toNumber(row?.fdiscount) > 0;
                },

                isDiscountDisabled(row) {
                    if (this.isRebRow(row)) return true;
                    // Discount (Rp) disabled when Disc% has been manually filled
                    return this.toNumber(row?.fdiscpersen) > 0;
                },

                referenceTextClass(row) {
                    const code = String(row?.ftrcode || '').trim().toUpperCase();
                    return ['REJ', 'REB'].includes(code) ? 'text-red-600 transaction-code-red' : 'text-black';
                },

                handleManualPblInput(row) {
                    row.frefno = String(row.frefno || '').trim().toUpperCase();
                    if (!row.frefno) {
                        this.clearPblRow(row, false);
                        return;
                    }
                    row.ftrcode = 'BUY';
                },

                clearPblRow(row, keepRef = true) {
                    if (!row) return;
                    const refNo = keepRef ? String(row.frefno || '').trim() : '';
                    row.frefno = refNo;
                    row.ftrcode = 'BUY';
                    row.fsupplier = '';
                    row.fsuppliername = '';
                    row.ftempo = 0;
                    row.fnilai_order = 0;
                    row.fsisa_hutang = 0;
                    row.originalSisa = 0;
                    row.fdiscpersen = 0;
                    row.fdiscount = 0;
                    row.fkasdtvalue = 0;
                    this.recalcTotals();
                },

                async resolveManualPbl(row, silent = false) {
                    const refNo = String(row?.frefno || '').trim();
                    if (!refNo) {
                        this.clearPblRow(row, false);
                        return;
                    }

                    if (silent && refNo.length < 5) return;

                    const duplicate = this.rows.find(item => item !== row && String(item.frefno || '').trim().toUpperCase() === refNo.toUpperCase());
                    if (duplicate) {
                        if (!silent) this.showValidationError(`No. penerimaan ${refNo} tidak boleh sama.`);
                        this.clearPblRow(row, false);
                        return;
                    }

                    try {
                        const params = new URLSearchParams({
                            supplier_code: this.supplierCode || '',
                            search: refNo,
                            start: '0',
                            length: '10',
                            draw: '1',
                            order_column: 'fstockmtdate',
                            order_dir: 'desc'
                        });
                        const response = await fetch(`{{ route('bayarsupplier.pickable-pbl') }}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                        if (!response.ok) throw new Error('Gagal memuat data faktur.');

                        const payload = await response.json();
                        const record = (Array.isArray(payload.data) ? payload.data : [])
                            .find(item => String(item.fstockmtno || '').trim().toUpperCase() === refNo.toUpperCase());

                        if (!record) {
                            this.clearPblRow(row);
                            if (!silent) this.showValidationError(`No. penerimaan ${refNo} tidak ditemukan.`);
                            return;
                        }

                        if (!this.isPblSupplierValid(record)) {
                            this.clearPblRow(row);
                            if (!silent) this.showValidationError('Nota harus sesuai supplier yang dipilih.');
                            return;
                        }

                        this.applyPblToRow(row, record);
                    } catch (error) {
                        this.clearPblRow(row);
                        if (!silent) this.showValidationError(error?.message || 'Gagal memuat data faktur.');
                    }
                },

                applyPblToRow(targetRow, record) {
                    const remain = this.toNumber(record.famountremain);
                    const invoiceAmount = this.toNumber(record.famountmt);
                    if (Math.abs(remain) > Math.abs(invoiceAmount)) {
                        this.clearPblRow(targetRow);
                        this.showValidationError('Sisa hutang tidak boleh melebihi nilai nota.');
                        return;
                    }

                    targetRow.frefno = String(record.fstockmtno || '').trim();
                    targetRow.ftrcode = String(record.ftrcode || record.fstockmtcode || 'BUY').trim() || 'BUY';
                    targetRow.fsupplier = String(record.fsupplier || '').trim();
                    targetRow.fsuppliername = String(record.fsuppliername || '').trim();
                    targetRow.ftempo = Number(record.ftempo || 0);
                    if (!String(this.supplierCode || '').trim() && targetRow.fsupplier) {
                        this.syncSupplierFromPbl(record);
                    }
                    targetRow.fnilai_order = invoiceAmount;
                    
                    const isReb = this.isRebRow(targetRow);
                    targetRow.fsisa_hutang = isReb ? 0 : remain;
                    targetRow.originalSisa = remain;
                    targetRow.fdiscpersen = 0;
                    targetRow.fdiscount = 0;
                    targetRow.fkasdtvalue = isReb ? -remain : remain;
                    
                    this.recalcTotals();
                    this.ensureTrailingRow();
                },

                openPblModal() {
                    this.tempSelectedPbls = this.rows.filter(row => row.frefno).map(row => ({
                        fstockmtno: row.frefno,
                        fstockmtcode: row.ftrcode || 'BUY',
                        ftrcode: row.ftrcode || 'BUY',
                        fsupplier: String(row.fsupplier || this.supplierCode || '').trim(),
                        fsuppliername: String(row.fsuppliername || '').trim(),
                        ftempo: Number(row.ftempo || this.supplierTempo || 0),
                        famountmt: row.fnilai_order,
                        famountremain: row.fsisa_hutang,
                    }));
                    this.pblPage = 1;
                    this.pblModalOpen = true;
                    this.fetchPblRecords();
                },

                closePblModal() { this.pblModalOpen = false; this.pblError = ''; },

                async fetchPblRecords() {
                    this.pblLoading = true;
                    this.pblError = '';
                    try {
                        const params = new URLSearchParams({
                            supplier_code: this.supplierCode || '',
                            search: this.pblSearch || '',
                            start: String((this.pblPage - 1) * this.pblLength),
                            length: String(this.pblLength),
                            draw: '1',
                            order_column: 'fstockmtdate',
                            order_dir: 'desc'
                        });
                        const response = await fetch(`{{ route('bayarsupplier.pickable-pbl') }}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                        if (!response.ok) throw new Error('Gagal memuat data faktur.');
                        const payload = await response.json();
                        this.pblRecords = Array.isArray(payload.data) ? payload.data : [];
                        this.pblRecordsFiltered = Number(payload.recordsFiltered || 0);
                        this.pblRecordsTotal = Number(payload.recordsTotal || 0);
                    } catch (error) {
                        this.pblRecords = [];
                        this.pblRecordsFiltered = 0;
                        this.pblRecordsTotal = 0;
                        this.pblError = error?.message || 'Gagal memuat data faktur.';
                    } finally {
                        this.pblLoading = false;
                    }
                },

                get pblInfoText() {
                    if (!this.pblRecordsFiltered) return 'Tidak ada data';
                    const start = ((this.pblPage - 1) * this.pblLength) + 1;
                    const end = start + this.pblRecords.length - 1;
                    return `Menampilkan ${start} - ${end} dari ${this.pblRecordsFiltered} data`;
                },
                get canNextPblPage() { return (this.pblPage * this.pblLength) < this.pblRecordsFiltered; },
                prevPblPage() { if (this.pblPage > 1) { this.pblPage -= 1; this.fetchPblRecords(); } },
                nextPblPage() { if (this.canNextPblPage) { this.pblPage += 1; this.fetchPblRecords(); } },
                isPblSelected(record) { return this.tempSelectedPbls.some(item => String(item.fstockmtno).trim() === String(record.fstockmtno).trim()); },
                showPblSelectionError(message) {
                    if (window.showTransactionErrorModal) {
                        window.showTransactionErrorModal(message);
                        return;
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Terjadi kesalahan', text: message });
                        return;
                    }
                    alert(message);
                },
                isPblSupplierValid(record, supplierCode = null) {
                    const selectedSupplier = String(supplierCode ?? (this.supplierCode || '')).trim();
                    const pblSupplier = String(record.fsupplier || '').trim();
                    if (pblSupplier === '') return false;
                    return selectedSupplier === '' || selectedSupplier === pblSupplier;
                },
                syncSupplierFromPbl(record) {
                    const code = String(record.fsupplier || '').trim();
                    if (!code || String(this.supplierCode || '').trim()) return;

                    const name = String(record.fsuppliername || '').trim();
                    this.supplierCode = code;
                    this.supplierTempo = Number(record.ftempo || 0);
                    if (this.isGiroMundur) this.syncDueDate();

                    const select = document.getElementById('modal_filter_supplier_id');
                    if (select) {
                        const label = name ? `${name} (${code})` : code;
                        let option = Array.from(select.options).find((item) => item.value === code);
                        if (!option) {
                            option = new Option(label, code, true, true);
                            select.add(option);
                        } else {
                            option.text = label;
                            option.selected = true;
                        }
                        select.value = code;
                    }
                },
                togglePblSelection(record) {
                    const remain = this.toNumber(record.famountremain);
                    if (remain <= 0) return;

                    const pblSupplier = String(record.fsupplier || '').trim();
                    if (pblSupplier === '') {
                        this.showPblSelectionError('supplier belum terisi.');
                        return;
                    }

                    if (!this.isPblSupplierValid(record)) {
                        this.showPblSelectionError('Nota harus sesuai supplier yang dipilih.');
                        return;
                    }

                    const idx = this.tempSelectedPbls.findIndex(item => String(item.fstockmtno).trim() === String(record.fstockmtno).trim());
                    if (idx > -1) this.tempSelectedPbls.splice(idx, 1); else this.tempSelectedPbls.push(record);
                },
                isAllOnPageSelected() { return this.pblRecords.length > 0 && this.pblRecords.every(record => this.toNumber(record.famountremain) <= 0 || this.isPblSelected(record)); },
                toggleAllOnPage() {
                    const allSelected = this.isAllOnPageSelected();
                    let hasInvalidSupplier = false;
                    let hasMissingSupplier = false;
                    const selectedSupplier = String(this.supplierCode || '').trim();

                    this.pblRecords.forEach(record => {
                        if (this.toNumber(record.famountremain) <= 0) return;
                        const pblSupplier = String(record.fsupplier || '').trim();
                        if (pblSupplier === '') {
                            hasMissingSupplier = true;
                            return;
                        }
                        if (selectedSupplier !== '' && selectedSupplier !== pblSupplier) {
                            hasInvalidSupplier = true;
                            return;
                        }
                    });

                    if (hasMissingSupplier) {
                        this.showPblSelectionError('supplier belum terisi.');
                        return;
                    }
                    if (hasInvalidSupplier) {
                        this.showPblSelectionError('Nota harus sesuai supplier yang dipilih.');
                        return;
                    }

                    this.pblRecords.forEach(record => {
                        if (this.toNumber(record.famountremain) <= 0) return;
                        const pblSupplier = String(record.fsupplier || '').trim();
                        if (pblSupplier === '') return;
                        if (selectedSupplier !== '' && selectedSupplier !== pblSupplier) return;
                        const idx = this.tempSelectedPbls.findIndex(item => String(item.fstockmtno).trim() === String(record.fstockmtno).trim());
                        if (allSelected) { if (idx > -1) this.tempSelectedPbls.splice(idx, 1); }
                        else if (idx === -1) this.tempSelectedPbls.push(record);
                    });
                },
                findTargetRowForPbl() {
                    const emptyIndex = this.rows.findIndex(row => !String(row.frefno || '').trim());
                    if (emptyIndex !== -1) return this.rows[emptyIndex];
                    const row = this.emptyRow();
                    this.rows.push(row);
                    return row;
                },
                handleFormSubmit(event) {
                    const refs = new Set();
                    const duplicate = this.rows.find(row => {
                        const refNo = String(row.frefno || '').trim().toUpperCase();
                        if (!refNo) return false;
                        if (refs.has(refNo)) return true;
                        refs.add(refNo);
                        return false;
                    });

                    if (duplicate) {
                        event.preventDefault();
                        this.showValidationError(`No. penerimaan ${duplicate.frefno} tidak boleh sama.`);
                        return;
                    }

                    const invalidRemaining = this.rows.find(row => {
                        const refNo = String(row.frefno || '').trim();
                        if (!refNo) return false;
                        const invoiceAmount = Math.abs(this.toNumber(row.fnilai_order));
                        const remainingAmount = Math.abs(this.toNumber(row.fsisa_hutang));
                        return invoiceAmount > 0 && remainingAmount > invoiceAmount;
                    });

                    if (invalidRemaining) {
                        event.preventDefault();
                        this.showValidationError('Sisa hutang tidak boleh melebihi nilai nota.');
                        return;
                    }

                    const invalidRetur = this.rows.find(row => this.isRebRow(row) && this.toNumber(row.fkasdtvalue) >= 0);
                    if (invalidRetur) {
                        event.preventDefault();
                        this.showValidationError('Harus Mengurangi Hutang.,Penyimpanan dibatalkan.');
                    }
                },
                submitSelectedPbls() {
                    const selectedSupplier = String(this.supplierCode || '').trim();
                    const inferredSupplier = selectedSupplier !== ''
                        ? selectedSupplier
                        : String(this.tempSelectedPbls[0]?.fsupplier || '').trim();

                    const invalidSupplier = this.tempSelectedPbls.find(record => {
                        const pblSupplier = String(record.fsupplier || '').trim();
                        if (pblSupplier === '') return true;
                        return inferredSupplier !== '' && pblSupplier !== inferredSupplier;
                    });

                    if (invalidSupplier) {
                        if (String(invalidSupplier.fsupplier || '').trim() === '') {
                            this.showPblSelectionError('supplier belum terisi.');
                        } else {
                            this.showPblSelectionError('Nota harus sesuai supplier yang dipilih.');
                        }
                        return;
                    }

                    if (!selectedSupplier && this.tempSelectedPbls.length) {
                        this.syncSupplierFromPbl(this.tempSelectedPbls[0]);
                    }

                    const selectedNos = this.tempSelectedPbls.map(item => String(item.fstockmtno).trim());
                    this.rows = this.rows.filter(row => String(row.frefno || '').trim() === '' || selectedNos.includes(String(row.frefno || '').trim()));
                    this.tempSelectedPbls.forEach(record => {
                        const refNo = String(record.fstockmtno || '').trim();
                        if (!refNo || this.rows.some(row => String(row.frefno || '').trim() === refNo)) return;
                        this.applyPblToRow(this.findTargetRowForPbl(), record);
                    });
                    this.ensureMinimumRows();
                    this.recalcTotals();
                    this.closePblModal();
                },
                removeRow(index) { this.rows.splice(index, 1); this.recalcTotals(); },
                showValidationError(message) {
                    if (window.showTransactionErrorModal) {
                        window.showTransactionErrorModal(message);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Validasi', text: message });
                    } else {
                        alert(message);
                    }
                },

                /**
                 * When user inputs Disc%:
                 *   Total Bayar  = originalSisa - (originalSisa × Disc% / 100)
                 *   Discount(Rp) = 0 and DISABLED (mutually exclusive)
                 *   Sisa Hutang  = 0
                 */
                syncDiscountFromPercent(row, event) {
                    const percent = this.toNumber(row.fdiscpersen);
                    const original = this.toNumber(row.originalSisa);

                    if (percent > 100) {
                        this.showValidationError('Disc% tidak boleh melebihi 100%');
                        row.fdiscpersen = 100;
                        if (event && event.target) event.target.value = 100;
                    }

                    const validPercent = Math.min(Math.max(this.toNumber(row.fdiscpersen), 0), 100);
                    row.fdiscpersen = validPercent;
                    row.fdiscount = 0;

                    if (validPercent > 0) {
                        const discAmount = parseFloat((original * validPercent / 100).toFixed(2));
                        row.fkasdtvalue = parseFloat(Math.max(original - discAmount, 0).toFixed(2));
                        row.fsisa_hutang = 0;
                    } else {
                        row.fkasdtvalue = parseFloat(Math.max(original, 0).toFixed(2));
                        row.fsisa_hutang = 0;
                    }
                    this.recalcTotals();
                },

                /**
                 * When user inputs Discount (Rp):
                 *   Total Bayar  = originalSisa - Discount(Rp)
                 *   Disc%        = 0 and DISABLED (mutually exclusive)
                 *   Sisa Hutang  = 0
                 */
                syncDiscountFromRp(row, inputValue) {
                    const discount = this.toNumber(inputValue);
                    const original = this.toNumber(row.originalSisa);

                    if (discount > original) {
                        this.showValidationError('Discount tidak boleh melebihi Sisa Hutang awal');
                        row.fdiscount = original;
                    } else {
                        row.fdiscount = parseFloat(Math.max(discount, 0).toFixed(2));
                    }

                    row.fdiscpersen = 0;
                    row.fkasdtvalue = parseFloat(Math.max(original - row.fdiscount, 0).toFixed(2));
                    row.fsisa_hutang = 0;
                    this.recalcTotals();
                },

                /**
                 * When user inputs Total Bayar:
                 *   Sisa Hutang = originalSisa - Total Bayar - effectiveDiscount
                 *   Disc% and Discount(Rp) do NOT change.
                 * effectiveDiscount = (Disc% > 0) ? originalSisa × Disc%/100 : Discount(Rp)
                 */
                syncTotalBayarInput(row, inputValue) {
                    const original = this.toNumber(row.originalSisa);

                    if (this.isRebRow(row)) {
                        if (this.toNumber(inputValue) >= 0) {
                            this.showValidationError('Harus Mengurangi Hutang.,Penyimpanan dibatalkan.');
                        }

                        const pay = Math.abs(this.toNumber(inputValue));
                        if (pay > original) {
                            this.showValidationError('Total Bayar tidak boleh melebihi sisa retur');
                            row.fkasdtvalue = -original;
                            row.fsisa_hutang = 0;
                            this.recalcTotals();
                            return;
                        }
                        row.fkasdtvalue = -pay;
                        row.fsisa_hutang = parseFloat(Math.max(original - pay, 0).toFixed(2));
                        this.recalcTotals();
                        return;
                    }

                    const pay = this.toNumber(inputValue);
                    const effectiveDiscount = row.fdiscpersen > 0
                        ? parseFloat((original * row.fdiscpersen / 100).toFixed(2))
                        : parseFloat(Math.max(this.toNumber(row.fdiscount), 0).toFixed(2));

                    const maxPay = parseFloat(Math.max(original - effectiveDiscount, 0).toFixed(2));

                    if (pay > maxPay) {
                        this.showValidationError('Total Bayar melebihi tagihan yang tersisa');
                        row.fkasdtvalue = maxPay;
                        row.fsisa_hutang = 0;
                        this.recalcTotals();
                        return;
                    }

                    row.fkasdtvalue = parseFloat(Math.max(pay, 0).toFixed(2));
                    row.fsisa_hutang = parseFloat(Math.max(original - row.fkasdtvalue - effectiveDiscount, 0).toFixed(2));
                    this.recalcTotals();
                },
                recalcTotals() {
                    const totalBayar = this.rows.reduce((sum, row) => sum + this.toNumber(row.fkasdtvalue), 0)
                        + this.toNumber(this.bankAdminFee)
                        + this.toNumber(this.hargaAdmin)
                        + this.toNumber(this.hargaAdmin2);
                    this.totalBayarDisplay = this.formatNumber(totalBayar);
                },
                syncDueDate() {
                    if (!this.transactionDate) return;
                    const baseDate = new Date(`${this.transactionDate}T00:00:00`);
                    if (Number.isNaN(baseDate.getTime())) return;
                    baseDate.setDate(baseDate.getDate() + this.supplierTempo);
                    this.dueDate = `${baseDate.getFullYear()}-${String(baseDate.getMonth() + 1).padStart(2, '0')}-${String(baseDate.getDate()).padStart(2, '0')}`;
                }
            }
        }
    </script>
@endpush
