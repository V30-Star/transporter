@extends('layouts.app')

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
                    <th class="border px-2 py-1">Date Time</th>
                    <th class="border px-2 py-1">Fuserid</th>
                    <th class="border px-2 py-1">Cabang</th>
                    <th class="border px-2 py-1">Actions</th> <!-- Add Actions Column -->
                </tr>
            </thead>
            <tbody>
                @foreach ($sysusers as $sysuser)
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

                            <!-- Delete Button (You can add an actual delete form here if needed) -->
                            <form action="{{ route('sysuser.destroy', $sysuser->fuid) }}" method="POST"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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
