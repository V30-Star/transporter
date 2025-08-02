@extends('layouts.app')

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
}">
    @section('title', 'Master Customer')

    @section('content')
        <div class="bg-white rounded shadow p-4">
            <!-- Search + Order -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center space-x-2">
                    <label class="font-semibold">Search:</label>
                    <form action="{{ route('sysuser.index') }}" method="GET">
                        <input type="text" name="search" value="{{ request('search') }}" class="border rounded px-2 py-1"
                            placeholder="Cari...">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Cari
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <table class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">User Id</th>
                        <th class="border px-2 py-1">Nama User</th>
                        <th class="border px-2 py-1">Waktu</th>
                        <th class="border px-2 py-1">Fuserid</th>
                        <th class="border px-2 py-1">Cabang</th>
                        <th class="border px-2 py-1">Aksi</th> <!-- Add Actions Column -->
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sysusers as $sysuser)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-1">{{ $sysuser->fsysuserid }}</td>
                            <td class="border px-2 py-1">{{ $sysuser->fname }}</td>
                            <td class="border px-2 py-1">{{ $sysuser->created_at }}</td>
                            <td class="border px-2 py-1">{{ $sysuser->fuserid ?? 'N/A' }}</td>
                            <td class="border px-2 py-1">{{ $sysuser->fcabang }}</td>

                            <!-- Actions Column -->
                            <td class="border px-2 py-1">
                                <!-- Edit Button -->
                                <a href="{{ route('sysuser.edit', $sysuser->fuid) }}">
                                    <button
                                        class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                                        Edit
                                    </button>
                                </a>

                                <button @click="openDelete('{{ route('sysuser.destroy', $sysuser->fuid) }}')"
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
                {{ $sysusers->links() }}
            </div>

            <!-- Bottom Actions -->
            <div class="mt-4 flex justify-between items-center">
                <!-- Left Buttons -->
                <div class="space-x-2">
                    <a href="{{ route('sysuser.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                        Baru
                    </a>
                </div>

                <!-- Pagination -->
                <div class="flex items-center space-x-2">
                    {{ $sysusers->appends(['search' => request('search')])->links() }}
                </div>
            </div>
        </div>
    @endsection
