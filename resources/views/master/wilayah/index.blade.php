@extends('layouts.app')

@section('title', 'Master Wilayah')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) {
            this.deleteUrl = url;
            this.showDeleteModal = true;
        },
        closeDelete() {
            this.showDeleteModal = false;
            this.deleteUrl = null;
        }
    }" class="bg-white rounded shadow p-4">
        {{--  Search & Filter Form  --}}
        <form method="GET" action="{{ route('wilayah.index') }}"
            class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <div class="flex items-center space-x-2 w-full">
                <label class="font-semibold">Search:</label>
                <input type="text" name="search" value="{{ $search }}" class="border rounded px-2 py-1 w-1/4"
                    placeholder="Cari...">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Cari
                </button>
            </div>

        </form>

        {{--  Table Data  --}}
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">Kode Wilayah</th>
                    <th class="border px-2 py-1">Nama Wilayah</th>
                    <th class="border px-2 py-1">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wilayahs as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $item->fwilayahcode }}</td>
                        <td class="border px-2 py-1">{{ $item->fwilayahname }}</td>
                        <td class="border px-2 py-1 space-x-2">
                            <a href="{{ route('wilayah.edit', $item->fwilayahid) }}">
                                <button
                                    class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                                    Edit
                                </button>
                            </a>

                            <button @click="openDelete('{{ route('wilayah.destroy', $item->fwilayahid) }}')"
                                class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                Hapus
                            </button>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{--  Modal Delete  --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                        Batal
                    </button>

                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $wilayahs->links() }}
        </div>

        <div class="mt-4 flex justify-between items-center">
            <div class="space-x-2">
                <a href="{{ route('wilayah.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                    Baru
                </a>
            </div>
            <div class="flex items-center space-x-2">
                <button class="px-3 py-1 rounded border hover:bg-gray-100 disabled:opacity-50" disabled>
                    &larr;
                </button>
                <span class="text-sm">Page {{ $wilayahs->currentPage() }} of {{ $wilayahs->lastPage() }}</span>
                <button class="px-3 py-1 rounded border hover:bg-gray-100"
                    {{ $wilayahs->hasMorePages() ? '' : 'disabled' }}>
                    &rarr;
                </button>
            </div>
        </div>
    </div>
@endsection
