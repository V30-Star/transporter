<div class="w-64 bg-white shadow-md">
    <div class="p-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">{{ config('app.name') }}</h2>
    </div>

    <nav class="p-4">
        <ul class="space-y-2">

            <!-- Dashboard -->
            <li>
                <a href="{{ route('dashboard') }}"
                    class="flex items-center p-2 text-gray-600 rounded-lg hover:bg-gray-100">
                    <x-heroicon-o-home class="w-5 h-5" />
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>

            <!-- Master Menu -->
            <li x-data="{ open: false }">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 text-gray-600 rounded-lg hover:bg-gray-100 focus:outline-none">
                    <x-heroicon-o-collection class="w-5 h-5" />
                    <span class="ml-3 flex-1 text-left">Master</span>
                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open" x-transition class="ml-6 mt-2 space-y-1" x-cloak>
                    <!-- Customer -->
                    <li>
                        <a href="{{ route('customer.index') }}"
                            class="flex items-center p-2 text-gray-600 rounded hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A10 10 0 0112 2a10 10 0 016.879 15.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="ml-3">Customer</span>
                        </a>
                    </li>

                    <!-- Group Customer -->
                    <li>
                        <a href="{{ route('groupcustomer.index') }}"
                            class="flex items-center p-2 text-gray-600 rounded hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-5-3.87M12 12a4 4 0 100-8 4 4 0 000 8zm6 8v-1a4 4 0 00-3-3.87m-6 0A4 4 0 006 19v1" />
                            </svg>
                            <span class="ml-3">Group Customer</span>
                        </a>
                    </li>

                    <!-- Wilayah -->
                    <li>
                        <a href="{{ route('wilayah.index') }}"
                            class="flex items-center p-2 text-gray-600 rounded hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4s-4 1.79-4 4 1.79 4 4 4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z" />
                            </svg>
                            <span class="ml-3">Wilayah</span>
                        </a>
                    </li>

                    <!-- Rute -->
                    <li>
                        <a href="{{ route('rute.index') }}"
                            class="flex items-center p-2 text-gray-600 rounded hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 11c0-2.5 2-4.5 4.5-4.5S21 8.5 21 11s-2 4.5-4.5 4.5S12 13.5 12 11zm0 0v10m-6-5.5c-1.5 0-2.75-1.25-2.75-2.75S4.5 10 6 10s2.75 1.25 2.75 2.75S7.5 15.5 6 15.5z" />
                            </svg>
                            <span class="ml-3">Rute</span>
                        </a>
                    </li>
                </ul>

                <!-- Profile -->
            <li>
                <a href="{{ route('profile') }}"
                    class="flex items-center p-2 text-gray-600 rounded-lg hover:bg-gray-100">
                    <x-heroicon-o-user class="w-5 h-5" />
                    <span class="ml-3">Profile</span>
                </a>
            </li>

            <!-- Settings -->
            <li>
                <a href="{{ route('settings') }}"
                    class="flex items-center p-2 text-gray-600 rounded-lg hover:bg-gray-100">
                    <x-heroicon-o-cog class="w-5 h-5" />
                    <span class="ml-3">Settings</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
