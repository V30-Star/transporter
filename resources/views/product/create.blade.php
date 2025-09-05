@extends('layouts.app')

@section('title', 'Master Product')

@section('content')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>

    <style>
        .ui-autocomplete {
            z-index: 9999;
            max-height: 240px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* The switch - the outer box */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        /* Hide the default checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        /* The slider circle */
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
        }

        /* When the checkbox is checked, change the background color */
        input:checked+.slider {
            background-color: #4CAF50;
        }

        /* Move the slider circle when checked */
        input:checked+.slider:before {
            transform: translateX(26px);
        }

        /* Add a border when checked */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
    <style>
        input:focus,
        select:focus,
        textarea:focus,
        .select2-container--default .select2-selection--single:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #000000 !important;
            border-radius: 0.375rem;
            height: 42px;
            padding: 0.5rem 0.75rem;
            width: 100% !important;
            background-color: white;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        .select2-dropdown {
            border: 1px solid #000000 !important;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .select2-results__option {
            padding: 8px 12px;
        }

        .select2-results__option--highlighted {
            background-color: #2563eb !important;
            color: white !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000000 !important;
        }
    </style>

    <div x-data="{ open: false, keyword: '', rows: [], page: 1, lastPage: 1, total: 0 }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-[1500px] w-full mx-auto">
            <form action="{{ route('product.store') }}" method="POST">
                @csrf

                <div>
                    <!-- Group Produk Dropdown -->
                    <div class="mt-2 w-1/2" x-data="{ isEditable: false }">
                        <label class="block text-sm font-medium">Group Produk</label>
                        <div class="flex items-center gap-2">
                            <select name="fgroupcode" :disabled="!isEditable"
                                class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror"
                                id="groupSelect">
                                <option value="">-- Pilih Group Produk --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupid }}"
                                        {{ old('fgroupcode') == $group->fgroupid ? 'selected' : '' }}>
                                        {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                            <!-- Hidden input to store selected fgroupcode -->
                            <input type="hidden" name="fgroupcode" id="fgroupcode" value="{{ old('fgroupcode') }}">

                            <!-- Add Group Produk (Icon Button) -->
                            <button type="button" @click="isEditable = true; $dispatch('open-group-modal')"
                                class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                <i class="fa fa-plus"></i>
                            </button>

                            <!-- Browse Group (Icon Button) -->
                            <button type="button" @click="isEditable = false; $dispatch('groupproduct-browse-open')"
                                class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>

                        <!-- Only show error if field is required and empty -->
                        @error('fgroupcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <!-- Input Kode Product -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Kode Product</label>
                            <input type="text" name="fproductcode" class="w-full border rounded px-3 py-2"
                                placeholder="Masukkan Kode Product" :disabled="autoCode"
                                :value="autoCode ? '' : '{{ old('fproductcode') }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>

                        <!-- Checkbox Auto Generate -->
                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <!-- Merek Dropdown + Button Create -->
                    <div class="mt-2 w-1/2" x-data="{ isMerekEditable: false }">
                        <label class="block text-sm font-medium">Merek</label>
                        <div class="flex items-center gap-2">
                            <!-- Merek Dropdown -->
                            <select name="fmerek" id="merkSelect" :disabled="!isMerekEditable"
                                class="w-full border rounded px-3 py-2 @error('fmerek') border-red-500 @enderror">
                                <option value="">-- Pilih Merek --</option>
                                @foreach ($merks as $merk)
                                    <option value="{{ $merk->fmerekid }}"
                                        {{ old('fmerek') == $merk->fmerekid ? 'selected' : '' }}>
                                        {{ $merk->fmerekname }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="hidden" name="fmerek" id="fmerek" value="{{ old('fmerek') }}">

                            <!-- Button to Add Merek -->
                            <button type="button" @click="isMerekEditable = true; $dispatch('open-merk-modal')"
                                class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">
                                <i class="fa fa-plus"></i>
                            </button>

                            <!-- Button to Browse Merek -->
                            <button type="button" @click="isMerekEditable = false; $dispatch('merek-browse-open')"
                                class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>

                        <!-- Validation error for fmerek -->
                        @error('fmerek')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="open" x-transition.opacity x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
                            <div class="p-4 border-b flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Browse Merek</h3>
                                <div class="ml-auto flex items-center gap-2">
                                    <input type="text" x-model="keyword" @keydown.enter.prevent="search()"
                                        placeholder="Cari kode / nama…" class="border rounded px-3 py-2 w-64">
                                    <button type="button" @click="search()"
                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
                                </div>
                            </div>

                            <div class="p-3 border-t flex items-center gap-2">
                                <div class="text-sm text-gray-600"><span
                                        x-text="`Page ${page} / ${lastPage} • Total ${total}`"></span></div>
                                <div class="ml-auto flex items-center gap-2">
                                    <button type="button" @click="prev()" :disabled="page <= 1"
                                        class="px-3 py-1 rounded border"
                                        :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                            'bg-gray-100 hover:bg-gray-200'">Prev</button>
                                    <button type="button" @click="next()" :disabled="page >= lastPage"
                                        class="px-3 py-1 rounded border"
                                        :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                            'bg-gray-100 hover:bg-gray-200'">Next</button>
                                    <button type="button" @click="open = false"
                                        class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nama Product -->
                <div class="mt-2 w-1/2">
                    <label class="block text-sm font-medium">Nama Product</label>
                    <input type="text" name="fproductname" id="fproductname" value="{{ old('fproductname') }}"
                        class="w-full border rounded px-3 py-2 @error('fproductname') border-red-500 @enderror">
                    @error('fproductname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Barcode -->
                <div class="mt-2 w-1/3">
                    <label class="block text-sm font-medium">Barcode</label>
                    <input type="text" name="fbarcode" value="{{ old('fbarcode') }}"
                        class="w-full border rounded px-3 py-2 @error('fbarcode') border-red-500 @enderror">
                    @error('fbarcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Satuan Kecil --}}
                <div class="mt-2 w-1/4">
                    <label class="block text-sm font-medium">Satuan Kecil</label>
                    <select class="w-full border rounded px-3 py-2 @error('fsatuankecil') border-red-500 @enderror"
                        name="fsatuankecil" id="fsatuankecil" onchange="checkSatuan();">
                        <option value="" selected>Pilih Satuan 1</option>
                        @foreach ($satuan as $satu)
                            <option value="{{ $satu->fsatuancode }}">
                                {{ $satu->fsatuancode }}
                            </option>
                        @endforeach
                    </select>
                    @error('fsatuankecil')
                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Satuan 2 --}}
                <div class="mt-2">
                    <div class="flex items-end gap-4">
                        <div class="w-1/3">
                            <label class="block text-sm font-medium">Satuan 2</label>
                            <select class="w-full border rounded px-3 py-2 @error('fsatuanbesar') border-red-500 @enderror"
                                name="fsatuanbesar" id="fsatuanbesar" disabled>
                                <option value="" selected>Pilih Satuan 2</option>
                                @foreach ($satuan as $satu)
                                    <option value="{{ $satu->fsatuancode }}" data-name="{{ $satu->fsatuanname }}">
                                        {{ $satu->fsatuancode }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fsatuanbesar')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="w-1/6">
                            <label class="block text-sm font-medium">Isi</label>
                            <input type="number" name="fqtykecil" id="fqtykecil" value="{{ old('fqtykecil', 0) }}"
                                class="w-full border rounded px-3 py-2 @error('fqtykecil') border-red-500 @enderror"
                                disabled>
                            @error('fqtykecil')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Satuan 3 --}}
                <div class="mt-2">
                    <div class="flex items-end gap-4">
                        <div class="w-1/3">
                            <label class="block text-sm font-medium">Satuan 3</label>
                            <select
                                class="w-full border rounded px-3 py-2 @error('fsatuanbesar2') border-red-500 @enderror"
                                name="fsatuanbesar2" id="fsatuanbesar2" disabled>
                                <option value="" selected>Pilih Satuan 3</option>
                                @foreach ($satuan as $satu)
                                    <option value="{{ $satu->fsatuancode }}" data-name="{{ $satu->fsatuanname }}">
                                        {{ $satu->fsatuancode }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fsatuanbesar2')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="w-1/6">
                            <label class="block text-sm font-medium">Isi</label>
                            <input type="number" name="fqtykecil2" id="fqtykecil2" value="{{ old('fqtykecil2', 0) }}"
                                class="w-full border rounded px-3 py-2 @error('fqtykecil2') border-red-500 @enderror"
                                disabled>
                            @error('fqtykecil2')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Satuan Default Dropdown -->
                <div class="mt-2 w-1/4">
                    <label class="block text-sm font-medium">Satuan Default</label>
                    <select name="fsatuandefault"
                        class="w-full border rounded px-3 py-2 @error('fsatuandefault') border-red-500 @enderror">
                        <option value="1"> Satuan 1 </option>
                        <option value="2"> Satuan 2 </option>
                        <option value="3"> Satuan 3 </option>
                    </select>
                    @error('fsatuandefault')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Harga Pokok Penjualan -->
                <div class="mt-2 w-1/4">
                    <label class="block text-sm font-medium">Harga Pokok Penjualan</label>
                    <input type="text" name="fhpp" id="fhpp" value="{{ old('fhpp', 0) }}"
                        class="w-full border rounded px-3 py-2 @error('fhpp') border-red-500 @enderror">
                    @error('fhpp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Harga Satuan 3 Level 1 -->
                    <div>
                        <label for="fhargasatuankecillevel1" class="block text-sm font-medium">HJ. Kecil Level
                            1</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel1') is-invalid @enderror"
                                name="fhargasatuankecillevel1" id="fhargasatuankecillevel1"
                                value="{{ old('fhargasatuankecillevel1', 0) }}">
                            @error('fhargasatuankecillevel1')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Harga Satuan 3 Level 2 -->
                    <div>
                        <label for="fhargasatuankecillevel2" class="block text-sm font-medium">HJ. Kecil Level
                            2</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel2') is-invalid @enderror"
                                name="fhargasatuankecillevel2" id="fhargasatuankecillevel2"
                                value="{{ old('fhargasatuankecillevel2', 0) }}">
                            @error('fhargasatuankecillevel2')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Harga Satuan 3 Level 3 -->
                    <div>
                        <label for="fhargasatuankecillevel3" class="block text-sm font-medium">HJ. Kecil Level
                            3</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargasatuankecillevel3') is-invalid @enderror"
                                name="fhargasatuankecillevel3" id="fhargasatuankecillevel3"
                                value="{{ old('fhargasatuankecillevel3', 0) }}">
                            @error('fhargasatuankecillevel3')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Harga Satuan 2 Level 1 -->
                    <div>
                        <label for="fhargajuallevel1" class="block text-sm font-medium">HJ. Besar Level
                            1</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargajuallevel1') is-invalid @enderror"
                                name="fhargajuallevel1" id="fhargajuallevel1" value="{{ old('fhargajuallevel1', 0) }}">
                            @error('fhargajuallevel1')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Harga Satuan 2 Level 2 -->
                    <div>
                        <label for="fhargajuallevel2" class="block text-sm font-medium">HJ. Besar Level
                            2</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargajuallevel2') is-invalid @enderror"
                                name="fhargajuallevel2" id="fhargajuallevel2" value="{{ old('fhargajuallevel2', 0) }}">
                            @error('fhargajuallevel2')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Harga Satuan 2 Level 3 -->
                    <div>
                        <label for="fhargajuallevel3" class="block text-sm font-medium">HJ. Besar Level
                            3</label>
                        <div class="d-flex">
                            <input type="text"
                                class="w-1/10 border rounded px-3 py-2 @error('fhargajuallevel3') is-invalid @enderror"
                                name="fhargajuallevel3" id="fhargajuallevel3" value="{{ old('fhargajuallevel3', 0) }}">
                            @error('fhargajuallevel3')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Min.Stok -->
                <div class="mt-2 w-1/4">
                    <label class="block text-sm font-medium">Min.Stok</label>
                    <input type="text" name="fminstock" value="{{ old('fminstock', 0) }}"
                        class="w-full border rounded px-3 py-2 @error('fminstock') border-red-500 @enderror">
                    @error('fminstock')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Jenis --}}
                <div class="mt-2 w-1/4">
                    <label class="block text-sm font-medium">Jenis</label>
                    <select name="ftype"
                        class="w-full border rounded px-3 py-2 @error('ftype') border-red-500 @enderror">
                        <option value="Produk">Product</option>
                        <option value="Jasa"> Jasa </option>
                    </select>
                    @error('ftype')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label class="block text-sm font-medium">Approval</label>
                    <label class="switch">
                        <input type="checkbox" name="fapproval" id="approvalToggle"
                            {{ session('fapproval') ? 'checked' : '' }}>
                        <span class="slider round"></span>
                    </label>
                </div>
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                    </label>
                </div>
                <!-- Tombol Aksi -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Keluar -->
                    <button type="button" @click="window.location.href='{{ route('product.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
        </div>

        <br>

        </form>
    </div>

    <div x-data="{
        open: false,
        loading: false,
        errors: {},
        form: { fmerekcode: '', fmerekname: '', fnonactive: false },
        saveData() {
            this.loading = true;
            this.errors = {};
    
            $.ajax({
                    url: '{{ route('merek.store.ajax') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        fmerekcode: this.form.fmerekcode,
                        fmerekname: this.form.fmerekname,
                        fnonactive: this.form.fnonactive ? 1 : 0
                    }
                })
                .done((res) => {
                    if (res && res.name && res.id) {
                        const opt = new Option(res.name, res.id, true, true);
                        $('#merkSelect').append(opt).trigger('change');
                        this.open = false;
                        this.form.fmerekcode = '';
                        this.form.fmerekname = '';
                        this.form.fnonactive = false;
                    } else {
                        alert('Response format is incorrect or missing expected data.');
                    }
                    this.loading = false;
                })
                .fail((xhr) => {
                    this.loading = false;
                    if (xhr.status === 422) {
                        this.errors = xhr.responseJSON?.errors || {};
                    } else {
                        alert('Gagal menyimpan merek.');
                    }
                });
        }
    }" x-on:open-merk-modal.window="open = true; errors = {}; loading = false;" x-show="open"
        style="display:none" class="fixed inset-0 z-[10000] flex items-center justify-center">

        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <!-- card -->
        <div class="relative bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Tambah Merek</h3>

            <div class="space-y-4 mt-2">
                <div>
                    <label class="block text-sm font-medium">Kode Merek</label>
                    <input type="text" x-model="form.fmerekcode" class="w-full border rounded px-3 py-2"
                        maxlength="10" :class="errors.fmerekcode ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekcode">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekcode[0]"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Merek</label>
                    <input type="text" x-model="form.fmerekname" class="w-full border rounded px-3 py-2"
                        :class="errors.fmerekname ? 'border-red-500' : ''">
                    <template x-if="errors.fmerekname">
                        <p class="text-red-600 text-sm mt-1" x-text="errors.fmerekname[0]"></p>
                    </template>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_fnonactive" class="block text-sm font-medium">Non Aktif</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open=false"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>

                <!-- FIXED BUTTON - Single button with all elements inside -->
                <button type="button" @click="saveData()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 disabled:opacity-60"
                    :disabled="loading">

                    <svg x-show="loading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            opacity=".25"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" opacity=".75"></path>
                    </svg>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL BROWSE GROUP PRODUCT -->
    <div x-data="groupBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-[9998] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
            <div class="p-4 border-b flex items-center gap-3">
                <h3 class="text-lg font-semibold">Browse Group Product</h3>
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
                            <th class="text-left p-2 w-40">Kode</th>
                            <th class="text-left p-2">Nama Group</th>
                            <th class="text-center p-2 w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="g in rows" :key="g.fgroupid">
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2 font-mono" x-text="g.fgroupcode"></td>
                                <td class="p-2" x-text="g.fgroupname"></td>
                                <td class="p-2 text-center">
                                    <button type="button" @click="choose(g)"
                                        class="px-3 py-1 rounded text-xs bg-emerald-600 hover:bg-emerald-700 text-white">Pilih</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="rows.length === 0">
                            <td colspan="3" class="p-4 text-center text-gray-500">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-t flex items-center gap-2">
                <div class="text-sm text-gray-600" x-text="`Page ${page} / ${lastPage} • Total ${total}`"></div>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" @click="prev()" :disabled="page <= 1" class="px-3 py-1 rounded border"
                        :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gray-100 hover:bg-gray-200'">
                        Prev
                    </button>
                    <button type="button" @click="next()" :disabled="page >= lastPage" class="px-3 py-1 rounded border"
                        :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                            'bg-gray-100 hover:bg-gray-200'">
                        Next
                    </button>
                    <button type="button" @click="close()"
                        class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-data="groupProductModal()" x-show="openGroupModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="closeGroupModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl p-6">
            <h3 class="text-lg font-semibold mb-4">Tambah Group Produk</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Kode Group Produk</label>
                    <input type="text" x-model="form.fgroupcode" class="w-full border rounded px-3 py-2"
                        maxlength="50" placeholder="Kode Group Produk">
                </div>
                <div>
                    <label class="block text-sm font-medium">Nama Group Produk</label>
                    <input type="text" x-model="form.fgroupname" class="w-full border rounded px-3 py-2"
                        maxlength="100" placeholder="Nama Group Produk">
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" x-model="form.fnonactive" id="modal_fnonactive"
                        class="form-checkbox h-5 w-5 text-indigo-600">
                    <label for="modal_fnonactive" class="block text-sm font-medium">Non Aktif</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeGroupModal()"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Batal</button>
                <button type="button" @click="saveData()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2 disabled:opacity-60"
                    :disabled="loading">
                    <svg x-show="loading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            opacity=".25"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" opacity=".75"></path>
                    </svg>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL BROWSE MEREK -->
    <div x-data="merekBrowser()" x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="close()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-[92vw] max-w-4xl max-h-[85vh] flex flex-col">
            <div class="p-4 border-b flex items-center gap-3">
                <h3 class="text-lg font-semibold">Browse Merek</h3>
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
                            <th class="text-left p-2">Merek (Kode - Nama)</th>
                            <th class="text-center p-2 w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="m in rows" :key="m.fmerekid">
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2" x-text="`${m.fmerekcode} - ${m.fmerekname}`"></td>
                                <td class="p-2 text-center">
                                    <button type="button" @click="choose(m)"
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
                <div class="text-sm text-gray-600" x-text="`Page ${page} / ${lastPage} • Total ${total}`"></div>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" @click="prev()" :disabled="page <= 1" class="px-3 py-1 rounded border"
                        :class="page <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gray-100 hover:bg-gray-200'">
                        Prev
                    </button>
                    <button type="button" @click="next()" :disabled="page >= lastPage" class="px-3 py-1 rounded border"
                        :class="page >= lastPage ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                            'bg-gray-100 hover:bg-gray-200'">
                        Next
                    </button>
                    <button type="button" @click="close()"
                        class="px-3 py-1 rounded border bg-gray-100 hover:bg-gray-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.8.1/autoNumeric.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#groupSelect, #merkSelect, #fsatuankecil, #fsatuanbesar, #fsatuanbesar2').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || '-- Pilih --';
            },
        });

        $('#groupSelect').select2({
            placeholder: '-- Pilih Group Produk --'
        });

        $('#merkSelect').select2({
            placeholder: '-- Pilih Merek --'
        });

        // Initialize AutoNumeric
        let fhpp = new AutoNumeric('#fhpp', 'commaDecimalCharDotSeparator');
        let fhargasatuankecillevel1 = new AutoNumeric('#fhargasatuankecillevel1',
            'commaDecimalCharDotSeparator');
        let hargasatuankecillevel2 = new AutoNumeric('#fhargasatuankecillevel2',
            'commaDecimalCharDotSeparator');
        let hargasatuankecillevel3 = new AutoNumeric('#fhargasatuankecillevel3',
            'commaDecimalCharDotSeparator');
        let hargajuallevel1 = new AutoNumeric('#fhargajuallevel1', 'commaDecimalCharDotSeparator');
        let hargajuallevel2 = new AutoNumeric('#fhargajuallevel2', 'commaDecimalCharDotSeparator');
        let hargajuallevel3 = new AutoNumeric('#fhargajuallevel3', 'commaDecimalCharDotSeparator');

        // Product Name Autocomplete
        $(function() {
            const $inp = $("#fproductname");
            let lastXHR = null;
            const localCache = {};

            $inp.autocomplete({
                source: function(request, response) {
                    const term = request.term || "";

                    if (localCache[term]) {
                        response(localCache[term]);
                        return;
                    }

                    if (lastXHR && lastXHR.readyState !== 4) lastXHR.abort();

                    lastXHR = $.getJSON("{{ route('product.name.suggest') }}", {
                        term
                    }, function(data) {
                        localCache[term] = data;
                        response(data);
                    });
                },
                minLength: 0,
                delay: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    return false;
                },
                open: function() {
                    $(".ui-autocomplete").css("width", $inp.outerWidth());
                }
            });

            $inp.on("focus", function() {
                if (!$(".ui-autocomplete:visible").length) {
                    $(this).autocomplete("search", $(this).val() || "");
                }
            });

            $inp.on("keydown", function(e) {
                if (e.key === "ArrowDown" && !$(".ui-autocomplete:visible").length) {
                    $(this).autocomplete("search", $(this).val() || "");
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#fsatuanbesar').on('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var fsatuanname = selectedOption.getAttribute('data-name');

            if (fsatuanname) {
                $('#fsatuanname-label').text(fsatuanname);
            } else {
                $('#fsatuanname-label').text('Tidak ada pilihan');
            }
        });

        $('#fsatuanbesar2').on('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var fsatuanname = selectedOption.getAttribute('data-name');

            if (fsatuanname) {
                $('#fsatuanname-label-2').text(fsatuanname);
            } else {
                $('#fsatuanname-label-2').text('Tidak ada pilihan');
            }
        });
    });
</script>

<script>
    function checkSatuan() {
        const fsatuankecil = document.getElementById('fsatuankecil').value;
        const fsatuanbesar = document.getElementById('fsatuanbesar');
        const fsatuanbesar2 = document.getElementById('fsatuanbesar2');
        const fqtykecil = document.getElementById('fqtykecil');
        const fqtykecil2 = document.getElementById('fqtykecil2');

        if (fsatuankecil !== "") {
            fsatuanbesar.disabled = false;
            fsatuanbesar2.disabled = false;
            fqtykecil.disabled = false;
            fqtykecil2.disabled = false;
        } else {
            fsatuanbesar.disabled = true;
            fsatuanbesar2.disabled = true;
            fqtykecil.disabled = true;
            fqtykecil2.disabled = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkSatuan();
    });
</script>

<script>
    function merekBrowser() {
        return {
            open: false,
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('mereks.browse') }}", window.location.origin);
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
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
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
            choose(m) {
                // Set value ke Select2 #merkSelect
                const $sel = $('#merkSelect');
                // pastikan option ada
                if ($sel.find(`option[value="${m.fmerekid}"]`).length === 0) {
                    const opt = new Option(m.fmerekname, m.fmerekid, true, true);
                    $sel.append(opt);
                }
                $sel.val(m.fmerekid).trigger('change');
                $('input[name="fmerek"]').val(m.fmerekid);
                this.close();
            },
            init() {
                // buka modal saat tombol Browse Merek di-klik
                window.addEventListener('merek-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    function groupBrowser() {
        return {
            open: false,
            keyword: '',
            page: 1,
            lastPage: 1,
            perPage: 10,
            total: 0,
            rows: [],
            apiUrl() {
                const u = new URL("{{ route('groupproducts.browse') }}", window.location.origin);
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
                    this.rows = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }
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
            choose(g) {
                // Set value to the group select field
                const $sel = $('#groupSelect');

                // Make sure the option is present, if not, add it
                if ($sel.find(`option[value="${g.fgroupid}"]`).length === 0) {
                    const opt = new Option(g.fgroupname, g.fgroupid, true, true);
                    $sel.append(opt);
                }

                // Set the selected value to the dropdown
                $sel.val(g.fgroupid).trigger('change'); // Triggers 'change' to update any dynamic behavior

                // Also update the hidden input field 'fgroupcode' directly if needed
                $('input[name="fgroupcode"]').val(g.fgroupid);

                this.close(); // Close the modal after selecting
            },
            init() {
                // Buka modal Browse Group Produk
                window.addEventListener('groupproduct-browse-open', () => this.openBrowse(), {
                    passive: true
                });
            }
        }
    }

    function groupProductModal() {
        return {
            openGroupModal: false,
            form: {
                fgroupcode: '',
                fgroupname: '',
                fnonactive: false,
            },
            loading: false,
            saveData() {
                this.loading = true;
                $.ajax({
                        url: '{{ route('groupproduct.store.ajax') }}',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        data: {
                            fgroupcode: this.form.fgroupcode,
                            fgroupname: this.form.fgroupname,
                            fnonactive: this.form.fnonactive ? 1 : 0
                        }
                    })
                    .done((res) => {
                        if (res && res.name && res.id) {
                            const opt = new Option(res.name, res.id, true, true);
                            $('#groupSelect').append(opt).trigger('change');
                            this.closeGroupModal();
                        } else {
                            alert('Response format is incorrect or missing expected data.');
                        }
                        this.loading = false;
                    })
                    .fail((xhr) => {
                        this.loading = false;
                        if (xhr.status === 422) {
                            this.errors = xhr.responseJSON?.errors || {};
                        } else {
                            alert('Gagal menyimpan group produk.');
                        }
                    });
            },
            closeGroupModal() {
                this.openGroupModal = false;
                this.form.fgroupcode = '';
                this.form.fgroupname = '';
                this.form.fnonactive = false;
            },
            openGroupProductModal() {
                this.openGroupModal = true;
            },
            init() {
                // Buka modal Add Group Produk
                window.addEventListener('open-group-modal', () => this.openGroupProductModal(), {
                    passive: true
                });
            }
        }
    }
</script>
