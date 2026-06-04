@php
    $pageTitle = $pageTitle ?? 'Pelunasan Customer';
    $formAction = $formAction ?? route('pelunasancustomer.store');
    $formMethod = strtoupper($formMethod ?? 'POST');
    $isReadOnly = (bool) ($isReadOnly ?? false);
    $isDeleteMode = (bool) ($isDeleteMode ?? false);
    $submitLabel = $submitLabel ?? 'Simpan';
    $backRoute = $backRoute ?? route('pelunasancustomer.index');
    $draftKey = $draftKey ?? 'pelunasancustomer:create';
    $headerData = $headerData ?? null;
    $oldDetails = old('details', []);
    $seedDetails = is_array($oldDetails) && count($oldDetails) > 0 ? $oldDetails : ($detailRows ?? [[]]);
    $initialDetailRows = collect($seedDetails)
        ->values()
        ->map(function ($detail, $index) {
            return [
                'uid' => 'pc-' . $index . '-' . substr(md5((string) $index), 0, 8),
                'frefno' => trim((string) ($detail['frefno'] ?? '')),
                'fnilai_nota' => (float) ($detail['fnilai_nota'] ?? 0),
                'fsisa_piutang' => (float) ($detail['fsisa_piutang'] ?? 0),
                'fdiscpersen' => (float) ($detail['fdiscpersen'] ?? 0),
                'fdiscount' => (float) ($detail['fdiscount'] ?? 0),
                'fkasdtvalue' => (float) ($detail['fkasdtvalue'] ?? 0),
                'ftrcode' => trim((string) ($detail['ftrcode'] ?? $detail['freftype'] ?? 'INV')),
                'fdatetime' => !empty($detail['fdatetime']) ? \Illuminate\Support\Carbon::parse($detail['fdatetime'])->format('Y-m-d') : '',
            ];
        })
        ->all();

    $selectedCustomerCode = trim((string) old('fcustomer', $selectedCustomer->fcustomercode ?? ''));
    $selectedCustomerName = trim((string) ($selectedCustomer->fcustomername ?? ''));
    $selectedCustomerTempo = (int) old('fcustomer_tempo', $selectedCustomer->ftempo ?? 0);
    $selectedAccountCode = trim((string) old('faccountheader', $selectedAccount->faccount ?? ''));
    $selectedAccountName = trim((string) ($selectedAccount->faccname ?? ''));
    $selectedCustomerLabel = $selectedCustomerCode !== ''
        ? trim($selectedCustomerName . ' (' . $selectedCustomerCode . ')')
        : '';
    $selectedAdminAccount = $selectedAdminAccount ?? null;
    $selectedAdminAccount2 = $selectedAdminAccount2 ?? null;
@endphp

<div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto"
    x-data="pelunasanCustomerForm(@js($initialDetailRows), @js($selectedCustomerTempo))" x-init="init()">
    <form action="{{ $formAction }}" method="POST" class="space-y-6"
        @if (!$isReadOnly && !empty($draftKey)) data-form-draft="true" data-draft-key="{{ $draftKey }}" @endif>
        @csrf
        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <input type="hidden" name="fcustomer_tempo" x-model="customerTempo">
        <fieldset @disabled($isReadOnly) class="space-y-3.5">
            <!-- Row 1: Branch, Voucher Number, Date -->
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Cabang' }}</label>
                    <input type="text" value="{{ $currentBranchLabel ?? old('fbranchcode', $currentBranchCode) }}"
                        class="w-full border rounded px-3 py-1.5 bg-gray-100 cursor-not-allowed text-gray-700" readonly>
                    <input type="hidden" name="fbranchcode" value="{{ old('fbranchcode', $currentBranchCode) }}">
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'No. Voucher' }}</label>
                    <input type="text" name="fkasmtno" value="{{ old('fkasmtno', $voucherNo) }}"
                        class="w-full border rounded px-3 py-1.5 @error('fkasmtno') border-red-500 @enderror"
                        placeholder="Kosongkan untuk auto number">
                    @error('fkasmtno')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Tanggal' }}</label>
                    <input type="date" name="fkasmtdate" x-model="transactionDate"
                        value="{{ old('fkasmtdate', $transactionDate) }}"
                        class="w-full border rounded px-3 py-1.5 @error('fkasmtdate') border-red-500 @enderror">
                    @error('fkasmtdate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Row 2: Customer, Account, Giro/Cek -->
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Customer' }}</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_customer_id" name="filter_customer_id"
                                class="w-full border rounded-l px-3 py-1.5 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value="{{ $selectedCustomerCode }}">{{ $selectedCustomerLabel }}</option>
                            </select>
                            @if (!$isReadOnly)
                                <div class="absolute inset-0" role="button" aria-label="Browse Customer"
                                    @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"></div>
                            @endif
                        </div>
                        <input type="hidden" name="fcustomer" id="customerCodeHidden" x-model="customerCode">
                        @if (!$isReadOnly)
                            <button type="button" @click="window.dispatchEvent(new CustomEvent('customer-browse-open'))"
                                class="border -ml-px px-3 py-1.5 bg-white hover:bg-gray-50 rounded-r" title="Browse Customer">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                        @endif
                    </div>
                    @error('fcustomer')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Account' }}</label>
                    <div class="flex">
                        <input type="text" x-model="accountLabel"
                            class="w-full border rounded-l px-3 py-1.5 bg-gray-100 cursor-not-allowed" readonly>
                        <input type="hidden" name="faccountheader" x-model="accountCode">
                        @if (!$isReadOnly)
                            <button type="button" @click="activeAccountField = 'main'; window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                class="border -ml-px px-3 py-1.5 bg-white hover:bg-gray-50 rounded-r" title="Browse Account">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                        @endif
                    </div>
                    @error('faccountheader')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'No.Giro/Cek' }}</label>
                    <input type="text" name="fnogiro" value="{{ old('fnogiro', $giroNo ?? '') }}"
                        class="w-full border rounded px-3 py-1.5 @error('fnogiro') border-red-500 @enderror">
                    @error('fnogiro')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Row 3: Giro Mundur, Due Date -->
            <div class="grid grid-cols-3 gap-3 items-end">
                <div>
                    <label class="inline-flex items-center gap-2 h-9 px-3 border rounded w-full bg-white">
                        <input type="checkbox" x-model="isGiroMundur" class="rounded">{{ 'Giro Mundur' }}
                    </label>
                    <input type="hidden" name="fgiromundur" :value="isGiroMundur ? '1' : '0'">
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Tgl.Jatuh Tempo' }}</label>
                    <input type="date" name="ftgljatuhtempo" x-model="dueDate"
                        class="w-full border rounded px-3 py-1.5 @error('ftgljatuhtempo') border-red-500 @enderror"
                        :readonly="!isGiroMundur" :disabled="!isGiroMundur"
                        :class="!isGiroMundur ? 'bg-gray-100 cursor-not-allowed text-gray-400' : 'bg-white'">
                    @error('ftgljatuhtempo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div></div>
            </div>

            <!-- Row 4: Description -->
            <div>
                <label class="block text-sm font-bold mb-1">{{ 'Keterangan' }}</label>
                <input type="text" name="fket" value="{{ old('fket', $noteValue ?? '') }}"
                    class="w-full border rounded px-3 py-1.5 @error('fket') border-red-500 @enderror">
                @error('fket')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-800">{{ 'Detail Item' }}</h3>
                </div>

                <div class="overflow-auto border rounded-lg">
                    <table class="min-w-full text-sm balanced-detail-table">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-2 py-1.5 w-12">{{ 'No' }}</th>
                                <th class="border px-2 py-1.5 min-w-[12rem]">{{ 'No.Nota' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-left">{{ 'Tgl. Nota' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-right">{{ 'Nilai Nota' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-right">{{ 'Sisa Piutang' }}</th>
                                <th class="border px-2 py-1.5 min-w-[8rem] text-right">{{ 'Disc%' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-right">{{ 'Discount' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-right">{{ 'Total Bayar' }}</th>
                                @if (!$isReadOnly)
                                    <th class="border px-2 py-1.5 w-16 text-center">{{ 'Aksi' }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="row.uid">
                                <tr>
                                    <td class="border px-2 py-1 text-center" x-text="index + 1"></td>
                                    <td class="border px-2 py-1">
                                        <input type="text" :name="`details[${index}][frefno]`" x-model="row.frefno"
                                            class="w-full border rounded px-2 py-1">
                                        <input type="hidden" :name="`details[${index}][ftrcode]`" :value="row.ftrcode || 'INV'">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="date" x-model="row.fdatetime"
                                            class="w-full border rounded px-2 py-1 bg-gray-100 cursor-not-allowed"
                                            readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fdatetime]`" :value="row.fdatetime">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01" x-model="row.fnilai_nota"
                                            class="w-full border rounded px-2 py-1 text-right bg-gray-100 cursor-not-allowed"
                                            readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fnilai_nota]`" :value="row.fnilai_nota">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01" x-model="row.fsisa_piutang"
                                            class="w-full border rounded px-2 py-1 text-right bg-gray-100 cursor-not-allowed"
                                            readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fsisa_piutang]`" :value="row.fsisa_piutang">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" max="100" step="0.01"
                                            :name="`details[${index}][fdiscpersen]`" x-model="row.fdiscpersen"
                                            @input="syncDiscountFromPercent(row)"
                                            :disabled="isDiscPercentDisabled(row)"
                                            class="w-full border rounded px-2 py-1 text-right disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                        <input type="hidden" x-show="isDiscPercentDisabled(row)"
                                            :name="`details[${index}][fdiscpersen]`" :value="row.fdiscpersen">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01"
                                            :name="`details[${index}][fdiscount]`" x-model="row.fdiscount"
                                            @input="syncTotalBayar(row)"
                                            :disabled="isDiscountDisabled(row)"
                                            class="w-full border rounded px-2 py-1 text-right disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                        <input type="hidden" x-show="isDiscountDisabled(row)"
                                            :name="`details[${index}][fdiscount]`" :value="row.fdiscount">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01"
                                            :name="`details[${index}][fkasdtvalue]`" x-model="row.fkasdtvalue"
                                            @input="syncTotalBayar(row)"
                                            class="w-full border rounded px-2 py-1 text-right disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    </td>
                                    @if (!$isReadOnly)
                                        <td class="border px-2 py-1 text-center">
                                            <div class="flex items-center justify-center">
                                                <button type="button" @click="removeRow(index)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 text-lg font-bold transition-colors duration-150"
                                                    title="Hapus baris">
                                                    -
                                                </button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 flex justify-start">
                    <button type="button" @click="openNotaModal()"
                        @disabled($isReadOnly)
                        class="inline-flex items-center justify-center gap-2 border rounded px-3 py-2 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span>{{ 'Add Nota' }}</span>
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <div class="w-full max-w-2xl">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-sm font-semibold text-gray-700">{{ 'Biaya Admin Bank (-)' }}</label>
                            <div class="w-52">
                                <input type="number" min="0" step="0.01" name="fbiayaadminbank" x-model="bankAdminFee"
                                    @input="recalcTotals()"
                                    class="w-full border rounded px-3 py-2 text-right @error('fbiayaadminbank') border-red-500 @enderror">
                            </div>
                        </div>
                        @error('fbiayaadminbank')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="flex">
                                    <input type="text" x-model="adminAccountLabel"
                                        class="w-full border rounded-l px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                                    <input type="hidden" name="faccountadmin" x-model="adminAccountCode">
                                    @if (!$isReadOnly)
                                        <button type="button" @click="activeAccountField = 'admin'; window.dispatchEvent(new CustomEvent('admin-account-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Account Admin">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                        </button>
                                    @endif
                                </div>
                                @error('faccountadmin')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <input type="number" min="0" step="0.01" name="fhargaadmin" x-model="hargaAdmin"
                                    @input="recalcTotals()"
                                    :disabled="!adminAccountCode"
                                    class="w-52 border rounded px-3 py-2 text-right text-sm @error('fhargaadmin') border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                @error('fhargaadmin')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="flex">
                                    <input type="text" x-model="adminAccount2Label"
                                        class="w-full border rounded-l px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                                    <input type="hidden" name="faccountadmin2" x-model="adminAccount2Code">
                                    @if (!$isReadOnly)
                                        <button type="button" @click="activeAccountField = 'admin2'; window.dispatchEvent(new CustomEvent('admin-account-browse-open'))"
                                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Account Admin 2">
                                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                        </button>
                                    @endif
                                </div>
                                @error('faccountadmin2')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <input type="number" min="0" step="0.01" name="fhargaadmin2" x-model="hargaAdmin2"
                                    @input="recalcTotals()"
                                    :disabled="!adminAccount2Code"
                                    class="w-52 border rounded px-3 py-2 text-right text-sm @error('fhargaadmin2') border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                @error('fhargaadmin2')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t pt-3">
                            <span class="text-sm font-semibold text-gray-800">{{ 'Total Penerimaan' }}</span>
                            <input type="text" x-model="totalPenerimaanDisplay"
                                class="w-52 border rounded px-3 py-2 bg-gray-100 text-right font-semibold cursor-not-allowed"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="flex items-center justify-center gap-3">
            <a href="{{ $backRoute }}"
                class="inline-flex items-center px-4 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50">
                {{ 'Keluar' }}
            </a>
            @if ($isDeleteMode)
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                    {{ $submitLabel }}
                </button>
            @elseif (!$isReadOnly && !empty($submitLabel))
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    {{ $submitLabel }}
                </button>
            @endif
        </div>
        @if (!$isReadOnly)
            <div x-cloak x-show="notaModalOpen" x-transition.opacity
                class="fixed inset-0 z-[95] flex items-center justify-center overflow-hidden p-3 md:p-6">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeNotaModal()"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-7xl flex flex-col overflow-hidden"
                    style="height: min(760px, calc(100vh - 1.5rem));" x-transition.scale>
                    <div
                        class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-gradient-to-r from-blue-50 to-white">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Browse nota</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Pilih nota yang ingin ditambahkan</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="submitSelectedNotas()" :disabled="!tempSelectedNotas.length"
                                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-medium text-sm transition-all duration-150 shadow-sm">
                                Submit (<span x-text="tempSelectedNotas.length"></span>)
                            </button>
                            <button type="button" @click="closeNotaModal()"
                                class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all duration-150 font-medium text-gray-700 text-sm">
                                Tutup
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pt-4 pb-2 flex-shrink-0 border-b border-gray-100">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <span class="font-medium">Cari:</span>
                                <input type="text" x-model="notaSearch" @input.debounce.400ms="notaPage = 1; fetchNotaRecords()"
                                    class="w-full md:w-[320px] rounded-lg border-2 border-gray-200 px-3 py-2 text-sm"
                                    placeholder="No.nota, customer, tipe, atau kode customer">
                            </label>

                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <span class="font-medium">Tampilkan</span>
                                    <select x-model.number="notaLength" @change="notaPage = 1; fetchNotaRecords()"
                                        class="rounded-lg border-2 border-gray-200 px-3 py-2 text-sm">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </label>
                                <button type="button" @click="fetchNotaRecords()"
                                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-auto px-6" style="min-height: 0;">
                        <div x-show="notaError" x-text="notaError"
                            class="my-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

                        <div x-show="notaLoading" class="py-10 text-center text-sm text-gray-500">
                            Memuat data nota...
                        </div>

                        <div x-show="!notaLoading" class="bg-white min-w-max py-4">
                            <table class="min-w-full text-sm border border-gray-200">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200 w-16">
                                            <input type="checkbox" :checked="isAllOnPageSelected()" @change="toggleAllOnPage()"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                        </th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">No.Nota</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Tanggal</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Customer</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Tipe</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Nilai Nota</th>
                                        <th class="text-right p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Sisa Piutang</th>
                                        <th class="text-center p-3 font-semibold text-gray-700 border-b-2 border-r border-gray-200">Item</th>
                                        <th class="text-left p-3 font-semibold text-gray-700 border-b-2 border-gray-200">Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="!notaRecords.length">
                                        <tr>
                                            <td colspan="9" class="p-6 text-center text-gray-500 border-b border-gray-200">
                                                Tidak ada data nota.
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-for="record in notaRecords" :key="record.ftranmtid">
                                        <tr class="bg-white hover:bg-gray-50 cursor-pointer" @click="toggleNotaSelection(record)">
                                            <td class="p-3 text-center border-b border-r border-gray-200 align-middle" @click.stop>
                                                <input type="checkbox" :checked="isNotaSelected(record)" @change="toggleNotaSelection(record)"
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                            </td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 align-middle font-mono" x-text="record.fsono || '-'"></td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 align-middle" x-text="formatDateDisplay(record.fsodate)"></td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 align-middle">
                                                <div class="font-medium" x-text="record.fcustomername || '-'"></div>
                                                <div class="text-xs text-gray-500" x-text="record.fcustno || '-'"></div>
                                            </td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 align-middle" x-text="record.ftrcode || '-'"></td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 text-right align-middle" x-text="formatNumber(record.famountso ?? record.famount)"></td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 text-right align-middle" x-text="formatNumber(record.famountremain)"></td>
                                            <td class="p-3 text-sm text-gray-700 border-b border-r border-gray-200 text-center align-middle" x-text="record.detail_count ?? 0"></td>
                                            <td class="p-3 text-sm text-gray-700 border-b align-middle" x-text="formatDateDisplay(record.fjatuhtempo)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between gap-3 flex-shrink-0 bg-gray-50 text-sm text-gray-600">
                        <div x-text="notaInfoText"></div>
                        <div class="flex items-center gap-4">
                            <button type="button" @click="submitSelectedNotas()" :disabled="!tempSelectedNotas.length"
                                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-medium text-sm transition-all duration-150 shadow-sm">
                                Submit (<span x-text="tempSelectedNotas.length"></span>)
                            </button>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="prevNotaPage()" :disabled="notaPage <= 1"
                                    class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    Sebelumnya
                                </button>
                                <span class="font-medium" x-text="`Hal. ${notaPage}`"></span>
                                <button type="button" @click="nextNotaPage()" :disabled="!canNextNotaPage"
                                    class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    Selanjutnya
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>

@if (!$isReadOnly)
    @include('components.transaction.browse-customer-modal')
    @include('components.transaction.browse-customer-script')
    <x-transaction.browse-account-modal />
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
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script>
        function pelunasanCustomerForm(initialRows, initialTempo) {
            return {
                rows: [],
                customerCode: @js($selectedCustomerCode),
                customerTempo: Number(initialTempo || 0),
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
                totalPenerimaanDisplay: '0.00',
                notaModalOpen: false,
                notaLoading: false,
                notaSearch: '',
                notaError: '',
                notaRecords: [],
                notaLength: 10,
                notaPage: 1,
                notaRecordsFiltered: 0,
                notaRecordsTotal: 0,
                tempSelectedNotas: [],

                init() {
                    const isEdit = @js(isset($headerData));
                    this.rows = (Array.isArray(initialRows) && initialRows.length ? initialRows : [])
                        .map((row, index) => {
                            const normalized = this.normalizeRow(row, index);
                            if (isEdit) {
                                normalized.originalSisa = normalized.fsisa_piutang + normalized.fkasdtvalue + normalized.fdiscount;
                            } else {
                                normalized.originalSisa = normalized.fsisa_piutang;
                            }
                            return normalized;
                        });

                    this.ensureMinimumRows();
                    this.recalcTotals();

                    this.$watch('rows', () => {
                        this.ensureMinimumRows();
                        this.ensureTrailingRow();
                    }, { deep: true });

                    window.addEventListener('customer-selected', (event) => {
                        const detail = event.detail || {};
                        const code = String(detail.fcustomercode || '').trim();
                        const name = String(detail.fcustomername || '').trim();

                        this.customerCode = code;
                        this.customerTempo = Number(detail.ftempo || 0);

                        if (this.isGiroMundur) {
                            this.syncDueDate();
                        }

                        const select = document.getElementById('modal_filter_customer_id');
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

                    this.$watch('transactionDate', () => {
                        if (this.isGiroMundur) {
                            this.syncDueDate();
                        }
                    });

                    this.$watch('isGiroMundur', (value) => {
                        if (value) {
                            this.syncDueDate();
                            return;
                        }
                        this.dueDate = '';
                    });

                    this.$watch('bankAdminFee', () => {
                        this.recalcTotals();
                    });

                    this.$watch('hargaAdmin', () => {
                        this.recalcTotals();
                    });

                    this.$watch('adminAccountCode', (value) => {
                        if (!value) {
                            this.hargaAdmin = 0;
                            this.recalcTotals();
                        }
                    });

                    this.$watch('hargaAdmin2', () => {
                        this.recalcTotals();
                    });

                    this.$watch('adminAccount2Code', (value) => {
                        if (!value) {
                            this.hargaAdmin2 = 0;
                            this.recalcTotals();
                        }
                    });
                },

                emptyRow() {
                    return {
                        uid: this.makeUid(),
                        frefno: '',
                        fdatetime: '',
                        fnilai_nota: 0,
                        fsisa_piutang: 0,
                        originalSisa: 0,
                        fdiscpersen: 0,
                        fdiscount: 0,
                        fkasdtvalue: 0,
                        ftrcode: 'INV',
                    };
                },

                normalizeRow(row = {}, index = 0) {
                    const sisa = this.toNumber(row.fsisa_piutang);
                    return {
                        uid: row.uid || `pc-row-${index}-${this.makeUid()}`,
                        frefno: String(row.frefno || '').trim(),
                        fdatetime: row.fdatetime || '',
                        fnilai_nota: this.toNumber(row.fnilai_nota),
                        fsisa_piutang: sisa,
                        originalSisa: row.originalSisa !== undefined ? this.toNumber(row.originalSisa) : sisa,
                        fdiscpersen: this.toNumber(row.fdiscpersen),
                        fdiscount: this.toNumber(row.fdiscount),
                        fkasdtvalue: this.toNumber(row.fkasdtvalue),
                        ftrcode: String(row.ftrcode || 'INV').trim() || 'INV',
                    };
                },

                rowHasContent(row) {
                    if (!row) return false;
                    return String(row.frefno || '').trim() !== '';
                },

                ensureMinimumRows() {
                    while (this.rows.length < 5) {
                        this.rows.push(this.emptyRow());
                    }
                },

                ensureTrailingRow() {
                    if (!this.rows.length) {
                        this.ensureMinimumRows();
                        return;
                    }
                    const lastRow = this.rows[this.rows.length - 1];
                    if (this.rowHasContent(lastRow)) {
                        this.rows.push(this.emptyRow());
                    }
                },

                makeUid() {
                    return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
                },

                toNumber(value) {
                    const number = Number(value);
                    return Number.isFinite(number) ? number : 0;
                },

                formatNumber(value) {
                    return this.toNumber(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                formatDateDisplay(value) {
                    if (!value) return '-';
                    const date = new Date(`${value}T00:00:00`);
                    if (Number.isNaN(date.getTime())) return value;
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    return `${day}/${month}/${year}`;
                },



                openNotaModal() {
                    if (!this.customerCode) {
                        if (window.showTransactionErrorModal) {
                            window.showTransactionErrorModal('Pilih customer terlebih dahulu sebelum browse nota.');
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan',
                            text: 'Pilih customer terlebih dahulu sebelum browse nota.'
                        });
                        return;
                    }

                    this.tempSelectedNotas = [];
                    this.rows.forEach(row => {
                        if (row.frefno) {
                            this.tempSelectedNotas.push({
                                fsono: row.frefno,
                                famount: row.fnilai_nota,
                                famountremain: row.fsisa_piutang,
                                ftrcode: row.ftrcode
                            });
                        }
                    });

                    this.notaPage = 1;
                    this.notaModalOpen = true;
                    this.fetchNotaRecords();
                },

                closeNotaModal() {
                    this.notaModalOpen = false;
                    this.notaError = '';
                },

                async fetchNotaRecords() {
                    this.notaLoading = true;
                    this.notaError = '';

                    try {
                        const params = new URLSearchParams({
                            customer_code: this.customerCode || '',
                            search: this.notaSearch || '',
                            start: String((this.notaPage - 1) * this.notaLength),
                            length: String(this.notaLength),
                            draw: '1',
                            order_column: 'fsodate',
                            order_dir: 'desc'
                        });

                        const response = await fetch(`{{ route('pelunasancustomer.pickable-nota') }}?${params.toString()}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Gagal memuat data nota.');
                        }

                        const payload = await response.json();
                        this.notaRecords = Array.isArray(payload.data) ? payload.data : [];
                        this.notaRecordsFiltered = Number(payload.recordsFiltered || 0);
                        this.notaRecordsTotal = Number(payload.recordsTotal || 0);
                    } catch (error) {
                        this.notaRecords = [];
                        this.notaRecordsFiltered = 0;
                        this.notaRecordsTotal = 0;
                        this.notaError = error?.message || 'Gagal memuat data nota.';
                    } finally {
                        this.notaLoading = false;
                    }
                },

                get notaInfoText() {
                    if (!this.notaRecordsFiltered) {
                        return 'Tidak ada data';
                    }

                    const start = ((this.notaPage - 1) * this.notaLength) + 1;
                    const end = start + this.notaRecords.length - 1;

                    return `Menampilkan ${start} - ${end} dari ${this.notaRecordsFiltered} data`;
                },

                get canNextNotaPage() {
                    return (this.notaPage * this.notaLength) < this.notaRecordsFiltered;
                },

                prevNotaPage() {
                    if (this.notaPage <= 1) {
                        return;
                    }

                    this.notaPage -= 1;
                    this.fetchNotaRecords();
                },

                nextNotaPage() {
                    if (!this.canNextNotaPage) {
                        return;
                    }

                    this.notaPage += 1;
                    this.fetchNotaRecords();
                },

                findTargetRowForNota() {
                    const emptyIndex = this.rows.findIndex((row) => !String(row.frefno || '').trim());
                    if (emptyIndex !== -1) {
                        return this.rows[emptyIndex];
                    }

                    const row = this.emptyRow();
                    this.rows.push(row);
                    return row;
                },

                isNotaSelected(record) {
                    return this.tempSelectedNotas.some(item => String(item.fsono).trim() === String(record.fsono).trim());
                },

                toggleNotaSelection(record) {
                    const remain = this.toNumber(record.famountremain);
                    if (remain <= 0) {
                        if (window.showTransactionErrorModal) {
                            window.showTransactionErrorModal('Nota tidak bisa dipilih karena sisa piutang harus lebih besar dari 0.');
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan',
                            text: 'Nota tidak bisa dipilih karena sisa piutang harus lebih besar dari 0.'
                        });
                        return;
                    }

                    const idx = this.tempSelectedNotas.findIndex(item => String(item.fsono).trim() === String(record.fsono).trim());
                    if (idx > -1) {
                        this.tempSelectedNotas.splice(idx, 1);
                    } else {
                        this.tempSelectedNotas.push(record);
                    }
                },

                isAllOnPageSelected() {
                    return this.notaRecords.length > 0 && this.notaRecords.every(record => {
                        const remain = this.toNumber(record.famountremain);
                        if (remain <= 0) return true;
                        return this.isNotaSelected(record);
                    });
                },

                toggleAllOnPage() {
                    const allSelected = this.isAllOnPageSelected();
                    this.notaRecords.forEach(record => {
                        const remain = this.toNumber(record.famountremain);
                        if (remain <= 0) return;

                        const idx = this.tempSelectedNotas.findIndex(item => String(item.fsono).trim() === String(record.fsono).trim());
                        if (allSelected) {
                            if (idx > -1) this.tempSelectedNotas.splice(idx, 1);
                        } else {
                            if (idx === -1) this.tempSelectedNotas.push(record);
                        }
                    });
                },

                submitSelectedNotas() {
                    const selectedSonos = this.tempSelectedNotas.map(item => String(item.fsono).trim());
                    
                    this.rows = this.rows.filter(row => {
                        const refNo = String(row.frefno || '').trim();
                        return refNo === '' || selectedSonos.includes(refNo);
                    });

                    this.tempSelectedNotas.forEach(record => {
                        const remain = this.toNumber(record.famountremain);
                        if (remain <= 0) return;

                        const fsono = String(record.fsono || '').trim();
                        const existing = this.rows.find((row) => String(row.frefno || '').trim() === fsono);
                        
                        if (!existing) {
                            const targetRow = this.findTargetRowForNota();
                            let amount = this.toNumber(record.famountso ?? record.famount);
                            const trCode = String(record.ftrcode || 'INV').trim() || 'INV';

                            if (trCode.toUpperCase() === 'REJ') {
                                amount = amount * -1;
                            }

                            targetRow.frefno = fsono;
                            targetRow.fdatetime = record.fsodate || '';
                            targetRow.fnilai_nota = Math.abs(amount);
                            targetRow.fsisa_piutang = remain;
                            targetRow.originalSisa = remain;
                            targetRow.fdiscpersen = 0;
                            targetRow.fdiscount = 0;
                            targetRow.fkasdtvalue = remain;
                            targetRow.ftrcode = trCode;
                        }
                    });

                    if (this.rows.length === 0) {
                        this.rows.push(this.emptyRow());
                    }

                    this.recalcTotals();
                    this.closeNotaModal();
                },

                removeRow(index) {
                    this.rows.splice(index, 1);
                    this.recalcTotals();
                },

                isDiscPercentDisabled(row) {
                    const percent = this.toNumber(row?.fdiscpersen);
                    const discount = this.toNumber(row?.fdiscount);
                    return percent <= 0 && discount > 0;
                },

                isDiscountDisabled(row) {
                    const percent = this.toNumber(row?.fdiscpersen);
                    return percent > 0;
                },

                syncDiscountFromPercent(row) {
                    const percent = this.toNumber(row.fdiscpersen);
                    const original = this.toNumber(row.originalSisa);
                    if (percent > 0) {
                        row.fdiscount = original * percent / 100;
                        row.fkasdtvalue = Math.max(original - row.fdiscount, 0);
                        row.fsisa_piutang = 0;
                    } else {
                        row.fdiscount = 0;
                        row.fsisa_piutang = Math.max(original - row.fkasdtvalue, 0);
                    }
                    this.recalcTotals();
                },

                syncTotalBayar(row) {
                    const pay = this.toNumber(row.fkasdtvalue);
                    const disc = this.toNumber(row.fdiscount);
                    const original = this.toNumber(row.originalSisa);
                    row.fsisa_piutang = Math.max(original - pay - disc, 0);
                    this.recalcTotals();
                },

                recalcTotals() {
                    const totalBayar = this.rows.reduce((sum, row) => sum + this.toNumber(row.fkasdtvalue), 0);
                    const netPenerimaan = Math.max(totalBayar - this.toNumber(this.bankAdminFee) - this.toNumber(this.hargaAdmin) - this.toNumber(this.hargaAdmin2), 0);
                    this.totalPenerimaanDisplay = this.formatNumber(netPenerimaan);
                },

                syncDueDate() {
                    if (!this.transactionDate) {
                        return;
                    }

                    const baseDate = new Date(`${this.transactionDate}T00:00:00`);
                    if (Number.isNaN(baseDate.getTime())) {
                        return;
                    }

                    baseDate.setDate(baseDate.getDate() + this.customerTempo);
                    const year = baseDate.getFullYear();
                    const month = String(baseDate.getMonth() + 1).padStart(2, '0');
                    const day = String(baseDate.getDate()).padStart(2, '0');
                    this.dueDate = `${year}-${month}-${day}`;
                },

                handleImportInvoice() {}
            }
        }
    </script>
@endpush
