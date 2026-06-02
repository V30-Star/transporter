@php
    $pageTitle = $pageTitle ?? 'Bayar Supplier';
    $formAction = $formAction ?? route('bayarsupplier.store');
    $formMethod = strtoupper($formMethod ?? 'POST');
    $isReadOnly = (bool) ($isReadOnly ?? false);
    $submitLabel = $submitLabel ?? 'Simpan';
    $backRoute = $backRoute ?? route('bayarsupplier.index');
    $draftKey = $draftKey ?? 'bayarsupplier:create';
    $oldDetails = old('details', []);
    $seedDetails = is_array($oldDetails) && count($oldDetails) > 0 ? $oldDetails : ($detailRows ?? [[]]);
    $initialDetailRows = collect($seedDetails)
        ->values()
        ->map(function ($detail, $index) {
            return [
                'uid' => 'bs-' . $index . '-' . substr(md5((string) $index), 0, 8),
                'frefno' => trim((string) ($detail['frefno'] ?? '')),
                'fnilai_order' => (float) ($detail['fnilai_order'] ?? 0),
                'fsisa_hutang' => (float) ($detail['fsisa_hutang'] ?? 0),
                'fdiscpersen' => (float) ($detail['fdiscpersen'] ?? 0),
                'fdiscount' => (float) ($detail['fdiscount'] ?? 0),
                'fkasdtvalue' => (float) ($detail['fkasdtvalue'] ?? 0),
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

<div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto"
    x-data="bayarSupplierForm(@js($initialDetailRows), @js($selectedSupplierTempo))" x-init="init()">
    <form action="{{ $formAction }}" method="POST" class="space-y-6"
        @if (!$isReadOnly && !empty($draftKey)) data-form-draft="true" data-draft-key="{{ $draftKey }}" @endif>
        @csrf
        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <input type="hidden" name="fsupplier_tempo" x-model="supplierTempo">
        <fieldset @disabled($isReadOnly) class="space-y-3.5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Supplier' }}</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="modal_filter_supplier_id" name="filter_supplier_id"
                                class="w-full border rounded-l px-3 py-1.5 bg-gray-100 text-gray-700 cursor-not-allowed" disabled>
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
                                class="border -ml-px px-3 py-1.5 bg-white hover:bg-gray-50 rounded-r" title="Browse Supplier">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                            </button>
                        @endif
                    </div>
                    @error('fsupplier')
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
                    <label class="inline-flex items-center gap-2 h-9 mt-6 px-3 border rounded w-full bg-white">
                        <input type="checkbox" x-model="isGiroMundur" class="rounded">{{ 'Giro Mundur' }}
                    </label>
                    <input type="hidden" name="fgiromundur" :value="isGiroMundur ? '1' : '0'">
                </div>

                <div>
                    <label class="block text-sm font-bold mb-1">{{ 'Tgl.Jatuh Tempo' }}</label>
                    <input type="date" name="ftgljatuhtempo" x-model="dueDate"
                        :readonly="!isGiroMundur" :disabled="!isGiroMundur"
                        :class="!isGiroMundur ? 'bg-gray-100 cursor-not-allowed text-gray-400' : 'bg-white'"
                        class="w-full border rounded px-3 py-1.5 @error('ftgljatuhtempo') border-red-500 @enderror">
                    @error('ftgljatuhtempo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1">{{ 'No.Giro' }}</label>
                <input type="text" name="fnogiro" value="{{ old('fnogiro', $giroNo ?? '') }}"
                    class="w-full border rounded px-3 py-1.5 @error('fnogiro') border-red-500 @enderror">
                @error('fnogiro')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

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
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-2 py-1.5 w-12">{{ 'No' }}</th>
                                <th class="border px-2 py-1.5 min-w-[12rem]">{{ 'No.Penerimaan' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-right">{{ 'Nilai Order' }}</th>
                                <th class="border px-2 py-1.5 min-w-[10rem] text-right">{{ 'Sisa Hutang' }}</th>
                                <th class="border px-2 py-1.5 min-w-[8rem] text-right">{{ 'Disc.%' }}</th>
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
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01" x-model="row.fnilai_order"
                                            class="w-full border rounded px-2 py-1 text-right bg-gray-100 cursor-not-allowed" readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fnilai_order]`" :value="row.fnilai_order">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01" x-model="row.fsisa_hutang"
                                            class="w-full border rounded px-2 py-1 text-right bg-gray-100 cursor-not-allowed" readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fsisa_hutang]`" :value="row.fsisa_hutang">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" max="100" step="0.01"
                                            :name="`details[${index}][fdiscpersen]`" x-model="row.fdiscpersen"
                                            @input="syncDiscountFromPercent(row)"
                                            class="w-full border rounded px-2 py-1 text-right">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01"
                                            :name="`details[${index}][fdiscount]`" x-model="row.fdiscount"
                                            @input="syncTotalBayar(row)"
                                            class="w-full border rounded px-2 py-1 text-right">
                                    </td>
                                    <td class="border px-2 py-1">
                                        <input type="number" min="0" step="0.01" x-model="row.fkasdtvalue"
                                            class="w-full border rounded px-2 py-1 text-right bg-gray-100 cursor-not-allowed" readonly disabled>
                                        <input type="hidden" :name="`details[${index}][fkasdtvalue]`" :value="row.fkasdtvalue">
                                    </td>
                                    @if (!$isReadOnly)
                                        <td class="border px-2 py-1 text-center">
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
                        class="inline-flex items-center justify-center gap-2 border rounded px-3 py-2 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span>{{ 'Add Faktur' }}</span>
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <div class="w-full max-w-2xl">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-sm font-semibold text-gray-700">{{ 'Biaya Admin Bank' }}</label>
                            <input type="number" min="0" step="0.01" name="fbiayaadminbank" x-model="bankAdminFee"
                                @input="recalcTotals()"
                                class="w-52 border rounded px-3 py-2 text-right @error('fbiayaadminbank') border-red-500 @enderror">
                        </div>
                        @error('fbiayaadminbank')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex">
                                <input type="text" x-model="adminAccountLabel"
                                    class="w-full border rounded-l px-3 py-2 text-sm bg-gray-100 cursor-not-allowed" readonly>
                                <input type="hidden" name="faccountadmin" x-model="adminAccountCode">
                                @if (!$isReadOnly)
                                    <button type="button" @click="activeAccountField = 'admin'; window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                        class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r" title="Browse Account Admin">
                                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                    </button>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t md:border-t-0 pt-3 md:pt-0">
                                <span class="text-sm font-semibold text-gray-800">{{ 'Total Bayar' }}</span>
                                <input type="text" x-model="totalBayarDisplay"
                                    class="w-52 border rounded px-3 py-2 bg-gray-100 text-right font-semibold cursor-not-allowed" readonly>
                            </div>
                        </div>
                        @error('faccountadmin')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="flex items-center justify-center gap-3">
            <a href="{{ $backRoute }}" class="inline-flex items-center px-4 py-2 rounded border border-gray-300 bg-white hover:bg-gray-50">
                {{ 'Keluar' }}
            </a>
            @if (!$isReadOnly && !empty($submitLabel))
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    {{ $submitLabel }}
                </button>
            @endif
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
    <x-transaction.browse-account-modal />
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
        function bayarSupplierForm(initialRows, initialTempo) {
            return {
                rows: [],
                supplierCode: @js($selectedSupplierCode),
                supplierTempo: Number(initialTempo || 0),
                accountCode: @js($selectedAccountCode),
                accountLabel: @js($selectedAccountCode !== '' ? trim($selectedAccountCode . ' - ' . $selectedAccountName) : ''),
                adminAccountCode: @js($selectedAdminAccount->faccount ?? ''),
                adminAccountLabel: @js(isset($selectedAdminAccount) ? trim($selectedAdminAccount->faccount . ' - ' . $selectedAdminAccount->faccname) : ''),
                activeAccountField: null,
                transactionDate: @js(old('fkasmtdate', $transactionDate)),
                dueDate: @js(old('ftgljatuhtempo', $dueDate ?? '')),
                isGiroMundur: @js(old('fgiromundur', ($giroMundur ?? false) ? '1' : '0') === '1'),
                bankAdminFee: @js((float) old('fbiayaadminbank', $bankAdminFee ?? 0)),
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
                    this.rows = (Array.isArray(initialRows) && initialRows.length ? initialRows : []).map((row, index) => this.normalizeRow(row, index));
                    this.ensureMinimumRows();
                    this.recalcTotals();

                    this.$watch('rows', () => {
                        this.ensureMinimumRows();
                        this.ensureTrailingRow();
                    }, { deep: true });

                    window.addEventListener('supplier-picked', (event) => {
                        const detail = event.detail || {};
                        this.supplierCode = String(detail.fsuppliercode || '').trim();
                        this.supplierTempo = Number(detail.ftempo || 0);
                        if (this.isGiroMundur) this.syncDueDate();
                    });

                    window.addEventListener('account-picked', (event) => {
                        const detail = event.detail || {};
                        const code = String(detail.faccount || '').trim();
                        const name = String(detail.faccname || '').trim();
                        if (this.activeAccountField === 'admin') {
                            this.adminAccountCode = code;
                            this.adminAccountLabel = code && name ? `${code} - ${name}` : code;
                        } else {
                            this.accountCode = code;
                            this.accountLabel = code && name ? `${code} - ${name}` : code;
                        }
                    });

                    this.$watch('transactionDate', () => { if (this.isGiroMundur) this.syncDueDate(); });
                    this.$watch('isGiroMundur', (value) => value ? this.syncDueDate() : this.dueDate = '');
                    this.$watch('bankAdminFee', () => this.recalcTotals());
                },

                emptyRow() {
                    return { uid: this.makeUid(), frefno: '', fnilai_order: 0, fsisa_hutang: 0, fdiscpersen: 0, fdiscount: 0, fkasdtvalue: 0 };
                },

                normalizeRow(row = {}, index = 0) {
                    return {
                        uid: row.uid || `bs-row-${index}-${this.makeUid()}`,
                        frefno: String(row.frefno || '').trim(),
                        fnilai_order: this.toNumber(row.fnilai_order),
                        fsisa_hutang: this.toNumber(row.fsisa_hutang),
                        fdiscpersen: this.toNumber(row.fdiscpersen),
                        fdiscount: this.toNumber(row.fdiscount),
                        fkasdtvalue: this.toNumber(row.fkasdtvalue),
                    };
                },

                ensureMinimumRows() { while (this.rows.length < 5) this.rows.push(this.emptyRow()); },
                ensureTrailingRow() { if (this.rows.length && String(this.rows[this.rows.length - 1].frefno || '').trim() !== '') this.rows.push(this.emptyRow()); },
                makeUid() { return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`; },
                toNumber(value) { const number = Number(value); return Number.isFinite(number) ? number : 0; },
                formatNumber(value) { return this.toNumber(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                formatDateDisplay(value) {
                    if (!value) return '-';
                    const date = new Date(`${value}T00:00:00`);
                    if (Number.isNaN(date.getTime())) return value;
                    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
                },

                openPblModal() {
                    if (!this.supplierCode) {
                        Swal.fire({ icon: 'error', title: 'Terjadi kesalahan', text: 'Pilih supplier terlebih dahulu sebelum browse faktur.' });
                        return;
                    }
                    this.tempSelectedPbls = this.rows.filter(row => row.frefno).map(row => ({ fstockmtno: row.frefno, famountmt: row.fnilai_order, famountremain: row.fsisa_hutang }));
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
                togglePblSelection(record) {
                    const remain = this.toNumber(record.famountremain);
                    if (remain <= 0) return;
                    const idx = this.tempSelectedPbls.findIndex(item => String(item.fstockmtno).trim() === String(record.fstockmtno).trim());
                    if (idx > -1) this.tempSelectedPbls.splice(idx, 1); else this.tempSelectedPbls.push(record);
                },
                isAllOnPageSelected() { return this.pblRecords.length > 0 && this.pblRecords.every(record => this.toNumber(record.famountremain) <= 0 || this.isPblSelected(record)); },
                toggleAllOnPage() {
                    const allSelected = this.isAllOnPageSelected();
                    this.pblRecords.forEach(record => {
                        if (this.toNumber(record.famountremain) <= 0) return;
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
                submitSelectedPbls() {
                    const selectedNos = this.tempSelectedPbls.map(item => String(item.fstockmtno).trim());
                    this.rows = this.rows.filter(row => String(row.frefno || '').trim() === '' || selectedNos.includes(String(row.frefno || '').trim()));
                    this.tempSelectedPbls.forEach(record => {
                        const refNo = String(record.fstockmtno || '').trim();
                        if (!refNo || this.rows.some(row => String(row.frefno || '').trim() === refNo)) return;
                        const targetRow = this.findTargetRowForPbl();
                        const remain = this.toNumber(record.famountremain);
                        targetRow.frefno = refNo;
                        targetRow.fnilai_order = this.toNumber(record.famountmt);
                        targetRow.fsisa_hutang = remain;
                        targetRow.fdiscpersen = 0;
                        targetRow.fdiscount = 0;
                        targetRow.fkasdtvalue = remain;
                    });
                    this.ensureMinimumRows();
                    this.recalcTotals();
                    this.closePblModal();
                },
                removeRow(index) { this.rows.splice(index, 1); this.recalcTotals(); },
                syncDiscountFromPercent(row) {
                    const basis = this.toNumber(row.fsisa_hutang || row.fnilai_order);
                    row.fdiscount = basis * this.toNumber(row.fdiscpersen) / 100;
                    row.fkasdtvalue = Math.max(basis - row.fdiscount, 0);
                    this.recalcTotals();
                },
                syncTotalBayar(row) {
                    const basis = this.toNumber(row.fsisa_hutang || row.fnilai_order);
                    row.fkasdtvalue = Math.max(basis - this.toNumber(row.fdiscount), 0);
                    this.recalcTotals();
                },
                recalcTotals() {
                    const totalBayar = this.rows.reduce((sum, row) => sum + this.toNumber(row.fkasdtvalue), 0) + this.toNumber(this.bankAdminFee);
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
