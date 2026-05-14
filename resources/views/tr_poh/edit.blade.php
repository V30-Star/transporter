@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Order Pembelian' : 'Edit Order Pembelian')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 34px
        }

        .slider:before {
            content: "";
            position: absolute;
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background: #fff;
            transition: .4s
        }

        input:checked+.slider {
            background: #4CAF50
        }

        input:checked+.slider:before {
            transform: translateX(26px)
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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow p-0 overflow-hidden" role="alert">
            {{-- Header Strip --}}
            <div class="d-flex align-items-center px-4 py-3" style="background-color: #c0392b;">
                <i class="bi bi-exclamation-triangle-fill text-white me-2 fs-5"></i>
                <strong class="text-white fs-6">Gagal Menyimpan Data!</strong>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="px-4 py-3" style="background-color: #fdeded; border-left: 5px solid #c0392b;">
                <p class="mb-2 text-danger fw-semibold">
                    <i class="bi bi-info-circle me-1"></i>
                    Periksa kembali data berikut sebelum menyimpan:
                </p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li class="text-danger mb-1">
                            <i class="bi bi-dot fs-5 align-middle"></i>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    @php
        $permissions = explode(',', session('user_restricted_permissions', ''));
        $canEditPermission = in_array('updateTr_poh', $permissions, true);
        $canDeletePermission = in_array('deleteTr_poh', $permissions, true);
    @endphp
    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MODAL BLOCKED BY PENERIMAAN BARANG (QTY TERIMA)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    @if ((!empty($blockedByTerima) && $blockedByTerima) || session('blocked_by_terima'))
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center"
            x-transition.opacity>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div class="relative bg-white w-[92vw] max-w-2xl rounded-2xl shadow-2xl overflow-hidden">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <x-heroicon-o-truck class="w-5 h-5 text-orange-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-orange-700">
                            PO Tidak Dapat {{ $action === 'delete' ? 'Dihapus' : 'Diedit' }}
                        </h3>
                        <p class="text-sm text-orange-500 mt-0.5">
                            PO <strong>{{ $tr_poh->fpono }}</strong> sudah memiliki transaksi Penerimaan Barang:
                        </p>
                    </div>
                    {{-- Tombol X tutup modal --}}
                    <button type="button" @click="open = false"
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 hover:bg-orange-200 flex items-center justify-center transition-colors"
                        title="Tutup">
                        <x-heroicon-o-x-mark class="w-4 h-4 text-orange-600" />
                    </button>
                </div>

                {{-- Body: tabel daftar penerimaan --}}
                <div class="px-6 py-4 max-h-72 overflow-y-auto">
                    @if (!empty($existingTerima) && $existingTerima->isNotEmpty())
                        <table class="w-full text-sm border rounded overflow-hidden">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th class="px-3 py-2 text-left font-semibold">#</th>
                                    <th class="px-3 py-2 text-left font-semibold">No. Terima</th>
                                    <th class="px-3 py-2 text-left font-semibold">Tanggal</th>
                                    <th class="px-3 py-2 text-right font-semibold">Total Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($existingTerima as $idx => $terima)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 font-mono font-medium text-orange-700">
                                            {{ $terima->fstockmtno ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $terima->fdatetime ? \Carbon\Carbon::parse($terima->fdatetime)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-600">
                                            {{ $fmtQty($terima->total_qty) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-600">PO ini sudah memiliki transaksi penerimaan barang terkait.</p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center gap-3">
                    <p class="text-xs text-gray-500">
                        Batalkan transaksi Penerimaan Barang terkait terlebih dahulu sebelum
                        {{ $action === 'delete' ? 'menghapus' : 'mengedit' }} PO ini.
                    </p>
                    <button type="button" @click="open = false"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center gap-2">
                        <x-heroicon-o-arrow-left class="w-5 h-5" />
                        Kembali
                    </button>
                </div>
            </div>
        </div>
    @endif

    @php
        $isDelete = $action === 'delete';
        $isEdit = $action === 'edit';
        $disabled = $isDelete ? 'disabled' : '';
        $readonly = $isDelete ? 'readonly' : '';
        $bgDisabled = $isDelete ? 'bg-gray-100 cursor-not-allowed text-gray-500' : '';
        $canClosePo = $isEdit && $tr_poh->fclose != '1' && (string) ($tr_poh->fprdin ?? '') !== '1';
        $fmtQty = function ($value) {
            $num = (float) ($value ?? 0);
            return number_format($num, 2, ',', '.');
        };
        $oldItemCodes = old('fitemcode', []);
        $oldItemNames = old('fitemname', []);
        $oldSatuans = old('fsatuan', []);
        $oldRefDtNos = old('frefdtno', []);
        $oldRefDtIds = old('frefdtid', []);
        $oldNoUrefs = old('fnouref', []);
        $oldNoAcaks = old('fnoacak', []);
        $oldRefNoAcaks = old('frefnoacak', []);
        $oldRefPrs = old('frefpr', []);
        $oldPrhIds = old('fprhid', []);
        $oldPrNos = old('fprno', []);
        $oldQtys = old('fqty', []);
        $oldPrices = old('fprice', []);
        $oldDiscs = old('fdisc', []);
        $oldTotals = old('ftotal', []);
        $oldDescs = old('fdesc', []);
        $oldKetdts = old('fketdt', []);
        $initialEditPoItems = [];

        foreach ($oldItemCodes as $index => $itemCode) {
            $initialEditPoItems[] = [
                'fitemcode' => $itemCode,
                'fitemname' => $oldItemNames[$index] ?? '',
                'fsatuan' => $oldSatuans[$index] ?? '',
                'frefdtno' => $oldRefDtNos[$index] ?? '',
                'frefdtid' => $oldRefDtIds[$index] ?? '',
                'fnouref' => $oldNoUrefs[$index] ?? '',
                'fnoacak' => $oldNoAcaks[$index] ?? '',
                'frefnoacak' => $oldRefNoAcaks[$index] ?? '',
                'frefpr' => $oldRefPrs[$index] ?? '',
                'fprhid' => $oldPrhIds[$index] ?? '',
                'fprno' => $oldPrNos[$index] ?? '',
                'fqty' => $oldQtys[$index] ?? 0,
                'fprice' => $oldPrices[$index] ?? 0,
                'fdisc' => $oldDiscs[$index] ?? 0,
                'ftotal' => $oldTotals[$index] ?? 0,
                'fdesc' => $oldDescs[$index] ?? '',
                'fketdt' => $oldKetdts[$index] ?? '',
            ];
        }
    @endphp

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[96rem] mx-auto" x-data="mainForm()"
        x-init="init()">

        @if ($isEdit)
            <form action="{{ route('tr_poh.update', $tr_poh->fpohid) }}" method="POST" class="mt-6"
                @submit.prevent="submitForm($el)">
                @csrf
                @method('PATCH')

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold mb-1">Tidak dapat menyimpan</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
            @else
                <div class="mt-6">
        @endif

        @include('tr_poh._form', [
            'mode' => $isEdit ? 'edit' : 'delete',
            'fcabang' => $fcabang,
            'fbranchcode' => $fbranchcode,
            'suppliers' => $suppliers,
            'currencies' => $currencies,
            'currentCurrency' => $currentCurrency,
            'tr_poh' => $tr_poh,
        ])

        {{-- MODAL SUPPLIER (edit only) --}}
        @if ($isEdit)
            <x-transaction.browse-supplier-modal />

            <x-transaction.browse-product-modal />
        @endif

        @php
            $canApproval = in_array('approvePO', explode(',', session('user_restricted_permissions', '')));
        @endphp

        <div class="flex justify-center items-center space-x-2 mt-6">
            @if ($canApproval)
                <label class="block text-sm font-medium">{{ $isDelete ? 'Status Persetujuan' : 'Approve' }}</label>
                <input type="hidden" name="fapproval" value="0">
                <label class="switch">
                    <input type="checkbox" name="fapproval" id="approvalToggle" value="1"
                        {{ $isDelete ? 'disabled' : '' }}
                        {{ old('fapproval', $tr_poh->fapproval ?? 0) ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            @endif
        </div>

        <div class="mt-8 flex justify-center gap-4">
            @if ($isEdit && $canEditPermission)
                @if (!empty($blockedByTerima) && $blockedByTerima)
                    {{-- Simpan di-disable karena ada penerimaan barang --}}
                    <button type="button" disabled title="Tidak dapat disimpan karena sudah ada penerimaan barang"
                        class="bg-blue-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-60">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                @else
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" /> Simpan
                    </button>
                @endif
                @if ($canClosePo)
                    <button type="button" onclick="showClosePoModal()"
                        class="bg-amber-500 text-white px-6 py-2 rounded hover:bg-amber-600 flex items-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 mr-2" /> Close
                    </button>
                @endif
            @elseif (!$isEdit && $canDeletePermission)
                @if (!empty($blockedByTerima) && $blockedByTerima)
                    {{-- Hapus di-disable karena ada penerimaan barang --}}
                    <button type="button" disabled title="Tidak dapat dihapus karena sudah ada penerimaan barang"
                        class="bg-red-300 text-white px-6 py-2 rounded flex items-center cursor-not-allowed opacity-60">
                        <x-heroicon-o-trash class="w-5 h-5 mr-2" /> Hapus
                    </button>
                @else
                    <button type="button" onclick="showDeleteModal()"
                        class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                        <x-heroicon-o-trash class="w-5 h-5 mr-2" /> Hapus
                    </button>
                @endif
            @endif
            <button type="button" onclick="window.location.href='{{ route('tr_poh.index') }}'"
                class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                {{ $isEdit ? 'Keluar' : 'Kembali' }}
            </button>
        </div>

        @if ($isEdit)
            </form>
            @if ($canClosePo)
                <form id="closePoForm" action="{{ route('tr_poh.update', $tr_poh->fpohid) }}" method="POST" class="hidden">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="close_only" value="1">
                    <input type="hidden" name="fclose" value="1">
                </form>
                <div id="closePoModal"
                    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                        <h3 class="text-lg font-semibold mb-2">Konfirmasi Close</h3>
                        <p class="text-sm text-gray-600 mb-4">Apakah anda yakin mau close PO
                            <strong>{{ $tr_poh->fpono }}</strong>?
                        </p>
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeClosePoModal()"
                                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm font-medium">No</button>
                            <button type="submit" form="closePoForm"
                                class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm font-medium">Yes</button>
                        </div>
                    </div>
                </div>
                <script>
                    function showClosePoModal() {
                        document.getElementById('closePoModal')?.classList.remove('hidden');
                    }

                    function closeClosePoModal() {
                        document.getElementById('closePoModal')?.classList.add('hidden');
                    }
                </script>
            @endif
        @else
    </div>
    @endif
    </div>

    {{-- Modal Konfirmasi Hapus (delete only) --}}
    @if ($isDelete && $canDeletePermission)
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-2">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-600 mb-4">Yakin ingin menghapus Order Pembelian
                    <strong>{{ $tr_poh->fpono }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
                <form action="{{ route('tr_poh.destroy', $tr_poh->fpohid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-sm font-medium">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium">Ya,
                            Hapus</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
    @endif

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush

<style>
    div#productTable_length select,
    .dataTables_wrapper #productTable_length select,
    div#supplierBrowseTable_length select,
    .dataTables_wrapper #supplierBrowseTable_length select,
    div#prTable_length select,
    .dataTables_wrapper #prTable_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    div#productTable_length,
    .dataTables_wrapper #productTable_length,
    div#supplierBrowseTable_length,
    .dataTables_wrapper #supplierBrowseTable_length,
    div#prTable_length,
    .dataTables_wrapper #prTable_length {
        min-width: 250px !important;
    }

    div#productTable_length label,
    .dataTables_wrapper #productTable_length label,
    div#supplierBrowseTable_length label,
    .dataTables_wrapper #supplierBrowseTable_length label,
    div#prTable_length label,
    .dataTables_wrapper #prTable_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
</style>

<script>
    window.PRODUCT_MAP = {
        @foreach ($products as $p)
            "{{ $p->fprdcode }}": {
                id: @json($p->fprdid),
                name: @json($p->fprdname),
                units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
                stock: @json($p->fminstock ?? 0),
                unit_ratios: {
                    satuankecil: 1,
                    satuanbesar: @json((float) ($p->fqtykecil ?? 1)),
                    satuanbesar2: @json((float) ($p->fqtykecil2 ?? 1)),
                },
            },
        @endforeach
    };

    window.CURRENCY_MAP = {
        @foreach ($currencies as $cur)
            {{ $cur->fcurrid }}: {
                id: {{ $cur->fcurrid }},
                code: @json($cur->fcurrcode),
                name: @json($cur->fcurrname),
                rate: {{ $cur->frate ?? 0 }}
            },
        @endforeach
    };

    window.cryptoRandom = function() {
        try {
            if (window.crypto?.getRandomValues) {
                const arr = new Uint32Array(1);
                window.crypto.getRandomValues(arr);
                return 'r' + arr[0].toString(16);
            }
        } catch (e) {}
        return 'r' + (Date.now().toString(16) + Math.random().toString(16).slice(2));
    };

    window.fetchLastPrice = async function(fprdcode, fsupplier, fsatuan) {
        if (!fprdcode || !fsupplier || !fsatuan) return null;
        try {
            const url = new URL("{{ route('tr_poh.lastPrice') }}", window.location.origin);
            url.searchParams.set('fprdcode', fprdcode);
            url.searchParams.set('fsupplier', fsupplier);
            url.searchParams.set('fsatuan', fsatuan);
            const res = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!res.ok) return null;
            return await res.json();
        } catch (e) {
            return null;
        }
    };

    function mainForm() {
        const IS_EDIT = {{ $isEdit ? 'true' : 'false' }};

        function newRow() {
            return {
                uid: null,
                fitemcode: '',
                fitemname: '',
                units: [],
                fsatuan: '',
                frefdtno: '',
                fnouref: '',
                fnoacak: '',
                frefnoacak: '',
                frefpr: '',
                fprhid: '',
                fprno: '',
                fqty: 0,
                fprice: 0,
                fdisc: 0,
                ftotal: 0,
                fdesc: '',
                fketdt: '',
                maxqty: 0,
                fqtypr: 0,
                fqtypr_satuan: '',
                fsatuankecil: '',
                fsatuanbesar: '',
                fsatuanbesar2: '',
                fqtykecil: 0,
                fqtykecil2: 0,
                unit_ratios: {
                    satuankecil: 1,
                    satuanbesar: 1,
                    satuanbesar2: 1
                },
                maxqty_satuan: '',
                frefdtid: '',
                fqtypo: 0,
                fqtysisapr: 0,
                fqtydipo: 0,
                fqtykecil_ref: 0,
            };
        }

        return {
            autoCode: true,
            selectedCurrId: '{{ old('fcurrencyid', $currentCurrency->fcurrid ?? '') }}',
            selectedCurrCode: '{{ $currentCurrency->fcurrcode ?? 'IDR' }}',
            rateValue: {{ old('frate', $tr_poh->frate ?? ($currentCurrency->frate ?? 1)) }},
            includePPN: {{ (int) old('fapplyppn', $tr_poh->fapplyppn ?? 0) === 1 ? 'true' : 'false' }},
            ppnMode: {{ old('ppn_mode', $tr_poh->fincludeppn ?? 0) }},
            ppnRate: {{ old('ppn_rate', $tr_poh->fppnpersen ?? 11) }},
            savedItems: @json(count($initialEditPoItems) ? $initialEditPoItems : ($savedItems ?? [])),
            draft: null,
            activeRow: null,
            browseTarget: 'draft',
            showNoItems: false,
            showNoSupplier: false,
            showDupItemModal: false,
            dupItemName: '',
            dupItemSatuan: '',
            showDescModal: false,
            descValue: '',
            descReadonly: false,
            _descTarget: null,

            normalizeNoAcak(value) {
                return (value || '').toString().replace(/\D/g, '').slice(0, 3);
            },

            generateUniqueNoAcak() {
                const used = new Set(this.savedItems.map(item => this.normalizeNoAcak(item.fnoacak)).filter(Boolean));
                let candidate = '';

                do {
                    candidate = Array.from({ length: 3 }, () => '123456789'[Math.floor(Math.random() * 9)]).join('');
                } while (used.has(candidate));

                return candidate;
            },

            get totalHarga() {
                return this.savedItems.reduce((s, it) => s + (it.ftotal || 0), 0);
            },
            get ppnNominal() {
                if (!this.includePPN) return 0;
                const total = this.totalHarga,
                    rate = +this.ppnRate || 0;
                return this.ppnMode === 1 ? Math.round(total * rate / (100 + rate)) : Math.round(total * rate /
                    100);
            },
            get grandTotal() {
                if (!this.includePPN) return this.totalHarga;
                return this.ppnMode === 1 ? this.totalHarga : this.totalHarga + this.ppnNominal;
            },
            get grandTotalRp() {
                if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return this.grandTotal;
                return +(this.grandTotal * (+this.rateValue || 1)).toFixed(2);
            },

            fmtCurr(n) {
                const v = Number(n || 0);
                if (!isFinite(v)) return '-';
                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            rupiah(n) {
                const v = Number(n || 0);
                if (!isFinite(v)) return '-';
                return v.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            itemTotalRp(value) {
                const total = Number(value || 0);
                if (!Number.isFinite(total)) return 0;
                if (!this.selectedCurrCode || this.selectedCurrCode === 'IDR') return total;
                return +(total * (+this.rateValue || 1)).toFixed(2);
            },
            formatQtyValue(value) {
                const num = Number(value);
                if (!Number.isFinite(num)) return '0,00';
                return num.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            openDesc(targetRow, readonly = false) {
                this._descTarget = targetRow;
                this.descValue = targetRow?.fdesc || '';
                this.descReadonly = readonly;
                this.showDescModal = true;
            },
            closeDesc() {
                this.showDescModal = false;
                this._descTarget = null;
                this.descReadonly = false;
            },
            applyDesc() {
                if (this._descTarget) this._descTarget.fdesc = this.descValue;
                this.closeDesc();
            },

            onCurrencyChange() {
                const id = parseInt(this.selectedCurrId);
                const cur = window.CURRENCY_MAP[id];
                if (cur) {
                    this.selectedCurrCode = cur.code;
                    this.rateValue = cur.rate;
                } else {
                    this.selectedCurrCode = '';
                    this.rateValue = 0;
                }
            },

            recalc(row) {
                const qty = Math.max(0, +row.fqty || 0);
                const price = Math.max(0, +row.fprice || 0);
                const disc = Math.min(100, Math.max(0, +row.fdisc || 0));
                row.fqty = qty;
                row.fprice = price;
                row.fdisc = disc;
                row.ftotal = +(qty * price * (1 - disc / 100)).toFixed(2);
            },

            productMeta(code) {
                const key = (code || '').trim();
                const meta = window.PRODUCT_MAP?.[key];
                if (!meta) {
                    return {
                        name: '',
                        units: [],
                        stock: 0,
                        unit_ratios: {
                            satuankecil: 1,
                            satuanbesar: 1,
                            satuanbesar2: 1
                        }
                    };
                }
                return meta;
            },

            formatPrRemainHint(row) {
                return '';
            },

            enforcePrQtyRow(row) {
                const n = +row.fqty;
                if (!Number.isFinite(n)) {
                    row.fqty = 1;
                    return;
                }
                if (n < 1) row.fqty = 1;
                if (!row.frefdtid) return;
                row.maxqty = this.calcMaxQty(row);
            },

            hydrateRowFromMeta(row, meta, keepMaxqty = false) {
                if (!meta) {
                    row.fitemname = '';
                    row.units = [];
                    row.fsatuan = '';
                    if (!keepMaxqty) row.maxqty = 0;
                    if (row === this.draft) {
                        clearDraftUnitSelect();
                    }
                    return;
                }
                row.fitemname = meta.name || '';
                const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim()).filter(Boolean))];
                const currentSatuan = (row.fsatuan || '').trim();
                if (currentSatuan && !units.includes(currentSatuan)) units.unshift(currentSatuan);
                row.units = units;
                if (!currentSatuan) row.fsatuan = units[0] || '';
                if (meta.unit_ratios) row.unit_ratios = meta.unit_ratios;
                if (!keepMaxqty) row.maxqty = 0;
                
                if (row === this.draft) {
                    if (units.length > 1) {
                        populateDraftUnitSelect(units);
                    } else {
                        clearDraftUnitSelect();
                    }
                }
            },

            onCodeTypedRow(row) {
                this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                this.$nextTick(() => this.applyLastPrice(row));
            },
            onCodeTypedSaved(item) {
                this.hydrateRowFromMeta(item, this.productMeta(item.fitemcode));
                this.$nextTick(() => this.applyLastPrice(item));
            },

            getSupplier() {
                return (document.getElementById('supplierCodeHidden')?.value || '').trim();
            },

            async applyLastPrice(row) {
                if (!IS_EDIT) return;
                const supplier = this.getSupplier();
                const code = (row.fitemcode || '').trim();
                const satuan = (row.fsatuan || '').trim();
                if (!code || !supplier || !satuan) return;
                const hist = await window.fetchLastPrice(code, supplier, satuan);
                if (!hist) return;
                if (!row.fprice || row.fprice === 0) {
                    row.fprice = hist.fprice;
                    row.fdisc = hist.fdisc ?? 0;
                    this.recalc(row);
                }
            },

            isComplete(row) {
                return row.fitemcode && row.fitemname && row.fsatuan && Number(row.fqty) > 0;
            },

            calcMaxQty(row) {
                const eq = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();
                const satuanPO = (row.fsatuan || '').trim();
                const satuanPR = (row.fqtypr_satuan || '').trim();
                const satKecil = (row.fsatuankecil || '').trim();
                const satBesar = (row.fsatuanbesar || '').trim();
                const satBesar2 = (row.fsatuanbesar2 || '').trim();
                const rasio = Number(row.fqtykecil || 0);
                const rasio2 = Number(row.fqtykecil2 || 0);
                const sisaPrBaris = Number(row.fqtysisapr ?? 0);

                if (sisaPrBaris > 0 && (!satuanPR || eq(satuanPO, satuanPR))) {
                    return sisaPrBaris;
                }

                const hasRemainField = row.fqtykecil_ref !== undefined && row.fqtykecil_ref !== null && row.fqtykecil_ref !== '';

                let sisaKecil = 0;
                if (hasRemainField) {
                    sisaKecil = Math.max(0, Number(row.fqtykecil_ref) || 0);
                } else {
                    const qtyPR = Number(row.fqtypr) || 0;
                    const fqtypo = Number(row.fqtypo) || 0;
                    const satuanPR = (row.fqtypr_satuan || '').trim();
                    if (!satuanPR || !(qtyPR > 0)) return 0;
                    let qtyPRInKecil = qtyPR;
                    if (eq(satuanPR, satBesar) && rasio > 0) {
                        qtyPRInKecil = qtyPR * rasio;
                    } else if (eq(satuanPR, satBesar2) && rasio2 > 0) {
                        qtyPRInKecil = qtyPR * rasio2;
                    }
                    sisaKecil = Math.max(0, qtyPRInKecil - fqtypo);
                }

                if (!satuanPO || eq(satuanPO, satKecil)) {
                    return sisaKecil;
                }
                if (eq(satuanPO, satBesar) && rasio > 0) {
                    return Math.floor(sisaKecil / rasio);
                }
                if (eq(satuanPO, satBesar2) && rasio2 > 0) {
                    return Math.floor(sisaKecil / rasio2);
                }
                return sisaKecil;
            },

            focusSavedUnit(item, i) {
                if (item.units.length > 1) this.$nextTick(() => document.getElementById('unit_saved_' + i)?.focus());
                else this.focusSavedQty(i);
            },
            focusSavedQty(i) {
                this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
            },
            focusSavedPrice(i) {
                this.$nextTick(() => document.getElementById('price_saved_' + i)?.focus());
            },
            focusSavedDisc(i) {
                this.$nextTick(() => document.getElementById('disc_saved_' + i)?.focus());
            },
            focusDraftCode() {
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            addIfComplete() {
                if (!IS_EDIT) return;
                if (!this.getSupplier()) {
                    this.showNoSupplier = true;
                    return;
                }
                const r = this.draft;
                if (!this.isComplete(r)) {
                    if (!r.fitemcode) return this.$refs.draftCode?.focus();
                    if (!r.fitemname) return this.$refs.draftCode?.focus();
                    if (!r.fsatuan) return r.units.length > 1 ? this.$refs.draftUnit?.focus() : this.$refs.draftCode
                        ?.focus();
                    if (!(Number(r.fqty) > 0)) return this.$refs.draftQty?.focus();
                    return;
                }
                this.recalc(r);
                const dupe = this.savedItems.find(it =>
                    it.fitemcode.trim() === r.fitemcode.trim() && it.fsatuan.trim() === r.fsatuan.trim()
                );
                if (dupe) {
                    this.showDupItemModal = true;
                    this.dupItemName = r.fitemname || r.fitemcode;
                    this.dupItemSatuan = r.fsatuan;
                    return;
                }
                this.savedItems.push({
                    ...r,
                    fnoacak: this.normalizeNoAcak(r.fnoacak) || this.generateUniqueNoAcak(),
                    frefnoacak: this.normalizeNoAcak(r.frefnoacak),
                    uid: cryptoRandom()
                });
                this.showNoItems = false;
                this.draft = newRow();
                this.draft.fnoacak = this.generateUniqueNoAcak();
                this.$nextTick(() => this.$refs.draftCode?.focus());
            },

            removeSaved(i) {
                if (IS_EDIT) this.savedItems.splice(i, 1);
            },

            handleEnterOnCode() {
                if (!IS_EDIT) return;
                if (!this.getSupplier()) {
                    this.showNoSupplier = true;
                    return;
                }
                if (this.draft.units.length > 1) this.$refs.draftUnit?.focus();
                else this.$refs.draftQty?.focus();
            },

            onPrPicked(e) {
                const {
                    header,
                    items
                } = e.detail || {};
                if (!items || !Array.isArray(items)) return;
                const existingKey = (code, satuan) =>
                    `${(code??'').toString().trim()}::${(satuan??'').toString().trim()}`;
                const existingSet = new Set(this.savedItems.map(it => existingKey(it.fitemcode, it.fsatuan)));
                const skipped = [],
                    toAdd = [];
                items.forEach(src => {
                    const fsatuan = (src.fsatuan ?? '').trim();
                    const key = existingKey(src.fitemcode, fsatuan);
                    if (existingSet.has(key)) {
                        skipped.push(src);
                        return;
                    }
                    const meta = this.productMeta(src.fitemcode ?? '');
                    const units = meta ? [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                            .filter(Boolean))] :
                        (Array.isArray(src.units) && src.units.length ? src.units : [fsatuan].filter(Boolean));
                    if (fsatuan && !units.includes(fsatuan)) units.unshift(fsatuan);

                    // Konversi data: prioritas dari src (data PR), fallback ke PRODUCT_MAP
                    const fsatuankecil = src.fsatuankecil || meta?.fsatuankecil || '';
                    const fsatuanbesar = src.fsatuanbesar || meta?.fsatuanbesar || '';
                    const fsatuanbesar2 = src.fsatuanbesar2 || meta?.fsatuanbesar2 || '';
                    const fqtykecil = Number(src.fqtykecil ?? meta?.fqtykecil ?? 0);
                    const fqtykecil2 = Number(src.fqtykecil2 ?? meta?.fqtykecil2 ?? 0);

                    const row = {
                        uid: cryptoRandom(),
                        fitemcode: src.fitemcode ?? '',
                        fitemname: meta ? (meta.name || src.fitemname || '') : (src.fitemname ?? ''),
                        units,
                        fsatuan: fsatuan || units[0] || '',
                        frefdtno: src.frefdtno ?? '',
                        fnouref: src.fnouref ?? '',
                        fnoacak: this.generateUniqueNoAcak(),
                        frefnoacak: this.normalizeNoAcak(src.frefnoacak ?? src.fnoacak ?? ''),
                        frefpr: String(header?.fprhid ?? src.fprhid ?? ''),
                        fprhid: String(src.fprhid ?? header?.fprhid ?? ''),
                        fprno: String(header?.fprno ?? src.fprno ?? ''),
                        fqty: (src.fqty !== null && src.fqty !== undefined && Number(src.fqty) > 0) ?
                            Number(src.fqty) : 1,
                        frefdtid: src.frefdtid ?? '',
                        fqtypo: Number(src.fqtypo ?? 0),
                        fqtysisapr: Number(src.fqtysisapr ?? 0),
                        fqtydipo: Number(src.fqtydipo ?? 0),
                        fqtykecil_ref: Number(src.fqtykecil_ref ?? src.fqtyremain ?? 0),
                        fqtypr: Number(src.fqtypr ?? src.fqty ?? 0),
                        fqtypr_satuan: (src.fqtypr_satuan ?? src.fsatuan ?? '').trim(),
                        fsatuankecil,
                        fsatuanbesar,
                        fsatuanbesar2,
                        fqtykecil,
                        fqtykecil2,
                        maxqty_satuan: src.maxqty_satuan ?? fsatuankecil,
                        fprice: Number(src.fprice ?? 0),
                        fdisc: Number(src.fdisc ?? 0),
                        ftotal: Number(src.ftotal ?? 0),
                        fdesc: src.fdesc ?? src.fketdt ?? '',
                        fketdt: src.fketdt ?? '',
                    };
                    // Hitung maxqty berdasarkan satuan PO saat ini
                    row.maxqty = this.calcMaxQty(row);
                    if (!(Number(row.maxqty) > 0)) return;
                    if (Number(row.maxqty) > 0) {
                        row.fqty = Number(row.maxqty);
                    }
                    if (!row.ftotal && row.fqty && row.fprice)
                        row.ftotal = +(row.fqty * row.fprice * (1 - row.fdisc / 100)).toFixed(2);
                    toAdd.push(row);
                    existingSet.add(key);
                });
                toAdd.forEach(row => {
                    this.savedItems.push(row);
                    if (!row.fprice || row.fprice === 0)
                        this.$nextTick(() => this.applyLastPrice(row));
                });
                if (skipped.length > 0 && toAdd.length === 0) {
                    this.showDupItemModal = true;
                    this.dupItemName = skipped.map(s => s.fitemname || s.fitemcode).join(', ');
                    this.dupItemSatuan = '';
                }
            },

            itemKey(it) {
                return `${(it.fitemcode??'').toString().trim()}::${(it.fsatuan??'').toString().trim()}`;
            },
            getCurrentItemKeys() {
                return this.savedItems.map(it => this.itemKey(it));
            },

            openBrowseFor(where, idx = null) {
                if (!IS_EDIT) return;
                if (!this.getSupplier()) {
                    this.showNoSupplier = true;
                    return;
                }
                this.browseTarget = (where === 'saved' && idx !== null) ? idx : 'draft';
                window.dispatchEvent(new CustomEvent('browse-open', {
                    detail: {
                        forEdit: false
                    }
                }));
            },

            submitForm(form) {
                if (!IS_EDIT) return;
                // Guard: blok submit jika ada penerimaan barang
                if ({{ !empty($blockedByTerima) && $blockedByTerima ? 'true' : 'false' }}) return;
                if (this.savedItems.length < 1) {
                    this.showNoItems = true;
                    return;
                }
                form.submit();
            },

            init() {
                this.savedItems = this.savedItems.map(it => {
                    it.fsatuan = (it.fsatuan ?? '').trim();

                    if (!it.uid) it.uid = cryptoRandom();

                    // Hydrate units
                    if (!it.units || !it.units.length) {
                        const meta = this.productMeta(it.fitemcode);
                        if (meta) {
                            const units = [...new Set((meta.units || []).map(u => (u ?? '').toString().trim())
                                .filter(Boolean))];
                            const matched = units.find(u => u.toLowerCase() === it.fsatuan.toLowerCase());
                            if (matched) it.fsatuan = matched;
                            else if (it.fsatuan) units.unshift(it.fsatuan);
                            it.units = units;
                        } else {
                            it.units = it.fsatuan ? [it.fsatuan] : [];
                        }
                    } else {
                        it.units = [...new Set(it.units.map(u => (u ?? '').toString().trim()).filter(Boolean))];
                        const matched = it.units.find(u => u.toLowerCase() === it.fsatuan.toLowerCase());
                        if (matched) it.fsatuan = matched;
                        else if (it.fsatuan && !it.units.includes(it.fsatuan)) it.units.unshift(it.fsatuan);
                    }

                    // Hydrate data konversi dari PRODUCT_MAP jika belum ada
                    const meta = this.productMeta(it.fitemcode);
                    if (meta && meta.unit_ratios) {
                        it.unit_ratios = it.unit_ratios || meta.unit_ratios;
                    }

                    it.maxqty = this.calcMaxQty(it);
                    it.fnoacak = this.normalizeNoAcak(it.fnoacak) || this.generateUniqueNoAcak();
                    it.frefnoacak = this.normalizeNoAcak(it.frefnoacak);

                    if (!it.uid) it.uid = cryptoRandom();
                    if (!it.fprno) it.fprno = it.frefpr || '';
                    return it;
                });

                if (IS_EDIT) this.draft = newRow();
                if (IS_EDIT) this.draft.fnoacak = this.generateUniqueNoAcak();

                const currId = parseInt(this.selectedCurrId);
                if (currId && window.CURRENCY_MAP[currId]) {
                    this.selectedCurrCode = window.CURRENCY_MAP[currId].code;
                }

                if (!IS_EDIT) return;

                window.getCurrentItemKeys = () => this.getCurrentItemKeys();
                window.addEventListener('show-no-supplier', () => {
                    this.showNoSupplier = true;
                }, {
                    passive: true
                });
                window.addEventListener('pr-picked', this.onPrPicked.bind(this), {
                    passive: true
                });
                window.addEventListener('product-chosen', (e) => {
                    const {
                        product
                    } = e.detail || {};
                    if (!product) return;
                    const apply = (row) => {
                        row.fitemcode = (product.fprdcode || '').toString();
                        this.hydrateRowFromMeta(row, this.productMeta(row.fitemcode));
                        row.fnoacak = this.normalizeNoAcak(row.fnoacak) || this.generateUniqueNoAcak();
                        if (!row.fqty) row.fqty = 1;
                        this.recalc(row);
                        this.$nextTick(() => this.applyLastPrice(row));
                    };
                    if (typeof this.browseTarget === 'number') {
                        const item = this.savedItems[this.browseTarget];
                        if (item) {
                            apply(item);
                            const i = this.browseTarget;
                            this.$nextTick(() => document.getElementById('qty_saved_' + i)?.focus());
                        }
                    } else {
                        apply(this.draft);
                        this.$nextTick(() => this.$refs.draftQty?.focus());
                    }
                }, {
                    passive: true
                });

                const self = this;
                document.addEventListener('change', function(e) {
                    if (e.target && e.target.id === 'draftUnitSelect') {
                        self.draft.fsatuan = e.target.value;
                    }
                });
            }
        };
    }

    function getDraftUnitSelect() {
        return document.getElementById('draftUnitSelect');
    }

    function populateDraftUnitSelect(units) {
        const sel = getDraftUnitSelect();
        if (!sel) return;
        sel.innerHTML = '';
        units.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u;
            opt.textContent = u;
            sel.appendChild(opt);
        });
    }

    function clearDraftUnitSelect() {
        const sel = getDraftUnitSelect();
        if (sel) sel.innerHTML = '';
    }

    @if ($isEdit)
        window.prhFormModal = function() {
            return {
                show: false,
                table: null,
                showDupModal: false,
                dupCount: 0,
                dupSample: [],
                pendingHeader: null,
                pendingUniques: [],
                initDataTable() {
                    if (this.table) this.table.destroy();
                    this.table = $('#prTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('tr_poh.pickable') }}",
                            type: 'GET',
                            data: d => ({
                                draw: d.draw,
                                start: d.start,
                                length: d.length,
                                search: d.search.value,
                                order_column: d.columns[d.order[0].column].data,
                                order_dir: d.order[0].dir
                            })
                        },
                        columns: [{
                                data: 'fprno',
                                name: 'fprno',
                                className: 'font-mono text-sm'
                            },
                            {
                                data: 'fsuppliername',
                                name: 'fsuppliername',
                                className: 'text-sm',
                                render: d => d || '-'
                            },
                            {
                                data: 'fprdate',
                                name: 'fprdate',
                                className: 'text-sm',
                                render: d => formatDate(d)
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center',
                                render: () =>
                                    '<button type="button" class="btn-pick px-4 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white">Pilih</button>'
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        dom: '<"flex justify-between items-center mb-4"f<"ml-auto"l>>rtip',
                        language: {
                            processing: "Memuat data...",
                            search: "Cari:",
                            lengthMenu: "Tampilkan _MENU_",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                            infoEmpty: "Tidak ada data",
                            infoFiltered: "(disaring dari _MAX_ total data)",
                            zeroRecords: "Tidak ada data yang ditemukan",
                            emptyTable: "Tidak ada data tersedia",
                            paginate: {
                                first: "Pertama",
                                last: "Terakhir",
                                next: "Selanjutnya",
                                previous: "Sebelumnya"
                            }
                        },
                        order: [
                            [2, 'desc']
                        ],
                        autoWidth: false,
                        initComplete: function() {
                            const $c = $(this.api().table().container());
                            $c.find('.dt-search .dt-input, .dataTables_filter input').css({
                                width: '300px',
                                padding: '8px 12px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            }).focus();
                            $c.find('.dt-length select, .dataTables_length select').css({
                                padding: '6px 32px 6px 10px',
                                border: '2px solid #e5e7eb',
                                borderRadius: '8px',
                                fontSize: '14px'
                            });
                        }
                    });
                    const self = this;
                    $('#prTable').off('click', '.btn-pick').on('click', '.btn-pick', function() {
                        self.pick(self.table.row($(this).closest('tr')).data());
                    });
                },
                openModal() {
                    if (!(document.getElementById('supplierCodeHidden')?.value || '').trim()) {
                        window.dispatchEvent(new CustomEvent('show-no-supplier'));
                        return;
                    }
                    this.show = true;
                    this.$nextTick(() => this.initDataTable());
                },
                closeModal() {
                    this.show = false;
                    if (this.table) this.table.search('').draw();
                },
                openDupModal(header, duplicates, uniques) {
                    window.transactionReferenceModalHelper.openDupModal(this, header, duplicates, uniques);
                },
                closeDupModal() {
                    window.transactionReferenceModalHelper.closeDupModal(this);
                },
                confirmAddUniques() {
                    window.transactionReferenceModalHelper.confirmAddUniques(this, 'pr-picked');
                },
                async pick(row) {
                    try {
                        const url = `{{ route('tr_poh.items', ['id' => 'PR_ID_PLACEHOLDER']) }}`.replace(
                            'PR_ID_PLACEHOLDER', row.fprhid);
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const json = await res.json();
                        const items = json.items || [];
                        const currentKeys = new Set((window.getCurrentItemKeys?.() || []).map(String));
                        const keyOf = src =>
                            `${(src.fitemcode??'').toString().trim()}::${(src.frefdtno??'').toString().trim()}`;
                        const duplicates = items.filter(src => currentKeys.has(keyOf(src)));
                        const uniques = items.filter(src => !currentKeys.has(keyOf(src)));
                        if (duplicates.length > 0) {
                            this.openDupModal(row, duplicates, uniques);
                            return;
                        }
                        window.dispatchEvent(new CustomEvent('pr-picked', {
                            detail: {
                                header: row,
                                items
                            }
                        }));
                        this.closeModal();
                    } catch (e) {
                        console.error(e);
                    }
                }
            };
        };
    @endif

    function formatDate(s) {
        if (!s || s === 'No Date') return '-';
        const d = new Date(s);
        if (isNaN(d.getTime())) return '-';
        const pad = n => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    document.addEventListener('alpine:init', () => {
        Alpine.store('prh', {
            descPreview: {
                uid: null,
                index: null,
                label: '',
                text: ''
            },
            descList: []
        });
    });
</script>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    @if ($isEdit)
        @include('components.transaction.browse-product-script')
    @endif
@endpush
