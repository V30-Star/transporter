@extends('layouts.app')

@section('title', 'Jurnal Transaksi')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        [x-cloak] {
            display: none !important
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>

    <script>
        window.ACCOUNTS_DATA = @json($accounts);
        window.SUBACCOUNTS_DATA = @json($subaccounts);
    </script>

    <div x-data="{ open: true }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1600px] w-full mx-auto">

            <form action="{{ route('jurnaltransaksi.store') }}" method="POST" x-data="itemsTable()" x-init="init()"
                @submit="onSubmit($event)"> @csrf

                {{-- ── HEADER jurnalmt ── --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                    {{-- fbranchcode --}}
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium">Cabang</label>
                        <input type="text" class="w-full border rounded px-3 py-2 bg-gray-200 cursor-not-allowed"
                            value="{{ $fcabang }}" disabled>
                        <input type="hidden" name="fbranchcode" value="{{ $fbranchcode }}">
                    </div>

                    {{-- fjurnalno (auto) --}}
                    <div class="lg:col-span-4" x-data="{ autoCode: true }">
                        <label class="block text-sm font-medium mb-1">No. Jurnal</label>
                        <div class="flex items-center gap-3">
                            <input type="text" name="fjurnalno" class="w-full border rounded px-3 py-2"
                                :disabled="autoCode" :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                            <label class="inline-flex items-center select-none">
                                <input type="checkbox" x-model="autoCode" checked>
                                <span class="ml-2 text-sm text-gray-700">Auto</span>
                            </label>
                        </div>
                    </div>

                    {{-- fjurnaltype --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium">Tipe Jurnal</label>
                        <select name="fjurnaltype" class="w-full border rounded px-3 py-2">
                            <option value="JV" selected>JV – Journal Voucher</option>
                            <option value="AP">AP – Accounts Payable</option>
                            <option value="AR">AR – Accounts Receivable</option>
                        </select>
                    </div>

                    {{-- fjurnaldate --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium">Tanggal</label>
                        <input type="date" name="fjurnaldate" value="{{ old('fjurnaldate', date('Y-m-d')) }}"
                            class="w-full border rounded px-3 py-2 @error('fjurnaldate') border-red-500 @enderror">
                        @error('fjurnaldate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- fjurnalnote --}}
                    <div class="lg:col-span-12">
                        <label class="block text-sm font-medium">Keterangan Jurnal</label>
                        <textarea name="fjurnalnote" rows="2"
                            class="w-full border rounded px-3 py-2 @error('fjurnalnote') border-red-500 @enderror"
                            placeholder="Keterangan jurnal...">{{ old('fjurnalnote') }}</textarea>
                        @error('fjurnalnote')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>{{-- end header grid --}}

                {{-- ── DETAIL jurnaldt ── --}}
                <div class="mt-6 space-y-2">

                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800">Detail Jurnal</h3>
                        {{-- Indikator balance --}}
                        <div class="text-sm flex gap-6">
                            <span>Total Debit: <strong x-text="fmt(totalDebit)" class="text-blue-700"></strong></span>
                            <span>Total Kredit: <strong x-text="fmt(totalKredit)" class="text-green-700"></strong></span>
                            <span x-show="totalDebit > 0 || totalKredit > 0"
                                :class="isBalanced ? 'text-green-600 font-semibold' : 'text-red-500 font-semibold'"
                                x-text="isBalanced ? '✓ Balance' : '✗ Tidak Balance'"></span>
                        </div>
                    </div>

                    @error('detail')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror

                    <div class="overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 text-left w-8">#</th>
                                    <th class="p-2 text-left w-52">Account <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-left w-52">Sub Account</th>
                                    <th class="p-2 text-left w-20">D/K <span class="text-red-500">*</span></th>
                                    <th class="p-2 text-left w-72">Keterangan (faccountnote)</th>
                                    <th class="p-2 text-left w-28">Ref No (frefno)</th>
                                    <th class="p-2 text-right w-40">Jumlah (famount) <span class="text-red-500">*</span>
                                    </th>
                                    <th class="p-2 text-center w-28">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                {{-- ── SAVED ROWS ── --}}
                                <template x-for="(it, i) in savedItems" :key="it.uid">
                                    <tr class="border-t align-middle hover:bg-gray-50">
                                        <td class="p-2 text-gray-500" x-text="i + 1"></td>

                                        {{-- faccount --}}
                                        <td class="p-2">
                                            <div class="font-medium text-gray-800" x-text="it.faccname"></div>
                                            <div class="text-xs text-gray-400" x-text="it.faccount"></div>
                                        </td>

                                        {{-- fsubaccount --}}
                                        <td class="p-2 text-gray-700" x-text="it.fsubaccountname || '—'"></td>

                                        {{-- fdk --}}
                                        <td class="p-2">
                                            <span
                                                :class="it.fdk === 'D' ?
                                                    'px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700 font-semibold' :
                                                    'px-2 py-0.5 rounded text-xs bg-green-100 text-green-700 font-semibold'"
                                                x-text="it.fdk === 'D' ? 'Debit' : 'Kredit'"></span>
                                        </td>

                                        {{-- faccountnote --}}
                                        <td class="p-2 text-gray-700 max-w-xs truncate" x-text="it.faccountnote || '—'">
                                        </td>

                                        {{-- frefno --}}
                                        <td class="p-2 text-gray-500 text-xs" x-text="it.frefno || '—'"></td>

                                        {{-- famount --}}
                                        <td class="p-2 text-right font-medium" x-text="fmt(it.famount)"></td>

                                        {{-- Aksi --}}
                                        <td class="p-2 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="edit(i)"
                                                    class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700 hover:bg-amber-200">Edit</button>
                                                <button type="button" @click="removeSaved(i)"
                                                    class="px-2 py-1 rounded text-xs bg-red-100 text-red-600 hover:bg-red-200">Hapus</button>
                                            </div>
                                        </td>

                                        {{-- ── Hidden inputs untuk POST ── --}}
                                        {{-- Sesuai kolom jurnaldt --}}
                                        <td class="hidden">
                                            <input type="hidden" name="faccount[]" :value="it.faccount">
                                            <input type="hidden" name="fsubaccount[]" :value="it.fsubaccountcode">
                                            <input type="hidden" name="fdk[]" :value="it.fdk">
                                            <input type="hidden" name="faccountnote[]" :value="it.faccountnote">
                                            <input type="hidden" name="frefno[]" :value="it.frefno">
                                            <input type="hidden" name="famount[]" :value="it.famount">
                                            <input type="hidden" name="frate[]" :value="it.frate">
                                        </td>
                                    </tr>
                                </template>

                                {{-- ── ROW EDIT ── --}}
                                <tr x-show="editingIndex !== null" class="border-t bg-amber-50 align-middle" x-cloak>
                                    <td class="p-2 text-gray-500" x-text="(editingIndex ?? 0) + 1"></td>

                                    {{-- Account --}}
                                    <td class="p-2">
                                        <select class="w-full border rounded px-2 py-1 select2-acc-edit"
                                            :value="editRow.faccid"
                                            @input="updateAccount(editRow, $event.target.value,
                                                $event.target.options[$event.target.selectedIndex].dataset.name,
                                                $event.target.options[$event.target.selectedIndex].dataset.code)">
                                            <option value="">Pilih Akun</option>
                                            <template x-for="acc in accounts" :key="acc.faccid">
                                                <option :value="acc.faccid" :data-name="acc.faccname"
                                                    :data-code="acc.faccount"
                                                    x-text="`${acc.faccount} - ${acc.faccname}`"></option>
                                            </template>
                                        </select>
                                    </td>

                                    {{-- Sub Account --}}
                                    <td class="p-2">
                                        <select class="w-full border rounded px-2 py-1 select2-sacc-edit transition-colors"
                                            :value="editRow.fsubaccountid" :disabled="!editRow.fhavesubaccount"
                                            :class="!editRow.fhavesubaccount ?
                                                'bg-gray-100 text-gray-400 cursor-not-allowed opacity-60' :
                                                'bg-white'"
                                            @input="updateSubAccount(editRow, $event.target.value,
                                                $event.target.options[$event.target.selectedIndex].dataset.name,
                                                $event.target.options[$event.target.selectedIndex].dataset.code)">
                                            <option value="">— Pilih Sub Akun —</option>
                                            <template x-for="sacc in subaccounts" :key="sacc.fsubaccountid">
                                                <option :value="sacc.fsubaccountid" :data-name="sacc.fsubaccountname"
                                                    :data-code="sacc.fsubaccountcode"
                                                    x-text="`${sacc.fsubaccountcode} - ${sacc.fsubaccountname}`"></option>
                                            </template>
                                        </select>
                                    </td>

                                    {{-- D/K --}}
                                    <td class="p-2">
                                        <select class="w-full border rounded px-2 py-1 select2-dk-edit"
                                            :value="editRow.fdk"
                                            @input="editRow.fdk = $event.target.value; recalcTotals()">
                                            <option value="">D/K</option>
                                            <option value="D">Debit</option>
                                            <option value="K">Kredit</option>
                                        </select>
                                    </td>

                                    {{-- faccountnote --}}
                                    <td class="p-2">
                                        <input type="text" class="w-full border rounded px-2 py-1"
                                            x-model="editRow.faccountnote" placeholder="Keterangan baris">
                                    </td>

                                    {{-- frefno --}}
                                    <td class="p-2">
                                        <input type="text" class="w-full border rounded px-2 py-1"
                                            x-model="editRow.frefno" placeholder="No Ref">
                                    </td>

                                    {{-- famount --}}
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-full text-right"
                                            min="0" step="0.01" x-ref="editAmt"
                                            x-model.number="editRow.famount" @input="recalcTotals()">
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="p-2 text-center">
                                        <div class="flex items-center justify-center gap-1 flex-wrap">
                                            <button type="button" @click="applyEdit()"
                                                class="px-2 py-1 rounded text-xs bg-emerald-600 text-white">Simpan</button>
                                            <button type="button" @click="cancelEdit()"
                                                class="px-2 py-1 rounded text-xs bg-gray-200">Batal</button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- ── ROW DRAFT ── --}}
                                <tr class="border-t bg-green-50 align-middle">
                                    <td class="p-2 text-gray-400" x-text="savedItems.length + 1"></td>

                                    {{-- Account --}}
                                    <td class="p-2">
                                        <select class="w-full border rounded px-2 py-1 select2-acc-draft"
                                            :value="draft.faccid"
                                            @input="updateAccount(draft, $event.target.value,
                                                $event.target.options[$event.target.selectedIndex].dataset.name,
                                                $event.target.options[$event.target.selectedIndex].dataset.code)">
                                            <option value="">Pilih Akun</option>
                                            <template x-for="acc in accounts" :key="acc.faccid">
                                                <option :value="acc.faccid" :data-name="acc.faccname"
                                                    :data-code="acc.faccount"
                                                    x-text="`${acc.faccount} - ${acc.faccname}`"></option>
                                            </template>
                                        </select>
                                    </td>

                                    {{-- Sub Account --}}
                                    <td class="p-2">
                                        <select
                                            class="w-full border rounded px-2 py-1 select2-sacc-draft transition-colors"
                                            :value="draft.fsubaccountid" :disabled="!draft.fhavesubaccount"
                                            :class="!draft.fhavesubaccount ?
                                                'bg-gray-100 text-gray-400 cursor-not-allowed opacity-60' :
                                                'bg-white'"
                                            @input="updateSubAccount(draft, $event.target.value,
                                                $event.target.options[$event.target.selectedIndex].dataset.name,
                                                $event.target.options[$event.target.selectedIndex].dataset.code)">
                                            <option value="">— Pilih Sub Akun —</option>
                                            <template x-for="sacc in subaccounts" :key="sacc.fsubaccountid">
                                                <option :value="sacc.fsubaccountid" :data-name="sacc.fsubaccountname"
                                                    :data-code="sacc.fsubaccountcode"
                                                    x-text="`${sacc.fsubaccountcode} - ${sacc.fsubaccountname}`"></option>
                                            </template>
                                        </select>
                                        <p x-show="!draft.fhavesubaccount && draft.faccid"
                                            class="mt-1 text-xs text-gray-400 italic" x-cloak>
                                            Akun ini tidak memiliki sub akun.
                                        </p>
                                    </td>

                                    {{-- D/K --}}
                                    <td class="p-2">
                                        <select class="w-full border rounded px-2 py-1 select2-dk-draft"
                                            :value="draft.fdk"
                                            @input="draft.fdk = $event.target.value; recalcTotals()">
                                            <option value="">D/K</option>
                                            <option value="D">Debit</option>
                                            <option value="K">Kredit</option>
                                        </select>
                                    </td>

                                    {{-- faccountnote --}}
                                    <td class="p-2">
                                        <input type="text" class="w-full border rounded px-2 py-1"
                                            x-model="draft.faccountnote" placeholder="Keterangan baris">
                                    </td>

                                    {{-- frefno --}}
                                    <td class="p-2">
                                        <input type="text" class="w-full border rounded px-2 py-1"
                                            x-model="draft.frefno" placeholder="No Ref">
                                    </td>

                                    {{-- famount --}}
                                    <td class="p-2 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-full text-right"
                                            min="0" step="0.01" x-ref="draftAmt"
                                            x-model.number="draft.famount" @input="recalcTotals()"
                                            @keydown.enter.prevent="addIfComplete()">
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="p-2 text-center">
                                        <button type="button" @click="addIfComplete()"
                                            class="px-3 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700">
                                            + Tambah
                                        </button>
                                    </td>
                                </tr>

                                {{-- Total row --}}
                                <tr class="border-t bg-gray-50 font-semibold text-sm">
                                    <td colspan="6" class="p-2 text-right text-gray-600">Total:</td>
                                    <td class="p-2 text-right" x-text="fmt(totalDebit + totalKredit)"></td>
                                    <td></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    {{-- Error: tidak ada item --}}
                    <div x-show="showNoItems && savedItems.length === 0" x-cloak
                        class="fixed inset-0 z-[90] flex items-center justify-center" x-transition.opacity>
                        <div class="absolute inset-0 bg-black/50" @click="showNoItems=false"></div>
                        <div class="relative bg-white w-[92vw] max-w-md rounded-2xl shadow-2xl overflow-hidden">
                            <div class="px-5 py-4 border-b flex items-center">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 mr-2" />
                                <h3 class="text-lg font-semibold text-gray-800">Tidak Ada Item</h3>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-sm text-gray-700">Tambahkan minimal satu baris jurnal sebelum menyimpan.</p>
                            </div>
                            <div class="px-5 py-3 border-t flex justify-end">
                                <button type="button" @click="showNoItems=false"
                                    class="h-9 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium">OK</button>
                            </div>
                        </div>
                    </div>

                </div>{{-- end itemsTable --}}

                <div class="mt-8 flex justify-center gap-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                    <button type="button" onclick="window.location='{{ route('jurnaltransaksi.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Keluar
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // ─── Select2 init + bridge ke Alpine ───────────────────────────────────────
        $(document).ready(function() {

            function initSelect2(selector) {
                $(selector).select2({
                    width: '100%'
                });
            }

            // Bridge: setiap kali Select2 memilih nilai → trigger native 'input'
            // agar Alpine @input handler terpanggil
            $(document).on('select2:select select2:clear', 'select', function() {
                this.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            });

            initSelect2('.select2-acc-draft');
            initSelect2('.select2-sacc-draft');
            initSelect2('.select2-dk-draft');
            initSelect2('.select2-acc-edit');
            initSelect2('.select2-sacc-edit');
            initSelect2('.select2-dk-edit');
        });

        // ─── Alpine component ───────────────────────────────────────────────────────
        function itemsTable() {
            return {
                // ── State ──
                showNoItems: false,
                savedItems: [],
                draft: newRow(),
                editingIndex: null,
                editRow: newRow(),

                // Data master
                accounts: window.ACCOUNTS_DATA ?? [],
                subaccounts: window.SUBACCOUNTS_DATA ?? [],

                // Totals
                totalDebit: 0,
                totalKredit: 0,

                get isBalanced() {
                    return this.totalDebit > 0 &&
                        Math.abs(this.totalDebit - this.totalKredit) < 0.005;
                },

                // ── Format angka ──
                fmt(n) {
                    const v = Number(n);
                    if (!isFinite(v) || n === '' || n === null) return '0';
                    return v.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                // ── Update Account → set faccount, faccname, fhavesubaccount ──
                updateAccount(row, faccid, accName, accCode) {
                    const accObj = this.accounts.find(a => String(a.faccid) === String(faccid));
                    Object.assign(row, {
                        faccid: faccid,
                        faccname: accName || (accObj?.faccname ?? ''),
                        faccount: accCode || (accObj?.faccount ?? ''),
                        fhavesubaccount: accObj ? Number(accObj.fhavesubaccount ?? 0) : 0,
                        // reset sub account jika tidak punya
                        fsubaccountid: 0,
                        fsubaccountcode: '',
                        fsubaccountname: '',
                    });
                },

                // ── Update Sub Account → set fsubaccountcode (yang masuk ke DB) ──
                updateSubAccount(row, fsubaccountid, subName, subCode) {
                    Object.assign(row, {
                        fsubaccountid: fsubaccountid,
                        fsubaccountname: subName || '',
                        fsubaccountcode: subCode || '',
                    });
                },

                // ── Hitung total debit & kredit ──
                recalcTotals() {
                    this.totalDebit = this.savedItems
                        .filter(it => it.fdk === 'D')
                        .reduce((s, it) => s + Number(it.famount || 0), 0);
                    this.totalKredit = this.savedItems
                        .filter(it => it.fdk === 'K')
                        .reduce((s, it) => s + Number(it.famount || 0), 0);
                },

                // ── Validasi baris lengkap ──
                isComplete(row) {
                    return row.faccount && row.fdk && Number(row.famount) > 0;
                },

                // ── Tambah baris draft ke savedItems ──
                addIfComplete() {
                    const r = this.draft;
                    if (!r.faccount) return alert('Pilih akun terlebih dahulu.');
                    if (!r.fdk) return alert('Pilih Debit atau Kredit.');
                    if (!(Number(r.famount) > 0)) return this.$refs.draftAmt?.focus();

                    this.savedItems.push({
                        ...r,
                        uid: cryptoRandom()
                    });
                    this.draft = newRow();
                    this.recalcTotals();
                    this.$nextTick(() => {
                        // re-init select2 pada row baru
                        $('.select2-acc-draft').val('').trigger('change');
                        $('.select2-sacc-draft').val('').trigger('change');
                        $('.select2-dk-draft').val('').trigger('change');
                    });
                },

                // ── Edit ──
                edit(i) {
                    this.editingIndex = i;
                    this.editRow = {
                        ...this.savedItems[i]
                    };
                    this.$nextTick(() => {
                        // Sync select2 edit ke nilai editRow
                        $('.select2-acc-edit').val(this.editRow.faccid).trigger('change');
                        $('.select2-sacc-edit').val(this.editRow.fsubaccountid).trigger('change');
                        $('.select2-dk-edit').val(this.editRow.fdk).trigger('change');
                        this.$refs.editAmt?.focus();
                    });
                },

                applyEdit() {
                    const r = this.editRow;
                    if (!r.faccount) return alert('Pilih akun terlebih dahulu.');
                    if (!r.fdk) return alert('Pilih Debit atau Kredit.');
                    if (!(Number(r.famount) > 0)) return this.$refs.editAmt?.focus();

                    this.savedItems.splice(this.editingIndex, 1, {
                        ...r
                    });
                    this.cancelEdit();
                    this.recalcTotals();
                },

                cancelEdit() {
                    this.editingIndex = null;
                    this.editRow = newRow();
                },

                // ── Hapus ──
                removeSaved(i) {
                    this.savedItems.splice(i, 1);
                    this.recalcTotals();
                },

                // ── Submit guard ──
                onSubmit($event) {
                    if (this.savedItems.length === 0) {
                        $event.preventDefault();
                        this.showNoItems = true;
                        return;
                    }
                    if (!this.isBalanced) {
                        $event.preventDefault();
                        alert(
                            `Jurnal tidak balance!\nDebit: ${this.fmt(this.totalDebit)}\nKredit: ${this.fmt(this.totalKredit)}`
                        );
                    }
                },

                init() {
                    // nothing extra needed
                },
            };

            // ─── newRow: field = kolom jurnaldt ─────────────────────────────────────
            function newRow() {
                return {
                    uid: null,
                    // jurnaldt columns
                    faccount: '', // kode akun string → di-submit
                    faccid: '', // hanya untuk select2 binding (tidak di-submit)
                    faccname: '', // display only
                    fhavesubaccount: 0, // flag dari master akun
                    fsubaccountcode: '', // → di-submit sebagai fsubaccount
                    fsubaccountid: '', // hanya untuk select2 binding
                    fsubaccountname: '', // display only
                    fdk: '', // 'D' | 'K'
                    faccountnote: '', // keterangan baris
                    frefno: '', // referensi nomor
                    famount: 0, // jumlah
                    frate: 1, // rate (default 1)
                };
            }

            function cryptoRandom() {
                try {
                    if (window.crypto?.getRandomValues) {
                        const a = new Uint32Array(2);
                        window.crypto.getRandomValues(a);
                        return [...a].map(n => n.toString(16)).join('') + Date.now();
                    }
                } catch (e) {}
                return Math.random().toString(36).slice(2) + Date.now();
            }
        }
    </script>
@endpush
