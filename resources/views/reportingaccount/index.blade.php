@extends('layouts.app')

@section('title', 'Chart of Account Report')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-blue-700 to-indigo-800 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
                    </svg>
                    Laporan Chart of Account (COA)
                </h2>
            </div>

            <div class="p-8 text-center">
                <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Filter & Cetak Laporan</h3>
                <p class="text-gray-500 mb-6">Tentukan rentang akun yang ingin ditampilkan dalam laporan.</p>

                <button onclick="toggleModal(true)"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg transition-all transform hover:-translate-y-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Set Filter Account
                </button>
            </div>
        </div>
    </div>

    {{-- --- MODAL FILTER --- --}}
    <div id="filterModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="toggleModal(false)"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 overflow-hidden">
                <div class="flex justify-between items-center border-b pb-4 mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Chart of Account</h3>
                    <button onclick="toggleModal(false)"
                        class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="GET" action="{{ route('reportingaccount.rebuildAndPrint') }}" target="_blank">
                    <div class="space-y-4"> {{-- Menggunakan space-y untuk jarak antar grup --}}

                        {{-- Grup Account From --}}
                        <div class="flex flex-col">
                            <label for="account_from" class="block text-sm font-bold text-gray-700 mb-1">Account
                                From</label>
                            <select name="account_from" id="account_from" onchange="autoFillAccountTo()"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                <option value="">-- Pilih Akun Awal --</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->faccount }}" data-fdxorder="{{ $acc->fdxorder }}">
                                        {{ $acc->faccount }} - {{ $acc->faccname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Grup Account To --}}
                        <div class="flex flex-col">
                            <label for="account_to" class="block text-sm font-bold text-gray-700 mb-1">Account To</label>
                            <select name="account_to" id="account_to"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                <option value="">-- Pilih Akun Akhir --</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->faccount }}" data-forder="{{ $acc->forder }}">
                                        {{ $acc->faccount }} - {{ $acc->faccname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="flex justify-end space-x-3 mt-8 pt-4 border-t">
                        <button type="submit"
                            class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md transition-all flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print & Preview
                        </button>
                        <button type="button" onclick="toggleModal(false)"
                            class="px-5 py-2.5 bg-gray-100 text-gray-600 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi Select2
            const fromSelect = $('#account_from').select2({
                width: '100%',
                placeholder: '-- Pilih Akun --',
                allowClear: true
            });

            $('#account_to').select2({
                width: '100%',
                placeholder: '-- Pilih Akun --',
                allowClear: true
            });

            // Pemicu otomatis saat Select2 "Account From" berubah
            fromSelect.on('select2:select', function(e) {
                autoFillAccountTo();
            });
        });
    </script>
    <script>
        function toggleModal(show) {
            const modal = document.getElementById('filterModal');
            if (show) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        // Tambahkan ini agar modal langsung muncul saat halaman di-load
        document.addEventListener('DOMContentLoaded', function() {
            toggleModal(true);
        });

        function autoFillAccountTo() {
            // 1. Dapatkan fdxorder dari akun yang dipilih di Account From
            // Menggunakan jQuery agar kompatibel dengan Select2
            const selectedOption = $('#account_from').find(':selected');
            const targetDxOrder = selectedOption.data('fdxorder');

            if (!targetDxOrder) return;

            // 2. Cari di dropdown Account To yang memiliki data-forder == targetDxOrder
            // Kita lakukan loop pada semua option di account_to
            $('#account_to option').each(function() {
                if ($(this).data('forder') == targetDxOrder) {
                    // 3. Set nilai di select asli
                    $('#account_to').val($(this).val());

                    // 4. PENTING: Update tampilan Select2
                    $('#account_to').trigger('change');

                    return false; // Berhenti looping (break)
                }
            });
        }
    </script>
@endsection
