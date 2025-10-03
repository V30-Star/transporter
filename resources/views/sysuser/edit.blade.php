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
            <form action="{{ route('sysuser.update', $sysuser->fuid) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="space-y-4 mt-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-sm font-medium">Nama Lengkap</label>
                        <input type="text" name="fname" value="{{ old('fname', $sysuser->fname) }}"
                            class="w-full border rounded px-3 py-2 @error('fname') border-red-500 @enderror" autofocus>
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

                    <div x-data="{ salesman: {{ $sysuser->fsalesman ? 'true' : 'false' }} }">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" x-model="salesman" class="rounded text-blue-600">
                            <span class="text-sm font-medium">Salesman</span>
                        </label>

                        <div x-show="salesman" x-transition>
                            <label class="block text-sm font-medium">Salesman Level</label>
                            <select name="fsalesman"
                                class="w-full border rounded px-3 py-2 @error('fsalesman') border-red-500 @enderror"
                                id="salesmanSelect">
                                <option value="">-- Pilih Salesman --</option>
                                @foreach ($salesman as $salesmans)
                                    <option value="{{ $salesmans->fsalesmanid }}"
                                        {{ old('fsalesman', $sysuser->fsalesman) == $salesmans->fsalesmanid ? 'selected' : '' }}>
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

                    <!-- Cabang -->
                    <div>
                        <label class="block text-sm font-medium">Cabang</label>
                        <select name="fcabang"
                            class="w-full border rounded px-3 py-2 @error('fcabang') border-red-500 @enderror" required>
                            <option value="">-- Pilih Cabang --</option>
                            @foreach ($cabangs as $c)
                                <option value="{{ $c->fcabangkode }}"
                                    {{ old('fcabang', $sysuser->fcabang) == $c->fcabangkode ? 'selected' : '' }}>
                                    {{ $c->fcabangkode }} - {{ $c->fcabangname }}
                                </option>
                            @endforeach
                        </select>
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
                <br>
                <hr>
                <br>
                @php
                    $lastUpdate = $sysuser->fupdatedat ?: $sysuser->fcreatedat;
                    $isUpdated = !empty($sysuser->fupdatedat);
                @endphp

                <span class="text-sm text-gray-600 md:col-span-2 flex justify-between items-center">
                    <strong>{{ auth('sysuser')->user()->fname ?? '—' }}</strong>

                    <span class="ml-2 text-right">
                        {{ \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}
                    </span>
                </span>
            </form>
        </div>
    </div>
@endsection


<style>
    hr {
        border: 0;
        border-top: 2px dashed #000000;
        margin-top: 20px;
        margin-bottom: 20px;
    }
</style>
