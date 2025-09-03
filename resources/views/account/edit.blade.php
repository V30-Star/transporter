@extends('layouts.app')

@section('title', 'Master Account')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
        <form action="{{ route('account.update', $account->faccid) }}" method="POST">
            @csrf
            @method('PATCH')

            {{-- Account Header (Browse) --}}
            {{-- Bungkus semua dengan x-data --}}
            <div x-data="accHeaderBrowser()">

                {{-- Field Account Header --}}
                <div class="mt-4 lg:col-span-4">
                    <label class="block text-sm font-medium mb-1">Account Header</label>
                    <div class="flex">
                        <div class="relative flex-1">
                            <select id="accHeaderSelect" name="faccupline_view"
                                class="w-full border rounded-l px-3 py-2 bg-gray-100 text-gray-700 cursor-not-allowed"
                                disabled>
                                <option value=""></option>
                                @foreach ($headers as $header)
                                    <option value="{{ $header->faccid }}"
                                        {{ old('faccupline', $account->faccupline) == $header->faccid ? 'selected' : '' }}>
                                        {{ $header->faccount }} - {{ $header->faccname }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="hidden" name="faccupline" id="accHeaderHidden"
                                value="{{ old('faccupline', $account->faccupline) }}">

                            {{-- overlay klik --}}
                            <div class="absolute inset-0" role="button" aria-label="Browse Account Header"
                                @click="openBrowse()"></div>
                        </div>

                        {{-- tombol browse --}}
                        <button type="button" @click="openBrowse()"
                            class="border -ml-px px-3 py-2 bg-white hover:bg-gray-50 rounded-r"
                            title="Browse Account Header">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- Modal Browse Account Header --}}
                <div x-show="open" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/40" @click="close()"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                        <div class="p-4 border-b flex items-center gap-3">
                            <h3 class="text-lg font-semibold">Browse Account Header</h3>
                            <div class="ml-auto flex items-center gap-2">
                                <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                    placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                <button type="button" @click="search()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                            </div>
                        </div>

                        <div class="p-0 overflow-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th class="text-left p-2">Account (Kode - Nama)</th>
                                        <th class="text-center p-2 w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in rows" :key="row.id">
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="p-2" x-text="`${row.faccount} - ${row.faccname}`"></td>
                                            <td class="p-2 text-center">
                                                <button type="button" @click="choose(row)"
                                                    class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">
                                                    Pilih
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="rows.length === 0">
                                        <td colspan="2" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3 border-t flex items-center gap-2">
                            <div class="text-sm text-gray-600">
                                <span x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span>
                            </div>
                            <div class="ml-auto flex items-center gap-2">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                    class="px-3 py-1 rounded border"
                                    :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'">Prev</button>
                                <button type="button" @click="next()" :disabled="page >= lastPage"
                                    class="px-3 py-1 rounded border"
                                    :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                        'bg-gray-100 hover:bg-gray-200'">Next</button>
                                <button type="button" @click="close()"
                                    class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kode Account --}}
            <div class="mt-4">
                <label class="block text-sm font-medium">Kode Account</label>
                <input type="text" name="faccount" value="{{ old('faccount', $account->faccount) }}"
                    class="w-full border rounded px-3 py-2 @error('faccount') border-red-500 @enderror" maxlength="10"
                    pattern="^\d+(-\d+)*$" title="Format harus angka & boleh pakai '-' (mis: 1-123)">
                @error('faccount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nama Account --}}
            <div class="mt-4">
                <label class="block text-sm font-medium">Nama Account</label>
                <input type="text" name="faccname" value="{{ old('faccname', $account->faccname) }}"
                    class="w-full border rounded px-3 py-2 @error('faccname') border-red-500 @enderror" maxlength="50">
                @error('faccname')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Saldo Normal --}}
            <div class="mt-4">
                <label for="fnormal" class="block text-sm font-medium">Saldo Normal</label>
                <select name="fnormal" id="fnormal" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fnormal', $account->fnormal) == '1' ? 'selected' : '' }}>Debet</option>
                    <option value="2" {{ old('fnormal', $account->fnormal) == '2' ? 'selected' : '' }}>Kredit</option>
                </select>
            </div>

            {{-- Account Type (per UI kamu saat ini: 1=Detil, 2=Header) --}}
            <div class="mt-4">
                <label for="fend" class="block text-sm font-medium">Account Type</label>
                <select name="fend" id="fend" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fend', $account->fend) == '1' ? 'selected' : '' }}>Detil</option>
                    <option value="0" {{ old('fend', $account->fend) == '0' ? 'selected' : '' }}>Header</option>
                </select>
            </div>

            {{-- Sub Account --}}
            <div class="mt-4" x-data="{ subAccount: {{ old('fhavesubaccount', $account->fhavesubaccount ?? 0) ? 'true' : 'false' }} }">
                <label for="fhavesubaccount" class="flex items-center space-x-2">
                    <input type="checkbox" name="fhavesubaccount" id="fhavesubaccount" value="1"
                        x-model="subAccount">
                    <span class="text-sm">Ada Sub Account?</span>
                </label>

                <div class="mt-3">
                    <label for="ftypesubaccount" class="block text-sm font-medium">Type</label>
                    <select name="ftypesubaccount" id="ftypesubaccount" class="w-full border rounded px-3 py-2"
                        :disabled="!subAccount" :class="!subAccount ? 'bg-gray-200' : ''">
                        <option value="Sub Account"
                            {{ old('ftypesubaccount', ($account->ftypesubaccount ?? '') === 'S' ? 'Sub Account' : '') == 'Sub Account' ? 'selected' : '' }}>
                            Sub Account</option>
                        <option value="Customer"
                            {{ old('ftypesubaccount', ($account->ftypesubaccount ?? '') === 'C' ? 'Customer' : '') == 'Customer' ? 'selected' : '' }}>
                            Customer</option>
                        <option value="Supplier"
                            {{ old('ftypesubaccount', ($account->ftypesubaccount ?? '') === 'P' ? 'Supplier' : '') == 'Supplier' ? 'selected' : '' }}>
                            Supplier</option>
                    </select>
                </div>
            </div>

            {{-- Initial Jurnal --}}
            <div class="mt-4">
                <label class="block text-sm font-medium">Initial Jurnal#</label>
                <input type="text" name="finitjurnal" value="{{ old('finitjurnal', $account->finitjurnal) }}"
                    class="w-full border rounded px-3 py-2 @error('finitjurnal') border-red-500 @enderror" maxlength="2">
                @error('finitjurnal')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-red-600 text-sm mt-1">** Khusus Jurnal Kas/Bank</p>
            </div>

            {{-- User Level --}}
            <div class="mt-4">
                <label for="fuserlevel" class="block text-sm font-medium">User Level</label>
                <select name="fuserlevel" id="fuserlevel" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ old('fuserlevel', $account->fuserlevel) == '1' ? 'selected' : '' }}>User
                    </option>
                    <option value="2" {{ old('fuserlevel', $account->fuserlevel) == '2' ? 'selected' : '' }}>
                        Supervisor</option>
                    <option value="3" {{ old('fuserlevel', $account->fuserlevel) == '3' ? 'selected' : '' }}>Admin
                    </option>
                </select>
            </div>

            {{-- Non Aktif --}}
            <div class="mt-6 md:col-span-2 flex justify-center items-center space-x-2">
                <input type="checkbox" name="fnonactive" id="statusToggle" class="form-checkbox h-5 w-5 text-indigo-600"
                    {{ old('fnonactive', $account->fnonactive) == '1' ? 'checked' : '' }}>
                <label class="block text-sm font-medium">Non Aktif</label>
            </div>

            {{-- Tombol --}}
            <div class="mt-6 flex justify-center space-x-4">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                </button>
                <button type="button" onclick="window.location.href='{{ route('account.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" /> Kembali
                </button>
            </div>

            {{-- Footer info --}}
            <div class="mt-6 text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                <strong>{{ auth()->user()->fname ?? '—' }}</strong>
                <span class="ml-2 text-right">
                    {{ now()->format('d M Y, H:i') }}, Terakhir di Update oleh:
                    <strong>{{ $account->fupdatedby ?? '—' }}</strong>
                </span>
            </div>
        </form>
    </div>
    </div>
    <script>
        function accHeaderBrowser() {
            return {
                open: false,
                keyword: '',
                page: 1,
                lastPage: 1,
                perPage: 10,
                total: 0,
                rows: [],
                apiUrl() {
                    const u = new URL("{{ route('accounts.browse') }}", window.location.origin);
                    u.searchParams.set('q', this.keyword || '');
                    u.searchParams.set('per_page', this.perPage);
                    u.searchParams.set('page', this.page);
                    return u.toString();
                },
                async fetch() {
                    try {
                        const res = await fetch(this.apiUrl(), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const j = await res.json();
                        this.rows = j.data || [];
                        this.page = j.current_page || 1;
                        this.lastPage = j.last_page || 1;
                        this.total = j.total || 0;
                    } catch (e) {
                        console.error('Browse error:', e);
                        this.rows = [];
                        this.page = 1;
                        this.lastPage = 1;
                        this.total = 0;
                    }
                },
                openBrowse() {
                    this.open = true;
                    this.page = 1;
                    this.fetch();
                },
                close() {
                    this.open = false;
                    this.keyword = '';
                    this.rows = [];
                },
                search() {
                    this.page = 1;
                    this.fetch();
                },
                prev() {
                    if (this.page > 1) {
                        this.page--;
                        this.fetch();
                    }
                },
                next() {
                    if (this.page < this.lastPage) {
                        this.page++;
                        this.fetch();
                    }
                },
                choose(row) {
                    const sel = document.getElementById('accHeaderSelect');
                    const hid = document.getElementById('accHeaderHidden');
                    if (!sel) {
                        this.close();
                        return;
                    }
                    const label = `${row.faccount} - ${row.faccname}`;
                    let opt = [...sel.options].find(o => o.value == String(row.id));
                    if (!opt) {
                        opt = new Option(label, row.id, true, true);
                        sel.add(opt);
                    } else {
                        opt.text = label;
                        opt.selected = true;
                    }
                    sel.dispatchEvent(new Event('change'));
                    if (hid) hid.value = row.id;
                    this.close();
                },
            }
        }
    </script>

@endsection
