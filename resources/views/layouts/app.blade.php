    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Dashboard')</title>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Add this -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
        <div class="flex h-screen">

            <!-- Sidebar -->
            <x-sidebar />

            <!-- Main Content -->
            <div class="flex-1 overflow-auto flex flex-col">

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

                            <div x-show="open" @click.away="open = false"
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

            @stack('scripts')
    </body>

    </html>
