{{-- Hapus x-data, :class, dan styling lain dari sini karena sudah dihandle oleh parent (app.blade.php) --}}
<div>
    <!-- Header -->
    <div class="p-4 border-b border-white/10 flex items-center justify-between">
        <!-- Brand / Title -->
        <h2 class="text-xl font-semibold truncate" x-show="openSidebar" x-transition.opacity.duration.200>Laravel</h2>
        <!-- Titik-tiga button -->
        <button @click="openSidebar = !openSidebar"
            class="ml-auto inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-white/10 focus:outline-none group"
            :aria-label="openSidebar ? 'Tutup sidebar' : 'Buka sidebar'">
            <!-- tiga titik -->
            <span class="relative flex items-center justify-between w-5">
                <span class="h-1 w-1 rounded-full bg-white transition-transform duration-300"
                    :class="openSidebar ? 'translate-x-0' : '-translate-x-1'"></span>
                <span class="h-1 w-1 rounded-full bg-white transition-opacity duration-300"
                    :class="openSidebar ? 'opacity-100' : 'opacity-60'"></span>
                <span class="h-1 w-1 rounded-full bg-white transition-transform duration-300"
                    :class="openSidebar ? 'translate-x-0' : 'translate-x-1'"></span>
            </span>
        </button>
    </div>

    <!-- Nav -->
    <nav class="p-3"
        @click.capture="
    const el = $event.target.closest('a,button');
    if (!el) return;

    // 1) Kalau sidebar sedang kecil, klik pertama hanya untuk buka sidebar
    if (!openSidebar) {
      openSidebar = true;
      if (window.innerWidth > 1024) {
        localStorage.setItem('desktopSidebarOpen', true);
      }
      // stop agar tidak langsung navigate / toggle accordion
      $event.preventDefault();
      $event.stopPropagation();
      return;
    }

    // 2) Sidebar sudah terbuka:
    //    - kalau yang diklik adalah <a> (link menu), boleh pakai auto-shrink setelah pindah halaman
    if (el.tagName === 'A') {
      sessionStorage.setItem('collapseSidebarOnce', '1');       // kecilkan sekali setelah load
      if (window.innerWidth > 1024) {
        localStorage.setItem('desktopSidebarOpen', false);      // persist di desktop
      }
    }
  ">
        <ul class="space-y-1">

            <!-- Dashboard -->
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                    <x-heroicon-o-home class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3" x-show="openSidebar" x-transition.opacity.duration.150>Dashboard</span>
                </a>
            </li>

            <!-- Master Accounting -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar" x-transition.opacity.duration.150>
                        Master Accounting
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3"
                    x-cloak>

                    @if (in_array('viewAccount', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('account.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">Account</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSubAccount', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('subaccount.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-document-duplicate class="w-5 h-5" />
                                <span class="ml-3">Sub Account</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewRekening', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('rekening.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">Rekening</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </li>

            <!-- Master Barang -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar" x-transition.opacity.duration.150>
                        Master Barang
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3"
                    x-cloak>

                    @if (in_array('viewGroupProduct', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('product.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-cube class="w-5 h-5" />
                                <span class="ml-3">Product</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewGroupProduct', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('groupproduct.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                <span class="ml-3">Group Produk</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewMerek', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('merek.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-tag class="w-5 h-5" />
                                <span class="ml-3">Merek</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSatuan', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('satuan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-scale class="w-5 h-5" />
                                <span class="ml-3">Satuan</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </li>

            <!-- Master Penjualan -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar" x-transition.opacity.duration.150>
                        Master Penjualan
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3"
                    x-cloak>

                    @if (in_array('viewGroupCustomer', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('groupcustomer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                <span class="ml-3">Group Customer</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewCustomer', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('customer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-group class="w-5 h-5" />
                                <span class="ml-3">Customer</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewWilayah', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('wilayah.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-globe-alt class="w-5 h-5" />
                                <span class="ml-3">Wilayah</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSalesman', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('salesman.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-briefcase class="w-5 h-5" />
                                <span class="ml-3">Salesman</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </li>

            <!-- Master Pembelian -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar" x-transition.opacity.duration.150>
                        Master Pembelian
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if (in_array('viewGudang', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('gudang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-archive-box class="w-5 h-5" />
                                <span class="ml-3">Gudang</span>
                            </a>
                        </li>
                    @endif

                    @if (in_array('viewSupplier', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('supplier.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-truck class="w-5 h-5" />
                                <span class="ml-3">Supplier</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </li>

            <!-- Transaksi Pembelian (accordion) -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar" x-transition.opacity.duration.150>
                        Transaksi Pembelian
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if (in_array('viewTr_prh', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('tr_prh.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">Permintaan Pembelian</span>
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('tr_poh.index') }}"
                            class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                            <x-heroicon-o-banknotes class="w-5 h-5" />
                            <span class="ml-3">Order Pembelian</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('penerimaanbarang.index') }}"
                            class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                            <x-heroicon-o-banknotes class="w-5 h-5" />
                            <span class="ml-3">Penerimaan Barang</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('fakturpembelian.index') }}"
                            class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                            <x-heroicon-o-banknotes class="w-5 h-5" />
                            <span class="ml-3">Faktur Pembelian</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Utility -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>Utility</span>
                    <!-- caret -->
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Submenu -->
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if (in_array('viewSysuser', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('sysuser.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Wewenang User</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            {{-- Reporting --}}
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>Reporting</span>
                    <!-- caret -->
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Reporting -->
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if (in_array('viewSysuser', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('reporting.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Reporting</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            <!-- Mutasi-->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>Stock</span>
                    <!-- caret -->
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Submenu -->
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if (in_array('viewSysuser', explode(',', session('user_restricted_permissions', ''))))
                        <li>
                            <a href="{{ route('adjstock.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">Adjustment Stock</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('mutasi.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Mutasi Stok</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('pemakaianbarang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Pemakaian Barang</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('returpembelian.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Retur Pembelian</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('assembling.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Assembling</span>
                            </a>
                        </li>
                    @endif
                </ul>

            </li>

        </ul>
    </nav>
</div>
