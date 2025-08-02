@extends('layouts.app')

@section('title', 'Tambah Product')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true }">
        <div x-show="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
            <div class="bg-white w-full max-w-5xl p-6 rounded shadow relative overflow-y-auto max-h-screen">
                <!-- Tombol X -->
                <button type="button" @click="window.location.href='{{ route('product.index') }}'"
                    class="absolute top-4 right-6 text-gray-500 hover:text-red-600 text-xl font-bold">
                    &times;
                </button>
                <!-- Header -->
                <div class="mb-6 border-b pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                        <x-heroicon-o-plus-circle class="w-6 h-6 text-blue-600" />
                        <span>Edit Product</span>
                    </h2>
                </div>
                <form action="{{ route('product.update', $product->fproductid) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div>
                        <!-- Group Produk Dropdown -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Group Produk</label>
                            <select name="fgroupcode"
                                class="w-full border rounded px-3 py-2 @error('fgroupcode') border-red-500 @enderror"
                                id="groupSelect">
                                <option value="">-- Pilih Group Produk --</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->fgroupid }}"
                                        {{ old('fgroupcode', $product->fgroupcode) == $group->fgroupid ? 'selected' : '' }}>
                                        {{ $group->fgroupname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fgroupcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kode Product -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Kode Product</label>
                            <input type="text" name="fproductcode"
                                value="{{ old('fproductcode', $product->fproductcode) }}"
                                class="w-full border rounded px-3 py-2 @error('fproductcode') border-red-500 @enderror">
                            @error('fproductcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nama Product -->
                        <div class="mt-2 w-1/2">
                            <label class="block text-sm font-medium">Nama Product</label>
                            <input type="text" name="fproductname"
                                value="{{ old('fproductname', $product->fproductname) }}"
                                class="w-full border rounded px-3 py-2 @error('fproductname') border-red-500 @enderror">
                            @error('fproductname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Barcode -->
                        <div class="mt-2 w-1/3">
                            <label class="block text-sm font-medium">Barcode</label>
                            <input type="text" name="fbarcode" value="{{ old('fbarcode', $product->fbarcode) }}"
                                class="w-full border rounded px-3 py-2 @error('fbarcode') border-red-500 @enderror">
                            @error('fbarcode')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Merek Dropdown -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Merek</label>
                            <select name="fmerek"
                                class="w-full border rounded px-3 py-2 @error('fmerek') border-red-500 @enderror"
                                id="merkSelect">
                                <option value="">-- Pilih Merek --</option>
                                @foreach ($merks as $merk)
                                    <option value="{{ $merk->fmerekid }}"
                                        {{ old('fmerek', $product->fmerek) == $merk->fmerekid ? 'selected' : '' }}>
                                        {{ $merk->fmerekname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fmerek')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Satuan Kecil --}}
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Satuan Kecil</label>
                            <select class="w-full border rounded px-3 py-2 @error('fsatuankecil') border-red-500 @enderror"
                                name="fsatuankecil" id="fsatuankecil" onchange="changeUnit()">
                                <option value="" selected> Pilih Satuan 1</option>
                                @foreach ($satuan as $satu)
                                    <option value="{{ $satu->fsatuancode }}"
                                        {{ old('fsatuankecil', $product->fsatuankecil) == $satu->fsatuancode ? 'selected' : '' }}>
                                        {{ $satu->fsatuancode }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fsatuankecil')
                                <div class="text-red-600 text-sm mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Satuan 2 --}}
                        <div class="mt-2">
                            <div class="flex items-end gap-4"> <!-- Tambahkan flex container dengan gap -->
                                <!-- Satuan 2 Select -->
                                <div class="w-1/3">
                                    <label class="block text-sm font-medium">Satuan 2</label>
                                    <select
                                        class="w-full border rounded px-3 py-2 @error('fsatuanbesar') border-red-500 @enderror"
                                        name="fsatuanbesar" id="fsatuanbesar">
                                        <option value="" selected>Pilih Satuan 2</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                {{ old('fsatuanbesar', $product->fsatuanbesar) == $satu->fsatuancode ? 'selected' : '' }}>
                                                {{ $satu->fsatuancode }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fsatuanbesar')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Isi Input -->
                                <div class="w-1/6">
                                    <label class="block text-sm font-medium">Isi</label>
                                    <input type="number"
                                        class="w-full border rounded px-3 py-2 @error('fqtykecil') border-red-500 @enderror"
                                        name="fqtykecil" id="fqtykecil"
                                        value="{{ old('fqtykecil', $product->fqtykecil) }}">
                                    @error('fqtykecil')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Satuan 3 --}}
                        <div class="mt-2">
                            <div class="flex items-end gap-4"> <!-- Flex container for horizontal alignment -->
                                <!-- Satuan 3 Select -->
                                <div class="w-1/3">
                                    <label class="block text-sm font-medium">Satuan 3</label>
                                    <select
                                        class="w-full border rounded px-3 py-2 @error('fsatuanbesar2') border-red-500 @enderror"
                                        name="fsatuanbesar2" id="fsatuanbesar2">
                                        <option value="" selected>Pilih Satuan 3</option>
                                        @foreach ($satuan as $satu)
                                            <option value="{{ $satu->fsatuancode }}"
                                                {{ old('fsatuanbesar2', $product->fsatuanbesar2) == $satu->fsatuancode ? 'selected' : '' }}>
                                                {{ $satu->fsatuancode }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fsatuanbesar2')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Isi Input -->
                                <div class="w-1/6">
                                    <label class="block text-sm font-medium">Isi</label>
                                    <input type="number"
                                        class="w-full border rounded px-3 py-2 @error('fqtykecil2') border-red-500 @enderror"
                                        name="fqtykecil2" id="fqtykecil2"
                                        value="{{ old('fqtykecil2', $product->fqtykecil2) }}">
                                    @error('fqtykecil2')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Satuan Default Dropdown -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Satuan Default</label>
                            <select name="fsatuandefault"
                                class="w-full border rounded px-3 py-2 @error('fsatuandefault') border-red-500 @enderror">
                                <option value="1"
                                    {{ old('fsatuandefault', $product->fsatuandefault) == '1' ? 'selected' : '' }}>Satuan 1
                                </option>
                                <option value="2"
                                    {{ old('fsatuandefault', $product->fsatuandefault) == '2' ? 'selected' : '' }}>Satuan 2
                                </option>
                                <option value="3"
                                    {{ old('fsatuandefault', $product->fsatuandefault) == '3' ? 'selected' : '' }}>Satuan 3
                                </option>
                            </select>
                            @error('fsatuandefault')
                                <div class="text-red-600 text-sm mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Harga Pokok Produksi -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Harga Pokok Produksi</label>
                            <input type="text" name="fhpp" value="{{ old('fhpp', $product->fhpp) }}"
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
                                    <input type="number"
                                        class="w-1/4 border rounded px-3 py-2 @error('fhargasatuankecillevel1') is-invalid @enderror"
                                        name="fhargasatuankecillevel1" id="fhargasatuankecillevel1"
                                        value="{{ old('fhargasatuankecillevel1', $product->fhargasatuankecillevel1) }}">
                                    @error('fhargasatuankecillevel1')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="number"
                                        class="w-1/4 border rounded px-3 py-2 @error('fhargasatuankecillevel2') is-invalid @enderror"
                                        name="fhargasatuankecillevel2" id="fhargasatuankecillevel2"
                                        value="{{ old('fhargasatuankecillevel2', $product->fhargasatuankecillevel2) }}">
                                    @error('fhargasatuankecillevel2')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="number"
                                        class="w-1/4 border rounded px-3 py-2 @error('fhargasatuankecillevel3') is-invalid @enderror"
                                        name="fhargasatuankecillevel3" id="fhargasatuankecillevel3"
                                        value="{{ old('fhargasatuankecillevel3', $product->fhargasatuankecillevel3) }}">
                                    @error('fhargasatuankecillevel3')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="number"
                                        class="w-1/4 border rounded px-3 py-2 @error('fhargajuallevel1') is-invalid @enderror"
                                        name="fhargajuallevel1" id="fhargajuallevel1"
                                        value="{{ old('fhargajuallevel1', $product->fhargajuallevel1) }}">
                                    @error('fhargajuallevel1')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="number"
                                        class="w-1/4 border rounded px-3 py-2 @error('fhargajuallevel2') is-invalid @enderror"
                                        name="fhargajuallevel2" id="fhargajuallevel2"
                                        value="{{ old('fhargajuallevel2', $product->fhargajuallevel2) }}">
                                    @error('fhargajuallevel2')
                                        <div class="text-red-600 text-sm mt-1">
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
                                    <input type="number"
                                        class="w-1/4 border rounded px-3 py-2 @error('fhargajuallevel3') is-invalid @enderror"
                                        name="fhargajuallevel3" id="fhargajuallevel3"
                                        value="{{ old('fhargajuallevel3', $product->fhargajuallevel3) }}">
                                    @error('fhargajuallevel3')
                                        <div class="text-red-600 text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Min.Stok -->
                        <div class="mt-2 w-1/4">
                            <label class="block text-sm font-medium">Min.Stok</label>
                            <input type="text" name="fminstock" value="{{ old('fminstock', $product->fminstock) }}"
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
                                <option value="Produk" {{ old('ftype', $product->ftype) == 'Produk' ? 'selected' : '' }}>
                                    Product</option>
                                <option value="Jasa" {{ old('ftype', $product->ftype) == 'Jasa' ? 'selected' : '' }}>
                                    Jasa</option>
                            </select>
                            @error('ftype')
                                <div class="text-red-600 text-sm mt-1">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
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
                </form>
            </div>
        </div>
    </div>
@endsection

<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#groupSelect').select2({
            placeholder: '-- Pilih Group Produk --',
            allowClear: true
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#merkSelect').select2({
            placeholder: '-- Pilih Merek --',
            allowClear: true
        });
    });
</script>
