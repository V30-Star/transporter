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
                <h3 class="text-lg font-semibold">Detail Pengeluaran</h3>
                @unless ($isReadOnly)
                    <button type="button" @click="addRow()"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Tambah Detail
                    </button>
                @endunless
            </div>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 w-16">No</th>
                            <th class="border px-3 py-2">Account</th>
                            <th class="border px-3 py-2">Description</th>
                            <th class="border px-3 py-2 text-right">Payment Amount</th>
                            @unless ($isReadOnly)
                                <th class="border px-3 py-2 w-20 text-center">Aksi</th>
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
                                    <textarea name="details[{{ $index }}][fnote]" rows="2"
                                        class="w-full border rounded px-3 py-2 {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'readonly' : '' }}>{{ old("details.$index.fnote", $detail->fnote ?? '') }}</textarea>
                                    @error("details.$index.fnote")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="border px-3 py-2 align-top">
                                    <input type="number" name="details[{{ $index }}][fkasdtvalue]" min="0"
                                        step="0.01" value="{{ old("details.$index.fkasdtvalue", $detail->fkasdtvalue ?? '') }}"
                                        class="detail-amount w-full border rounded px-3 py-2 text-right {{ $isReadOnly ? 'bg-gray-100' : '' }}"
                                        {{ $isReadOnly ? 'readonly' : '' }}>
                                    @error("details.$index.fkasdtvalue")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                @unless ($isReadOnly)
                                    <td class="border px-3 py-2 text-center align-top">
                                        <button type="button" @click="removeRow($event)"
                                            class="inline-flex items-center bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="{{ $isReadOnly ? 3 : 3 }}" class="border px-3 py-2 text-right font-semibold">
                                Total
                            </td>
                            <td class="border px-3 py-2">
                                <input type="text" id="detailTotal" value="{{ number_format($totalAmount, 2, '.', ',') }}"
                                    class="w-full border rounded px-3 py-2 text-right bg-gray-100 font-semibold" readonly>
                            </td>
                            @unless ($isReadOnly)
                                <td class="border px-3 py-2"></td>
                            @endunless
                        </tr>
                    </tfoot>
                </table>
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
                    },

                    updateTotal() {
                        const total = Array.from(document.querySelectorAll('.detail-amount'))
                            .reduce((sum, field) => sum + (parseFloat(field.value || 0) || 0), 0);

                        refreshPengeluaranKasTotal();
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
        </script>
    @endpush
@endunless
