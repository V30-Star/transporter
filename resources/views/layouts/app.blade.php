<!DOCTYPE html>
<html lang="en">

<head>
    @php
        $layoutRestrictedPermissions = array_filter(
            array_map('trim', explode(',', (string) session('user_restricted_permissions', '')))
        );
        $layoutCanAccessAllBranches = in_array('semuacabang', $layoutRestrictedPermissions, true);
        $layoutBranchOptions = $layoutCanAccessAllBranches
            ? \Illuminate\Support\Facades\DB::table('mscabang')
                ->select('fcabangkode', 'fcabangname')
                ->whereNotNull('fcabangkode')
                ->orderBy('fcabangkode')
                ->get()
                ->map(function ($branch) {
                    return [
                        'code' => trim((string) $branch->fcabangkode),
                        'name' => trim((string) ($branch->fcabangname ?? '')),
                    ];
                })
                ->values()
                ->all()
            : [];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', "Dashboard")</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('app-theme');
            const preferredTheme = savedTheme === 'dark' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', preferredTheme);
            document.documentElement.style.colorScheme = preferredTheme;
        })();
    </script>

    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
    @if (session('error') || $errors->any())
        <style>
            .alert.alert-danger[role="alert"],
            .border-red-200.bg-red-50[role="alert"] {
                display: none !important;
            }
        </style>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Lato', sans-serif;
        }

        :root {
            --app-bg: #f3f4f6;
            --app-surface: #ffffff;
            --app-surface-soft: #f9fafb;
            --app-surface-muted: #f3f4f6;
            --app-text: #111827;
            --app-text-soft: #374151;
            --app-text-muted: #6b7280;
            --app-border: #d1d5db;
            --app-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            --app-sidebar-bg: #000000;
            --app-sidebar-hover: rgba(255, 255, 255, 0.1);
        }

        html[data-theme="dark"] {
            --app-bg: #030712;
            --app-surface: #111827;
            --app-surface-soft: #1f2937;
            --app-surface-muted: #0f172a;
            --app-text: #f9fafb;
            --app-text-soft: #e5e7eb;
            --app-text-muted: #cbd5e1;
            --app-border: #374151;
            --app-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
            --app-sidebar-bg: #020617;
            --app-sidebar-hover: rgba(148, 163, 184, 0.18);
        }

        html,
        body {
            background: var(--app-bg);
            color: var(--app-text);
        }

        body {
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        html[data-theme="dark"] .bg-gray-100,
        html[data-theme="dark"] body.bg-gray-100 {
            background-color: var(--app-bg) !important;
        }

        html[data-theme="dark"] .bg-white,
        html[data-theme="dark"] header.bg-white,
        html[data-theme="dark"] .rounded.bg-white,
        html[data-theme="dark"] .shadow.bg-white {
            background-color: var(--app-surface) !important;
            color: var(--app-text) !important;
        }

        html[data-theme="dark"] .bg-gray-50,
        html[data-theme="dark"] .bg-gray-100 {
            background-color: var(--app-surface-soft) !important;
            color: var(--app-text) !important;
        }

        html[data-theme="dark"] .text-gray-800,
        html[data-theme="dark"] .text-gray-700,
        html[data-theme="dark"] .text-gray-600,
        html[data-theme="dark"] .text-gray-500,
        html[data-theme="dark"] .text-gray-400 {
            color: var(--app-text-soft) !important;
        }

        html[data-theme="dark"] .border,
        html[data-theme="dark"] .border-gray-100,
        html[data-theme="dark"] .border-gray-200,
        html[data-theme="dark"] .border-gray-300,
        html[data-theme="dark"] .border-gray-400,
        html[data-theme="dark"] .shadow,
        html[data-theme="dark"] .shadow-sm,
        html[data-theme="dark"] .shadow-md,
        html[data-theme="dark"] .shadow-lg {
            border-color: var(--app-border) !important;
            box-shadow: var(--app-shadow) !important;
        }

        html[data-theme="dark"] input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea {
            background-color: #0f172a !important;
            color: var(--app-text) !important;
            border-color: var(--app-border) !important;
        }

        html[data-theme="dark"] input::placeholder,
        html[data-theme="dark"] textarea::placeholder {
            color: #94a3b8 !important;
        }

        html[data-theme="dark"] input[readonly],
        html[data-theme="dark"] input:disabled,
        html[data-theme="dark"] textarea[readonly],
        html[data-theme="dark"] textarea:disabled,
        html[data-theme="dark"] select:disabled {
            background-color: #111827 !important;
            color: #cbd5e1 !important;
        }

        html[data-theme="dark"] table thead,
        html[data-theme="dark"] table thead tr,
        html[data-theme="dark"] table thead th {
            background-color: var(--app-surface-soft) !important;
            color: var(--app-text) !important;
            border-color: var(--app-border) !important;
        }

        html[data-theme="dark"] table tbody td,
        html[data-theme="dark"] table tfoot td,
        html[data-theme="dark"] table th {
            border-color: var(--app-border) !important;
            color: var(--app-text-soft) !important;
        }

        html[data-theme="dark"] .hover\:bg-gray-50:hover,
        html[data-theme="dark"] tr.hover\:bg-gray-50:hover,
        html[data-theme="dark"] .transaction-detail-table tbody tr:hover {
            background-color: #172033 !important;
        }

        html[data-theme="dark"] .bg-black {
            background-color: var(--app-sidebar-bg) !important;
        }

        html[data-theme="dark"] .hover\:bg-gray-700:hover,
        html[data-theme="dark"] .hover\:bg-white\/10:hover {
            background-color: var(--app-sidebar-hover) !important;
        }

        html[data-theme="dark"] .dt-container .dt-search .dt-input,
        html[data-theme="dark"] .dataTables_wrapper .dt-search .dt-input,
        html[data-theme="dark"] .dt-container .dt-length select.dt-input,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_length select {
            background-color: #0f172a !important;
            color: var(--app-text) !important;
            border-color: var(--app-border) !important;
        }

        .theme-toggle-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--app-border);
            background: var(--app-surface);
            color: var(--app-text-soft);
            border-radius: 0.5rem;
            padding: 0.5rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .theme-toggle-button:hover {
            background: var(--app-surface-soft);
        }

        html[data-theme="dark"] .theme-toggle-button {
            background: #0f172a;
            color: var(--app-text-soft);
            border-color: var(--app-border);
        }

        html[data-theme="dark"] .bg-green-100.border-green-400.text-green-700 {
            background-color: #052e16 !important;
            border-color: #166534 !important;
            color: #dcfce7 !important;
        }

        html[data-theme="dark"] .bg-red-100,
        html[data-theme="dark"] .bg-red-50 {
            background-color: #3f0d12 !important;
            color: #fecaca !important;
        }

        html[data-theme="dark"] .border-red-200,
        html[data-theme="dark"] .border-red-400 {
            border-color: #7f1d1d !important;
        }

        html[data-theme="dark"] .bg-blue-50 {
            background-color: #172554 !important;
            color: #dbeafe !important;
        }

        html[data-theme="dark"] .dt-container .dt-info,
        html[data-theme="dark"] .dt-container .dt-paging,
        html[data-theme="dark"] .dt-container .dt-search label,
        html[data-theme="dark"] .dt-container .dt-length label,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_info,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_paginate,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_filter label,
        html[data-theme="dark"] .dataTables_wrapper .dataTables_length label {
            color: var(--app-text-soft) !important;
        }

        html[data-theme="dark"] .dt-container .dt-paging .dt-paging-button,
        html[data-theme="dark"] .dataTables_wrapper .paginate_button {
            background: #0f172a !important;
            color: var(--app-text-soft) !important;
            border: 1px solid var(--app-border) !important;
        }

        html[data-theme="dark"] .dt-container .dt-paging .dt-paging-button.current,
        html[data-theme="dark"] .dataTables_wrapper .paginate_button.current {
            background: #1d4ed8 !important;
            color: #fff !important;
            border-color: #1d4ed8 !important;
        }

        html[data-theme="dark"] .dt-container .dt-paging .dt-paging-button:hover,
        html[data-theme="dark"] .dataTables_wrapper .paginate_button:hover {
            background: #1f2937 !important;
            color: #fff !important;
        }

        html[data-theme="dark"] .swal2-popup {
            background: #111827 !important;
            color: #f9fafb !important;
        }

        html[data-theme="dark"] .swal2-title,
        html[data-theme="dark"] .swal2-html-container {
            color: #f9fafb !important;
        }

        html[data-theme="dark"] .user-dropdown-panel {
            background-color: var(--app-surface) !important;
            border: 1px solid var(--app-border) !important;
        }

        html[data-theme="dark"] .user-dropdown-link:hover {
            background-color: var(--app-surface-soft) !important;
        }

        .transaction-detail-table {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .transaction-detail-table thead tr {
            background: #f3f4f6;
        }

        .transaction-detail-table thead th {
            padding: 10px 12px;
            color: #374151;
            font-weight: 600;
            border-bottom: 1px solid #d1d5db;
            white-space: nowrap;
            vertical-align: top;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .transaction-detail-table tbody td {
            padding: 10px 12px;
            border-top: 1px solid #e5e7eb;
            vertical-align: top;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .transaction-detail-table thead th>*,
        .transaction-detail-table tbody td>* {
            white-space: inherit;
            overflow: hidden;
            text-overflow: inherit;
        }

        .transaction-detail-table th[title],
        .transaction-detail-table td[title] {
            cursor: help;
        }

        .transaction-detail-table tbody tr:hover {
            background: #f9fafb;
        }

        .transaction-detail-table .transaction-product-column {
            min-width: 0;
            width: 36%;
            white-space: nowrap;
            vertical-align: top !important;
        }

        .transaction-detail-table .transaction-code-column {
            min-width: 0;
            width: 18%;
            white-space: nowrap;
        }

        .transaction-detail-table .transaction-unit-column {
            min-width: 0;
            width: 9%;
            white-space: nowrap;
        }

        .transaction-detail-table .transaction-reference-column {
            min-width: 0;
            width: 10%;
            white-space: nowrap;
        }

        .transaction-detail-table .transaction-action-column {
            min-width: 0;
            width: 7%;
            white-space: nowrap;
        }

        .transaction-detail-table .transaction-code-column,
        .transaction-detail-table .transaction-product-column {
            vertical-align: top;
        }

        .transaction-detail-table .transaction-code-column .flex,
        .transaction-detail-table .transaction-product-column .flex {
            min-width: 0;
        }

        .transaction-detail-table .transaction-product-column > * {
            width: 100%;
        }

        .transaction-detail-table .transaction-code-column input[type="text"],
        .transaction-detail-table .transaction-product-column input[type="text"],
        .transaction-detail-table .transaction-product-column select,
        .transaction-detail-table .transaction-product-column textarea {
            min-width: 0;
            width: 100%;
        }

        .transaction-detail-table .transaction-product-column input[type="text"],
        .transaction-detail-table .transaction-product-column select,
        .transaction-detail-table .transaction-product-column textarea,
        .transaction-detail-table .transaction-product-column .border {
            display: block;
            line-height: 1.25rem;
        }

        .transaction-detail-table .transaction-product-column textarea {
            margin-top: 0.25rem;
        }

        .transaction-detail-table .transaction-code-column .font-mono,
        .transaction-detail-table .transaction-product-column > div,
        .transaction-detail-table .transaction-product-column > span,
        .transaction-detail-table .transaction-product-column .align-middle {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .transaction-detail-table thead .transaction-reference-column {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
        }

        .transaction-detail-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden !important;
        }

        .po-detail-table {
            width: 100%;
            table-layout: auto !important;
        }

        .po-detail-table th,
        .po-detail-table td {
            overflow: visible !important;
            text-overflow: clip !important;
        }

        .po-detail-table input,
        .po-detail-table select,
        .po-detail-table textarea {
            max-width: 100%;
        }

        .balanced-detail-table {
            width: 100%;
            table-layout: auto !important;
        }

        .balanced-detail-table th,
        .balanced-detail-table td {
            overflow: visible !important;
            text-overflow: clip !important;
        }

        .balanced-detail-table input,
        .balanced-detail-table select,
        .balanced-detail-table textarea {
            max-width: 100%;
        }

        .fpb-detail-table {
            width: 100%;
            table-layout: fixed !important;
        }

        .fpb-detail-table th,
        .fpb-detail-table td {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            vertical-align: top;
            padding-top: 0.2rem !important;
            padding-bottom: 0.2rem !important;
        }

        .fpb-detail-table th:nth-child(2),
        .fpb-detail-table td:nth-child(2) {
            overflow: visible !important;
        }

        .fpb-detail-table td:nth-child(2) .flex {
            width: 100%;
            max-width: 100%;
            min-width: 0 !important;
        }

        .fpb-detail-table td:nth-child(2) .flex > input {
            min-width: 0 !important;
            flex: 1 1 auto !important;
        }

        .fpb-detail-table td:nth-child(2) .flex > button {
            flex: 0 0 auto !important;
        }

        .fpb-detail-table input,
        .fpb-detail-table select,
        .fpb-detail-table textarea {
            max-width: 100%;
        }

        .fpb-detail-table th:nth-child(n+6):nth-child(-n+11),
        .fpb-detail-table td:nth-child(n+6):nth-child(-n+11) {
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
        }

        .fpb-detail-table th:nth-child(n+2):nth-child(-n+5),
        .fpb-detail-table td:nth-child(n+2):nth-child(-n+5) {
            padding-left: 0.4rem !important;
            padding-right: 0.4rem !important;
        }

        .dt-container .dt-length,
        .dataTables_wrapper .dataTables_length {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dt-container .dt-length label,
        .dataTables_wrapper .dataTables_length label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .dt-container .dt-length select.dt-input,
        .dataTables_wrapper .dataTables_length select {
            min-width: 5rem !important;
            width: auto !important;
            padding: 0.35rem 2rem 0.35rem 0.65rem !important;
            line-height: 1.25rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            background-color: #fff !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 8 4 4 4-4'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.55rem center !important;
            background-size: 0.9rem 0.9rem !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        .dt-container .dt-length select.dt-input::-ms-expand,
        .dataTables_wrapper .dataTables_length select::-ms-expand {
            display: none;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('styles')
</head>

<body class="bg-gray-100" style="font-family: 'Lato', sans-serif;">
    <div x-data="{
        openSidebar: true,
        updateSidebarState() {
            if (window.innerWidth <= 1024) {
                this.openSidebar = false;
            } else {
                this.openSidebar = JSON.parse(localStorage.getItem('desktopSidebarOpen') ?? 'true');
            }
        }
    }" x-init="updateSidebarState();
    
    if (sessionStorage.getItem('collapseSidebarOnce') === '1') {
        openSidebar = false;
        sessionStorage.removeItem('collapseSidebarOnce');
        if (window.innerWidth > 1024) {
            localStorage.setItem('desktopSidebarOpen', false);
        }
    }
    
    $watch('openSidebar', value => {
        if (window.innerWidth > 1024) {
            localStorage.setItem('desktopSidebarOpen', value);
        }
    });
    
    window.addEventListener('pageshow', () => updateSidebarState());" @resize.window="updateSidebarState()"
        class="flex min-h-screen">

        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

        <div :class="openSidebar ? 'w-64' : 'w-16'"
            class="flex-shrink-0 bg-black text-white shadow-md overflow-y-auto transition-all duration-300">
            <x-sidebar />
        </div>

        <div class="flex-1 min-w-0 overflow-auto flex flex-col">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>

                <div class="flex items-center gap-3 ml-auto">
                    <div class="flex flex-col items-end gap-1">
                    @auth
                        <div class="text-sm text-gray-600 font-medium mr-4">
                            <span>{{ Auth::user()->fname }} | {{ Auth::user()->fsysuserid }}</span>
                        </div>
                    @endauth

                    <div x-data="{
                        display: '',
                        update() {
                            const dt = new Date();
                    
                            const months = [
                                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                            ];
                    
                            const day = dt.getDate();
                            const month = months[dt.getMonth()];
                            const year = dt.getFullYear();
                            const hour = dt.getHours().toString().padStart(2, '0');
                            const minute = dt.getMinutes().toString().padStart(2, '0');
                            const second = dt.getSeconds().toString().padStart(2, '0');
                    
                            this.display = `${day} ${month} ${year} | ${hour}:${minute}:${second}`;
                        },
                        init() {
                            this.update();
                            setInterval(() => this.update(), 1000);
                        }
                    }" x-init="init()" class="text-sm text-gray-600 font-medium mr-4"
                        x-text="display"></div>
                    </div>
                </div>

                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}"
                                class="w-8 h-8 rounded-full" alt="avatar">
                            <span class="text-gray-700 hidden sm:inline">{{ Auth::user()->name }}</span>
                        </button>

                        <div x-show="open" @click.outside="open = false"
                            class="user-dropdown-panel absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-20" x-cloak>
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                                    {{ "Tema Tampilan" }}
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" onclick="window.appTheme.set('light')"
                                        class="theme-toggle-button justify-center w-full !px-3 !py-2 !text-xs"
                                        id="theme-light-option">
                                        {{ "Terang" }}
                                    </button>
                                    <button type="button" onclick="window.appTheme.set('dark')"
                                        class="theme-toggle-button justify-center w-full !px-3 !py-2 !text-xs"
                                        id="theme-dark-option">
                                        {{ "Gelap" }}
                                    </button>
                                </div>
                            </div>
                            <a href="{{ route('settings') }}"
                                class="user-dropdown-link block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ "Pengaturan" }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="user-dropdown-link w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ "Logout" }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </header>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <main class="p-6 flex-1">
                @yield('content')
            </main>
        </div>
    </div>
    @php
        $transactionErrorMessages = collect();

        if (session('error')) {
            $transactionErrorMessages->push((string) session('error'));
        }

        if ($errors->any()) {
            $transactionErrorMessages = $transactionErrorMessages->merge($errors->all());
        }

        $transactionErrorMessages = $transactionErrorMessages
            ->map(fn ($message) => trim((string) $message))
            ->filter()
            ->unique()
            ->values();
    @endphp
    @stack('scripts')
    <script>
        (() => {
            function resolveTheme(theme) {
                return theme === 'dark' ? 'dark' : 'light';
            }

            function applyTheme(theme) {
                const nextTheme = resolveTheme(theme);
                document.documentElement.setAttribute('data-theme', nextTheme);
                document.documentElement.style.colorScheme = nextTheme;
                localStorage.setItem('app-theme', nextTheme);

                const lightOption = document.getElementById('theme-light-option');
                const darkOption = document.getElementById('theme-dark-option');

                if (lightOption) {
                    lightOption.classList.toggle('ring-2', nextTheme === 'light');
                    lightOption.classList.toggle('ring-blue-300', nextTheme === 'light');
                    lightOption.classList.toggle('border-blue-500', nextTheme === 'light');
                }

                if (darkOption) {
                    darkOption.classList.toggle('ring-2', nextTheme === 'dark');
                    darkOption.classList.toggle('ring-blue-300', nextTheme === 'dark');
                    darkOption.classList.toggle('border-blue-500', nextTheme === 'dark');
                }

                window.dispatchEvent(new CustomEvent('theme-changed', {
                    detail: {
                        theme: nextTheme
                    }
                }));
            }

            window.appTheme = {
                get() {
                    return resolveTheme(localStorage.getItem('app-theme'));
                },
                set(theme) {
                    applyTheme(theme);
                },
                toggle() {
                    applyTheme(this.get() === 'dark' ? 'light' : 'dark');
                }
            };

            document.addEventListener('DOMContentLoaded', () => {
                applyTheme(window.appTheme.get());
            });
        })();
    </script>
    <script>
        (() => {
            if (window.transactionReferenceModalHelper) {
                return;
            }

            window.transactionReferenceModalHelper = {
                dispatchPick(eventName, header, items) {
                    window.dispatchEvent(new CustomEvent(eventName, {
                        detail: {
                            header,
                            items
                        }
                    }));
                },

                openDupModal(state, header, duplicates, uniques) {
                    state.dupCount = duplicates.length;
                    state.dupSample = duplicates.slice(0, 6);
                    state.pendingHeader = header;
                    state.pendingUniques = uniques;
                    state.showDupModal = true;
                },

                closeDupModal(state) {
                    state.showDupModal = false;
                    state.dupCount = 0;
                    state.dupSample = [];
                    state.pendingHeader = null;
                    state.pendingUniques = [];
                },

                confirmAddUniques(state, eventName, options = {}) {
                    const detailBuilder = typeof options.detailBuilder === 'function'
                        ? options.detailBuilder
                        : (header, items) => ({
                            header,
                            items
                        });

                    const detail = detailBuilder(state.pendingHeader, state.pendingUniques);

                    window.dispatchEvent(new CustomEvent(eventName, {
                        detail
                    }));

                    this.closeDupModal(state);

                    if (options.skipCloseModal === true) {
                        return;
                    }

                    if (typeof state.closeModal === 'function') {
                        state.closeModal();
                    }
                }
            };
        })();
    </script>
    <script>
        (() => {
            function isQtyInput(element) {
                if (!(element instanceof HTMLInputElement)) {
                    return false;
                }

                if (element.type !== 'number' && element.inputMode !== 'decimal') {
                    return false;
                }

                const name = (element.name || '').toLowerCase();
                const id = (element.id || '').toLowerCase();

                return name.includes('qty') || id.includes('qty');
            }

            function isMinStockInput(element) {
                if (!(element instanceof HTMLInputElement)) {
                    return false;
                }

                const name = (element.name || '').toLowerCase();
                const id = (element.id || '').toLowerCase();

                return name === 'fminstock' || id === 'fminstock';
            }

            function formatNumberAsId(value) {
                const parsedValue = Number(String(value ?? '').replace(/\./g, '').replace(',', '.'));
                if (!Number.isFinite(parsedValue)) {
                    return value;
                }

                return parsedValue.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function normalizeIdNumber(value) {
                const parsedValue = Number(String(value ?? '').replace(/\./g, '').replace(',', '.'));
                if (!Number.isFinite(parsedValue)) {
                    return value;
                }

                return String(parsedValue);
            }

            function applyQtyFormatting(root = document) {
                root.querySelectorAll('input').forEach((input) => {
                    if (!isQtyInput(input) || input.dataset.qtyFormatted === '1') {
                        return;
                    }

                    input.step = '0.01';
                    input.inputMode = 'decimal';

                    const formatCurrentValue = () => {
                        const currentValue = input.value;
                        if (currentValue === null || currentValue === undefined || currentValue === '') {
                            return;
                        }

                        const parsedValue = Number(currentValue);
                        if (!Number.isFinite(parsedValue)) {
                            return;
                        }

                        input.value = parsedValue.toFixed(2);
                    };

                    formatCurrentValue();

                    input.addEventListener('blur', () => {
                        formatCurrentValue();
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    input.dataset.qtyFormatted = '1';
                });
            }

            function applyMinStockFormatting(root = document) {
                root.querySelectorAll('input').forEach((input) => {
                    if (!isMinStockInput(input) || input.dataset.minstockFormatted === '1') {
                        return;
                    }

                    const formatCurrentValue = () => {
                        const currentValue = input.value;
                        if (currentValue === null || currentValue === undefined || currentValue === '') {
                            return;
                        }

                        const formattedValue = formatNumberAsId(currentValue);
                        if (formattedValue !== currentValue) {
                            input.value = formattedValue;
                        }
                    };

                    formatCurrentValue();

                    input.addEventListener('blur', formatCurrentValue);
                    input.dataset.minstockFormatted = '1';
                });
            }

            function scheduleQtyFormatting() {
                applyQtyFormatting();
                applyMinStockFormatting();

                requestAnimationFrame(() => {
                    applyQtyFormatting();
                    applyMinStockFormatting();
                    requestAnimationFrame(() => applyQtyFormatting());
                });

                window.setTimeout(() => {
                    applyQtyFormatting();
                    applyMinStockFormatting();
                }, 100);
                window.setTimeout(() => {
                    applyQtyFormatting();
                    applyMinStockFormatting();
                }, 500);
                window.setTimeout(() => {
                    applyQtyFormatting();
                    applyMinStockFormatting();
                }, 1000);
            }

            document.addEventListener('DOMContentLoaded', () => {
                scheduleQtyFormatting();

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node instanceof HTMLElement) {
                                if (node.matches && node.matches('input')) {
                                    applyQtyFormatting(node.parentElement || document);
                                } else {
                                    applyQtyFormatting(node);
                                }
                            }
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            });

            window.addEventListener('load', scheduleQtyFormatting);

            window.addEventListener('submit', (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                form.querySelectorAll('input').forEach((input) => {
                    if (!isMinStockInput(input)) {
                        return;
                    }

                    const normalizedValue = normalizeIdNumber(input.value);
                    if (normalizedValue !== input.value) {
                        input.value = normalizedValue;
                    }
                });
            }, true);
        })();
    </script>
    <script>
        (() => {
            if (window.showTransactionErrorModal) {
                return;
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[char]));
            }

            function inferReason(items) {
                const text = items.join(' ').toLowerCase();

                if (text.includes('balance') || text.includes('debit') || text.includes('kredit')) {
                    return 'Jumlah debit dan kredit masih belum seimbang.';
                }

                if (text.includes('minimal satu item') || text.includes('data items') || text.includes('detail')) {
                    return 'Detail transaksi masih belum lengkap.';
                }

                if (text.includes('qty') || text.includes('quantity')) {
                    return 'Jumlah item yang diinput masih belum sesuai.';
                }

                if (text.includes('supplier') || text.includes('customer') || text.includes('salesman') || text.includes('gudang') || text.includes('akun')) {
                    return 'Ada data pilihan yang masih kosong atau belum sesuai.';
                }

                if (text.includes('close') || text.includes('referensi')) {
                    return 'Data referensi transaksi ini masih belum bisa dipakai.';
                }

                return 'Masih ada data yang perlu diperbaiki.';
            }

            function simplifyMessage(message) {
                let result = String(message ?? '').trim();

                result = result.replace(/^the\s+/i, '');
                result = result.replace(/\.$/, '');
                result = result.replace(/\bvalidation\b/gi, 'pemeriksaan');
                result = result.replace(/\bfield\b/gi, 'kolom');
                result = result.replace(/\brequired\b/gi, 'wajib diisi');
                result = result.replace(/\bmust be\b/gi, 'harus');
                result = result.replace(/\bmay not be greater than\b/gi, 'tidak boleh lebih dari');
                result = result.replace(/\bmay not be less than\b/gi, 'tidak boleh kurang dari');
                result = result.replace(/\bmust not\b/gi, 'tidak boleh');
                result = result.replace(/\bselected\b/gi, 'dipilih');
                result = result.replace(/\binvalid\b/gi, 'tidak valid');
                result = result.replace(/\bexists\b/gi, 'sudah ada');
                result = result.replace(/\bqty\b/gi, 'jumlah');
                result = result.replace(/\baccount\b/gi, 'account');
                result = result.replace(/\s+/g, ' ').trim();

                if (!/[.!?]$/.test(result)) {
                    result += '.';
                }

                return result.charAt(0).toUpperCase() + result.slice(1);
            }

            window.showTransactionErrorModal = function(messages, options = {}) {
                const rawMessages = (Array.isArray(messages) ? messages : [messages])
                    .map((message) => String(message ?? '').trim())
                    .filter(Boolean);

                const simpleSingleMessage = rawMessages.length === 1 ? rawMessages[0] : '';
                const simpleLines = simpleSingleMessage !== '' ? simpleSingleMessage.split(/\r?\n/).map((line) => line.trim()).filter(Boolean) : [];
                const simpleTitle = simpleLines.length > 1 && ['information', 'warning'].includes(simpleLines[0].toLowerCase())
                    ? simpleLines[0]
                    : '';

                if (simpleTitle !== '') {
                    Swal.fire({
                        icon: simpleTitle.toLowerCase() === 'warning' ? 'warning' : 'info',
                        title: simpleTitle,
                        html: `<div class="text-left whitespace-pre-line" style="font-size:14px; line-height:1.7;">${escapeHtml(simpleLines.slice(1).join('\n'))}</div>`,
                        confirmButtonText: 'Ok',
                        confirmButtonColor: '#f59e0b',
                        width: 560
                    });
                    return;
                }

                const normalizedMessages = rawMessages
                    .map((message) => simplifyMessage(message))
                    .filter(Boolean);

                if (normalizedMessages.length === 0) {
                    normalizedMessages.push('Terjadi kesalahan validasi yang tidak diketahui.');
                }

                const listHtml = normalizedMessages.map((message) =>
                    `<li style="margin-bottom:8px;">${escapeHtml(message)}</li>`
                ).join('');

                Swal.fire({
                    icon: 'warning',
                    title: options.title || 'Information',
                    html: `
                        <div style="text-align:left; font-size:14px; line-height:1.6;">
                            <p style="margin:0 0 10px 0;">${escapeHtml(options.reason || inferReason(normalizedMessages))}</p>
                            <ul style="margin:0 0 12px 18px; padding:0;">${listHtml}</ul>
                            <p style="margin:0; color:#6b7280;">Silakan cek poin di atas lalu simpan lagi.</p>
                        </div>
                    `,
                    confirmButtonText: 'Ok',
                    confirmButtonColor: '#f59e0b',
                    width: 560
                });
            };
        })();
    </script>
    @if ($transactionErrorMessages->isNotEmpty())
        <script>
            (() => {
                const messages = @json($transactionErrorMessages);

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('.alert.alert-danger[role="alert"], .border-red-200.bg-red-50[role="alert"]')
                        .forEach((element) => element.remove());

                    window.showTransactionErrorModal(messages);
                });
            })();
        </script>
    @endif
    <script>
        (() => {
            window.formatNumber2 = function(value, fallback = '-') {
                if (value === null || value === undefined || value === '') {
                    return fallback;
                }

                const amount = Number(value);
                if (!isFinite(amount)) {
                    return fallback;
                }

                return amount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            window.formatCurrency2 = function(value, prefix = 'Rp ', fallback = '-') {
                const formatted = window.formatNumber2(value, fallback);
                return formatted === fallback ? `${prefix}${fallback}` : `${prefix}${formatted}`;
            };

            window.formatTransactionAmount = function(value) {
                return window.formatNumber2(value, '-');
            };

            const blockedQtyKeys = new Set(['e', 'E', '+', '-']);

            function isDecimalQtyInput(element) {
                if (!(element instanceof HTMLInputElement)) {
                    return false;
                }

                if (element.type !== 'number' || element.disabled || element.readOnly) {
                    return false;
                }

                const alpineModel = element.getAttribute('x-model.number') || element.getAttribute('x-model') || '';
                return alpineModel.endsWith('.fqty');
            }

            function bindDecimalQtyInput(input) {
                if (!isDecimalQtyInput(input) || input.dataset.decimalQtyBound === '1') {
                    return;
                }

                input.dataset.decimalQtyBound = '1';
                input.setAttribute('step', '0.01');
                input.setAttribute('inputmode', 'decimal');

                input.addEventListener('keydown', (event) => {
                    if (blockedQtyKeys.has(event.key)) {
                        event.preventDefault();
                    }
                }, true);
            }

            function bindDecimalQtyInputs(root = document) {
                root.querySelectorAll('input[type="number"]').forEach(bindDecimalQtyInput);
            }

            document.addEventListener('DOMContentLoaded', () => {
                bindDecimalQtyInputs();

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (!(node instanceof HTMLElement)) {
                                return;
                            }

                            if (node.matches?.('input[type="number"]')) {
                                bindDecimalQtyInput(node);
                            }

                            bindDecimalQtyInputs(node);
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        })();
    </script>
    <script>
        (() => {
            function isDetailHeadingText(text) {
                return (text || '').toLowerCase().includes('detail item');
            }

            function findNearestDetailHeading(table) {
                let current = table;

                while (current && current !== document.body) {
                    let sibling = current.previousElementSibling;

                    while (sibling) {
                        if (/^H[1-6]$/.test(sibling.tagName) && isDetailHeadingText(sibling.textContent)) {
                            return sibling;
                        }

                        const nestedHeading = sibling.querySelector?.('h1, h2, h3, h4, h5, h6');
                        if (nestedHeading && isDetailHeadingText(nestedHeading.textContent)) {
                            return nestedHeading;
                        }

                        sibling = sibling.previousElementSibling;
                    }

                    current = current.parentElement;
                }

                return null;
            }

            function applyDetailTableStyles(root = document) {
                root.querySelectorAll('table').forEach((table) => {
                    if (table.dataset.skipAutoDetailStyle === 'true') {
                        return;
                    }

                    const headerCells = Array.from(table.querySelectorAll('thead th'));
                    const normalizedHeaders = headerCells.map((th) =>
                        th.textContent.replace(/\s+/g, ' ').trim().toLowerCase()
                    );

                    const productHeaderIndex = normalizedHeaders.findIndex((text) => text === 'nama produk');
                    const codeHeaderIndex = normalizedHeaders.findIndex((text) => text === 'kode produk');
                    const unitHeaderIndex = normalizedHeaders.findIndex((text) => text === 'satuan');
                    const actionHeaderIndex = normalizedHeaders.findIndex((text) => text === 'aksi');

                    const referenceHeaderIndexes = normalizedHeaders
                        .map((text, index) => ({
                            index,
                            text
                        }))
                        .filter((item) => item.text.includes('ref'))
                        .map((item) => item.index);

                    if (productHeaderIndex === -1) {
                        return;
                    }

                    const detailHeading = findNearestDetailHeading(table);
                    if (!detailHeading) {
                        return;
                    }

                    table.classList.add('transaction-detail-table');

                    const wrapper = table.closest('.overflow-auto, .overflow-x-auto, .overflow-scroll');
                    if (wrapper) {
                        wrapper.classList.add('transaction-detail-wrapper');
                    }

                    const rows = table.querySelectorAll('tr');
                    rows.forEach((row) => {
                        const cells = row.children;
                        if (cells[codeHeaderIndex]) {
                            cells[codeHeaderIndex].classList.add('transaction-code-column');
                        }

                        if (cells[productHeaderIndex]) {
                            cells[productHeaderIndex].classList.add('transaction-product-column');
                        }

                        if (cells[unitHeaderIndex]) {
                            cells[unitHeaderIndex].classList.add('transaction-unit-column');
                        }

                        referenceHeaderIndexes.forEach((index) => {
                            if (cells[index]) {
                                cells[index].classList.add('transaction-reference-column');
                            }
                        });

                        if (cells[actionHeaderIndex]) {
                            cells[actionHeaderIndex].classList.add('transaction-action-column');
                        }
                    });

                    table.querySelectorAll('th, td').forEach((cell) => {
                        const text = cell.textContent.replace(/\s+/g, ' ').trim();

                        if (!text) {
                            cell.removeAttribute('title');
                            return;
                        }

                        cell.setAttribute('title', text);
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                applyDetailTableStyles();

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (!(node instanceof HTMLElement)) {
                                return;
                            }

                            if (node.matches?.('table')) {
                                applyDetailTableStyles(node.parentElement || document);
                                return;
                            }

                            applyDetailTableStyles(node);
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        })();
    </script>
    <script>
        (() => {
            const canAccessAllBranches = @json($layoutCanAccessAllBranches);
            const branchOptions = @json($layoutBranchOptions);

            if (canAccessAllBranches && Array.isArray(branchOptions) && branchOptions.length > 0) {
                function buildBranchOptionLabel(branch) {
                    const code = String(branch?.code || '').trim();
                    const name = String(branch?.name || '').trim();

                    if (!code) {
                        return '';
                    }

                    return name ? `${code} - ${name}` : code;
                }

                function ensureBranchOption(select, value) {
                    const normalizedValue = String(value || '').trim();
                    if (!normalizedValue) {
                        return;
                    }

                    const exists = Array.from(select.options).some((option) => option.value === normalizedValue);
                    if (!exists) {
                        select.add(new Option(normalizedValue, normalizedValue, false, false));
                    }
                }

                function syncBranchSelect(hiddenInput, select) {
                    ensureBranchOption(select, hiddenInput.value);
                    select.value = String(hiddenInput.value || '').trim();
                }

                function enhanceTransactionBranchField(hiddenInput) {
                    if (!(hiddenInput instanceof HTMLInputElement) || hiddenInput.dataset.branchEnhanced === '1') {
                        return;
                    }

                    const fieldGroup = hiddenInput.closest('div');
                    if (!fieldGroup) {
                        return;
                    }

                    const label = fieldGroup.querySelector('label');
                    if (!label || !/cabang/i.test(label.textContent || '')) {
                        return;
                    }

                    const form = hiddenInput.closest('form[data-form-draft="true"]');
                    if (!form) {
                        return;
                    }

                    const displayInput = Array.from(fieldGroup.querySelectorAll('input[type="text"]')).find((input) => input !== hiddenInput && input.disabled);
                    if (!displayInput) {
                        return;
                    }

                    if (!hiddenInput.id) {
                        hiddenInput.id = `branch-hidden-${Math.random().toString(36).slice(2, 10)}`;
                    }

                    const select = document.createElement('select');
                    select.className = displayInput.className;
                    select.classList.remove('bg-gray-200', 'cursor-not-allowed');
                    select.classList.add('bg-white');
                    select.dataset.branchSelectFor = hiddenInput.id;

                    branchOptions.forEach((branch) => {
                        const code = String(branch?.code || '').trim();
                        if (!code) {
                            return;
                        }

                        select.add(new Option(buildBranchOptionLabel(branch), code, false, false));
                    });

                    syncBranchSelect(hiddenInput, select);

                    select.addEventListener('change', () => {
                        hiddenInput.value = select.value;
                    });

                    displayInput.replaceWith(select);
                    hiddenInput.dataset.branchEnhanced = '1';
                }

                function enhanceTransactionBranchFields(root = document) {
                    const scope = root instanceof HTMLElement || root instanceof Document ? root : document;
                    scope.querySelectorAll('form[data-form-draft="true"] input[type="hidden"][name="fbranchcode"]').forEach(enhanceTransactionBranchField);
                }

                function syncEnhancedBranchFields(root = document) {
                    const scope = root instanceof HTMLElement || root instanceof Document ? root : document;
                    scope.querySelectorAll('input[type="hidden"][name="fbranchcode"][data-branch-enhanced="1"]').forEach((hiddenInput) => {
                        const select = document.querySelector(`select[data-branch-select-for="${hiddenInput.id}"]`);
                        if (!select) {
                            return;
                        }

                        syncBranchSelect(hiddenInput, select);
                    });
                }

                document.addEventListener('DOMContentLoaded', () => {
                    enhanceTransactionBranchFields(document);
                    syncEnhancedBranchFields(document);
                });

                document.addEventListener('form-draft-restored', (event) => {
                    enhanceTransactionBranchFields(event.target instanceof HTMLElement ? event.target : document);
                    syncEnhancedBranchFields(event.target instanceof HTMLElement ? event.target : document);
                });
            }

            const shouldRestoreFormState = @json($errors->any() || session()->hasOldInput() || session()->has('error'));
            const formSelector = 'form:not([method="GET"]):not([data-disable-form-persist="true"])';

            const hasSuccessFlash = @json(session()->has('success'));
            const draftFormSelector = 'form[data-form-draft="true"]';
            const draftPrefix = 'transaction-form-draft:';
            const pendingSubmitKey = 'transaction-form-draft:pending-submit-keys';
            const draftTtlMs = 1000 * 60 * 60 * 24 * 7;

            function formStorageKey(form) {
                const customKey = form.dataset.draftKey;
                if (customKey) {
                    return `${draftPrefix}${customKey}`;
                }

                const action = form.getAttribute('action') || window.location.pathname;
                return `${draftPrefix}${window.location.pathname}:${action}`;
            }

            function readPendingKeys() {
                try {
                    const raw = sessionStorage.getItem(pendingSubmitKey);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            }

            function writePendingKeys(keys) {
                sessionStorage.setItem(pendingSubmitKey, JSON.stringify(Array.from(new Set(keys))));
            }

            function addPendingKey(key) {
                const keys = readPendingKeys();
                keys.push(key);
                writePendingKeys(keys);
            }

            function clearSubmittedDrafts() {
                const keys = readPendingKeys();
                keys.forEach((key) => localStorage.removeItem(key));
                sessionStorage.removeItem(pendingSubmitKey);
            }

            function normalizeDraftKey(key) {
                const rawKey = (key || '').toString().trim();
                if (!rawKey) {
                    return '';
                }

                return rawKey.startsWith(draftPrefix) ? rawKey : `${draftPrefix}${rawKey}`;
            }

            function clearDraftKeys(keys) {
                const normalizedKeys = keys
                    .map((key) => normalizeDraftKey(key))
                    .filter(Boolean);

                if (normalizedKeys.length === 0) {
                    return;
                }

                normalizedKeys.forEach((key) => localStorage.removeItem(key));

                const remainingPendingKeys = readPendingKeys().filter((key) => !normalizedKeys.includes(key));
                if (remainingPendingKeys.length > 0) {
                    writePendingKeys(remainingPendingKeys);
                } else {
                    sessionStorage.removeItem(pendingSubmitKey);
                }
            }

            function getAlpineFormState(form) {
                if (Array.isArray(form?._x_dataStack) && form._x_dataStack.length > 0) {
                    return form._x_dataStack[0];
                }

                if (form?.__x?.$data) {
                    return form.__x.$data;
                }

                return null;
            }

            function serializeForm(form) {
                const data = {};

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (!field.name || field.disabled || field.type === 'file' || field.name === '_token') {
                        return;
                    }

                    if (field.type === 'checkbox') {
                        if (!Array.isArray(data[field.name])) {
                            data[field.name] = [];
                        }

                        if (field.checked) {
                            data[field.name].push(field.value);
                        }
                        return;
                    }

                    if (field.type === 'radio') {
                        if (field.checked) {
                            data[field.name] = field.value;
                        }
                        return;
                    }

                    if (field.tagName === 'SELECT' && field.multiple) {
                        data[field.name] = Array.from(field.selectedOptions).map((option) => option.value);
                        return;
                    }

                    data[field.name] = field.value;
                });

                const customState = {};
                const alpineState = getAlpineFormState(form);
                if (alpineState?.savedItems && Array.isArray(alpineState.savedItems)) {
                    customState.savedItems = JSON.parse(JSON.stringify(alpineState.savedItems));
                }
                if (alpineState?.items && Array.isArray(alpineState.items)) {
                    customState.items = JSON.parse(JSON.stringify(alpineState.items));
                }
                if (alpineState?.draft && typeof alpineState.draft === 'object') {
                    customState.draft = JSON.parse(JSON.stringify(alpineState.draft));
                }
                if (alpineState?.editRow && typeof alpineState.editRow === 'object') {
                    customState.editRow = JSON.parse(JSON.stringify(alpineState.editRow));
                }
                if (typeof alpineState?.editingIndex !== 'undefined') {
                    customState.editingIndex = alpineState.editingIndex;
                }
                if (typeof alpineState?.showNoItems !== 'undefined') {
                    customState.showNoItems = alpineState.showNoItems;
                }

                const collectEvent = new CustomEvent('form-draft-collect', {
                    bubbles: true,
                    detail: {
                        customState
                    }
                });
                form.dispatchEvent(collectEvent);

                return {
                    values: data,
                    customState,
                    updatedAt: Date.now()
                };
            }

            function syncLinkedSelect(hiddenId, selectId) {
                const hidden = document.getElementById(hiddenId);
                const select = document.getElementById(selectId);
                if (!hidden || !select) {
                    return;
                }

                const value = (hidden.value || '').toString().trim();
                let found = false;

                Array.from(select.options).forEach((option) => {
                    const selected = String(option.value) === value;
                    option.selected = selected;
                    if (selected) {
                        found = true;
                    }
                });

                if (!found && value) {
                    select.add(new Option(value, value, true, true));
                }

                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            function syncCommonDisplayFields() {
                [
                    ['supplierCodeHidden', 'modal_filter_supplier_id'],
                    ['customerCodeHidden', 'modal_filter_customer_id'],
                    ['salesmanCodeHidden', 'modal_filter_salesman_id'],
                    ['accountCodeHidden', 'accountSelect'],
                    ['warehouseCodeHidden', 'warehouseSelect']
                ].forEach(([hiddenId, selectId]) => syncLinkedSelect(hiddenId, selectId));
            }

            function restoreField(field, value) {
                if (field.type === 'checkbox') {
                    const values = Array.isArray(value) ? value.map(String) : [String(value)];
                    field.checked = values.includes(String(field.value));
                    return;
                }

                if (field.type === 'radio') {
                    field.checked = String(value) === String(field.value);
                    return;
                }

                if (field.tagName === 'SELECT' && field.multiple) {
                    const values = Array.isArray(value) ? value.map(String) : [];
                    Array.from(field.options).forEach((option) => {
                        option.selected = values.includes(String(option.value));
                    });
                    return;
                }

                field.value = value ?? '';
            }

            function triggerFieldEvents(field) {
                field.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                field.dispatchEvent(new Event('change', {
                    bubbles: true
                }));

                if (window.jQuery) {
                    window.jQuery(field).trigger('change.select2');
                    window.jQuery(field).trigger('change');
                }
            }

            function restoreForm(form, savedDraft) {
                const data = savedDraft && typeof savedDraft === 'object' ? savedDraft.values : null;
                if (!data || typeof data !== 'object') {
                    return;
                }

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (!field.name || !(field.name in data) || field.type === 'file' || field.name === '_token') {
                        return;
                    }

                    restoreField(field, data[field.name]);
                    triggerFieldEvents(field);
                });

                syncCommonDisplayFields();

                const alpineState = getAlpineFormState(form);
                const customState = savedDraft?.customState && typeof savedDraft.customState === 'object'
                    ? savedDraft.customState
                    : {};
                const savedItems = customState.savedItems;
                if (Array.isArray(savedItems)) {
                    if (alpineState && typeof alpineState.restoreSavedItems === 'function') {
                        alpineState.restoreSavedItems(savedItems);
                    } else if (alpineState && Array.isArray(alpineState.savedItems)) {
                        alpineState.savedItems = JSON.parse(JSON.stringify(savedItems));
                        if (typeof alpineState.recalcTotals === 'function') {
                            alpineState.recalcTotals();
                        }
                    }
                }

                const restoredItems = customState.items;
                if (Array.isArray(restoredItems) && alpineState) {
                    if (typeof alpineState.restoreItems === 'function') {
                        alpineState.restoreItems(restoredItems);
                    } else if (Array.isArray(alpineState.items)) {
                        alpineState.items = JSON.parse(JSON.stringify(restoredItems));
                        if (typeof alpineState.recalcTotals === 'function') {
                            alpineState.recalcTotals();
                        }
                    }
                }

                if (customState.draft && alpineState) {
                    if (typeof alpineState.restoreDraft === 'function') {
                        alpineState.restoreDraft(customState.draft);
                    } else if (typeof alpineState.restoreDraftState === 'function') {
                        alpineState.restoreDraftState(customState);
                    } else if (alpineState.draft && typeof alpineState.draft === 'object') {
                        alpineState.draft = JSON.parse(JSON.stringify(customState.draft));
                    }
                }

                if (customState.editRow && alpineState) {
                    if (typeof alpineState.restoreEditRow === 'function') {
                        alpineState.restoreEditRow(customState.editRow);
                    } else if (alpineState.editRow && typeof alpineState.editRow === 'object') {
                        alpineState.editRow = JSON.parse(JSON.stringify(customState.editRow));
                    }
                }

                if (Object.prototype.hasOwnProperty.call(customState, 'editingIndex') && alpineState &&
                    Object.prototype.hasOwnProperty.call(alpineState, 'editingIndex')) {
                    alpineState.editingIndex = customState.editingIndex;
                }

                if (Object.prototype.hasOwnProperty.call(customState, 'showNoItems') && alpineState &&
                    Object.prototype.hasOwnProperty.call(alpineState, 'showNoItems')) {
                    alpineState.showNoItems = customState.showNoItems;
                }

                if (alpineState && typeof alpineState.recalcTotals === 'function') {
                    alpineState.recalcTotals();
                }

                form.dispatchEvent(new CustomEvent('form-draft-restored', {
                    bubbles: true,
                    detail: {
                        draft: savedDraft,
                        values: data
                    }
                }));
            }

            function isDraftExpired(savedDraft) {
                if (!savedDraft || typeof savedDraft !== 'object') {
                    return true;
                }

                if (!savedDraft.updatedAt) {
                    return false;
                }

                return (Date.now() - savedDraft.updatedAt) > draftTtlMs;
            }

            document.addEventListener('DOMContentLoaded', () => {
                if (hasSuccessFlash) {
                    clearSubmittedDrafts();
                }

                const clearOnLoadKeys = (document.body?.dataset?.clearFormDrafts || '')
                    .split(',')
                    .map((key) => key.trim())
                    .filter(Boolean);

                if (clearOnLoadKeys.length > 0) {
                    clearDraftKeys(clearOnLoadKeys);
                }

                document.querySelectorAll(draftFormSelector).forEach((form) => {
                    const storageKey = formStorageKey(form);
                    let saveTimer = null;

                    try {
                        const saved = localStorage.getItem(storageKey);
                        if (saved) {
                            const parsed = JSON.parse(saved);
                            if (isDraftExpired(parsed)) {
                                localStorage.removeItem(storageKey);
                            } else {
                                restoreForm(form, parsed);
                            }
                        }
                    } catch (error) {
                        localStorage.removeItem(storageKey);
                    }

                    const persist = () => {
                        window.clearTimeout(saveTimer);
                        saveTimer = window.setTimeout(() => {
                            localStorage.setItem(storageKey, JSON.stringify(serializeForm(form)));
                        }, 150);
                    };

                    form.addEventListener('input', persist);
                    form.addEventListener('change', persist);
                    form.addEventListener('submit', () => {
                        form.dataset.formDraftSubmitted = '1';
                        localStorage.setItem(storageKey, JSON.stringify(serializeForm(form)));
                        addPendingKey(storageKey);
                    });
                });

                window.addEventListener('pagehide', () => {
                    const keysToClear = Array.from(document.querySelectorAll(draftFormSelector))
                        .filter((form) => form.dataset.formDraftSubmitted !== '1')
                        .map((form) => formStorageKey(form));

                    if (keysToClear.length > 0) {
                        clearDraftKeys(keysToClear);
                    }
                });
            });
        })();
    </script>
</body>

</html>
