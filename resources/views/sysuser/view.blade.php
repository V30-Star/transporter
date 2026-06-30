@extends('layouts.app')

@section('title', 'Master Wewenang User')

@section('content')
<div>

    <div class="max-w-4xl mx-auto py-8 px-6">

        {{-- ─── CARD 1: Identitas User ────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 pt-3 pb-0">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Identitas User</p>
            </div>
            <div class="p-4 space-y-3">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Cabang --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cabang</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>
                            <option value="HQ">Cabang HQ</option>
                            @foreach ($cabangs as $c)
                                <option value="{{ $c->fcabangkode }}" {{ $sysuser->fcabang == $c->fcabangkode ? 'selected' : '' }}>
                                    {{ $c->fcabangkode }} - {{ $c->fcabangname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Nama Lengkap --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                        <input type="text" value="{{ $sysuser->fname }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- User Name / Login --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">User Name / Login</label>
                        <input type="text" value="{{ $sysuser->fsysuserid }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200"
                            readonly>
                    </div>

                    {{-- Account Level --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Account Level</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>
                            <option value="User" {{ $sysuser->fuserlevel == '1' ? 'selected' : '' }}>User</option>
                            <option value="Admin" {{ $sysuser->fuserlevel == '2' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        {{-- ─── CARD 2: Salesman Linkage ────────────────────── --}}
        <div class="bg-white border border-gray-200 rounded-xl mb-3 overflow-hidden">
            <div class="px-4 pt-3 pb-0">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Salesman Linkage</p>
            </div>
            <div class="p-4 space-y-4">

                {{-- Status Hubungan ke Salesman --}}
                @php
                    $hasSalesman = $sysuser->fsalesman && $sysuser->fsalesman != 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                        <div>
                            <p class="text-sm text-gray-800">Hubungkan ke Salesman</p>
                            <p class="text-xs text-gray-400 mt-0.5">Aktifkan jika user ini terkait dengan data salesman tertentu</p>
                        </div>
                        <div class="relative w-9 h-5 duration-200 flex-shrink-0 cursor-not-allowed {{ $hasSalesman ? 'bg-blue-500/60' : 'bg-gray-200' }}">
                            <div class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform duration-200 {{ $hasSalesman ? 'translate-x-4 left-0.5' : 'left-0.5' }}"></div>
                        </div>
                    </div>
                </div>

                {{-- Dropdown Nama Salesman --}}
                @if ($hasSalesman)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nama Salesman</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200" disabled>
                            @foreach ($salesman as $salesmans)
                                @if ($sysuser->fsalesman == $salesmans->fsalesmanid)
                                    <option value="{{ $salesmans->fsalesmanid }}" selected>
                                        {{ $salesmans->fsalesmancode }} - {{ $salesmans->fsalesmanname }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @endif

            </div>

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                <button type="button"
                    onclick="window.location.href='{{ route('sysuser.index') }}'"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Kembali
                </button>
            </div>
        </div>

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
