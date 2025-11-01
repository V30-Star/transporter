@extends('layouts.app')

@section('title', 'Master Customer')

@section('content')
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>

    <style>
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
        .invalid-feedback {
            color: #f87171;
            font-size: 0.875rem;
            margin-top: 4px;
            padding-left: 10px;
        }

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
            /* Black border */
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

    <div x-data="{ open: true, selected: 'alamatsurat' }">
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">
            <form action="{{ route('customer.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div x-data="{ autoCode: true }" class="flex items-center gap-4">
                        <!-- Input Kode Customer -->
                        <div class="flex-1">
                            <label class="block text-sm font-medium">Kode Customer</label>
                            <input type="text" name="fcustomercode" class="w-full border rounded px-3 py-2 uppercase"
                                placeholder="Masukkan Kode Customer" :disabled="autoCode"
                                :value="autoCode ? '' : '{{ old('fcustomercode') }}'"
                                :class="autoCode ? 'bg-gray-200 cursor-not-allowed' : 'bg-white'">
                        </div>

                        <!-- Checkbox Auto Generate -->
                        <label class="inline-flex items-center mt-6">
                            <input type="checkbox" x-model="autoCode" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Customer</label>
                        <input type="text"
                            class="w-full border rounded px-3 py-2 uppercase @error('fcustomername') is-invalid @enderror"
                            name="fcustomername" id="fcustomername" placeholder="Masukkan Nama Customer"
                            value="{{ old('fcustomername') }}" autofocus>
                        @error('fcustomername')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Group Customer</label>
                        <select name="fgroup"
                            class="w-full border rounded px-3 py-2 @error('fgroup') border-red-500 @enderror"
                            id="groupSelect">
                            <option value="">-- Pilih Group Produk --</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->fgroupid }}"
                                    {{ old('fgroup') == $group->fgroupid ? 'selected' : '' }}>
                                    {{ $group->fgroupname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Salesman</label>
                            <select name="fsalesman"
                                class="w-full border rounded px-3 py-2 @error('fsalesman') border-red-500 @enderror"
                                id="salesmanSelect">
                                <option value="">-- Pilih Salesman --</option>
                                @foreach ($salesman as $sales)
                                    <option value="{{ $sales->fsalesmanid }}"
                                        {{ old('fsalesman') == $sales->fsalesmanid ? 'selected' : '' }}>
                                        {{ $sales->fsalesmanname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fsalesman')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Wilayah</label>
                            <select name="fwilayah"
                                class="w-full border rounded px-3 py-2 @error('fwilayah') border-red-500 @enderror"
                                id="wilayahSelect">
                                <option value="">-- Pilih Wilayah --</option>
                                @foreach ($wilayah as $wil)
                                    <option value="{{ $wil->fwilayahid }}"
                                        {{ old('fwilayah') == $wil->fwilayahid ? 'selected' : '' }}>
                                        {{ $wil->fwilayahname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fwilayah')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NPWP</label>
                        <input type="text" class="w-full border rounded px-3 py-2 @error('fnpwp') is-invalid @enderror"
                            name="fnpwp" id="fnpwp" placeholder="Masukkan NPWP" value="{{ old('fnpwp') }}">
                        @error('fnpwp')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No. NIK</label>
                        <input type="text" class="w-full border rounded px-3 py-2 @error('fnik') is-invalid @enderror"
                            name="fnik" id="fnik" placeholder="Masukkan NIK" value="{{ old('fnik') }}">
                        @error('fnik')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Jadwal Tukar Faktur</label>
                        <select name="fjadwalmingguan"
                            class="w-full border rounded px-3 py-2 @error('fjadwalmingguan') border-red-500 @enderror">
                            <option value="1">Setiap Minggu</option>
                            <option value="2">Minggu Ganjil</option>
                            <option value="3">Minggu Genap</option>
                        </select>
                        @error('fjadwalmingguan')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Hari Tukar Faktur</label>
                            <select name="fjadwalhari"
                                class="w-full border rounded px-3 py-2 @error('fjadwalhari') border-red-500 @enderror">
                                <option value="">-- Pilih Hari --</option>
                                <option value="1">Senin</option>
                                <option value="2">Selasa</option>
                                <option value="3">Rabu</option>
                                <option value="4">Kamis</option>
                                <option value="5">Jumat</option>
                                <option value="6">Sabtu</option>
                                <option value="7">Minggu</option>
                            </select>
                            @error('fjadwalhari')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Kode Faktur Pajak</label>
                            <input type="text"
                                class="w-full border rounded px-3 py-2 @error('fkodefp') is-invalid @enderror"
                                name="fkodefp" id="fkodefp" placeholder="010" value="{{ old('fkodefp', '010') }}"
                                maxlength="3">
                            @error('fkodefp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">Alamat</label>
                        <div class="flex space-x-2 mb-4">
                            <button type="button" @click="selected = 'alamatsurat'"
                                :class="selected === 'alamatsurat' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-4 py-2 rounded border">Alamat Surat</button>
                            <button type="button" @click="selected = 'alamat1'"
                                :class="selected === 'alamat1' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-4 py-2 rounded border">Alamat Kirim</button>
                            <button type="button" @click="selected = 'alamatpajak'"
                                :class="selected === 'alamatpajak' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-4 py-2 rounded border">Alamat Pajak</button>
                        </div>
                        <div x-show="selected === 'alamatsurat'">
                            <textarea class="w-full border rounded px-3 py-2 @error('faddress') is-invalid @enderror" name="faddress"
                                id="faddress" placeholder="Masukkan Alamat Surat" cols="10" rows="6">{{ old('faddress') }}</textarea>
                            @error('faddress')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div x-show="selected === 'alamat1'">
                            <textarea class="w-full border rounded px-3 py-2 mb-4 @error('fkirimaddress1') is-invalid @enderror"
                                name="fkirimaddress1" id="fkirimaddress1" placeholder="Masukkan Alamat Kirim 1" cols="10" rows="6">{{ old('fkirimaddress1') }}</textarea>
                            @error('fkirimaddress1')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="w-full border rounded px-3 py-2 mb-4 @error('fkirimaddress2') is-invalid @enderror"
                                name="fkirimaddress2" id="fkirimaddress2" placeholder="Masukkan Alamat Kirim 2" cols="10" rows="6">{{ old('fkirimaddress2') }}</textarea>
                            @error('fkirimaddress2')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <textarea class="w-full border rounded px-3 py-2 mb-4 @error('fkirimaddress3') is-invalid @enderror"
                                name="fkirimaddress3" id="fkirimaddress3" placeholder="Masukkan Alamat Kirim 3" cols="10" rows="6">{{ old('fkirimaddress3') }}</textarea>
                            @error('fkirimaddress3')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div x-show="selected === 'alamatpajak'">
                            <textarea class="w-full border rounded px-3 py-2 @error('ftaxaddress') is-invalid @enderror" name="ftaxaddress"
                                id="ftaxaddress" placeholder="Masukkan Alamat Pajak" cols="10" rows="6">{{ old('ftaxaddress') }}</textarea>
                            @error('ftaxaddress')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Telp</label>
                        <input type="number"
                            class="w-full border rounded px-3 py-2 @error('ftelp') is-invalid @enderror" name="ftelp"
                            id="ftelp" placeholder="Masukkan Nomor Telepon" value="{{ old('ftelp') }}">
                        @error('ftelp')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Fax</label>
                        <input type="number" class="w-full border rounded px-3 py-2 @error('ffax') is-invalid @enderror"
                            name="ffax" id="ffax" placeholder="Masukkan Nomor Fax" value="{{ old('ffax') }}">
                        @error('ffax')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email"
                            class="w-full border rounded px-3 py-2 @error('femail') is-invalid @enderror" name="femail"
                            id="femail" placeholder="Masukkan Email" value="{{ old('femail') }}">
                        @error('femail')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Jatuh Tempo</label>
                        <input type="number"
                            class="w-full border rounded px-3 py-2 @error('ftempo') is-invalid @enderror" name="ftempo"
                            id="ftempo" value="{{ old('ftempo', 0) }}" maxlength="3">
                        @error('ftempo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Max JT Tempo</label>
                        <input type="number"
                            class="w-full border rounded px-3 py-2 @error('fmaxtempo') is-invalid @enderror"
                            name="fmaxtempo" id="fmaxtempo" value="{{ old('fmaxtempo', 0) }}">
                        @error('fmaxtempo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Limit Piutang</label>
                        <input type="text"
                            class="w-full border rounded px-3 py-2 @error('flimit') is-invalid @enderror" name="flimit"
                            id="flimit" placeholder="Masukkan Limit Piutang" value="{{ old('flimit') }}">
                        @error('flimit')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Set Harga</label>
                        <select class="w-full border rounded px-3 py-2 @error('fhargalevel') is-invalid @enderror"
                            name="fhargalevel" id="fhargalevel">
                            <option value="0" selected> Harga Level 1 </option>
                            <option value="1"> Harga Level 2 </option>
                            <option value="2"> Harga Level 3 </option>
                        </select>
                        @error('fhargalevel')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Kontak Person</label>
                        <input type="number"
                            class="w-full border rounded px-3 py-2 @error('fkontakperson') is-invalid @enderror"
                            name="fkontakperson" id="fkontakperson" placeholder="Masukkan Nama Kontak Person"
                            value="{{ old('fkontakperson') }}">
                        @error('fkontakperson')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Jabatan</label>
                        <input type="text"
                            class="w-full border rounded px-3 py-2 @error('fjabatan') is-invalid @enderror"
                            name="fjabatan" id="fjabatan" placeholder="Masukkan Jabatan"
                            value="{{ old('fjabatan') }}">
                        @error('fjabatan')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Kode Rekening</label>
                        <select class="w-full border rounded px-3 py-2 @error('frekening') is-invalid @enderror"
                            name="frekening" id="frekening">
                            <option value="" selected> Pilih Kode Rekening </option>
                            @foreach ($rekening as $rek)
                                <option value="{{ $rek->frekeningcode }}" {{ old('frekening') ? 'selected' : '' }}>
                                    {{ $rek->frekeningcode }} - {{ $rek->frekeningname }}
                                </option>
                            @endforeach
                        </select>
                        @error('frekening')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">Memo</label>
                        <textarea class="w-full border rounded px-3 py-2 @error('fmemo') is-invalid @enderror" name="fmemo" id="fmemo"
                            placeholder="Masukkan Memo" cols="10" rows="6">{{ old('fmemo') }}</textarea>
                        @error('fmemo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="md:col-span-2 flex flex-col items-center space-y-4">
                        <!-- Approval Switch -->
                        <div class="flex items-center space-x-2">
                            <label class="block text-sm font-medium">Approval</label>
                            <label class="switch">
                                <input type="checkbox" name="fapproval" id="approvalToggle"
                                    {{ session('fapproval') ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <!-- Non Aktif Checkbox -->
                        <label for="statusToggle"
                            class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <span class="text-sm font-medium">Non Aktif</span>
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                                {{ old('fnonactive') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
                <br>
                <!-- Tombol Aksi -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Keluar -->
                    <button type="button" @click="window.location.href='{{ route('customer.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.8.1/autoNumeric.min.js"></script>

<script>
    $(document).ready(function() {
        $('#groupSelect, #salesmanSelect, #wilayahSelect, #frekening').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || '-- Pilih --';
            },
            dropdownAutoWidth: true
        });
        $(function() {
            const $inp = $("#fcustomername");
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

                    lastXHR = $.getJSON("{{ route('customer.name.suggest') }}", {
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
