<div class="w-64 bg-black text-white shadow-md">
    <div class="p-4 border-b">
        <h2 class="text-xl font-semibold">Laravel</h2>
    </div>

    <nav class="p-4">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                    <x-heroicon-o-home class="w-5 h-5" />
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>

            <li x-data="{ open: false }">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 text-white rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5" />
                    <span class="ml-3 flex-1 text-left">Master</span>
                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul x-show="open" x-transition class="ml-6 mt-2 space-y-1" x-cloak>
                    <!-- Customer -->
                    @unless (in_array('viewCustomer', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('customer.index') }}"
                                class="flex items-center p-2 text-white rounded hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5.121 17.804A10 10 0 0112 2a10 10 0 016.879 15.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="ml-3">Customer</span>
                            </a>
                        </li>
                    @endunless

                    <!-- Group Customer -->
                    @unless (in_array('viewGroupCustomer', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('groupcustomer.index') }}"
                                class="flex items-center p-2 text-white rounded hover:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a4 4 0 00-5-3.87M9 20h6M3 20h5v-2a4 4 0 00-5-3.87M12 12a4 4 0 100-8 4 4 0 000 8zm6 8v-1a4 4 0 00-3-3.87m-6 0A4 4 0 006 19v1" />
                                </svg>
                                <span class="ml-3">Group Customer</span>
                            </a>
                        </li>
                    @endunless

                    <!-- Wilayah -->
                    @unless (in_array('viewWilayah', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('wilayah.index') }}"
                                class="flex items-center p-2 text-white rounded hover:bg-gray-700">
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
                    @endunless

                    @unless (in_array('viewSysuser', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('sysuser.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Sys User</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewSalesman', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('salesman.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Salesman</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewSatuan', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('satuan.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Satuan</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewMerek', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('merek.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Merek</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewGudang', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('gudang.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Gudang</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewGroupProduct', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('groupproduct.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Group Product</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewGroupProduct', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('product.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Product</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewSupplier', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('supplier.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Supplier</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewRekening', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('rekening.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Rekening</span>
                            </a>
                        </li>
                    @endunless

                    @unless (in_array('viewSubAccount', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('subaccount.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user class="w-5 h-5" />
                                <span class="ml-3">Sub Account</span>
                            </a>
                        </li>
                    @endunless

                    <li>
                        <a href="{{ route('account.index') }}"
                            class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                            <x-heroicon-o-user class="w-5 h-5" />
                            <span class="ml-3">Account</span>
                        </a>
                    </li>

                </ul>
            </li>

            {{-- <li>
                <a href="{{ route('settings') }}"
                    class="flex items-center p-2 text-gray-600 rounded-lg hover:bg-gray-100">
                    <x-heroicon-o-cog class="w-5 h-5" />
                    <span class="ml-3">Settings</span>
                </a>
            </li> --}}

        </ul>
    </nav>
</div>
