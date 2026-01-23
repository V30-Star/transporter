@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Currency' : 'Edit Currency')

@section('content')

    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">

        {{-- ============================================ --}}
        {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
        {{-- ============================================ --}}
        @if ($action === 'delete')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Kode Currency</label>
                    <input disabled type="text" name="fcurrcode" value="{{ old('fcurrcode', $currency->fcurrcode) }}"
                        class="w-full border rounded px-3 py-2 uppercase @error('fcurrcode') border-red-500 @enderror"
                        {{ old('fcurrcode', $currency->fcurrcode) }} autofocus>
                    @error('fcurrcode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Nama Currency</label>
                    <textarea disabled name="fcurrname" rows="6"
                        class="w-full border rounded px-3 py-2 uppercase @error('fcurrname') border-red-500 @enderror">{{ old('fcurrname', $currency->fcurrname) }}</textarea>
                    @error('fcurrname')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Rate</label>
                    <input disabled type="number" name="frate"
                        class="w-full border rounded px-3 py-2 uppercase @error('frate') border-red-500 @enderror"
                        value="{{ old('frate', $currency->frate) }}">
                    @error('frate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-center mt-4">
                    <label class="flex items-center justify-between w-40 p-3 border rounded-lg bg-gray-100">
                        <span class="text-sm font-medium">Non Active</span>
                        <input type="checkbox" class="h-5 w-5 text-green-600 rounded"
                            {{ $currency->fnonactive == '1' ? 'checked' : '' }} disabled>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="showDeleteModal()"
                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Hapus
                </button>
                <button type="button" onclick="window.location.href='{{ route('currency.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>

            {{-- ============================================ --}}
            {{-- MODE EDIT: FORM EDITABLE                    --}}
            {{-- ============================================ --}}
        @else
            <form action="{{ route('currency.update', $currency->fcurrid) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="space-y-4 mt-4">
                    <!-- Currency Code -->
                    <div>
                        <label class="block text-sm font-medium">Kode Currency</label>
                        <input type="text" name="fcurrcode" value="{{ old('fcurrcode', $currency->fcurrcode) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fcurrcode') border-red-500 @enderror"
                            {{ old('fcurrcode', $currency->fcurrcode) }} autofocus>
                        @error('fcurrcode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Currency Name -->
                    <div>
                        <label class="block text-sm font-medium">Nama Currency</label>
                        <textarea name="fcurrname" rows="6"
                            class="w-full border rounded px-3 py-2 uppercase @error('fcurrname') border-red-500 @enderror">{{ old('fcurrname', $currency->fcurrname) }}</textarea>
                        @error('fcurrname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Rate</label>
                        <input type="number" name="frate"
                            class="w-full border rounded px-3 py-2 uppercase @error('frate') border-red-500 @enderror"
                            value="{{ old('frate', $currency->frate) }}">
                        @error('frate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <br>
                    <div class="flex justify-center mt-4">
                        <label for="statusToggle"
                            class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <span class="text-sm font-medium">Non Aktif</span>
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                class="h-5 w-5 text-green-600 rounded focus:ring-green-500"
                                {{ old('fnonactive', $currency->fnonactive) == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
                <br>
                <!-- Action Buttons -->
                <div class="mt-6 flex justify-center space-x-4">
                    <!-- Save Button -->
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>

                    <!-- Back Button -->
                    <button type="button" onclick="window.location.href='{{ route('currency.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
                </div>
                @php
                    $lastUpdate = $currency->fupdatedat ?: $currency->fcreatedat;
                    $isUpdated = !empty($currency->fupdatedat);
                @endphp
            </form>
        @endif
        <br>
        <hr><br>
        <span class="text-sm text-gray-600 flex justify-between items-center">
            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
            <span>{{ \Carbon\Carbon::parse($currency->fupdatedat ?: $currency->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
        </span>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus currency ini?</h3>
                <form id="deleteForm" action="{{ route('currency.destroy', $currency->fcurrid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                            Tidak
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
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
                fetch('{{ route('currency.destroy', $currency->fcurrid) }}', {
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
                            window.location.href = '{{ route('currency.index') }}';
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
