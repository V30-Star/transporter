{{-- resources/views/reporting/index.blade.php --}}

@extends('layouts.app')

@push('styles')
<style>
    /* Custom styling untuk halaman reporting */
    .report-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .report-header h2 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }
    
    .filter-card .card-header {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-bottom: 2px solid #667eea;
        border-radius: 10px 10px 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    .filter-card .card-header h6 {
        margin: 0;
        color: #2d3748;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .results-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }
    
    .results-card .card-header {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-bottom: 2px solid #48bb78;
        border-radius: 10px 10px 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    .results-card .card-header h6 {
        margin: 0;
        color: #2d3748;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .form-control, .btn {
        border-radius: 6px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border: none;
        padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(72, 187, 120, 0.4);
    }
    
    /* DataTables custom styling */
    #pr-report-table {
        font-size: 0.9rem;
    }
    
    #pr-report-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: none;
        padding: 1rem;
    }
    
    #pr-report-table tbody tr {
        transition: background-color 0.2s;
    }
    
    #pr-report-table tbody tr:hover {
        background-color: #f7fafc;
    }
    
    #pr-report-table tbody td {
        padding: 0.875rem;
        vertical-align: middle;
    }
    
    .badge {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 20px;
    }
    
    .badge-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    }
    
    .badge-danger {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
    }
    
    /* DataTables controls styling */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.375rem 0.75rem;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        padding: 0.5rem 1rem;
        border: 2px solid #e2e8f0;
        transition: border-color 0.3s;
    }
    
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Pagination Styling */
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 1rem;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.35rem 0.6rem !important;
        margin: 0 2px !important;
        border-radius: 4px !important;
        border: 1px solid #e2e8f0 !important;
        background: white !important;
        color: #4a5568 !important;
        font-weight: 500 !important;
        font-size: 0.875rem !important;
        transition: all 0.3s !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-color: #667eea !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3) !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-color: #667eea !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3) !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: white !important;
        border-color: #e2e8f0 !important;
        color: #4a5568 !important;
        transform: none !important;
        box-shadow: none !important;
    }
    
    .dataTables_wrapper .dataTables_info {
        padding-top: 1rem;
        color: #718096;
        font-weight: 500;
        font-size: 0.875rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .report-header {
            padding: 1.5rem;
        }
        
        .btn {
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .ml-2 {
            margin-left: 0 !important;
        }
    }
</style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="report-header">
            <h2>
                <i class="fas fa-chart-line"></i>
                Reporting Purchase Request
            </h2>
            <p class="mb-0 mt-2" style="opacity: 0.9;">Laporan dan analisis data purchase request</p>
        </div>

        <!-- Filter Section -->
        <div class="card filter-card">
            <div class="card-header">
                <h6>
                    <i class="fas fa-filter me-2"></i>
                    Filter Data
                </h6>
            </div>
            <div class="card-body">
                <form id="report-filter-form" action="{{ route('reports.pr.index') }}" method="GET">
                    <div class="row">
                        {{-- Filter Status --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-info-circle text-primary"></i>
                                Status
                            </label>
                            <select name="status" id="status" class="form-control">
                                <option value="active" {{ $status == 'active' ? 'selected' : '' }}>
                                    ✓ Aktif
                                </option>
                                <option value="nonactive" {{ $status == 'nonactive' ? 'selected' : '' }}>
                                    ✗ Non-Aktif/Ditutup
                                </option>
                                <option value="all" {{ $status == 'all' ? 'selected' : '' }}>
                                    ⊕ Semua Status
                                </option>
                            </select>
                        </div>

                        {{-- Filter Tahun --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="year" class="form-label">
                                <i class="fas fa-calendar-alt text-primary"></i>
                                Tahun
                            </label>
                            <select name="year" id="year" class="form-control">
                                <option value="">Semua Tahun</option>
                                @foreach ($availableYears as $availableYear)
                                    <option value="{{ $availableYear }}" {{ $year == $availableYear ? 'selected' : '' }}>
                                        {{ $availableYear }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filter Bulan --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="month" class="form-label">
                                <i class="fas fa-calendar text-primary"></i>
                                Bulan
                            </label>
                            <select name="month" id="month" class="form-control">
                                <option value="">Semua Bulan</option>
                                @foreach ($months as $monthNumber => $monthName)
                                    <option value="{{ $monthNumber }}" {{ $month == $monthNumber ? 'selected' : '' }}>
                                        {{ $monthName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tombol Aksi --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="form-label d-none d-lg-block">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Tampilkan Data
                                </button>
                                <a id="download-excel-btn" href="{{ route('reports.pr.export') }}" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i>
                                    Download Excel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Section --}}
        <div class="card results-card">
            <div class="card-header">
                <h6>
                    <i class="fas fa-table me-2"></i>
                    Hasil Laporan
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pr-report-table" class="table table-bordered table-hover" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Nomor PR</th>
                                <th>Tanggal PR</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data dimuat via AJAX DataTables --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Debug check
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables belum di-load!');
                alert('Error: DataTables library belum dimuat. Silakan refresh halaman.');
                return;
            }

            console.log('jQuery version:', $.fn.jquery);
            console.log('DataTables version:', $.fn.DataTable.version);

            // Inisialisasi DataTables
            const prTable = $('#pr-report-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('reports.pr.data') }}',
                    type: 'GET',
                    data: function(d) {
                        d.status = $('#status').val();
                        d.year = $('#year').val();
                        d.month = $('#month').val();
                    },
                    error: function(xhr, error, code) {
                        console.error('AJAX Error:', xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Gagal memuat data dari server!',
                            footer: 'Silakan cek console untuk detail error'
                        });
                    }
                },
                columns: [
                    {
                        data: 'fprno',
                        name: 'fprno',
                        className: 'font-weight-bold'
                    },
                    {
                        data: 'fprdin',
                        name: 'fprdin'
                    },
                    {
                        data: 'fclose',
                        name: 'fclose',
                        className: 'text-center',
                        render: function(data, type, row) {
                            return data == '0' ?
                                '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Aktif</span>' :
                                '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Ditutup</span>';
                        }
                    }
                ],
                order: [[1, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                language: {
                    processing: '<i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><span class="mt-2">Memuat data...</span>',
                    search: "_INPUT_",
                    searchPlaceholder: "🔍 Cari data...",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    info: "Menampilkan <strong>_START_</strong> sampai <strong>_END_</strong> dari <strong>_TOTAL_</strong> data",
                    infoEmpty: "Tidak ada data untuk ditampilkan",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    loadingRecords: "Memuat...",
                    zeroRecords: '<div class="text-center py-4"><i class="fas fa-search fa-3x text-muted mb-3"></i><br><h5>Tidak ada data yang ditemukan</h5><p class="text-muted">Coba ubah filter atau kata kunci pencarian</p></div>',
                    emptyTable: '<div class="text-center py-4"><i class="fas fa-inbox fa-3x text-muted mb-3"></i><br><h5>Belum ada data</h5><p class="text-muted">Data akan muncul di sini setelah ada purchase request</p></div>',
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        previous: '<i class="fas fa-angle-left"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>'
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function() {
                    // Tambahkan efek smooth scroll ke atas setelah ganti halaman
                    $('html, body').animate({
                        scrollTop: $("#pr-report-table").offset().top - 100
                    }, 300);
                }
            });

            // Event submit form
            $('#report-filter-form').on('submit', function(e) {
                e.preventDefault();

                // Reload DataTables dengan animasi
                prTable.ajax.reload();

                // Update URL Download Excel
                const status = $('#status').val();
                const year = $('#year').val();
                const month = $('#month').val();

                let queryParams = `?status=${status}`;
                if (year) queryParams += `&year=${year}`;
                if (month) queryParams += `&month=${month}`;

                const exportUrl = '{{ route('reports.pr.export') }}' + queryParams;
                $('#download-excel-btn').attr('href', exportUrl);
            });

            // Trigger submit untuk load data awal
            $('#report-filter-form').trigger('submit');
        });
    </script>
@endpush