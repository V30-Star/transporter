@extends('layouts.app')

@section('title', 'Hapus Salesman')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                <x-heroicon-o-trash class="w-8 h-8 text-red-600" />
            </div>
            <h2 class="text-xl font-bold text-gray-800">Konfirmasi Hapus Salesman</h2>
            <p class="text-gray-600 mt-1">Data berikut akan dihapus secara permanen:</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Kode Salesman</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900 uppercase">
                    {{ $salesman->fsalesmancode }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Nama Salesman</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900 uppercase">
                    {{ $salesman->fsalesmanname }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Status</div>
                <div class="col-span-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                        {{ $salesman->fnonactive == '1' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                        {{ $salesman->fnonactive == '1' ? 'Non Active' : 'Active' }}
                    </span>
                </div>
            </div>

            @if($salesman->fcreatedby)
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Dibuat</div>
                <div class="col-span-2 text-sm text-gray-700">
                    {{ $salesman->fcreatedby ?? '-' }} - 
                    {{ $salesman->fcreatedat ? \Carbon\Carbon::parse($salesman->fcreatedat)->format('d/m/Y H:i') : '-' }}
                </div>
            </div>
            @endif

            @if($salesman->fupdatedat)
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Diupdate</div>
                <div class="col-span-2 text-sm text-gray-700">
                    {{ $salesman->fupdatedby ?? '-' }} - 
                    {{ \Carbon\Carbon::parse($salesman->fupdatedat)->format('d/m/Y H:i') }}
                </div>
            </div>
            @endif
        </div>

        @php
            $hasRelatedData = \Illuminate\Support\Facades\DB::table('mscustomer')
                ->where('fsalesman', $salesman->fsalesmanid)
                ->exists();
        @endphp

        @if($hasRelatedData)
        <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" />
                <div>
                    <h4 class="text-sm font-semibold text-red-700">Salesman Tidak Dapat Dihapus</h4>
                    <ul class="mt-2 text-sm text-red-600 list-disc list-inside space-y-1">
                        <li>Salesman sudah digunakan dalam data Customer</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <div class="mt-8 flex justify-center gap-4">
            @if(!$hasRelatedData)
                <button type="button" onclick="showDeleteModal()"
                    class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 flex items-center font-medium">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Ya, Hapus
                </button>
            @endif
            <a href="{{ route('salesman.index') }}"
                class="bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 flex items-center font-medium">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                Kembali
            </a>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Last updated: {{ $salesman->fupdatedat ? \Carbon\Carbon::parse($salesman->fupdatedat)->format('d M Y, H:i') : \Carbon\Carbon::parse($salesman->fcreatedat)->format('d M Y, H:i') }}</p>
        </div>
    </div>

    @if(!$hasRelatedData)
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-red-50">
                <h3 class="text-lg font-bold text-red-700 flex items-center">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 mr-2" />
                    Konfirmasi Penghapusan
                </h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">
                    Apakah Anda yakin ingin menghapus salesman <strong class="text-gray-900">{{ $salesman->fsalesmancode }} - {{ $salesman->fsalesmanname }}</strong>?
                </p>
                <p class="text-sm text-red-600 mb-6">
                    Tindakan ini tidak dapat dibatalkan dan data akan dihapus secara permanen.
                </p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
                        Batal
                    </button>
                    <button onclick="confirmDelete()" id="btnDelete"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium flex items-center">
                        <span id="btnDeleteText">Ya, Hapus</span>
                        <span id="btnDeleteLoading" class="hidden ml-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-data="notificationApp()" x-show="showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'"
            class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3">
            <span x-text="notificationMessage"></span>
            <button @click="showNotification = false" class="ml-4 text-white hover:text-gray-200">×</button>
        </div>
    </div>

    <script>
        function notificationApp() {
            return {
                showNotification: false,
                notificationMessage: '',
                notificationType: 'success',

                show(type, message) {
                    this.notificationType = type;
                    this.notificationMessage = message;
                    this.showNotification = true;
                    setTimeout(() => {
                        this.showNotification = false;
                    }, 3000);
                }
            }
        }

        function showDeleteModal() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('btnDeleteText').textContent = 'Ya, Hapus';
            document.getElementById('btnDeleteLoading').classList.add('hidden');
            document.getElementById('btnDelete').disabled = false;
        }

        function confirmDelete() {
            const btn = document.getElementById('btnDelete');
            const btnText = document.getElementById('btnDeleteText');
            const btnLoading = document.getElementById('btnDeleteLoading');
            
            btn.disabled = true;
            btnText.textContent = 'Menghapus...';
            btnLoading.classList.remove('hidden');

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
                
                const notifApp = document.querySelector('[x-data]');
                if (notifApp && notifApp._x_dataStack) {
                    const dataComponent = notifApp._x_dataStack[0];
                    if (dataComponent && typeof dataComponent.show === 'function') {
                        dataComponent.show(data.success ? 'success' : 'error', data.message || (data.success ? 'Data berhasil dihapus' : 'Gagal menghapus data'));
                    }
                }
                
                if (data.success || data.redirect) {
                    setTimeout(() => {
                        window.location.href = '{{ route('salesman.index') }}';
                    }, 1000);
                }
            })
            .catch(error => {
                closeDeleteModal();
                const notifApp = document.querySelector('[x-data]');
                if (notifApp && notifApp._x_dataStack) {
                    const dataComponent = notifApp._x_dataStack[0];
                    if (dataComponent && typeof dataComponent.show === 'function') {
                        dataComponent.show('error', 'Terjadi kesalahan saat menghapus data');
                    }
                }
            });
        }
    </script>
    @endif
@endsection
