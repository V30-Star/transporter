@php
    $isReadOnly = $isReadOnly ?? false;
    $formMethod = $formMethod ?? 'POST';
    $formAction = $formAction ?? '#';
    $isEditMode = strtoupper($formMethod) === 'PATCH';
    $isDeleteMode = strtoupper($formMethod) === 'DELETE';
    $submitLabel = $isEditMode ? 'Update' : 'Simpan';
    $transactionLabel = $transactionLabel ?? 'Pengeluaran Kas/Bank';
    $backRoute = $backRoute ?? route('pengeluarankas.index');
    $detailsOld = old('details');
    $detailRows = is_array($detailsOld) ? collect($detailsOld)->map(fn($row) => (object) $row) : $details;
    $selectedHeader = old('faccountheader', $pengeluaranKas->faccountheader);
    $isGiroMundur = old('fgiromundur', $pengeluaranKas->fgiromundur ?? '0') === '1';
    $selectedJatuhTempo = old(
        'ftgljatuhtempo',
        optional($pengeluaranKas->ftgljatuhtempo ?? null)?->format('Y-m-d') ?? ($pengeluaranKas->ftgljatuhtempo ?? ''),
    );
    $headerAccountOptions = collect($headerAccounts ?? []);
    $accountOptions = collect($accounts ?? []);
    $subaccountOptions = collect($subaccounts ?? []);
    $customerOptions = collect($customers ?? []);
    $supplierOptions = collect($suppliers ?? []);
    $branchOptions = collect($branches ?? []);
    $resolvedBranchCode = (string) old('fbranchcode', $pengeluaranKas->fbranchcode ?? ($currentBranchCode ?? ''));
    $resolvedBranchLabel = $branchOptions->firstWhere('fcabangkode', $resolvedBranchCode);
    $resolvedBranchLabel = $resolvedBranchLabel
        ? trim($resolvedBranchLabel->fcabangkode . ' - ' . $resolvedBranchLabel->fcabangname)
        : $resolvedBranchCode;
    $journalAccountValidation = $journalAccountValidation ?? ['system' => [], 'stock' => [], 'reference' => []];
    $normalizeSubaccountType = function ($value) {
        $normalized = strtoupper(trim((string) $value));

        return match ($normalized) {
            'C', 'CUSTOMER' => 'C',
            'P', 'SUPPLIER' => 'P',
            default => 'S',
        };
    };
    $accountCatalog = $accountOptions
        ->mapWithKeys(function ($account) use ($normalizeSubaccountType) {
            $code = strtoupper(trim((string) ($account->faccount ?? '')));
            if ($code === '') {
                return [];
            }

            return [
                $code => [
                    'faccount' => (string) ($account->faccount ?? ''),
                    'faccname' => (string) ($account->faccname ?? ''),
                    'fhavesubaccount' => (string) ($account->fhavesubaccount ?? '0'),
                    'ftypesubaccount' => $normalizeSubaccountType($account->ftypesubaccount ?? 'S'),
                ],
            ];
        })
        ->all();
    $selectedHeaderLookup = $isGiroMundur
        ? $giroMundurHeaderAccount ?? null
        : $headerAccountOptions->firstWhere('faccount', (string) $selectedHeader);
    $selectedHeaderLabel = $selectedHeaderLookup;
    $selectedHeaderLabel = $selectedHeaderLabel
        ? trim($selectedHeaderLabel->faccount . ' - ' . $selectedHeaderLabel->faccname)
        : (string) $selectedHeader;
    $isPenerimaanKasForm = ($transactionLabel ?? 'Pengeluaran Kas/Bank') === 'Penerimaan Kas/Bank';
    $resolveDetailDkLabel = function ($amount) use ($isPenerimaanKasForm) {
        $numericAmount = (float) ($amount ?? 0);

        if ($isPenerimaanKasForm) {
            return $numericAmount >= 0 ? 'K' : 'D';
        }

        return $numericAmount >= 0 ? 'D' : 'K';
    };
    $resolveDetailDkBadgeClass = function ($amount) use ($resolveDetailDkLabel) {
        return $resolveDetailDkLabel($amount) === 'D'
            ? 'border-blue-200 bg-blue-50 text-blue-700'
            : 'border-amber-200 bg-amber-50 text-amber-700';
    };
    $totalAmount = $detailRows->sum(fn($row) => (float) ($row->fkasdtvalue ?? 0));
@endphp

<style>
    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
    }

    .detail-dk-badge {
        min-width: 2.25rem;
        border-radius: 9999px;
        border-width: 1px;
        padding: .2rem .55rem;
        font-size: .75rem;
        font-weight: 700;
        line-height: 1;
    }
</style>

<div x-data="pengeluaranKasForm(@js($isReadOnly), @js(old('fkasmtno', $pengeluaranKas->fkasmtno ?? '')), @js($isGiroMundur), @js($isPenerimaanKasForm), @js($journalAccountValidation), @js($accountCatalog))" x-init="init()" class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto">

    <form action="{{ $formAction }}" method="POST" @submit="handleSubmit($event)">
        @csrf
        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ 'Cabang' }}</label>
                <input type="text" value="{{ $resolvedBranchLabel }}"
                    class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" readonly>
                <input type="hidden" name="fbranchcode" value="{{ $resolvedBranchCode }}">
                @error('fbranchcode')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ 'Voucher No.' }}</label>
                @if ($isReadOnly)
                    <input type="text" name="fkasmtno" value="{{ old('fkasmtno', $pengeluaranKas->fkasmtno ?? '') }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" readonly>
                @else
                    <div class="flex items-center gap-3">
                        <input type="text" name="fkasmtno" x-model="voucherNo" :disabled="autoCode"
                            class="w-full border rounded px-3 py-2"
                            :class="autoCode ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'"
                            placeholder="{{ 'Kosongkan untuk auto number' }}">
                        <label class="inline-flex items-center select-none">
                            <input type="checkbox" x-model="autoCode">
                            <span class="ml-2 text-sm text-gray-700">{{ 'Auto' }}</span>
                        </label>
                    </div>
                @endif
                @error('fkasmtno')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ 'Tanggal' }}</label>
                <input type="date" name="fkasmtdate"
                    value="{{ old('fkasmtdate', optional($pengeluaranKas->fkasmtdate)->format('Y-m-d') ?? $pengeluaranKas->fkasmtdate) }}"
                    class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>
                @error('fkasmtdate')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ 'Cash / Bank Account' }}</label>
                @if ($isReadOnly)
                    <input type="text" value="{{ $selectedHeaderLabel }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" readonly>
                @else
                    <div>
                        <select name="faccountheader" class="w-full border rounded px-3 py-2">
                            <option value="">{{ 'Pilih account' }}</option>
                            @foreach ($headerAccounts as $account)
                                <option value="{{ $account->faccount }}"
                                    {{ (string) $selectedHeader === (string) $account->faccount ? 'selected' : '' }}>
                                    {{ $account->faccount }} - {{ $account->faccname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($isReadOnly)
                    <input type="hidden" name="faccountheader" value="{{ $selectedHeader }}">
                @endif
                @error('faccountheader')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ 'Penerima' }}</label>
                <input type="text" name="fwhom" value="{{ old('fwhom', $pengeluaranKas->fwhom) }}"
                    class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>
                @error('fwhom')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ 'No.Giro/Cek' }}</label>
                <div class="flex items-center gap-3 flex-nowrap">
                    <div class="w-[12rem] shrink-0">
                        <input type="text" name="fnogiro" value="{{ old('fnogiro', $pengeluaranKas->fnogiro) }}"
                            class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                            {{ $isReadOnly ? 'readonly' : '' }}>
                    </div>

                    <div class="flex items-center h-10 px-1 shrink-0">
                        <label class="inline-flex items-center gap-2 cursor-pointer whitespace-nowrap">
                            <input type="checkbox" x-model="isGiroMundur" {{ $isReadOnly ? 'disabled' : '' }}
                                class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">{{ 'Giro Mundur' }}</span>
                        </label>
                        <input type="hidden" name="fgiromundur" :value="isGiroMundur ? '1' : '0'">
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <label class="text-sm font-medium whitespace-nowrap">{{ 'Tgl. Jatuh Tempo' }}</label>
                        <input type="date" name="ftgljatuhtempo" value="{{ $selectedJatuhTempo }}"
                            class="w-[12rem] border rounded px-3 py-2"
                            :class="isReadOnly || !isGiroMundur ? 'bg-gray-100 cursor-not-allowed text-gray-400' : 'bg-white'"
                            :readonly="isReadOnly || !isGiroMundur" :disabled="isReadOnly || !isGiroMundur">
                    </div>
                </div>

                @error('fnogiro')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                @error('fgiromundur')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
                @error('ftgljatuhtempo')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">{{ 'Keterangan' }}</label>
                <textarea name="fket" rows="3" class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>{{ old('fket', $pengeluaranKas->fket) }}</textarea>
                @error('fket')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-800">{{ 'Detail Item' }}</h3>
            </div>

            <div class="overflow-auto border rounded-lg">
                <table class="min-w-full text-sm balanced-detail-table" data-skip-auto-detail-style="true">
                    <colgroup>
                        @if ($isReadOnly)
                            <col style="width:4%;">
                            <col style="width:15%;">
                            <col style="width:16%;">
                            <col style="width:15%;">
                            <col style="width:40%;">
                            <col style="width:12%;">
                            <col style="width:6%;">
                        @else
                            <col style="width:4%;">
                            <col style="width:15%;">
                            <col style="width:16%;">
                            <col style="width:15%;">
                            <col style="width:40%;">
                            <col style="width:12%;">
                            <col style="width:6%;">
                        @endif
                    </colgroup>
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-1.5 py-1 whitespace-nowrap">{{ 'No' }}</th>
                            <th class="border px-1.5 py-1 whitespace-nowrap">{{ 'Kode Account' }}</th>
                            <th class="border px-1.5 py-1 whitespace-nowrap">{{ 'Nama Account' }}</th>
                            <th class="border px-1.5 py-1 whitespace-nowrap">{{ 'Sub Account' }}</th>
                            <th class="border px-1.5 py-1 whitespace-nowrap">{{ 'Uraian' }}</th>
                            <th class="border px-1.5 py-1 text-right whitespace-nowrap">{{ 'Nilai Bayar' }}</th>
                            @unless ($isReadOnly)
                                <th class="border px-1.5 py-1 text-center whitespace-nowrap">{{ 'Aksi' }}</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody id="detailRows">
                        @foreach ($detailRows as $index => $detail)
                            <tr class="detail-row">
                                <td class="border px-1.5 py-1 text-center align-top">{{ $index + 1 }}</td>
                                <td class="border px-1.5 py-1 align-top">
                                    @php
                                        $detailAccountCode = (string) old(
                                            "details.$index.faccount",
                                            $detail->faccount ?? '',
                                        );
                                        $detailAccount = $accountOptions->firstWhere('faccount', $detailAccountCode);
                                        $detailAccountName = $detailAccount
                                            ? trim($detailAccount->faccname)
                                            : (string) ($detail->account_name ?? '');
                                        $detailHasSubaccount =
                                            (string) ($detailAccount->fhavesubaccount ?? '0') === '1';
                                        $detailSubaccountType = $normalizeSubaccountType(
                                            $detailAccount->ftypesubaccount ?? 'S',
                                        );
                                    @endphp
                                    @if ($isReadOnly)
                                        <input type="text" value="{{ $detailAccountCode }}"
                                            class="w-full border rounded px-1.5 py-1 bg-gray-100 cursor-text select-all"
                                            @focus="$event.target.select()" @click="$event.target.select()" readonly>
                                        <input type="hidden" name="details[{{ $index }}][faccount]"
                                            value="{{ $detailAccountCode }}">
                                    @else
                                        <div class="flex items-center gap-1">
                                            <div class="flex-1 min-w-0">
                                                <input type="text"
                                                    class="detail-account-code w-full border rounded px-1.5 py-1 bg-white cursor-text select-all"
                                                    name="details[{{ $index }}][faccount]"
                                                    value="{{ $detailAccountCode }}" @focus="$event.target.select()"
                                                    @click="$event.target.select()"
                                                    @input="handleAccountCodeInput($event)"
                                                    @blur="handleAccountCodeBlur($event)"
                                                    data-role="account-code-display">
                                                <input type="hidden" value="{{ $detailHasSubaccount ? '1' : '0' }}"
                                                    data-role="account-has-subaccount">
                                                <input type="hidden" value="{{ $detailSubaccountType }}"
                                                    data-role="account-subaccount-type">
                                            </div>
                                            <button type="button" @click="openAccountBrowse($event)"
                                                class="border rounded px-2 py-1 bg-white hover:bg-gray-50 shrink-0"
                                                title="Cari Account">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @endif
                                    @error("details.$index.faccount")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="border px-1.5 py-1 align-top">
                                    <input type="text" value="{{ $detailAccountName }}"
                                        class="detail-account-name w-full border rounded px-1.5 py-1 bg-gray-100 cursor-not-allowed"
                                        readonly data-role="account-name-display">
                                </td>
                                <td class="border px-1.5 py-1 align-top">
                                    @php
                                        $detailSubaccountCode = (string) old(
                                            "details.$index.fsubaccount",
                                            $detail->fsubaccount ?? '',
                                        );
                                        $detailSubaccount = $subaccountOptions->firstWhere(
                                            'fsubaccountcode',
                                            $detailSubaccountCode,
                                        );
                                        $detailCustomer = $customerOptions->firstWhere(
                                            'fcustomercode',
                                            $detailSubaccountCode,
                                        );
                                        $detailSupplier = $supplierOptions->firstWhere(
                                            'fsuppliercode',
                                            $detailSubaccountCode,
                                        );
                                        $detailSubaccountLabel = match ($detailSubaccountType) {
                                            'C' => $detailCustomer
                                                ? trim(
                                                    $detailCustomer->fcustomercode .
                                                        ' - ' .
                                                        $detailCustomer->fcustomername,
                                                )
                                                : $detailSubaccountCode,
                                            'P' => $detailSupplier
                                                ? trim(
                                                    $detailSupplier->fsuppliercode .
                                                        ' - ' .
                                                        $detailSupplier->fsuppliername,
                                                )
                                                : $detailSubaccountCode,
                                            default => $detailSubaccount
                                                ? trim(
                                                    $detailSubaccount->fsubaccountcode .
                                                        ' - ' .
                                                        $detailSubaccount->fsubaccountname,
                                                )
                                                : $detailSubaccountCode,
                                        };
                                    @endphp
                                    @if ($isReadOnly)
                                        <input type="text" value="{{ $detailSubaccountLabel }}"
                                            class="w-full border rounded px-1.5 py-1 bg-gray-100 cursor-not-allowed"
                                            readonly>
                                        <input type="hidden" name="details[{{ $index }}][fsubaccount]"
                                            value="{{ $detailSubaccountCode }}">
                                    @else
                                        <div class="flex items-center gap-1">
                                            <div class="flex-1 min-w-0">
                                                <input type="text"
                                                    class="detail-subaccount-display w-full border rounded px-1.5 py-1 bg-gray-100 cursor-not-allowed"
                                                    value="{{ $detailSubaccountLabel }}" readonly
                                                    data-role="subaccount-display">
                                                <input type="hidden"
                                                    name="details[{{ $index }}][fsubaccount]"
                                                    value="{{ $detailSubaccountCode }}">
                                            </div>
                                            <button type="button" @click="openSubaccountBrowse($event)"
                                                class="detail-subaccount-btn border rounded px-2 py-1 bg-white hover:bg-gray-50 shrink-0"
                                                title="{{ $detailSubaccountType === 'C' ? 'Cari Customer' : ($detailSubaccountType === 'P' ? 'Cari Supplier' : 'Cari Sub Account') }}">
                                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @endif
                                    @error("details.$index.fsubaccount")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="border px-1.5 py-1 align-top">
                                    @php
                                        $detailReferenceValue = (string) old(
                                            "details.$index.frefno",
                                            $detail->frefno ?? '',
                                        );
                                    @endphp
                                    <input type="hidden" name="details[{{ $index }}][frefno]"
                                        value="{{ $detailReferenceValue }}" data-role="detail-reference-input">
                                    <textarea name="details[{{ $index }}][fnote]" rows="1"
                                        class="w-full border rounded px-1.5 py-1 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'readonly' : '' }}>{{ old("details.$index.fnote", $detail->fnote ?? '') }}</textarea>
                                    @error("details.$index.fnote")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <td class="border px-1.5 py-1 align-top">
                                    @php
                                        $detailAmountValue = old(
                                            "details.$index.fkasdtvalue",
                                            $detail->fkasdtvalue ?? '',
                                        );
                                    @endphp
                                    @if ($isReadOnly)
                                        <input type="text"
                                            value="{{ number_format((float) ($detailAmountValue ?: 0), 2, '.', ',') }}"
                                            class="detail-amount w-full border rounded px-1.5 py-1 text-right bg-gray-100"
                                            readonly>
                                        <input type="hidden" name="details[{{ $index }}][fkasdtvalue]"
                                            value="{{ $detailAmountValue }}">
                                    @else
                                        <input type="number" name="details[{{ $index }}][fkasdtvalue]"
                                            step="0.01" value="{{ $detailAmountValue }}"
                                            class="detail-amount w-full border rounded px-1.5 py-1 text-right"
                                            data-role="detail-amount-input">
                                    @endif
                                    @error("details.$index.fkasdtvalue")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                @unless ($isReadOnly)
                                    <td class="detail-action-cell border px-1 py-1 text-center align-middle">
                                        <div class="flex items-center justify-center">
                                            <button type="button" @click="removeRow($event)"
                                                class="detail-delete-btn inline-flex h-8 w-8 items-center justify-center rounded bg-red-100 text-red-600 hover:bg-red-200 text-lg font-bold transition-colors duration-150"
                                                title="Hapus baris">
                                                -
                                            </button>
                                        </div>
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <div class="w-full max-w-md">
                    <div class="rounded-lg border bg-gray-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-800">{{ 'Total Pengeluaran' }}</span>
                            <input type="text" id="detailTotal"
                                value="{{ number_format($totalAmount, 2, '.', ',') }}"
                                class="w-48 border rounded px-1.5 py-1 text-right bg-gray-100 font-semibold" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-center gap-4">
            @if ($isReadOnly && !$isDeleteMode && !empty($printRoute))
                <a href="{{ $printRoute }}" target="_blank"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m10 0v5H7v-5">
                        </path>
                    </svg>
                    {{ 'Print' }}
                </a>
            @endif

            @if ($isDeleteMode)
                <button type="submit"
                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 inline-flex items-center">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" /> {{ 'Hapus' }}
                </button>
            @elseif (!$isReadOnly)
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 inline-flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> {{ $submitLabel }}
                </button>
            @endif

            <a href="{{ $backRoute }}"
                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 inline-flex items-center">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> {{ 'Kembali' }}
            </a>
        </div>
    </form>
</div>

@unless ($isReadOnly)
    <x-transaction.browse-account-modal :fend="1" show-controls="true" show-pagination="true" />
    <x-transaction.browse-subaccount-modal show-controls="true" show-pagination="true" />
    <x-transaction.browse-customer-modal />
    <x-transaction.browse-supplier-modal />
    @include('components.transaction.browse-customer-script')

    @push('scripts')
        <script>
            function pengeluaranKasForm(isReadOnly, initialVoucherNo, initialGiroMundur, isPenerimaanKasForm,
                journalAccountValidation, accountCatalog) {
                return {
                    isReadOnly,
                    voucherNo: initialVoucherNo || '',
                    autoCode: !initialVoucherNo,
                    isGiroMundur: !!initialGiroMundur,
                    isPenerimaanKasForm: !!isPenerimaanKasForm,
                    journalAccountValidation: journalAccountValidation || {
                        system: {},
                        stock: {},
                        reference: {}
                    },
                    accountCatalog: accountCatalog || {},
                    activeLookupRow: null,
                    activeLookupType: null,

                    checkAndAutoAppendRow() {
                        if (this.isReadOnly) return;

                        const rows = Array.from(document.querySelectorAll('#detailRows tr.detail-row'));
                        if (rows.length === 0) return;

                        const lastRow = rows[rows.length - 1];

                        const accountVal = lastRow.querySelector('input[name$="[faccount]"]')?.value || '';
                        const noteVal = lastRow.querySelector('textarea[name$="[fnote]"]')?.value || '';
                        const amountVal = lastRow.querySelector('input[name$="[fkasdtvalue]"]')?.value || '';

                        const isLastRowFilled = accountVal.trim() !== '' || noteVal.trim() !== '' || amountVal.trim() !== '';

                        if (isLastRowFilled) {
                            this.addRow();
                        }
                    },

                    init() {
                        if (this.isReadOnly && this.voucherNo) {
                            this.autoCode = false;
                        }

                        window.addEventListener('account-picked', (event) => {
                            if (this.activeLookupType !== 'account' || !this.activeLookupRow) {
                                return;
                            }

                            const code = (event.detail?.faccount || '').toString().trim();
                            const name = (event.detail?.faccname || '').toString().trim();
                            const hasSubaccount = String(event.detail?.fhavesubaccount ?? '0') === '1';
                            const subaccountType = this.normalizeSubaccountType(event.detail?.ftypesubaccount);
                            this.applyAccountLookupValue(this.activeLookupRow, code, name, hasSubaccount,
                                subaccountType);
                        });

                        window.addEventListener('subaccount-picked', (event) => {
                            if (this.activeLookupType !== 'subaccount' || !this.activeLookupRow) {
                                return;
                            }

                            const activeRow = this.activeLookupRow;
                            const code = (event.detail?.fsubaccountcode || '').toString().trim();
                            const name = (event.detail?.fsubaccountname || '').toString().trim();
                            this.applyLookupValue(activeRow, 'fsubaccount', 'subaccount-display', code,
                                code && name ? `${code} - ${name}` : code);
                            if (activeRow) {
                                const result = this.validateJournalRow(activeRow);
                                if (result.status === 'ERROR') {
                                    this.presentValidationError(result, activeRow);
                                }
                            }
                        });

                        window.addEventListener('customer-picked', (event) => {
                            if (this.activeLookupType !== 'customer' || !this.activeLookupRow) {
                                return;
                            }

                            const activeRow = this.activeLookupRow;
                            const code = (event.detail?.fcustomercode || '').toString().trim();
                            const name = (event.detail?.fcustomername || '').toString().trim();
                            this.applyLookupValue(activeRow, 'fsubaccount', 'subaccount-display', code,
                                code && name ? `${code} - ${name}` : code);
                            this.validateActiveLookupRow(activeRow);
                        });

                        window.addEventListener('supplier-picked', (event) => {
                            if (this.activeLookupType !== 'supplier' || !this.activeLookupRow) {
                                return;
                            }

                            const activeRow = this.activeLookupRow;
                            const code = (event.detail?.fsuppliercode || '').toString().trim();
                            const name = (event.detail?.fsuppliername || '').toString().trim();
                            this.applyLookupValue(activeRow, 'fsubaccount', 'subaccount-display', code,
                                code && name ? `${code} - ${name}` : code);
                            this.validateActiveLookupRow(activeRow);
                        });

                        this.$nextTick(() => {
                            document.querySelectorAll('#detailRows tr.detail-row').forEach((row) => {
                                this.syncSubaccountState(row);
                                this.syncRowAmountState(row);
                            });
                            this.checkAndAutoAppendRow();
                        });
                    },

                    normalizeAccountCode(value) {
                        return (value || '').toString().trim().toUpperCase();
                    },

                    normalizeSubaccountType(value) {
                        const normalized = (value || 'S').toString().trim().toUpperCase();

                        if (normalized === 'C' || normalized === 'CUSTOMER') {
                            return 'C';
                        }

                        if (normalized === 'P' || normalized === 'SUPPLIER') {
                            return 'P';
                        }

                        return 'S';
                    },

                    getValidationAccountMeta(code) {
                        const normalized = this.normalizeAccountCode(code);
                        return this.accountCatalog?.[normalized] || null;
                    },

                    handleAccountCodeInput(event) {
                        const row = event.target.closest('tr.detail-row');
                        const rawCode = this.normalizeAccountCode(event.target.value);
                        event.target.value = rawCode;

                        if (rawCode === '') {
                            this.clearAccountSelection(row);
                            return;
                        }

                        const account = this.getValidationAccountMeta(rawCode);
                        const nameField = row?.querySelector('[data-role="account-name-display"]');
                        const hasSubaccountField = row?.querySelector('[data-role="account-has-subaccount"]');
                        const subaccountTypeField = row?.querySelector('[data-role="account-subaccount-type"]');
                        const subaccountHiddenField = row?.querySelector('input[name$="[fsubaccount]"]');
                        const subaccountDisplayField = row?.querySelector('[data-role="subaccount-display"]');

                        if (!account) {
                            if (nameField) nameField.value = '';
                            if (hasSubaccountField) hasSubaccountField.value = '0';
                            if (subaccountTypeField) subaccountTypeField.value = 'S';
                            if (subaccountHiddenField) subaccountHiddenField.value = '';
                            if (subaccountDisplayField) subaccountDisplayField.value = '';
                            this.syncSubaccountState(row, false, 'S');
                            this.checkAndAutoAppendRow();
                            return;
                        }

                        const hasSubaccount = String(account.fhavesubaccount || '0') === '1';
                        const subaccountType = this.normalizeSubaccountType(account.ftypesubaccount);
                        if (nameField) nameField.value = account.faccname || '';
                        if (hasSubaccountField) hasSubaccountField.value = hasSubaccount ? '1' : '0';
                        if (subaccountTypeField) subaccountTypeField.value = subaccountType;
                        if (subaccountHiddenField) subaccountHiddenField.value = '';
                        if (subaccountDisplayField) subaccountDisplayField.value = '';
                        this.syncSubaccountState(row, hasSubaccount, subaccountType);
                        this.checkAndAutoAppendRow();
                    },

                    handleAccountCodeBlur(event) {
                        const row = event.target.closest('tr.detail-row');
                        const result = this.validateJournalRow(row);
                        if (result.status === 'ERROR') {
                            this.presentValidationError(result, row);
                        }
                    },

                    getValidationAccountLabel(code) {
                        const normalized = this.normalizeAccountCode(code);
                        const meta = this.getValidationAccountMeta(normalized);

                        if (meta?.faccname) {
                            return meta.faccname;
                        }

                        const allGroups = [
                            ...(Object.entries(this.journalAccountValidation?.system || {})),
                            ...(Object.entries(this.journalAccountValidation?.stock || {})),
                            ...(Object.entries(this.journalAccountValidation?.reference || {})),
                        ];
                        const match = allGroups.find(([accountCode]) => this.normalizeAccountCode(accountCode) === normalized);
                        return match?.[1]?.display_name || normalized || 'Account';
                    },

                    clearAccountSelection(row) {
                        if (!row) {
                            return;
                        }

                        const accountHidden = row.querySelector('input[name$="[faccount]"]');
                        const accountCodeDisplay = row.querySelector('[data-role="account-code-display"]');
                        const accountNameDisplay = row.querySelector('[data-role="account-name-display"]');
                        const hasSubaccountField = row.querySelector('[data-role="account-has-subaccount"]');
                        const subaccountTypeField = row.querySelector('[data-role="account-subaccount-type"]');

                        if (accountHidden) accountHidden.value = '';
                        if (accountCodeDisplay) accountCodeDisplay.value = '';
                        if (accountNameDisplay) accountNameDisplay.value = '';
                        if (hasSubaccountField) hasSubaccountField.value = '0';
                        if (subaccountTypeField) subaccountTypeField.value = 'S';

                        this.syncSubaccountState(row, false, 'S');
                    },

                    focusValidationField(row, focusField) {
                        if (!row) {
                            return;
                        }

                        const focusTarget = {
                            faccount: row.querySelector('[data-role="account-code-display"]') || row.querySelector(
                                'button[title="Cari Account"]'),
                            fsubaccount: row.querySelector('.detail-subaccount-btn') || row.querySelector(
                                '[data-role="subaccount-display"]'),
                            frefno: row.querySelector('[data-role="detail-reference-input"]'),
                        } [focusField];

                        focusTarget?.focus?.();
                    },

                    presentValidationError(result, row) {
                        if (!result || result.status !== 'ERROR') {
                            return;
                        }

                        if (window.showTransactionErrorModal) {
                            window.showTransactionErrorModal(result.message, {
                                title: 'Validasi Jurnal Gagal',
                                reason: result.validasi || 'Masih ada data detail jurnal yang belum valid.',
                            });
                        }

                        this.$nextTick(() => this.focusValidationField(row, result.fokus));
                    },

                    validateJournalRow(row) {
                        const accountCode = this.normalizeAccountCode(row?.querySelector('input[name$="[faccount]"]')?.value);
                        const referenceNo = (row?.querySelector('input[name$="[frefno]"]')?.value || '').toString().trim();
                        const subaccountCode = (row?.querySelector('input[name$="[fsubaccount]"]')?.value || '').toString()
                            .trim();

                        if (accountCode === '') {
                            return {
                                status: 'OK',
                                message: ''
                            };
                        }

                        const systemAccounts = this.journalAccountValidation?.system || {};
                        const stockAccounts = this.journalAccountValidation?.stock || {};
                        const referenceAccounts = this.journalAccountValidation?.reference || {};
                        const accountMeta = this.getValidationAccountMeta(accountCode);
                        const accountLabel = this.getValidationAccountLabel(accountCode);

                        if (Object.prototype.hasOwnProperty.call(systemAccounts, accountCode)) {
                            return {
                                status: 'ERROR',
                                validasi: 'Account Sistem',
                                message: `Account ${accountLabel} tidak boleh digunakan untuk jurnal. Perlakuan khusus oleh System.`,
                                fokus: 'faccount',
                            };
                        }

                        if (Object.prototype.hasOwnProperty.call(stockAccounts, accountCode)) {
                            return {
                                status: 'ERROR',
                                validasi: 'Account Stok',
                                message: `Account ${accountLabel} sebaiknya menggunakan Adjustment Stok, karena berhubungan dengan stok.`,
                                fokus: 'faccount',
                            };
                        }

                        if (Object.prototype.hasOwnProperty.call(referenceAccounts, accountCode) && referenceNo === '') {
                            return {
                                status: 'ERROR',
                                validasi: 'Nomor Referensi',
                                message: 'No. Referensi harus diisi untuk account Piutang/Hutang Dagang.',
                                fokus: 'frefno',
                            };
                        }

                        if (!accountMeta) {
                            return {
                                status: 'ERROR',
                                validasi: 'Keberadaan Account di Database',
                                message: 'Account ini tidak ada atau bukan account detail.',
                                fokus: 'faccount',
                            };
                        }

                        if (String(accountMeta.fhavesubaccount || '0') === '1' && subaccountCode === '') {
                            const subaccountType = this.getRowSubaccountType(row);
                            const subaccountLabel = subaccountType === 'C' ? 'Customer' : (subaccountType === 'P' ? 'Supplier' :
                                'Sub-Account');

                            return {
                                status: 'ERROR',
                                validasi: subaccountLabel,
                                message: `Account ini memiliki ${subaccountLabel}. Harap pilih ${subaccountLabel} terlebih dahulu.`,
                                fokus: 'fsubaccount',
                            };
                        }

                        return {
                            status: 'OK',
                            message: ''
                        };
                    },

                    openAccountBrowse(event) {
                        if (this.isReadOnly) return;

                        this.activeLookupRow = event.currentTarget.closest('tr.detail-row');
                        this.activeLookupType = 'account';
                        window.dispatchEvent(new CustomEvent('account-browse-open'));
                    },

                    openSubaccountBrowse(event) {
                        if (this.isReadOnly) return;

                        this.activeLookupRow = event.currentTarget.closest('tr.detail-row');
                        if (!this.rowHasSubaccountEnabled(this.activeLookupRow)) {
                            return;
                        }

                        const type = this.getRowSubaccountType(this.activeLookupRow);
                        if (type === 'C') {
                            this.activeLookupType = 'customer';
                            window.dispatchEvent(new CustomEvent('customer-browse-open'));
                            return;
                        }

                        if (type === 'P') {
                            this.activeLookupType = 'supplier';
                            window.dispatchEvent(new CustomEvent('supplier-browse-open'));
                            return;
                        }

                        this.activeLookupType = 'subaccount';
                        window.dispatchEvent(new CustomEvent('subaccount-browse-open'));
                    },

                    applyLookupValue(row, fieldName, displayRole, code, label) {
                        if (!row) {
                            return;
                        }

                        const hiddenField = row.querySelector(`input[name$="[${fieldName}]"]`);
                        const displayField = row.querySelector(`[data-role="${displayRole}"]`);

                        if (hiddenField) {
                            hiddenField.value = code || '';
                        }

                        if (displayField) {
                            displayField.value = label || '';
                        }

                        this.activeLookupRow = null;
                        this.activeLookupType = null;
                        this.checkAndAutoAppendRow();
                    },

                    applyAccountLookupValue(row, code, name, hasSubaccount, subaccountType = 'S') {
                        if (!row) {
                            return;
                        }

                        const hiddenField = row.querySelector('input[name$="[faccount]"]');
                        const codeField = row.querySelector('[data-role="account-code-display"]');
                        const nameField = row.querySelector('[data-role="account-name-display"]');
                        const hasSubaccountField = row.querySelector('[data-role="account-has-subaccount"]');
                        const subaccountTypeField = row.querySelector('[data-role="account-subaccount-type"]');
                        const subaccountHiddenField = row.querySelector('input[name$="[fsubaccount]"]');
                        const subaccountDisplayField = row.querySelector('[data-role="subaccount-display"]');

                        if (hiddenField) {
                            hiddenField.value = code || '';
                        }

                        if (codeField) {
                            codeField.value = code || '';
                        }

                        if (nameField) {
                            nameField.value = name || '';
                        }

                        if (hasSubaccountField) {
                            hasSubaccountField.value = hasSubaccount ? '1' : '0';
                        }

                        if (subaccountTypeField) {
                            subaccountTypeField.value = subaccountType || 'S';
                        }

                        if (subaccountHiddenField) {
                            subaccountHiddenField.value = '';
                        }

                        if (subaccountDisplayField) {
                            subaccountDisplayField.value = '';
                        }

                        this.syncSubaccountState(row, hasSubaccount, subaccountType);
                        const validationResult = this.validateJournalRow(row);
                        if (validationResult.status === 'ERROR') {
                            if (validationResult.validasi === 'Account Sistem' || validationResult.validasi ===
                                'Account Stok') {
                                this.clearAccountSelection(row);
                            }
                            this.presentValidationError(validationResult, row);
                        }
                        this.activeLookupRow = null;
                        this.activeLookupType = null;
                        this.checkAndAutoAppendRow();
                    },

                    rowHasSubaccountEnabled(row) {
                        const field = row?.querySelector('[data-role="account-has-subaccount"]');
                        return String(field?.value || '0') === '1';
                    },

                    getRowSubaccountType(row) {
                        return this.normalizeSubaccountType(row?.querySelector('[data-role="account-subaccount-type"]')?.value);
                    },

                    syncSubaccountState(row, forceEnabled = null, forceType = null) {
                        if (!row) {
                            return;
                        }

                        const enabled = forceEnabled ?? this.rowHasSubaccountEnabled(row);
                        const type = this.normalizeSubaccountType(forceType || this.getRowSubaccountType(row));
                        const displayField = row.querySelector('[data-role="subaccount-display"]');
                        const hiddenField = row.querySelector('input[name$="[fsubaccount]"]');
                        const browseButton = row.querySelector('.detail-subaccount-btn');
                        const hint = row.querySelector('.detail-subaccount-hint');

                        if (!enabled) {
                            if (displayField) {
                                displayField.value = '';
                            }

                            if (hiddenField) {
                                hiddenField.value = '';
                            }
                        }

                        if (browseButton) {
                            browseButton.disabled = !enabled;
                            browseButton.title = type === 'C' ? 'Cari Customer' : (type === 'P' ? 'Cari Supplier' :
                                'Cari Sub Account');
                            browseButton.classList.toggle('opacity-50', !enabled);
                            browseButton.classList.toggle('cursor-not-allowed', !enabled);
                        }

                        if (displayField && !displayField.value) {
                            displayField.placeholder = type === 'C' ? 'Pilih Customer' : (type === 'P' ? 'Pilih Supplier' :
                                'Pilih Sub Account');
                        }

                        if (hint) {
                            hint.classList.toggle('hidden', !!enabled);
                        }
                    },

                    addRow() {
                        if (this.isReadOnly) return;

                        const tbody = document.getElementById('detailRows');
                        const template = tbody.querySelector('tr.detail-row');

                        if (!template) return;

                        const clone = template.cloneNode(true);
                        clone.querySelectorAll('input, textarea').forEach((field) => field.value = '');
                        clone.querySelectorAll('[data-role="account-subaccount-type"]').forEach((field) => field.value = 'S');
                        clone.querySelectorAll('select').forEach((field) => field.selectedIndex = 0);
                        tbody.appendChild(clone);
                        this.renumberRows();
                        this.syncSubaccountState(clone, false);
                        this.syncRowAmountState(clone);
                        this.updateTotal();
                    },

                    removeRow(event) {
                        if (this.isReadOnly) return;

                        const tbody = document.getElementById('detailRows');
                        if (tbody.querySelectorAll('tr.detail-row').length === 1) {
                            tbody.querySelectorAll('input, textarea').forEach((field) => field.value = '');
                            tbody.querySelectorAll('select').forEach((field) => field.selectedIndex = 0);
                        } else {
                            event.currentTarget.closest('tr.detail-row').remove();
                        }

                        this.renumberRows();
                        this.updateTotal();
                    },

                    renumberRows() {
                        const rows = document.querySelectorAll('#detailRows tr.detail-row');
                        rows.forEach((row, index) => {
                            row.querySelector('td').textContent = index + 1;
                            row.querySelectorAll('select, textarea, input').forEach((field) => {
                                const name = field.getAttribute('name');
                                if (name) {
                                    field.setAttribute('name', name.replace(/details\[\d+\]/,
                                        `details[${index}]`));
                                }
                            });
                        });

                        rows.forEach((row) => {
                            this.syncSubaccountState(row);
                            this.syncRowAmountState(row);
                        });
                        this.checkAndAutoAppendRow();
                    },

                    updateTotal() {
                        refreshPengeluaranKasTotal();
                    },

                    resolveDetailDk(amount) {
                        const numericAmount = parseFloat(amount || 0) || 0;

                        if (this.isPenerimaanKasForm) {
                            return numericAmount >= 0 ? 'K' : 'D';
                        }

                        return numericAmount >= 0 ? 'D' : 'K';
                    },

                    syncRowAmountState(row) {
                        if (!row) {
                            return;
                        }

                        const amountField = row.querySelector('.detail-amount');
                        const dkBadge = row.querySelector('[data-role="detail-dk-badge"]');

                        if (!amountField || !dkBadge) {
                            return;
                        }

                        const dkValue = this.resolveDetailDk(amountField.value);
                        dkBadge.textContent = dkValue;
                        dkBadge.classList.remove('border-blue-200', 'bg-blue-50', 'text-blue-700', 'border-amber-200',
                            'bg-amber-50', 'text-amber-700');
                        dkBadge.classList.add(...(dkValue === 'D' ? ['border-blue-200', 'bg-blue-50', 'text-blue-700'] : [
                            'border-amber-200', 'bg-amber-50', 'text-amber-700'
                        ]));
                    },


                    handleSubmit(event) {
                        if (this.isReadOnly) {
                            return;
                        }

                        const rows = Array.from(document.querySelectorAll('#detailRows tr.detail-row'));
                        for (const row of rows) {
                            const result = this.validateJournalRow(row);
                            if (result.status === 'ERROR') {
                                event.preventDefault();
                                this.presentValidationError(result, row);
                                return;
                            }
                        }
                    },

                    validateActiveLookupRow(row) {
                        if (row) {
                            const result = this.validateJournalRow(row);
                            if (result.status === 'ERROR') {
                                this.presentValidationError(result, row);
                            }
                        }
                    },
                }
            }

            function refreshPengeluaranKasTotal() {
                const total = Array.from(document.querySelectorAll('.detail-amount'))
                    .reduce((sum, field) => sum + (parseFloat(field.value || 0) || 0), 0);

                const totalField = document.getElementById('detailTotal');
                if (totalField) {
                    totalField.value = total.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }

            document.addEventListener('input', (event) => {
                if (event.target.classList.contains('detail-amount')) {
                    const formRoot = event.target.closest('[x-data]');
                    const alpineComponent = formRoot?._x_dataStack?.[0];
                    if (alpineComponent?.syncRowAmountState) {
                        alpineComponent.syncRowAmountState(event.target.closest('tr.detail-row'));
                    }
                    refreshPengeluaranKasTotal();
                }

                if (event.target.closest('tr.detail-row')) {
                    const formRoot = event.target.closest('[x-data]');
                    const alpineComponent = formRoot?._x_dataStack?.[0];
                    if (alpineComponent?.checkAndAutoAppendRow) {
                        alpineComponent.checkAndAutoAppendRow();
                    }
                }
            });

            document.addEventListener('change', (event) => {
                if (event.target.closest('tr.detail-row')) {
                    const formRoot = event.target.closest('[x-data]');
                    const alpineComponent = formRoot?._x_dataStack?.[0];
                    if (alpineComponent?.checkAndAutoAppendRow) {
                        alpineComponent.checkAndAutoAppendRow();
                    }
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                if (typeof pengeluaranKasForm === 'function') {
                    const rows = document.querySelectorAll('#detailRows tr.detail-row');
                    rows.forEach((row, index) => {
                        row.querySelector('td').textContent = index + 1;
                    });

                    rows.forEach((row) => {
                        const field = row.querySelector('[data-role="account-has-subaccount"]');
                        const enabled = String(field?.value || '0') === '1';
                        const browseButton = row.querySelector('.detail-subaccount-btn');
                        const hint = row.querySelector('.detail-subaccount-hint');

                        if (browseButton) {
                            browseButton.disabled = !enabled;
                            browseButton.classList.toggle('opacity-50', !enabled);
                            browseButton.classList.toggle('cursor-not-allowed', !enabled);
                        }

                        if (hint) {
                            hint.classList.toggle('hidden', enabled);
                        }

                        const amountField = row.querySelector('.detail-amount');
                        const dkBadge = row.querySelector('[data-role="detail-dk-badge"]');
                        if (amountField && dkBadge) {
                            const isPenerimaanKasForm = @json($isPenerimaanKasForm);
                            const numericAmount = parseFloat(amountField.value || 0) || 0;
                            const dkValue = isPenerimaanKasForm ?
                                (numericAmount >= 0 ? 'K' : 'D') :
                                (numericAmount >= 0 ? 'D' : 'K');
                            dkBadge.textContent = dkValue;
                            dkBadge.classList.remove('border-blue-200', 'bg-blue-50', 'text-blue-700',
                                'border-amber-200', 'bg-amber-50', 'text-amber-700');
                            dkBadge.classList.add(...(dkValue === 'D' ? ['border-blue-200', 'bg-blue-50',
                                'text-blue-700'
                            ] : ['border-amber-200', 'bg-amber-50', 'text-amber-700']));
                        }
                    });
                }
            });
        </script>
    @endpush
@endunless
