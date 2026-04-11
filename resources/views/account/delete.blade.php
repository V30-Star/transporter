@extends('layouts.app')

@section('title', 'Hapus Account')

@section('content')
    <div class="bg-white rounded shadow p-6 md:p-8 max-w-[700px] mx-auto">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                <x-heroicon-o-trash class="w-8 h-8 text-red-600" />
            </div>
            <h2 class="text-xl font-bold text-gray-800">Konfirmasi Hapus Account</h2>
            <p class="text-gray-600 mt-1">Data berikut akan dihapus secara permanen:</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Account #</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900 uppercase">
                    {{ $account->faccount }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Nama Account</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900 uppercase">
                    {{ $account->faccname }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Type Account</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900">
                    {{ $account->fend == '1' ? 'Detil' : 'Header' }}
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Saldo Normal</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900">
                    {{ $account->fnormal == 'D' ? 'Debit' : 'Kredit' }}
                </div>
            </div>

            @if ($account->finitjurnal)
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Initial Jurnal</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900">
                    {{ $account->finitjurnal }}
                </div>
            </div>
            @endif

            @if ($account->fhavesubaccount == 1)
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Sub Account</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900">
                    Yes - 
                    @if(($account->ftypesubaccount ?? '') === 'S')
                        Sub Account
                    @elseif(($account->ftypesubaccount ?? '') === 'C')
                        Customer
                    @elseif(($account->ftypesubaccount ?? '') === 'P')
                        Supplier
                    @else
                        {{ $account->ftypesubaccount }}
                    @endif
                </div>
            </div>
            @endif

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Status</div>
                <div class="col-span-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                        {{ $account->fnonactive == '1' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                        {{ $account->fnonactive == '1' ? 'Non Active' : 'Active' }}
                    </span>
                </div>
            </div>

            @if ($accountHeader)
            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Account Header</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900 uppercase">
                    {{ $accountHeader->faccount }} - {{ $accountHeader->faccname }}
                </div>
            </div>
            @endif

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">User Level</div>
                <div class="col-span-2 text-sm font-semibold text-gray-900">
                    @switch($account->fuserlevel)
                        @case('1') User @break
                        @case('2') Supervisor @break
                        @case('3') Admin @break
                        @default {{ $account->fuserlevel }}
                    @endswitch
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="text-sm font-medium text-gray-500">Dibuat</div>
                <div class="col-span-2 text-sm text-gray-700">
                    {{ $account->fcreatedby ?? '-' }} - 
                    {{ $account->fcreatedat ? \Carbon\Carbon::parse($account->fcreatedat)->format('d/m/Y H:i') : '-' }}
                </div>
            </div>
        </div>

        @php
            $hasSubAccounts = \App\Models\Account::where('faccupline', $account->faccid)->exists();
            $hasJournalTransactions = \Illuminate\Support\Facades\DB::table('jurnaldt')->where('faccount', $account->faccount)->exists();
            $isHeader = $account->fend == 0;
        @endphp

        @if($isHeader || $hasSubAccounts || $hasJournalTransactions)
        <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" />
                <div>
                    <h4 class="text-sm font-semibold text-red-700">Account Tidak Dapat Dihapus</h4>
                    <ul class="mt-2 text-sm text-red-600 list-disc list-inside space-y-1">
                        @if($isHeader)
                            <li>Account ini adalah Account Header</li>
                        @endif
                        @if($hasSubAccounts)
                            <li>Account memiliki sub-account yang masih aktif</li>
                        @endif
                        @if($hasJournalTransactions)
                            <li>Account sudah digunakan dalam transaksi jurnal</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <div class="mt-8 flex justify-center gap-4">
            @if(!$isHeader && !$hasSubAccounts && !$hasJournalTransactions)
                <button type="button" onclick="showDeleteModal()"
                    class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 flex items-center font-medium">
                    <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                    Ya, Hapus
                </button>
            @endif
            <a href="{{ route('account.index') }}"
                class="bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 flex items-center font-medium">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                Kembali
            </a>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Last updated: {{ $account->fupdatedat ? \Carbon\Carbon::parse($account->fupdatedat)->format('d M Y, H:i') : \Carbon\Carbon::parse($account->fcreatedat)->format('d M Y, H:i') }}</p>
        </div>
    </div>

    @if(!$isHeader && !$hasSubAccounts && !$hasJournalTransactions)
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
                    Apakah Anda yakin ingin menghapus account <strong class="text-gray-900">{{ $account->faccount }} - {{ $account->faccname }}</strong>?
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

    <div x-show="showNotification" x-cloak x-transition:enter="transition ease-out duration-300"
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
        function showDeleteModal() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('btnDeleteText').textContent = 'Ya, Hapus';
            document.getElementById('btnDeleteText').classList.remove('hidden');
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

            fetch('{{ route('account.destroy', $account->faccid) }}', {
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
                
                if (data.success) {
                    window.notificationApp?.showNotification('success', data.message || 'Data berhasil dihapus');
                    setTimeout(() => {
                        window.location.href = '{{ route('account.index') }}';
                    }, 1000);
                } else {
                    window.notificationApp?.showNotification('error', data.message || 'Gagal menghapus data');
                }
            })
            .catch(error => {
                closeDeleteModal();
                window.notificationApp?.showNotification('error', 'Terjadi kesalahan saat menghapus data');
            });
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('notificationApp', () => ({
                showNotification: false,
                notificationMessage: '',
                notificationType: 'success',

                showNotification(type, message) {
                    this.notificationType = type;
                    this.notificationMessage = message;
                    this.showNotification = true;
                    setTimeout(() => {
                        this.showNotification = false;
                    }, 3000);
                }
            }));
        });

        window.notificationApp = {
            showNotification(type, message) {
                const event = new CustomEvent('show-notification', { 
                    detail: { type, message } 
                });
                window.dispatchEvent(event);
                
                const notifEl = document.querySelector('[x-data]');
                if (notifEl && notifEl.__x) {
                    const data = notifEl.__x.$data;
                    data.notificationType = type;
                    data.notificationMessage = message;
                    data.showNotification = true;
                    setTimeout(() => { data.showNotification = false; }, 3000);
                }
            }
        };
    </script>
    @endif
@endsection
