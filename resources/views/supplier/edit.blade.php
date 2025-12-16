@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Supplier' : 'Edit Supplier')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-5xl mx-auto">

        @if ($action === 'delete')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium">Kode Supplier</label>
                    <input readonly type="text" name="fsuppliercode"
                        value="{{ old('fsuppliercode', $supplier->fsuppliercode) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fsuppliercode') border-red-500 @enderror"
                        autofocus>
                    @error('fsuppliercode')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Nama Supplier</label>
                    <input readonly type="text" name="fsuppliername"
                        value="{{ old('fsuppliername', $supplier->fsuppliername) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 uppercase @error('fsuppliername') border-red-500 @enderror">
                    @error('fsuppliername')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">NPWP</label>
                    <input readonly type="text" name="fnpwp" value="{{ old('fnpwp', $supplier->fnpwp) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fnpwp') border-red-500 @enderror">
                    @error('fnpwp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Alamat</label>
                    <textarea readonly name="faddress" rows="3"
                        class="w-full border rounded px-3 py-2 resize-y bg-gray-100 @error('faddress') border-red-500 @enderror">{{ old('faddress', $supplier->faddress) }}</textarea>
                    @error('faddress')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Telepon</label>
                    <input readonly type="text" name="ftelp" value="{{ old('ftelp', $supplier->ftelp) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('ftelp') border-red-500 @enderror">
                    @error('ftelp')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Fax</label>
                    <input readonly type="text" name="ffax" value="{{ old('ffax', $supplier->ffax) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('ffax') border-red-500 @enderror">
                    @error('ffax')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Jatuh Tempo</label>
                    <input readonly type="number" name="ftempo" value="{{ old('ftempo', $supplier->ftempo) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('ftempo') border-red-500 @enderror"
                        min="0" max="999" step="1"
                        oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,3)">
                    @error('ftempo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Kontak Person</label>
                    <input readonly type="text" name="fkontakperson"
                        value="{{ old('fkontakperson', $supplier->fkontakperson) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fkontakperson') border-red-500 @enderror">
                    @error('fkontakperson')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Jabatan</label>
                    <input readonly type="text" name="fjabatan" value="{{ old('fjabatan', $supplier->fjabatan) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fjabatan') border-red-500 @enderror">
                    @error('fjabatan')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">No. Rekening</label>
                    <input readonly type="text" name="fnorekening"
                        value="{{ old('fnorekening', $supplier->fnorekening) }}"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fnorekening') border-red-500 @enderror">
                    @error('fnorekening')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Memo</label>
                    <textarea readonly name="fmemo"
                        class="w-full border rounded px-3 py-2 bg-gray-100 @error('fmemo') border-red-500 @enderror">{{ old('fmemo', $supplier->fmemo) }}</textarea>
                    @error('fmemo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Mata Uang</label>
                    <select name="fcurr" class="bg-gray-100" disabled
                        class="w-full border rounded px-3 py-2 @error('fcurr') border-red-500 @enderror">
                        <option value="IDR" {{ old('fcurr', $supplier->fcurr) == 'IDR' ? 'selected' : '' }}>IDR (Rupiah)
                        </option>
                        <option value="USD" {{ old('fcurr', $supplier->fcurr) == 'USD' ? 'selected' : '' }}>USD (Dollar)
                        </option>
                        <option value="EUR" {{ old('fcurr', $supplier->fcurr) == 'EUR' ? 'selected' : '' }}>EUR (Euro)
                        </option>
                        <!-- Add more currencies as needed -->
                    </select>
                    @error('fcurr')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2 flex flex-col items-center space-y-4">
                    {{-- Checkbox Tetap di Atas --}}
                    <label for="statusToggle"
                        class="flex items-center justify-between w-40 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <span class="text-sm font-medium">Non Aktif</span>
                        <input disabled type="checkbox" name="fnonactive" id="statusToggle"
                            class="h-5 w-5 text-green-600 rounded focus:ring-green-500 bg-gray-100"
                            {{ old('fnonactive', $supplier->fnonactive) == '1' ? 'checked' : '' }}>
                    </label>

                    {{-- PEMBUNGKUS TOMBOL: Agar Sejajar Horizontal --}}
                    <div class="flex flex-row space-x-4">
                        <button type="button" onclick="showDeleteModal()"
                            class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 flex items-center">
                            <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                            Hapus
                        </button>

                        <button type="button" onclick="window.location.href='{{ route('supplier.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                            Kembali
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- MODE EDIT: FORM EDITABLE                    --}}
            {{-- ============================================ --}}
        @else
            <form action="{{ route('supplier.update', $supplier->fsupplierid) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium">Kode Supplier</label>
                        <input type="text" name="fsuppliercode"
                            value="{{ old('fsuppliercode', $supplier->fsuppliercode) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fsuppliercode') border-red-500 @enderror"
                            autofocus>
                        @error('fsuppliercode')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Nama Supplier</label>
                        <input type="text" name="fsuppliername"
                            value="{{ old('fsuppliername', $supplier->fsuppliername) }}"
                            class="w-full border rounded px-3 py-2 uppercase @error('fsuppliername') border-red-500 @enderror">
                        @error('fsuppliername')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">NPWP</label>
                        <input type="text" name="fnpwp" value="{{ old('fnpwp', $supplier->fnpwp) }}"
                            class="w-full border rounded px-3 py-2 @error('fnpwp') border-red-500 @enderror">
                        @error('fnpwp')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Alamat</label>
                        <textarea name="faddress" rows="3"
                            class="w-full border rounded px-3 py-2 resize-y @error('faddress') border-red-500 @enderror">{{ old('faddress', $supplier->faddress) }}</textarea>
                        @error('faddress')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Telepon</label>
                        <input type="text" name="ftelp" value="{{ old('ftelp', $supplier->ftelp) }}"
                            class="w-full border rounded px-3 py-2 @error('ftelp') border-red-500 @enderror">
                        @error('ftelp')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Fax</label>
                        <input type="text" name="ffax" value="{{ old('ffax', $supplier->ffax) }}"
                            class="w-full border rounded px-3 py-2 @error('ffax') border-red-500 @enderror">
                        @error('ffax')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Jatuh Tempo</label>
                        <input type="number" name="ftempo" value="{{ old('ftempo', $supplier->ftempo) }}"
                            class="w-full border rounded px-3 py-2 @error('ftempo') border-red-500 @enderror"
                            min="0" max="999" step="1"
                            oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,3)">
                        @error('ftempo')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Kontak Person</label>
                        <input type="text" name="fkontakperson"
                            value="{{ old('fkontakperson', $supplier->fkontakperson) }}"
                            class="w-full border rounded px-3 py-2 @error('fkontakperson') border-red-500 @enderror">
                        @error('fkontakperson')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Jabatan</label>
                        <input type="text" name="fjabatan" value="{{ old('fjabatan', $supplier->fjabatan) }}"
                            class="w-full border rounded px-3 py-2 @error('fjabatan') border-red-500 @enderror">
                        @error('fjabatan')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">No. Rekening</label>
                        <input type="text" name="fnorekening"
                            value="{{ old('fnorekening', $supplier->fnorekening) }}"
                            class="w-full border rounded px-3 py-2 @error('fnorekening') border-red-500 @enderror">
                        @error('fnorekening')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Memo</label>
                        <textarea name="fmemo" class="w-full border rounded px-3 py-2 @error('fmemo') border-red-500 @enderror">{{ old('fmemo', $supplier->fmemo) }}</textarea>
                        @error('fmemo')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Mata Uang</label>
                        <select name="fcurr"
                            class="w-full border rounded px-3 py-2 @error('fcurr') border-red-500 @enderror">
                            <option value="IDR" {{ old('fcurr', $supplier->fcurr) == 'IDR' ? 'selected' : '' }}>IDR
                                (Rupiah)
                            </option>
                            <option value="USD" {{ old('fcurr', $supplier->fcurr) == 'USD' ? 'selected' : '' }}>USD
                                (Dollar)
                            </option>
                            <option value="EUR" {{ old('fcurr', $supplier->fcurr) == 'EUR' ? 'selected' : '' }}>EUR
                                (Euro)
                            </option>
                            <!-- Add more currencies as needed -->
                        </select>
                        @error('fcurr')
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
                                {{ old('fnonactive', $supplier->fnonactive) == '1' ? 'checked' : '' }}>
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

                    <!-- Keluar -->
                    <button type="button" onclick="window.location.href='{{ route('supplier.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Kembali
                    </button>
                </div>
                @php
                    $lastUpdate = $supplier->fupdatedat ?: $supplier->fcreatedat;
                    $isUpdated = !empty($supplier->fupdatedat);
                @endphp
            </form>
        @endif
        <br>
        <hr><br>
        <span class="text-sm text-gray-600 flex justify-between items-center">
            <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>
            <span>{{ \Carbon\Carbon::parse($supplier->fupdatedat ?: $supplier->fcreatedat)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>
        </span>
    </div> {{-- ============================================ --}}
    {{-- MODAL & TOAST (HANYA UNTUK MODE DELETE)     --}}
    {{-- ============================================ --}}
    @if ($action === 'delete')
        {{-- Modal Delete --}}
        <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi hapus supplier ini?</h3>

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
                fetch('{{ route('supplier.destroy', $supplier->fsupplierid) }}', {
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
                            window.location.href = '{{ route('supplier.index') }}';
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
