@extends('layouts.app')

@section('title', 'Master Wewenang User')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        <form action="{{ route('sysuser.update', $sysuser->fuid) }}" method="POST" data-form-draft="true" data-draft-key="sysuser:edit">
            @csrf
            @method('PATCH')

            {{-- ─── CARD 1: Identitas User ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas User</p>
                </div>
                <div class="p-4 space-y-3">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Cabang --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Cabang <span class="text-red-500">*</span>
                            </label>
                            <select name="fcabang" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fcabang') border-red-400 @enderror" required>
                                <option value="HQ">Cabang HQ</option>
                                @foreach ($cabangs as $c)
                                    <option value="{{ $c->fcabangkode }}" {{ old('fcabang', $sysuser->fcabang) == $c->fcabangkode ? 'selected' : '' }}>
                                        {{ $c->fcabangkode }} - {{ $c->fcabangname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fcabang')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fname" value="{{ old('fname', $sysuser->fname) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fname') border-red-400 @enderror"
                                placeholder="Masukkan Nama Lengkap" autofocus>
                            @error('fname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- User Name / Login --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                User Name / Login <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="fsysuserid" value="{{ old('fsysuserid', $sysuser->fsysuserid) }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fsysuserid') border-red-400 @enderror"
                                placeholder="Masukkan Username">
                            @error('fsysuserid')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Account Level --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">
                                Account Level <span class="text-red-500">*</span>
                            </label>
                            <select name="fuserlevel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fuserlevel') border-red-400 @enderror">
                                <option value="User" {{ old('fuserlevel', $sysuser->fuserlevel == '2' ? 'Admin' : 'User') == 'User' ? 'selected' : '' }}>User</option>
                                <option value="Admin" {{ old('fuserlevel', $sysuser->fuserlevel == '2' ? 'Admin' : 'User') == 'Admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('fuserlevel')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- Password Baru --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Password Baru (Kosongkan jika tidak diubah)</label>
                            <input type="password" name="password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('password') border-red-400 @enderror"
                                placeholder="Masukkan Password Baru">
                            @error('password')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Confirm Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('password_confirmation') border-red-400 @enderror"
                                placeholder="Konfirmasi Password Baru">
                            @error('password_confirmation')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─── CARD 2: Salesman Linkage ────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden"
                x-data="{ hasSalesman: {{ ($sysuser->fsalesman && $sysuser->fsalesman != 0) || old('fsalesman', null) ? 'true' : 'false' }} }">
                <div class="flex items-center gap-2 px-4 pt-3 pb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Salesman Linkage</p>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Toggle Salesman --}}
                    <div>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-pointer hover:border-gray-300 transition-colors"
                            @click="hasSalesman = !hasSalesman">
                            <div>
                                <p class="text-sm text-gray-800">Hubungkan ke Salesman</p>
                                <p class="text-xs text-gray-400 mt-0.5">Aktifkan jika user ini terkait dengan data salesman tertentu</p>
                            </div>
                            <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                                :class="hasSalesman ? 'bg-blue-500' : 'bg-gray-300'">
                                <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200"
                                    :class="hasSalesman ? 'translate-x-4 left-0.5' : 'left-0.5'"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Dropdown Nama Salesman --}}
                    <div x-show="hasSalesman" x-transition>
                        <label class="block text-xs font-bold text-gray-600 mb-1">
                            Nama Salesman <span class="text-red-500">*</span>
                        </label>
                        <select name="fsalesman" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('fsalesman') border-red-400 @enderror" :disabled="!hasSalesman">
                            <option value="">-- Pilih Salesman --</option>
                            @foreach ($salesman as $salesmans)
                                <option value="{{ $salesmans->fsalesmanid }}" {{ old('fsalesman', $sysuser->fsalesman) == $salesmans->fsalesmanid ? 'selected' : '' }}>
                                    {{ $salesmans->fsalesmancode }} - {{ $salesmans->fsalesmanname }}
                                </option>
                            @endforeach
                        </select>
                        @error('fsalesman')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <input type="hidden" name="fsalesman" value="0" :disabled="hasSalesman">

                </div>

                {{-- Footer Buttons --}}
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <button type="button"
                        onclick="window.location.href='{{ route('sysuser.index') }}'"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Kembali
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Simpan
                    </button>
                </div>
            </div>

        </form>

        {{-- FOOTER INFO --}}
        @php
            $lastUpdate = $sysuser->updated_at ?: $sysuser->created_at;
            $updatedBy = $sysuser->fusercreate ?: '—';
        @endphp
        <div class="mt-4 px-4 flex justify-between items-center text-xs text-gray-400">
            <span>Terakhir diupdate oleh: <strong>{{ $updatedBy }}</strong></span>
            <span>{{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') : '—' }}</span>
        </div>

    </div>

</div>
@endsection
