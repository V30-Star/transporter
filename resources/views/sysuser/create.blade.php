@extends('layouts.app')

@section('title', 'Master Wewenang User')

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
        <div class="bg-white rounded shadow p-6 md:p-8 max-w-lg mx-auto">
            <form action="{{ route('sysuser.store') }}" method="POST">
                @csrf
                <input type="hidden" name="created_at" value="{{ now()->format('Y-m-d H:i:s') }}">
                <input type="hidden" name="fuserid" value="{{ auth()->user()->id ?? 'system' }}">

                <div class="space-y-4 mt-4">
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

                    <div>
                        <label class="block text-sm font-medium">Cabang</label>
                        <select name="fcabang"
                            class="w-full border rounded px-3 py-2 @error('fcabang') border-red-500 @enderror" required>
                            <option value="">-- Pilih Cabang --</option>
                            @foreach ($cabangs as $c)
                                <option value="{{ $c->fcabangkode }}"
                                    {{ old('fcabang') == $c->fcabangkode ? 'selected' : '' }}>
                                    {{ $c->fcabangkode }} - {{ $c->fcabangname }}
                                </option>
                            @endforeach
                        </select>
                        @error('fcabang')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ salesman: false }">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" x-model="salesman" class="rounded text-blue-600">
                            <span class="text-sm font-medium">Salesman</span>
                        </label>

                        <div x-show="salesman" x-transition>
                            <label class="block text-sm font-medium">Nama Salesman</label>
                            <select name="fsalesman"
                                class="w-full border rounded px-3 py-2 @error('fsalesman') border-red-500 @enderror"
                                id="salesmanSelect">
                                <option value="">-- Pilih Nama Salesman --</option>
                                @foreach ($salesman as $salesmans)
                                    <option value="{{ $salesmans->fsalesmanid }}"
                                        {{ old('fsalesman') == $salesmans->fsalesmanid ? 'selected' : '' }}>
                                        {{ $salesmans->fsalesmancode }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fsalesman')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
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

                    <button type="button" @click="window.location.href='{{ route('sysuser.index') }}'"
                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-2" />
                        Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
