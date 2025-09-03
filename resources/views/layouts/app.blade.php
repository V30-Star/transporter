<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>

    <style>
        [x-cloak] {
            display: none !important
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.full.min.js"></script>
    <style>
        body {
            font-family: 'Lato', sans-serif;
        }
    </style>

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
    
    // Jika halaman ini datang dari klik menu, kecilkan sekali (persist satu kali)
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


        <div :class="openSidebar ? 'w-64' : 'w-16'"
            class="flex-shrink-0 bg-black text-white shadow-md overflow-y-auto transition-all duration-300">
            <x-sidebar />
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0 overflow-auto flex flex-col">

            <!-- Header -->
            <header class="bg-white shadow-sm p-4 flex justify-between items-center">
                <!-- Kiri: Judul -->
                <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>

                <!-- Kanan: Dropdown User -->
                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}"
                                class="w-8 h-8 rounded-full" alt="avatar">
                            <span class="text-gray-700 hidden sm:inline">{{ Auth::user()->name }}</span>
                        </button>

                        <div x-show="open" @click.outside="open = false"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20" x-cloak>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </header>
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Page Content -->
            <main class="p-6 flex-1">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>

</html>
