@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Salesman' : 'Edit Salesman')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">

        {{-- ============================================ --}}
        {{-- MODE DELETE: VIEW ONLY + BUTTON HAPUS       --}}
        {{-- ============================================ --}}
        @if ($action === 'delete')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Salesman</label>
                    <input type="text" value="{{ $salesman->fsalesmancode }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Salesman</label>
                    <input type="text" value="{{ $salesman->fsalesmanname }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase" readonly>
                </div>

                <div class="flex justify-center mt-4">
                    <label class="flex items-center justify-between w-40 p-3 border rounded-lg bg-gray-100">
                        <span class="text-sm font-medium">Non Active</span>
                        <input type="checkbox" class="h-5 w-5 text-green-600 rounded"
                            {{ $salesman->fnonactive == '1' ? 'checked' : '' }} disabled>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-center space-x-4">
                <button type="button" onclick="showDeleteModal()"
                    class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Hapus
                </button>
                <button type="button" onclick="window.location.href='{{ route('salesman.index') }}'"
                    class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                    Kembali
                </button>
            </div>

            {{-- ============================================ --}}
            {{-- MODE EDIT: FORM EDITABLE                    --}}
            {{-- ============================================ --}}
        @else
            <form action="{{ route('salesman.update', $salesman->fsalesmanid) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Kode Salesman</label>
                        <input type="text" name="fsalesmancode"
                            value="{{ old('fsalesmancode', $salesman->fsalesmancode) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fsalesmancode') border-red-500 @enderror"
                            autofocus>
                        @error('fsalesmancode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Salesman</label>
                        <input type="text" name="fsalesmanname"
                            value="{{ old('fsalesmanname', $salesman->fsalesmanname) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fsalesmanname') border-red-500 @enderror">
                        @error('fsalesmanname')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-center mt-4">
                        <label for="statusToggle"
                            class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <span class="text-sm font-medium">Non Active</span>
                            <input type="checkbox" name="fnonactive" id="statusToggle"
                                class="h-5 w-5 text-green-600 rounded"
                                {{ old('fnonactive', $salesman->fnonactive) == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-center space-x-4">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                        <x-heroicon-o-check class="w-5 h-5 mr-2" />
                        Simpan
                    </button>
                    <button type="button" onclick="window.location.href='{{ route('salesman.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
                </div>
            </form>
        @endif

        {{-- FOOTER INFO --}}
        <br>
        <hr><br>
        <span class="text-sm text-gray-600 flex justify-between items-center">
            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
            <span>{{ \Carbon\Carbon::parse($salesman->fupdatedat ?: $salesman->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
        </span>
    </div>

    {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus salesman ini?</h3>

                <div class="flex justify-end space-x-2">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        id="btnTidak">
                        Tidak
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                        id="btnYa">
                        Ya, Hapus
                    </button>
                </div>
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
                fetch('{{ route('salesman.destroy', $salesman->fsalesmanid) }}', {
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

                        // Redirect ke index setelah 1.5 detik
                        setTimeout(() => {
                            window.location.href = '{{ route('salesman.index') }}';
                        }, 1500);
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
        border-top: 2px dashed #000;
        margin: 20px 0;
    }
</style>
