@php
    $isReadOnly = $isReadOnly ?? false;
    $formMethod = $formMethod ?? 'POST';
    $detailsOld = old('details');
    $detailRows = is_array($detailsOld)
        ? collect($detailsOld)->map(fn ($row) => (object) $row)
        : $details;
    $selectedHeader = old('faccountheader', $pengeluaranKas->faccountheader);
    $totalAmount = $detailRows->sum(fn ($row) => (float) ($row->fkasdtvalue ?? 0));
@endphp

<style>
    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
    }
</style>

<div x-data="pengeluaranKasForm(@js($isReadOnly))" class="bg-white rounded shadow p-6 md:p-8 max-w-7xl mx-auto">
    <form action="{{ $formAction }}" method="POST">
        @csrf
        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Voucher No.</label>
                <input type="text" name="fkasmtno" value="{{ old('fkasmtno', $pengeluaranKas->fkasmtno) }}"
                    class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    placeholder="Kosongkan untuk auto number" {{ $isReadOnly ? 'readonly' : '' }}>
                @error('fkasmtno')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Date</label>
                <input type="date" name="fkasmtdate"
                    value="{{ old('fkasmtdate', optional($pengeluaranKas->fkasmtdate)->format('Y-m-d') ?? $pengeluaranKas->fkasmtdate) }}"
                    class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>
                @error('fkasmtdate')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Check No.</label>
                <input type="text" name="fnogiro" value="{{ old('fnogiro', $pengeluaranKas->fnogiro) }}"
                    class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>
                @error('fnogiro')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Cash / Bank Account</label>
                <div class="flex">
                    <select name="faccountheader"
                        class="w-full border rounded-l px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                        {{ $isReadOnly ? 'disabled' : '' }}>
                        <option value="">Pilih account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->faccount }}" {{ (string) $selectedHeader === (string) $account->faccount ? 'selected' : '' }}>
                                {{ $account->faccount }} - {{ $account->faccname }}
                            </option>
                        @endforeach
                    </select>
                    @unless ($isReadOnly)
                        <a href="{{ route('account.create') }}" target="_blank" rel="noopener"
                            class="border border-l-0 rounded-r px-3 py-2 bg-white hover:bg-gray-50 inline-flex items-center"
                            title="Tambah Account">
                            <x-heroicon-o-plus class="w-5 h-5" />
                        </a>
                    @endunless
                </div>
                @if ($isReadOnly)
                    <input type="hidden" name="faccountheader" value="{{ $selectedHeader }}">
                @endif
                @error('faccountheader')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Pay To</label>
                <input type="text" name="fwhom" value="{{ old('fwhom', $pengeluaranKas->fwhom) }}"
                    class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>
                @error('fwhom')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Header Description</label>
                <textarea name="fket" rows="3" class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                    {{ $isReadOnly ? 'readonly' : '' }}>{{ old('fket', $pengeluaranKas->fket) }}</textarea>
                @error('fket')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-800">Detail Item</h3>
            </div>

            <div class="overflow-auto border rounded-lg">
                <table class="min-w-full text-sm balanced-detail-table" data-skip-auto-detail-style="true">
                    <colgroup>
                        @if ($isReadOnly)
                            <col style="width:4%;">
                            <col style="width:24%;">
                            <col style="width:24%;">
                            <col style="width:30%;">
                            <col style="width:18%;">
                        @else
                            <col style="width:4%;">
                            <col style="width:22%;">
                            <col style="width:22%;">
                            <col style="width:26%;">
                            <col style="width:18%;">
                            <col style="width:8%;">
                        @endif
                    </colgroup>
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 whitespace-nowrap">No</th>
                            <th class="border px-3 py-2 whitespace-nowrap">Account</th>
                            <th class="border px-3 py-2 whitespace-nowrap">Sub Account</th>
                            <th class="border px-3 py-2 whitespace-nowrap">Description</th>
                            <th class="border px-3 py-2 text-right whitespace-nowrap">Payment Amount</th>
                            @unless ($isReadOnly)
                                <th class="border px-3 py-2 text-center whitespace-nowrap">Aksi</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody id="detailRows">
                        @foreach ($detailRows as $index => $detail)
                            <tr class="detail-row">
                                <td class="border px-3 py-2 text-center align-top">{{ $index + 1 }}</td>
                                <td class="border px-3 py-2 align-top">
                                    <select name="details[{{ $index }}][faccount]"
                                        class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="">Pilih account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->faccount }}"
                                                {{ (string) old("details.$index.faccount", $detail->faccount ?? '') === (string) $account->faccount ? 'selected' : '' }}>
                                                {{ $account->faccount }} - {{ $account->faccname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($isReadOnly)
                                        <input type="hidden" name="details[{{ $index }}][faccount]"
                                            value="{{ old("details.$index.faccount", $detail->faccount ?? '') }}">
                                    @endif
                                    @error("details.$index.faccount")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="border px-3 py-2 align-top">
                                    <select name="details[{{ $index }}][fsubaccount]"
                                        class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="">Pilih sub account</option>
                                        @foreach ($subaccounts as $subaccount)
                                            <option value="{{ $subaccount->fsubaccountcode }}"
                                                {{ (string) old("details.$index.fsubaccount", $detail->fsubaccount ?? '') === (string) $subaccount->fsubaccountcode ? 'selected' : '' }}>
                                                {{ $subaccount->fsubaccountcode }} - {{ $subaccount->fsubaccountname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($isReadOnly)
                                        <input type="hidden" name="details[{{ $index }}][fsubaccount]"
                                            value="{{ old("details.$index.fsubaccount", $detail->fsubaccount ?? '') }}">
                                    @endif
                                    @error("details.$index.fsubaccount")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="border px-3 py-2 align-top">
                                    <textarea name="details[{{ $index }}][fnote]" rows="2"
                                        class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'readonly' : '' }}>{{ old("details.$index.fnote", $detail->fnote ?? '') }}</textarea>
                                    @error("details.$index.fnote")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="border px-3 py-2 align-top">
                                    <input type="number" name="details[{{ $index }}][fkasdtvalue]"
                                        step="0.01" value="{{ old("details.$index.fkasdtvalue", $detail->fkasdtvalue ?? '') }}"
                                        class="detail-amount w-full border rounded px-3 py-2 text-right {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'readonly' : '' }}>
                                    @error("details.$index.fkasdtvalue")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                @unless ($isReadOnly)
                                    <td class="detail-action-cell border px-3 py-2 text-center align-top">
                                        <button type="button" @click="addRow()"
                                            class="detail-add-btn inline-flex items-center bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 whitespace-nowrap">
                                            <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Detail
                                        </button>
                                        <button type="button" @click="removeRow($event)"
                                            class="detail-delete-btn inline-flex items-center bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 whitespace-nowrap">
                                            <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Delete
                                        </button>
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="{{ $isReadOnly ? 5 : 5 }}" class="border px-3 py-2"></td>
                            @unless ($isReadOnly)
                                <td class="border px-3 py-2"></td>
                            @endunless
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <div class="w-full max-w-md">
                    <div class="rounded-lg border bg-gray-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-800">Total</span>
                            <input type="text" id="detailTotal"
                                value="{{ number_format($totalAmount, 2, '.', ',') }}"
                                class="w-48 border rounded px-3 py-2 text-right bg-gray-100 font-semibold" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-center gap-4">
            @unless ($isReadOnly)
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 inline-flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                </button>
            @endunless

            <a href="{{ route('pengeluarankas.index') }}"
                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 inline-flex items-center">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
            </a>
        </div>
    </form>
</div>

@unless ($isReadOnly)
    @push('scripts')
        <script>
            function pengeluaranKasForm(isReadOnly) {
                return {
                    isReadOnly,

                    addRow() {
                        if (this.isReadOnly) return;

                        const tbody = document.getElementById('detailRows');
                        const template = tbody.querySelector('tr.detail-row');

                        if (!template) return;

                        const clone = template.cloneNode(true);
                        clone.querySelectorAll('input, textarea').forEach((field) => field.value = '');
                        clone.querySelectorAll('select').forEach((field) => field.selectedIndex = 0);
                        tbody.appendChild(clone);
                        this.renumberRows();
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
                                    field.setAttribute('name', name.replace(/details\[\d+\]/, `details[${index}]`));
                                }
                            });
                        });

                        this.updateActionButtons();
                    },

                    updateTotal() {
                        const total = Array.from(document.querySelectorAll('.detail-amount'))
                            .reduce((sum, field) => sum + (parseFloat(field.value || 0) || 0), 0);

                        refreshPengeluaranKasTotal();
                    },

                    updateActionButtons() {
                        const rows = Array.from(document.querySelectorAll('#detailRows tr.detail-row'));

                        rows.forEach((row, index) => {
                            const addButton = row.querySelector('.detail-add-btn');
                            const deleteButton = row.querySelector('.detail-delete-btn');
                            const isLastRow = index === rows.length - 1;

                            if (addButton) {
                                addButton.style.display = isLastRow ? 'inline-flex' : 'none';
                            }

                            if (deleteButton) {
                                deleteButton.style.display = isLastRow ? 'none' : 'inline-flex';
                            }
                        });
                    }
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
                    refreshPengeluaranKasTotal();
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                if (typeof pengeluaranKasForm === 'function') {
                    const rows = document.querySelectorAll('#detailRows tr.detail-row');
                    rows.forEach((row, index) => {
                        row.querySelector('td').textContent = index + 1;
                    });

                    const addButtons = document.querySelectorAll('#detailRows .detail-add-btn');
                    const deleteButtons = document.querySelectorAll('#detailRows .detail-delete-btn');
                    const totalRows = document.querySelectorAll('#detailRows tr.detail-row').length;

                    addButtons.forEach((button, index) => {
                        button.style.display = index === totalRows - 1 ? 'inline-flex' : 'none';
                    });

                    deleteButtons.forEach((button, index) => {
                        button.style.display = index === totalRows - 1 ? 'none' : 'inline-flex';
                    });
                }
            });
        </script>
    @endpush
@endunless
