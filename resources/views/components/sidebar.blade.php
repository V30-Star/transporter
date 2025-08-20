<div class="w-64 flex-shrink-0 bg-black text-white shadow-md overflow-y-auto">
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
                    @if (in_array('viewCustomer', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('customer.index') }}"
                                class="flex items-center p-2 text-white rounded hover:bg-gray-700">
                                <x-heroicon-o-user-group class="w-5 h-5" />
                                <span class="ml-3">Customer</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewGroupCustomer', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('groupcustomer.index') }}"
                                class="flex items-center p-2 text-white rounded hover:bg-gray-700">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                <span class="ml-3">Group Customer</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewWilayah', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('wilayah.index') }}"
                                class="flex items-center p-2 text-white rounded hover:bg-gray-700">
                                <x-heroicon-o-globe-alt class="w-5 h-5" />
                                <span class="ml-3">Wilayah</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSysuser', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('sysuser.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Wewenang User</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSalesman', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('salesman.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-briefcase class="w-5 h-5" />
                                <span class="ml-3">Salesman</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSatuan', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('satuan.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-scale class="w-5 h-5" />
                                <span class="ml-3">Satuan</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewMerek', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('merek.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-tag class="w-5 h-5" />
                                <span class="ml-3">Merek</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewGudang', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('gudang.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-archive-box class="w-5 h-5" />
                                <span class="ml-3">Gudang</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewGroupProduct', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('groupproduct.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <!-- ganti dari collection ke squares-2x2 -->
                                <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                <span class="ml-3">Group Produk</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewGroupProduct', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('product.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-cube class="w-5 h-5" />
                                <span class="ml-3">Product</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSupplier', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('supplier.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-truck class="w-5 h-5" />
                                <span class="ml-3">Supplier</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewRekening', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('rekening.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">Rekening</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSubAccount', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('subaccount.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-document-duplicate class="w-5 h-5" />
                                <span class="ml-3">Sub Account</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewAccount', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('account.index') }}"
                                class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">Account</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        </ul>
    </nav>
</div>
