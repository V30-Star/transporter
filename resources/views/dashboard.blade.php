@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Welcome Header --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Selamat datang, {{ session('fname') ?? Auth::user()->name ?? 'User' }}! 👋</h1>
            <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                <p>Cabang: <span class="font-semibold font-mono text-gray-700">{{ session('fcabang') ?? 'Semua Cabang' }}</span></p>
                <p>Last Login: <span class="font-semibold font-mono text-gray-700">{{ $lastLogin ? \Carbon\Carbon::parse($lastLogin)->isoFormat('D MMMM YYYY, HH:mm:ss') : '-' }}</span></p>
            </div>
        </div>
        <div class="text-right">
            <span class="text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY') }}
            </span>
        </div>
    </div>

    {{-- KPI Stat Cards Grid --}}
    @if ($canViewTotalPiutangUsaha || $canViewBelumJatuhTempo || $canViewLewatJatuhTempo || $canViewOmsetYtd)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Card 1: Total Piutang Usaha --}}
        @if ($canViewTotalPiutangUsaha)
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Piutang Usaha</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-extrabold text-gray-900 font-mono">
                    Rp {{ number_format($totalPiutangUsaha, 2, ',', '.') }}
                </h3>
                <p class="text-xs text-gray-400 mt-1">Status Saldo Piutang Customer</p>
            </div>
        </div>
        @endif

        {{-- Card 2: Belum Jatuh Tempo --}}
        @if ($canViewBelumJatuhTempo)
        <div class="bg-white border border-emerald-200 rounded-xl p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Belum Jatuh Tempo</span>
                <div class="w-9 h-9 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-extrabold text-emerald-700 font-mono">
                    Rp {{ number_format($piutangBelumJatuhTempo, 2, ',', '.') }}
                </h3>
                <p class="text-xs text-emerald-600 mt-1">Jatuh tempo hari ini & akan datang</p>
            </div>
        </div>
        @endif

        {{-- Card 3: Lewat Jatuh Tempo --}}
        @if ($canViewLewatJatuhTempo)
        <div class="bg-white border border-rose-200 rounded-xl p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Lewat Jatuh Tempo</span>
                <div class="w-9 h-9 rounded-lg bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-extrabold text-rose-700 font-mono">
                    Rp {{ number_format($piutangLewatJatuhTempo, 2, ',', '.') }}
                </h3>
                <p class="text-xs text-rose-600 mt-1">Menunggak (Perlu Penagihan)</p>
            </div>
        </div>
        @endif

        {{-- Card 4: Omset Penjualan Tahun Ini --}}
        @if ($canViewOmsetYtd)
        <div class="bg-white border border-indigo-200 rounded-xl p-5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Omset (YTD)</span>
                <div class="w-9 h-9 rounded-lg bg-indigo-100 border border-indigo-200 flex items-center justify-center text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-extrabold text-indigo-700 font-mono">
                    Rp {{ number_format($totalOmsetTahunIni, 2, ',', '.') }}
                </h3>
                <p class="text-xs text-indigo-600 mt-1">Total Sales Net (Faktur Penjualan)</p>
            </div>
        </div>
        @endif

    </div>
    @endif

    {{-- Main Section: Bar Chart & Top Overdue Table --}}
    @if ($canViewOmsetPenjualanBulan || $canViewTopPiutangLewatJatuhTempo)
    <div class="grid grid-cols-1 {{ ($canViewOmsetPenjualanBulan && $canViewTopPiutangLewatJatuhTempo) ? 'lg:grid-cols-2' : '' }} gap-6">

        {{-- Omset Penjualan Bar Chart --}}
        @if ($canViewOmsetPenjualanBulan)
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Omset Penjualan per Bulan
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">
                        Total nominal faktur penjualan bersih per bulan berjalan
                    </p>
                </div>

                {{-- Filter Year --}}
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 flex-shrink-0">
                    <label for="year" class="text-xs font-semibold text-gray-400 uppercase tracking-wide whitespace-nowrap">Tahun</label>
                    <select name="year" id="year" onchange="this.form.submit()"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition-colors">
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" @selected($y == $selectedYear)>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Chart Canvas --}}
            <div class="relative w-full" style="height: 320px;">
                <canvas id="omsetBarChart"></canvas>
            </div>
        </div>
        @endif

        {{-- Top Overdue Invoices --}}
        @if ($canViewTopPiutangLewatJatuhTempo)
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm flex flex-col">
            <div class="border-b border-gray-100 pb-4 mb-4">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Top Piutang Lewat Jatuh Tempo
                </h2>
                <p class="text-xs text-gray-400 mt-1">Prioritas penagihan ke customer</p>
            </div>

            @if ($topOverdueList->isEmpty())
                <div class="flex-1 flex flex-col items-center justify-center text-center py-10">
                    <svg class="w-12 h-12 text-emerald-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm font-bold text-emerald-700">Tidak ada piutang menunggak!</p>
                    <p class="text-xs text-gray-400 mt-1">Semua piutang terpantau lancar.</p>
                </div>
            @else
                <div class="space-y-2 overflow-y-auto" style="max-height: 320px;">
                    @foreach ($topOverdueList as $item)
                        <div class="p-3 border border-rose-100 rounded-lg bg-rose-50 hover:bg-rose-100 transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <span class="text-xs font-bold text-gray-800 truncate" title="{{ $item->fcustomername }}">
                                    {{ $item->fcustomername }}
                                </span>
                                <span class="flex-shrink-0 text-xs font-bold text-rose-700 bg-rose-100 border border-rose-200 px-2 py-0.5 rounded-full whitespace-nowrap">
                                    +{{ (int) $item->days_overdue }} hari
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs gap-2">
                                <span class="text-gray-400 font-mono truncate">{{ $item->fsono }}</span>
                                <span class="font-bold text-rose-800 font-mono whitespace-nowrap">
                                    Rp {{ number_format((float) $item->famountremain, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 pt-3 border-t border-gray-100 text-center">
                <a href="{{ route('analisaumurpiutang.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                    Lihat Analisa Umur Piutang lengkap
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        @endif

    </div>
    @endif

</div>

@push('scripts')
@if ($canViewOmsetPenjualanBulan)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartEl = document.getElementById('omsetBarChart');
        if (!chartEl) return;
        const ctx = chartEl.getContext('2d');
        const chartLabels = @json($chartLabels);
        const chartData = @json($chartData);

        const formatRupiah = (value) => {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        };

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Omset Penjualan (Rp)',
                    data: chartData,
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(29, 78, 216, 0.95)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += formatRupiah(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' }, color: '#4b5563' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f3f4f6' },
                        ticks: {
                            font: { size: 10 },
                            color: '#6b7280',
                            callback: function(value) {
                                if (value >= 1000000000) return (value / 1000000000).toFixed(1) + ' M';
                                if (value >= 1000000) return (value / 1000000).toFixed(0) + ' Jt';
                                if (value >= 1000) return (value / 1000).toFixed(0) + ' Rb';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endif
@endpush
@endsection