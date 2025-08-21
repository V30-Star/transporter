@extends('layouts.app')

@section('title', 'Master Gudang')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
        <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
            <x-heroicon-o-archive-box class="w-8 h-8 text-blue-600" />
            <span>Gudang Edit</span>
        </h2>
        <form action="{{ route('gudang.update', $gudang->fgudangid) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <!-- Field 1: Cabang (Dropdown) -->
                <div>
                    <label class="block text-sm font-medium">Cabang</label>
                    <select name="fcabangkode"
                        class="w-full border rounded px-3 py-2 @error('fcabangkode') border-red-500 @enderror">
                        <option value="">Pilih Cabang</option>
                        @foreach ($cabangOptions as $cabang)
                            <option value="{{ $cabang->fcabangkode }}"
                                {{ old('fcabangkode', $gudang->fcabangkode) == $cabang->fcabangkode ? 'selected' : '' }}>
                                {{ $cabang->fcabangname }}
                            </option>
                        @endforeach
                    </select>
                    @error('fcabangkode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 2: Kode Gudang -->
                <div>
                    <label class="block text-sm font-medium">Kode Gudang</label>
                    <input type="text" name="fgudangcode" value="{{ old('fgudangcode', $gudang->fgudangcode) }}"
                        class="w-full border rounded px-3 py-2 @error('fgudangcode') border-red-500 @enderror">
                    @error('fgudangcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 3: Nama Gudang -->
                <div>
                    <label class="block text-sm font-medium">Nama Gudang</label>
                    <input type="text" name="fgudangname" value="{{ old('fgudangname', $gudang->fgudangname) }}"
                        class="w-full border rounded px-3 py-2 @error('fgudangname') border-red-500 @enderror">
                    @error('fgudangname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 4: Alamat -->
                <div>
                    <label class="block text-sm font-medium">Alamat</label>
                    <input type="text" name="faddress" value="{{ old('faddress', $gudang->faddress) }}"
                        class="w-full border rounded px-3 py-2 @error('faddress') border-red-500 @enderror">
                    @error('faddress')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <br>
                <div class="md:col-span-2 flex justify-center items-center space-x-2">
                    <input type="checkbox" name="fnonactive" id="statusToggle" class="form-checkbox h-5 w-5 text-indigo-600"
                        {{ old('fnonactive', $gudang->fnonactive) == '1' ? 'checked' : '' }}>
                    <label class="block text-sm font-medium">Non Aktif</label>
                </div>
            </div>
            <br>
            <!-- Action Buttons -->
            <div class="mt-6 flex justify-center space-x-4">
                <!-- Simpan -->
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                    <x-heroicon-o-check class="w-5 h-5 mr-2" />
                    Simpan
                </button>

                <!-- Kembali -->
                <button type="button" onclick="window.location.href='{{ route('gudang.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>
            <br>
            <hr>
            <br>
            <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                <strong>{{ auth()->user()->fname ?? '—' }}</strong>

                <span class="ml-2 text-right" id="current-time">
                    {{ now()->format('d M Y, H:i') }}
                    , Terakhir di Update oleh: <strong>{{ $gudang->fupdatedby ?? '—' }}</strong>
                </span>
            </span>
        </form>
    </div>
@endsection

<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateTime() {
            const now = new Date();
            const formattedTime = now.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const currentTimeElement = document.getElementById('current-time');

            if (currentTimeElement) {
                currentTimeElement.textContent = formattedTime;
            } else {
                console.error("Element with ID 'current-time' not found.");
            }
        }

        setInterval(updateTime, 1000);
        updateTime();
    });
</script>
