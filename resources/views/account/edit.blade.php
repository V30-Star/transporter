@extends('layouts.app')

@section('title', 'Edit - Master Account')

@section('content')
@php
    $subType = 'Sub Account';
    if (($account->ftypesubaccount ?? '') === 'C') {
        $subType = 'Customer';
    } elseif (($account->ftypesubaccount ?? '') === 'P') {
        $subType = 'Supplier';
    }
@endphp
<div x-data="{ subAccount: {{ old('fhavesubaccount', $account->fhavesubaccount) ? 'true' : 'false' }} }">

    <div class="max-w-4xl mx-auto py-8 px-6">

        <form action="{{ route('account.update', $account->faccid) }}" method="POST" id="formAccount">
            @csrf
            @method('PATCH')
            <input type="hidden" name="fcurrency" value="IDR">

            {{-- ─── CARD 1: Identitas Akun ─────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Identitas akun</p>
                </div>
                <div class="p-4 space-y-3">

                    {{-- Account Header (Browse) --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Account Header</label>
                        <div class="flex">
                            <input type="text" id="headerDisplay"
                                class="flex-1 border border-r-0 border-gray-300 rounded-l-lg px-3 py-2 text-sm bg-gray-50 focus:outline-none {{ !empty($isUsedInTransaction) ? 'cursor-not-allowed text-gray-500' : 'cursor-pointer focus:border-blue-500' }}"
                                placeholder="Pilih account header..."
                                readonly
                                @if(empty($isUsedInTransaction))
                                @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                @endif
                                value="{{ old('faccupline', $account->faccupline) ? ($selectedHeader ? $selectedHeader->faccount . ' — ' . $selectedHeader->faccname : old('faccupline')) : '' }}">
                            <button type="button"
                                @if(empty($isUsedInTransaction))
                                @click="window.dispatchEvent(new CustomEvent('account-browse-open'))"
                                class="border border-gray-300 rounded-r-lg px-3 py-2 bg-white hover:bg-blue-50 hover:border-blue-400 hover:text-blue-600 text-gray-400 transition-colors"
                                @else
                                disabled
                                class="border border-gray-300 rounded-r-lg px-3 py-2 bg-gray-50 text-gray-300 cursor-not-allowed"
                                @endif
                                title="Browse Account Header">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Badge akun terpilih
                        <div id="selectedHeaderBadge" class="{{ old('faccupline', $account->faccupline) ? 'inline-flex' : 'hidden' }} mt-2 items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700 font-medium">
                            <x-heroicon-o-check class="w-3.5 h-3.5" />
                            <span id="selectedHeaderLabel">{{ $selectedHeader ? $selectedHeader->faccount . ' — ' . $selectedHeader->faccname : old('faccupline') }}</span>
                            @if(empty($isUsedInTransaction))
                            <button type="button" onclick="clearHeader()" class="ml-1 text-blue-400 hover:text-blue-700">
                                <x-heroicon-o-x-mark class="w-3 h-3" />
                            </button>
                            @endif
                        </div> --}}

                        <input type="hidden" name="faccupline" id="accountCodeHidden" value="{{ old('faccupline', $account->faccupline) }}">
                        <input type="hidden" name="faccid"     id="accountIdHidden"   value="{{ old('faccid', $account->faccid) }}">

                        @error('faccupline')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        @if(!empty($isUsedInTransaction))
                            <p class="text-amber-600 text-xs mt-1 font-medium">Account header tidak bisa diubah karena account sudah dipakai transaksi.</p>
                        @endif
                    </div>

                    {{-- Kode & Nama Account (2 kolom) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Kode Account <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="faccount" id="faccount"
                                value="{{ old('faccount', $account->faccount) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-100 @error('faccount') border-red-400 @enderror {{ !empty($isUsedInTransaction) ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'focus:border-blue-500' }}"
                                {{ !empty($isUsedInTransaction) ? 'readonly' : '' }}
                                maxlength="10" placeholder="cth. 1-100-001" autofocus>
                            <p id="faccount-hint" class="text-xs text-gray-400 italic mt-1"></p>
                            @error('faccount')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @if(!empty($isUsedInTransaction))
                                <p class="text-amber-600 text-xs mt-1 font-medium">Kode account tidak bisa diubah karena account sudah dipakai transaksi.</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Nama Account <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="faccname" id="faccname"
                                value="{{ old('faccname', $account->faccname) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('faccname') border-red-400 @enderror"
                                maxlength="50" placeholder="cth. Kas Besar">
                            <p id="faccname-hint" class="text-xs text-gray-400 italic mt-1"></p>
                            @error('faccname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── CARD 2: Konfigurasi ────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Konfigurasi</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Saldo Normal --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Saldo Normal</label>
                        <div class="flex gap-2" x-data="{ val: '{{ old('fnormal', $account->fnormal ?: 'D') }}' }">
                            <input type="hidden" name="fnormal" :value="val">
                            <button type="button" @click="val='D'"
                                :class="val==='D' ? 'bg-blue-50 border-blue-400 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'"
                                class="px-4 py-1.5 rounded-full text-xs border transition-all">Debit</button>
                            <button type="button" @click="val='K'"
                                :class="val==='K' ? 'bg-blue-50 border-blue-400 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'"
                                class="px-4 py-1.5 rounded-full text-xs border transition-all">Kredit</button>
                        </div>
                    </div>

                    {{-- Type Account --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Type Account</label>
                        <div class="flex gap-2" x-data="{ val: '{{ old('fend', $account->fend) }}' }">
                            <input type="hidden" name="fend" :value="val">
                            <button type="button" @click="val='1'"
                                :class="val==='1' ? 'bg-blue-50 border-blue-400 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'"
                                class="px-4 py-1.5 rounded-full text-xs border transition-all">Detil</button>
                            <button type="button" @click="val='0'"
                                :class="val==='0' ? 'bg-blue-50 border-blue-400 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'"
                                class="px-4 py-1.5 rounded-full text-xs border transition-all">Header</button>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Sub Account Toggle --}}
                    <div>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                            @click="subAccount = !subAccount">
                            <div>
                                <p class="text-sm text-gray-800">Ada Sub Account?</p>
                                <p class="text-xs text-gray-400 mt-0.5">Aktifkan jika akun ini memiliki turunan</p>
                            </div>
                            <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                :class="subAccount ? 'bg-blue-500' : 'bg-gray-300'">
                                <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                    :class="subAccount ? 'translate-x-4 left-0.5' : 'left-0.5'"></div>
                            </div>
                        </div>
                        <input type="hidden" name="fhavesubaccount" :value="subAccount ? 1 : 0">

                        {{-- Type Sub Account (muncul jika toggle on) --}}
                        <div x-show="subAccount" x-transition class="mt-2 pl-1">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Type Sub Account</label>
                            <div class="flex gap-2 flex-wrap" x-data="{ sub: '{{ old('ftypesubaccount', $subType) }}' }">
                                <input type="hidden" name="ftypesubaccount" :value="sub">
                                @foreach (['Sub Account', 'Customer', 'Supplier'] as $opt)
                                <button type="button" @click="sub='{{ $opt }}'"
                                    :class="sub==='{{ $opt }}' ? 'bg-blue-50 border-blue-400 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'"
                                    class="px-4 py-1.5 rounded-full text-xs border transition-all">{{ $opt }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Initial Jurnal --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Initial Jurnal
                            <span class="font-normal text-gray-400">(opsional)</span>
                        </label>
                        
                        <div class="flex flex-col items-start gap-1.5">
                            <input type="text" name="finitjurnal" value="{{ old('finitjurnal', $account->finitjurnal) }}"
                                class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase text-center tracking-widest focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('finitjurnal') border-red-400 @enderror"
                                maxlength="2" placeholder="KS">
                            
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-md text-xs text-amber-700">
                                <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                                Khusus untuk akun Kas / Bank
                            </div>

                            @error('finitjurnal')
                                <p class="text-red-500 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── CARD 3: Akses & Status ─────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Akses & status</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- User Level --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-2">User Level</label>
                        <div class="flex gap-2" x-data="{ val: '{{ old('fuserlevel', $account->fuserlevel ?: '1') }}' }">
                            <input type="hidden" name="fuserlevel" :value="val">
                            @foreach (['1' => 'User', '2' => 'Supervisor', '3' => 'Admin'] as $k => $label)
                            <button type="button" @click="val='{{ $k }}'"
                                :class="val==='{{ $k }}' ? 'bg-blue-50 border-blue-400 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-500 hover:border-gray-400'"
                                class="px-4 py-1.5 rounded-full text-xs border transition-all">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Status Aktif --}}
                    <div x-data="{ active: {{ old('fnonactive', $account->fnonactive) == '1' ? 'false' : 'true' }} }">
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                            @click="active = !active; $el.closest('[x-data]').querySelector('input[name=fnonactive]').value = active ? '0' : '1'">
                            <div>
                                <p class="text-sm text-gray-800">Akun aktif</p>
                                <p class="text-xs text-gray-400 mt-0.5">Non-aktif menyembunyikan akun dari transaksi baru</p>
                            </div>
                            <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                :class="active ? 'bg-blue-500' : 'bg-gray-300'">
                                <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                    :class="active ? 'translate-x-4 left-0.5' : 'left-0.5'"></div>
                            </div>
                        </div>
                        <input type="hidden" name="fnonactive" :value="active ? '0' : '1'">
                    </div>

                </div>

                {{-- Footer Buttons --}}
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <button type="button"
                        onclick="window.location.href='{{ route('account.index') }}'"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Kembali
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Simpan
                    </button>
                </div>
            </div>

        </form>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $account->fupdatedat ?: $account->fcreatedat;
            $updatedBy = $account->fupdatedby ?: ($account->fcreatedby ?: '—');
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

    {{-- ─── MODAL BROWSE ACCOUNT ───────────────────────────────────────── --}}
    <div x-data="accountBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="close()"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-3xl flex flex-col overflow-hidden" style="height:580px">

            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-base font-medium text-gray-800">Browse Account Header</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Pilih account yang akan dijadikan header</p>
                </div>
                <button type="button" @click="close()"
                    class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 transition">
                    Tutup
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4" style="min-height:0">
                <table id="accountTable" class="min-w-full text-sm display" style="width:100%">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left p-3 text-xs font-medium text-gray-500 border-b border-gray-200 w-1/3">Kode Account</th>
                            <th class="text-left p-3 text-xs font-medium text-gray-500 border-b border-gray-200">Nama Account</th>
                            <th class="text-center p-3 text-xs font-medium text-gray-500 border-b border-gray-200 w-24">Pilih</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        .ui-autocomplete {
            background: white; z-index: 9999 !important; max-width: 600px;
            border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .ui-menu-item-wrapper { padding: 8px 12px; font-size: 13px; }
        .ui-state-active, .ui-menu-item-wrapper:hover {
            background: #eff6ff !important; color: #1d4ed8 !important;
            border: none !important;
        }
        .hint-text { font-size: 12px; color: #9ca3af; font-style: italic; }
        #accountTable_wrapper .dataTables_filter input {
            padding: 6px 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px;
        }
        #accountTable_wrapper .dataTables_length select {
            padding: 4px 24px 4px 8px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        // ── Helper: clear header ──────────────────────────────────────────
        function clearHeader() {
            document.getElementById('headerDisplay').value = '';
            document.getElementById('accountCodeHidden').value = '';
            document.getElementById('accountIdHidden').value = '';
            document.getElementById('selectedHeaderBadge').classList.add('hidden');
        }

        // ── account-picked event ──────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('account-picked', (ev) => {
                const { faccount, faccid, faccname } = ev.detail || {};

                document.getElementById('headerDisplay').value     = faccount + ' — ' + (faccname || '');
                document.getElementById('accountCodeHidden').value = faccount || '';
                document.getElementById('accountIdHidden').value   = faccid   || '';

                const badge = document.getElementById('selectedHeaderBadge');
                document.getElementById('selectedHeaderLabel').textContent = faccount + ' — ' + (faccname || '');
                badge.classList.remove('hidden');

                const inputInit = document.querySelector('input[name=finitjurnal]');
                if (inputInit) inputInit.placeholder = 'Cek initial jika header Kas/Bank...';
            });
        });

        // ── Autocomplete ──────────────────────────────────────────────────
        $(document).ready(function () {
            function setupAutocomplete(fieldId, searchField, hintSelf, hintOther) {
                $('#' + fieldId).autocomplete({
                    source: function (req, res) {
                        $.getJSON("{{ route('account.suggest') }}", { term: req.term, field: searchField }, res);
                    },
                    minLength: 1,
                    select: function (e, ui) {
                        $('#faccount').val(ui.item.code);
                        $('#faccname').val(ui.item.name);
                        $('#' + hintSelf).text('');
                        $('#' + hintOther).text('');
                        return false;
                    }
                }).on('input', function () {
                    if (!$(this).val()) { $('#faccount-hint').text(''); $('#faccname-hint').text(''); }
                });
            }
            @if(empty($isUsedInTransaction))
            setupAutocomplete('faccount', 'faccount', 'faccount-hint', 'faccname-hint');
            @endif
            setupAutocomplete('faccname', 'faccname', 'faccname-hint', 'faccount-hint');
        });

        // ── Account Browser (Modal + DataTable) ───────────────────────────
        window.accountBrowser = function () {
            return {
                open: false,
                table: null,

                initDataTable() {
                    if (this.table) this.table.destroy();
                    this.table = $('#accountTable').DataTable({
                        processing: true, serverSide: true,
                        ajax: {
                            url: "{{ route('account.browse') }}",
                            data: d => ({
                                draw: d.draw, start: d.start, length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir,
                                fend: 0
                            }),
                            dataSrc: json => json.data
                        },
                        columns: [
                            { data: 'faccount', className: 'font-mono text-sm', width: '28%' },
                            { data: 'faccname', className: 'text-sm' },
                            {
                                data: null, orderable: false, searchable: false,
                                className: 'text-center', width: '18%',
                                render: () => `<button type="button" class="btn-choose px-3 py-1 rounded-md text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition">Pilih</button>`
                            }
                        ],
                        pageLength: 10,
                        dom: '<"flex justify-between items-center mb-3"f<"ml-auto"l>>rtip',
                        language: {
                            search: 'Cari:', lengthMenu: 'Tampilkan _MENU_',
                            info: '_START_–_END_ dari _TOTAL_', infoEmpty: 'Tidak ada data',
                            zeroRecords: 'Data tidak ditemukan', processing: 'Memuat...',
                            paginate: { next: '›', previous: '‹' }
                        },
                        order: [[1, 'asc']], autoWidth: false
                    });

                    $('#accountTable').on('click', '.btn-choose', (e) => {
                        const data = this.table.row($(e.target).closest('tr')).data();
                        this.choose(data);
                    });
                },

                openModal() {
                    this.open = true;
                    this.$nextTick(() => this.initDataTable());
                },

                close() {
                    this.open = false;
                    if (this.table) this.table.search('').draw();
                },

                choose(w) {
                    window.dispatchEvent(new CustomEvent('account-picked', {
                        detail: { faccid: w.faccid, faccount: w.faccount, faccname: w.faccname }
                    }));
                    this.close();
                },

                init() {
                    window.addEventListener('account-browse-open', () => this.openModal(), { passive: true });
                }
            };
        };
    </script>
@endpush
