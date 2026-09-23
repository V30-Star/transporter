{{-- Converted from udlgLaporanUangKasir.pas (filter dialog) --}}
@extends('layouts.app')

@section('title', 'Laporan Uang Kasir')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <div class="mb-6 border-l-4 border-emerald-700 pl-4">
        <h1 class="text-2xl font-serif text-slate-900">Laporan Uang Kasir</h1>
        <p class="text-sm text-slate-500 font-mono">Ringkasan uang kasir per cabang berdasarkan tanggal transaksi</p>
    </div>

    <form action="{{ route('laporanuangkasir.print') }}" method="GET" target="_blank"
          class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', $serverDate ?? $date ?? now()->toDateString()) }}"
                       class="w-full rounded-md border-slate-300 font-mono text-sm shadow-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kasir (User ID)</label>
                <input type="text" name="kasir" value="{{ old('kasir') }}" placeholder="Kosongkan untuk semua kasir"
                       class="w-full rounded-md border-slate-300 font-mono text-sm shadow-sm">
            </div>
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-slate-700">Cabang</label>
                @if ($canAccessAllBranches)
                    <div class="flex space-x-2">
                        <button type="button" onclick="selectBranches(true)" class="text-xs bg-emerald-50 text-emerald-700 hover:bg-emerald-100 px-3 py-1 rounded border border-emerald-300">Pilih Semua</button>
                        <button type="button" onclick="selectBranches(false)" class="text-xs bg-slate-50 text-slate-700 hover:bg-slate-100 px-3 py-1 rounded border border-slate-300">Batal Semua</button>
                    </div>
                @endif
            </div>
            <div class="border border-slate-200 rounded-md divide-y divide-slate-100 max-h-56 overflow-y-auto
                        {{ $canAccessAllBranches ? '' : 'opacity-80' }}">
                @foreach ($branchOptions as $branch)
                    <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer">
                        @if (! $canAccessAllBranches && $branch['checked'])
                            <input type="hidden" name="branch_codes[]" value="{{ $branch['code'] }}">
                        @endif
                        <input type="checkbox" name="branch_codes[]" value="{{ $branch['code'] }}"
                               class="branch-checkbox rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"
                               @checked($branch['checked'])
                               @disabled(! $canAccessAllBranches)>
                        <span class="font-mono font-semibold text-slate-700">{{ $branch['code'] }}</span> — <span class="text-slate-600">{{ $branch['name'] }}</span>
                    </label>
                @endforeach
            </div>
            @unless ($canAccessAllBranches)
                <p class="text-xs text-slate-400 mt-1">Anda hanya memiliki akses ke cabang Anda sendiri.</p>
            @endunless
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="hanya_tunai" value="1"
                   class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
            Hanya Uang Tunai Saja
        </label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end bg-slate-50 p-4 rounded-lg border border-slate-200">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Lebar Kertas (Dot Matrix)</label>
                <select name="width" class="w-full rounded-md border-slate-300 font-mono text-sm bg-white shadow-sm">
                    <option value="80" selected>80 kolom (standar)</option>
                    <option value="40">40 kolom (narrow carriage)</option>
                    <option value="132">132 kolom (wide carriage)</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer mb-2">
                <input type="checkbox" name="escp" value="1"
                       class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                Sertakan kode ESC/P (Epson NLQ + form feed otomatis)
            </label>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-4 border-t border-slate-100">
            <a href="{{ route('dashboard') }}"
               class="w-full sm:w-auto text-center px-4 py-2 text-sm font-medium rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">
                Kembali
            </a>
            <div class="flex flex-wrap gap-3 w-full sm:w-auto justify-end">
                <button type="submit" formaction="{{ route('laporanuangkasir.print-raw') }}"
                        class="px-4 py-2 text-sm font-medium rounded-md border border-slate-400 text-slate-700 hover:bg-slate-50 shadow-sm">
                    Cetak Dot Matrix (Raw Text)
                </button>
                <button type="submit" formaction="{{ route('laporanuangkasir.export') }}"
                        class="px-4 py-2 text-sm font-medium rounded-md border border-emerald-700 text-emerald-700 hover:bg-emerald-50 shadow-sm">
                    Export Excel
                </button>
                <button type="submit" formaction="{{ route('laporanuangkasir.print') }}"
                        class="px-5 py-2 text-sm font-medium rounded-md bg-emerald-700 text-white hover:bg-emerald-800 shadow-sm font-semibold">
                    Tampilkan Laporan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function selectBranches(checked) {
        document.querySelectorAll('.branch-checkbox:not(:disabled)').forEach((checkbox) => checkbox.checked = checked);
    }
</script>
@endsection
