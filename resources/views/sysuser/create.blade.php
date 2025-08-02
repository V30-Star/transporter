@extends('layouts.app')

@section('title', 'Tambah Sysuser')

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
                        <span>Tambah User</span>
                    </h2>
                </div>
                <form action="{{ route('sysuser.store') }}" method="POST">
                    @csrf
                    {{-- <input type="hidden" name="fsysuserid" value="{{ old('fsysuserid', 'USR' . time()) }}"> --}}
                    <input type="hidden" name="created_at" value="{{ now()->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="fuserid" value="{{ auth()->user()->id ?? 'system' }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-sm font-medium">Nama Lengkap</label>
                            <input type="text" name="fname" value="{{ old('fname') }}"
                                class="w-full border rounded px-3 py-2 @error('fname') border-red-500 @enderror">
                            @error('fname')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- User Name / Login -->
                        <div>
                            <label class="block text-sm font-medium">User Name / Login</label>
                            <input type="text" name="fsysuserid" value="{{ old('fsysuserid') }}"
                                class="w-full border rounded px-3 py-2 @error('fsysuserid') border-red-500 @enderror">
                            @error('fsysuserid')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium">Password</label>
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

                        <!-- Tambahkan di bagian grid setelah confirm password -->
                        <div>
                            <label class="block text-sm font-medium">Cabang</label>
                            <input type="text" name="fcabang" value="{{ old('fcabang', '-') }}"
                                class="w-full border rounded px-3 py-2 @error('fcabang') border-red-500 @enderror">
                            @error('fcabang')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type (Dropdown with User and Admin) -->
                        {{-- <div>
                            <label class="block text-sm font-medium">Type</label>
                            <select name="type"
                                class="w-full border rounded px-3 py-2 @error('type') border-red-500 @enderror">
                                <option value="User" {{ old('type') == 'User' ? 'selected' : '' }}>User</option>
                                <option value="Admin" {{ old('type') == 'Admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('type')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div> --}}

                        <!-- Salesman (Checkbox) -->
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="fsalesman" value="1"
                                    {{ old('fsalesman') ? 'checked' : '' }} class="rounded text-blue-600">
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
                                <option value="User" {{ old('fuserlevel') == 'User' ? 'selected' : '' }}>User</option>
                                <option value="Admin" {{ old('fuserlevel') == 'Admin' ? 'selected' : '' }}>Admin
                                </option>
                            </select>
                            @error('fuserlevel')
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

                        <!-- Clear Button -->
                        <button type="reset"
                            class="bg-yellow-500 text-white px-6 py-2 rounded hover:bg-yellow-600 flex items-center">
                            <x-heroicon-o-trash class="w-5 h-5 mr-2" />
                            Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
