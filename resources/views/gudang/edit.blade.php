@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Gudang' : 'Edit Gudang')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">

        {{-- ============================================ --}}
        {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
        {{-- ============================================ --}}
        @if ($action === 'delete')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Cabang</label>
                    <select name="fbranchcode" disabled
                        class="w-full border rounded px-3 py-2 @error('fbranchcode') border-red-500 @enderror">
                        <option value="">Pilih Cabang</option>
                        @foreach ($cabangOptions as $cabang)
                            <option value="{{ $cabang->fbranchcode }}"
                                {{ old('fbranchcode', $gudang->fbranchcode) == $cabang->fbranchcode ? 'selected' : '' }}>
                                {{ $cabang->fcabangname }}
                            </option>
                        @endforeach
                    </select>
                    @error('fbranchcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 2: Kode Gudang -->
                <div>
                    <label class="block text-sm font-medium">Kode Gudang</label>
                    <input readonly type="text" name="fwhcode" value="{{ old('fwhcode', $gudang->fwhcode) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fwhcode') border-red-500 @enderror">
                    @error('fwhcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 3: Nama Gudang -->
                <div>
                    <label class="block text-sm font-medium">Nama Gudang</label>
                    <input readonly type="text" name="fwhname" value="{{ old('fwhname', $gudang->fwhname) }}"
                        class="w-full border rounded px-3 py-2 uppercase bg-gray-100 @error('fwhname') border-red-500 @enderror">
                    @error('fwhname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Field 4: Alamat -->
                <div>
                    <label class="block text-sm font-medium">Alamat</label>
                    <input readonly type="text" name="faddress" value="{{ old('faddress', $gudang->faddress) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('faddress') border-red-500 @enderror">
                    @error('faddress')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <br>
                <div class="md:col-span-2 flex flex-col items-center space-y-4">
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input type="checkbox" name="fnonactive" id="statusToggle" disabled
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                            {{ old('fnonactive', $gudang->fnonactive) == '1' ? 'checked' : '' }}>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="showDeleteModal()"
                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Hapus
                </button>
                <button type="button" onclick="window.location.href='{{ route('gudang.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>

            {{-- ============================================ --}}
            {{-- MODE EDIT: FORM EDITABLE                    --}}
            {{-- ============================================ --}}
        @else
            <form action="{{ route('gudang.update', $gudang->fwhid) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <!-- Field 1: Cabang (Dropdown) -->
                    <div>
                        <label class="block text-sm font-medium">Cabang</label>
                        <select name="fbranchcode"
                            class="w-full border rounded px-3 py-2 @error('fbranchcode') border-red-500 @enderror">
                            <option value="">Pilih Cabang</option>
                            @foreach ($cabangOptions as $cabang)
                                <option value="{{ $cabang->fbranchcode }}"
                                    {{ old('fbranchcode', $gudang->fbranchcode) == $cabang->fbranchcode ? 'selected' : '' }}>
                                    {{ $cabang->fcabangname }}
                                </option>
                            @endforeach
                        </select>
                        @error('fbranchcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Field 2: Kode Gudang -->
                    <div>
                        <label class="block text-sm font-medium">Kode Gudang</label>
                        <input type="text" name="fwhcode" value="{{ old('fwhcode', $gudang->fwhcode) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fwhcode') border-red-500 @enderror"
                            autofocus>
                        @error('fwhcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Field 3: Nama Gudang -->
                    <div>
                        <label class="block text-sm font-medium">Nama Gudang</label>
                        <input type="text" name="fwhname" value="{{ old('fwhname', $gudang->fwhname) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fwhname') border-red-500 @enderror">
                        @error('fwhname')
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
                    <div class="md:col-span-2 flex flex-col items-center space-y-4">
                        <label for="statusToggle"
                            class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <span class="text-sm font-medium">Non Aktif</span>
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                                {{ old('fnonactive', $gudang->fnonactive) == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
                <br>
                <!-- Action Buttons -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Simpan -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
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
                @php
                    $lastUpdate = $gudang->fupdatedat ?: $gudang->fcreatedat;
                    $isUpdated = !empty($gudang->fupdatedat);
                @endphp
            </form>
        @endif
        <br>
        <hr><br>
        <span class="text-sm text-gray-600 flex justify-between items-center">
            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
            <span>{{ \Carbon\Carbon::parse($gudang->fupdatedat ?: $gudang->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
        </span>
    </div>
    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus gudang ini?</h3>
                <form id="deleteForm" action="{{ route('gudang.destroy', $gudang->fwhid) }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                            id="btnTidak">
                            Tidak
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div id="toast" class="hidden fixed top-4 right-4 z-50 max-w-sm">
            <div id="toastContent" class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center">
                <span id="toastMessage"></span>
                <button onclick="closeToast()" class="ml-4 text-white hover:text-gray-200">×</button>
            </div>
        </div>

        <script>
            // Tampilkan Modal
            function showDeleteModal() {
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            // Tutup Modal
            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            // Tutup Toast
            function closeToast() {
                document.getElementById('toast').classList.add('hidden');
            }

            // Tampilkan Toast
            function showToast(message, isSuccess = true) {
                const toast = document.getElementById('toast');
                const toastContent = document.getElementById('toastContent');
                const toastMessage = document.getElementById('toastMessage');

                toastMessage.textContent = message;
                toastContent.className = isSuccess ?
                    'bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center' :
                    'bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center';

                toast.classList.remove('hidden');
            }

            // Konfirmasi Delete
            function confirmDelete() {
                const btnYa = document.getElementById('btnYa');
                const btnTidak = document.getElementById('btnTidak');

                // Disable buttons
                btnYa.disabled = true;
                btnTidak.disabled = true;
                btnYa.textContent = 'Menghapus...';

                // Kirim request delete
                fetch('{{ route('gudang.destroy', $gudang->fwhid) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            _method: 'DELETE'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        closeDeleteModal();
                        showToast(data.message || 'Data berhasil dihapus', true);

                        // Redirect ke index setelah 0.5 detik
                        setTimeout(() => {
                            window.location.href = '{{ route('gudang.index') }}';
                        }, 500);
                    })
                    .catch(error => {
                        btnYa.disabled = false;
                        btnTidak.disabled = false;
                        btnYa.textContent = 'Ya, Hapus';
                        showToast('Terjadi kesalahan saat menghapus data', false);
                    });
            }
        </script>
    @endif
@endsection

<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>
