@extends('layouts.app')

@section('title', 'Edit Sysuser')

@section('content')
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>

    <div x-data="{ open: true, selected: 'surat' }">
        <div x-show="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
            <div class="bg-white w-full max-w-5xl p-6 rounded shadow relative overflow-y-auto max-h-screen">
                <!-- Header -->
                <div class="mb-6 border-b pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 flex items-center space-x-2">
                        <x-heroicon-o-user-plus class="w-6 h-6 text-blue-600" />
                        <span>Edit Sysuser</span>
                    </h2>
                </div>
                <form action="{{ route('sysuser.update', $sysuser->fuid) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-sm font-medium">Nama Lengkap</label>
                            <input type="text" name="fname" value="{{ old('fname', $sysuser->fname) }}"
                                class="w-full border rounded px-3 py-2 @error('fname') border-red-500 @enderror">
                            @error('fname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- User Name / Login -->
                        <div>
                            <label class="block text-sm font-medium">User Name / Login</label>
                            <input type="text" name="fsysuserid" value="{{ old('fsysuserid', $sysuser->fsysuserid) }}"
                                class="w-full border rounded px-3 py-2 @error('fsysuserid') border-red-500 @enderror">
                            @error('fsysuserid')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Baru -->
                        <div>
                            <label class="block text-sm font-medium">Password Baru</label>
                            <input type="password" name="password" value="{{ old('password') }}"
                                class="w-full border rounded px-3 py-2 @error('password') border-red-500 @enderror">
                            @error('password')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-sm font-medium">Confirm Password</label>
                            <input type="password" name="password_confirmation" value="{{ old('password_confirmation') }}"
                                class="w-full border rounded px-3 py-2 @error('password_confirmation') border-red-500 @enderror">
                            @error('password_confirmation')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Salesman (Checkbox) -->
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="fsalesman" value="1"
                                    {{ old('fsalesman', $sysuser->fsalesman) == '1' ? 'checked' : '' }}
                                    class="rounded text-blue-600">
                                <span class="text-sm font-medium">Salesman</span>
                            </label>
                            @error('fsalesman')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Account Level -->
                        <div>
                            <label class="block text-sm font-medium">Account Level</label>
                            <select name="fuserlevel"
                                class="w-full border rounded px-3 py-2 @error('fuserlevel') border-red-500 @enderror">
                                <option value="0"
                                    {{ old('fuserlevel', $sysuser->fuserlevel) == '0' ? 'selected' : '' }}>
                                    User</option>
                                <option value="1"
                                    {{ old('fuserlevel', $sysuser->fuserlevel) == '1' ? 'selected' : '' }}>
                                    Supervisor</option>
                                <option value="2"
                                    {{ old('fuserlevel', $sysuser->fuserlevel) == '2' ? 'selected' : '' }}>
                                    Admin</option>
                            </select>
                            @error('fuserlevel')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cabang -->
                        <div>
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" name="fcabang" value="{{ old('fcabang', $sysuser->fcabang) }}"
                                class="w-full border rounded px-3 py-2 @error('fcabang') border-red-500 @enderror">
                            @error('fcabang')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex justify-center space-x-4">
                        <!-- Save Button -->
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                            <x-heroicon-o-check class="w-5 h-5 mr-2" />
                            Simpan
                        </button>

                        <!-- Cancel Button -->
                        <button type="button" onclick="window.location.href='{{ route('sysuser.index') }}'"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                            <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                            Kembali
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
