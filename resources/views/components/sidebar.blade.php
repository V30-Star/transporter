{{-- Hapus x-data, :class, dan styling lain dari sini karena sudah dihandle oleh parent (app.blade.php) --}}
@php
    $sidebarPermissions = array_filter(
        array_map('trim', explode(',', (string) session('user_restricted_permissions', ''))),
    );
    $hasSidebarPermission = function (...$requiredPermissions) use ($sidebarPermissions) {
        foreach ($requiredPermissions as $permission) {
            if (is_array($permission)) {
                foreach ($permission as $nestedPermission) {
                    if (in_array($nestedPermission, $sidebarPermissions, true)) {
                        return true;
                    }
                }
                continue;
            }

            if (in_array($permission, $sidebarPermissions, true)) {
                return true;
            }
        }

        return false;
    };
@endphp
<div>
    <!-- Header -->
    <div class="p-4 border-b border-white/10 flex items-center justify-between">
        <!-- Brand / Title -->
        <h2 class="text-xl font-semibold truncate" x-show="openSidebar" x-transition.opacity.duration.200>
            {{ 'Laravel' }}</h2>
        <!-- Titik-tiga button -->
        <button @click="openSidebar = !openSidebar"
            class="ml-auto inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-white/10 focus:outline-none group"
            :aria-label="openSidebar ? @js('Tutup sidebar') : @js('Buka sidebar')">
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
                    <span class="ml-3" x-show="openSidebar"
                        x-transition.opacity.duration.150>{{ 'Dashboard' }}</span>
                </a>
            </li>

            <!-- Master Accounting -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar" x-transition.opacity.duration.150>
                        {{ 'Master Accounting' }}
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3"
                    x-cloak>

                    @if ($hasSidebarPermission('viewAccount'))
                        <li>
                            <a href="{{ route('account.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Account' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewSubAccount'))
                        <li>
                            <a href="{{ route('subaccount.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-document-duplicate class="w-5 h-5" />
                                <span class="ml-3">{{ 'Sub Account' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewRekening'))
                        <li>
                            <a href="{{ route('rekening.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Rekening' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewCurrency', 'createCurrency', 'updateCurrency', 'deleteCurrency'))
                        <li>
                            <a href="{{ route('currency.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Currency' }}</span>
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
                        {{ 'Master Barang' }}
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3"
                    x-cloak>

                    @if ($hasSidebarPermission('viewProduct', 'createProduct', 'updateProduct', 'deleteProduct'))
                        <li>
                            <a href="{{ route('product.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-cube class="w-5 h-5" />
                                <span class="ml-3">{{ 'Produk' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewGroupProduct'))
                        <li>
                            <a href="{{ route('groupproduct.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                <span class="ml-3">{{ 'Group Produk' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewMerek'))
                        <li>
                            <a href="{{ route('merek.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-tag class="w-5 h-5" />
                                <span class="ml-3">{{ 'Merek' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewSatuan'))
                        <li>
                            <a href="{{ route('satuan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-scale class="w-5 h-5" />
                                <span class="ml-3">{{ 'Satuan' }}</span>
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
                        {{ 'Master Penjualan' }}
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3"
                    x-cloak>

                    @if ($hasSidebarPermission('viewGroupCustomer'))
                        <li>
                            <a href="{{ route('groupcustomer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                <span class="ml-3">{{ 'Group Customer' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewCustomer'))
                        <li>
                            <a href="{{ route('customer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-group class="w-5 h-5" />
                                <span class="ml-3">{{ 'Customer' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewWilayah'))
                        <li>
                            <a href="{{ route('wilayah.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-globe-alt class="w-5 h-5" />
                                <span class="ml-3">{{ 'Wilayah' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewSalesman'))
                        <li>
                            <a href="{{ route('salesman.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-briefcase class="w-5 h-5" />
                                <span class="ml-3">{{ 'Salesman' }}</span>
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
                        {{ 'Master Pembelian' }}
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if ($hasSidebarPermission('viewGudang'))
                        <li>
                            <a href="{{ route('gudang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-archive-box class="w-5 h-5" />
                                <span class="ml-3">{{ 'Gudang' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewSupplier'))
                        <li>
                            <a href="{{ route('supplier.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-truck class="w-5 h-5" />
                                <span class="ml-3">{{ 'Supplier' }}</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </li>

            <!-- Transaksi Accounting -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>{{ 'Transaksi Accounting' }}</span>
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

                    @if ($hasSidebarPermission('createjurnaltransaksi', 'updatejurnaltransaksi', 'deletejurnaltransaksi'))
                        <li>
                            <a href="{{ route('jurnaltransaksi.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Jurnal Transaksi' }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('listingjurnal.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Jurnal' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            {{-- Transaksi Penjualan --}}
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>{{ 'Transaksi Penjualan' }}</span>
                    <!-- caret -->
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Sales Order -->
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if ($hasSidebarPermission('viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh'))
                        <li>
                            <a href="{{ route('salesorder.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Sales Order' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Surat Jalan --}}
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if ($hasSidebarPermission('createSuratJalan', 'updateSuratJalan', 'deleteSuratJalan'))
                        <li>
                            <a href="{{ route('suratjalan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Surat Jalan' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Faktur Penjualan --}}
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if ($hasSidebarPermission('createInvoice', 'updateInvoice', 'deleteInvoice'))
                        <li>
                            <a href="{{ route('invoice.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Faktur Penjualan' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Retur Penjualan --}}
                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>

                    @if ($hasSidebarPermission('createReturPenjualan', 'updateReturPenjualan', 'deleteReturPenjualan'))
                        <li>
                            <a href="{{ route('returpenjualan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Retur Penjualan' }}</span>
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
                        {{ 'Transaksi Pembelian' }}
                    </span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('viewTr_prh', 'createTr_prh', 'updateTr_prh', 'deleteTr_prh'))
                        <li>
                            <a href="{{ route('tr_prh.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Permintaan Pembelian' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh'))
                        <li>
                            <a href="{{ route('tr_poh.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Order Pembelian' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang'))
                        <li>
                            <a href="{{ route('penerimaanbarang.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Penerimaan Barang' }}</span>
                            </a>
                        </li>
                    @endif
                    @if (
                        $hasSidebarPermission(
                            'createFakturPembelian',
                            'updateFakturPembelian',
                            'deleteFakturPembelian',
                            'printFakturPembelian'))
                        <li>
                            <a href="{{ route('fakturpembelian.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Faktur Pembelian' }}</span>
                            </a>
                        </li>
                    @endif
                    @if (
                        $hasSidebarPermission(
                            'createReturPembelian',
                            'updateReturPembelian',
                            'deleteReturPembelian',
                            'printReturPembelian'))
                        <li>
                            <a href="{{ route('returpembelian.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Retur Pembelian' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            <!-- Transaksi Stock-->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150">{{ 'Transaksi Stock' }}</span>
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

                    @if ($hasSidebarPermission('createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang'))
                        <li>
                            <a href="{{ route('adjstock.index') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Adjustment Stock' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang'))
                        <li>
                            <a href="{{ route('mutasi.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Mutasi' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('createPemakaianbarang', 'updatePemakaianBarang', 'deletePemakaianBarang'))
                        <li>
                            <a href="{{ route('pemakaianbarang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Pemakaian Barang' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('createAssembling', 'updateAssembling', 'deleteAssembling'))
                        <li>
                            <a href="{{ route('assembling.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Assembling' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            {{-- Laporan Pengeluaran --}}
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150">{{ 'Transaksi Kas/Bank' }}</span>
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('createPengeluaranKas', 'updatePengeluaranKas', 'deletePengeluaranKas'))
                        <li>
                            <a href="{{ route('pengeluarankas.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Pengeluaran Kas/Bank' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('createPenerimaanKas', 'updatePenerimaanKas', 'deletePenerimaanKas'))
                        <li>
                            <a href="{{ route('penerimaankas.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Penerimaan Kas/Bank' }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('pelunasancustomer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-receipt-percent class="w-5 h-5" />
                                <span class="ml-3">{{ 'Pelunasan Customer' }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('bayarsupplier.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-receipt-percent class="w-5 h-5" />
                                <span class="ml-3">{{ 'Bayar Supplier' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            <!-- Laporan Master -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150">{{ 'Laporan Master' }}</span>
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

                    @if ($hasSidebarPermission('viewAccount'))
                        <li>
                            <a href="{{ route('reportingaccount.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Chart of Account' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewSubAccount'))
                        <li>
                            <a href="{{ route('reportingsubaccount.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Sub Account' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewCustomer'))
                        <li>
                            <a href="{{ route('reportingcustomer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Customer' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewSupplier'))
                        <li>
                            <a href="{{ route('reportingsupplier.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Supplier' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewProduct'))
                        <li>
                            <a href="{{ route('reportingproduct.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-credit-card class="w-5 h-5" />
                                <span class="ml-3">{{ 'Produk' }}</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </li>

            {{-- Reporting Penjualan --}}
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150">{{ 'Laporan Penjualan' }}</span>
                    <!-- caret -->
                    <svg x-show="openSidebar" :class="{ 'rotate-180': open }"
                        class="w-4 h-4 transition-transform ml-auto" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh'))
                        <li>
                            <a href="{{ route('listingso.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Sales Order (SO)' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh'))
                        <li>
                            <a href="{{ route('listingsobelum.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'SO Yang Belum Terkirim' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('createInvoice', 'updateInvoice', 'deleteInvoice'))
                        <li>
                            <a href="{{ route('listingpenjualan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Penjualan' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewlistingreturpenjualan'))
                        <li>
                            <a href="{{ route('listingreturpenjualan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Retur Penjualan' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>

                <!-- <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('viewTr_prh', 'createTr_prh', 'updateTr_prh', 'deleteTr_prh'))
<li>
                            <a href="{{ route('listingpr.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Listing Permintaan Pembelian (PR)</span>
                            </a>
                        </li>
@endif
                </ul>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('viewTr_prh', 'createTr_prh', 'updateTr_prh', 'deleteTr_prh'))
<li>
                            <a href="{{ route('listingpo.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Listing Order Pembelian (PO)</span>
                            </a>
                        </li>
@endif
                </ul>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh'))
<li>
                            <a href="{{ route('listingpenerimaanbarang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Listing Penerimaan Barang</span>
                            </a>
                        </li>
@endif
                </ul>

                <ul x-show="open && openSidebar" x-transition
                    class="ml-9 mt-1 space-y-1 border-l border-white/10 pl-3" x-cloak>
                    @if ($hasSidebarPermission('createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang'))
<li>
                            <a href="{{ route('listingfakturpembelian.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">Listing Faktur Pembelian</span>
                            </a>
                        </li>
@endif
                </ul> -->
            </li>

            {{-- Reporting Pembelian --}}
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150">{{ 'Laporan Pembelian' }}</span>
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
                    @if ($hasSidebarPermission('viewTr_prh', 'createTr_prh', 'updateTr_prh', 'deleteTr_prh'))
                        <li>
                            <a href="{{ route('listingpr.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Permintaan Pembelian (PR)' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('viewTr_poh', 'createTr_poh', 'updateTr_poh', 'deleteTr_poh'))
                        <li>
                            <a href="{{ route('listingpo.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Order Pembelian (PO)' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang'))
                        <li>
                            <a href="{{ route('listingpenerimaanbarang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Penerimaan Barang' }}</span>
                            </a>
                        </li>
                    @endif

                    @if (
                        $hasSidebarPermission(
                            'createFakturPembelian',
                            'updateFakturPembelian',
                            'deleteFakturPembelian',
                            'printFakturPembelian'))
                        <li>
                            <a href="{{ route('listingfakturpembelian.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                    <span class="ml-3">{{ 'Listing Faktur Pembelian' }}</span>
                                </a>
                            </li>
                        @endif

                        @if (
                            $hasSidebarPermission(
                                'viewlistingreturpembelian'))
                            <li>
                                <a href="{{ route('listingreturpembelian.index') }}"
                                    class="flex items-center p-2 rounded hover:bg-gray-700">
                                    <x-heroicon-o-user-circle class="w-5 h-5" />
                                    <span class="ml-3">{{ 'Listing Retur Pembelian' }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>

            {{-- Reporting Stock --}}
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150">{{ 'Laporan Stock' }}</span>
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
                    @if ($hasSidebarPermission('createPenerimaanBarang', 'updatePenerimaanBarang', 'deletePenerimaanBarang'))
                        <li>
                            <a href="{{ route('reportingadjstock.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Adjustment Stock' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('createPemakaianbarang', 'updatePemakaianBarang', 'deletePemakaianBarang'))
                        <li>
                            <a href="{{ route('reportingpemakaianbarang.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Pemakaian Barang' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('createAssembling', 'updateAssembling', 'deleteAssembling'))
                        <li>
                            <a href="{{ route('reportingassembling.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Listing Assembling' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('createPengeluaranKas', 'updatePengeluaranKas', 'deletePengeluaranKas'))
                        <li>
                            <a href="{{ route('reportingkas.pengeluaran.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Laporan Pengeluaran Kas/Bank/Bank' }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($hasSidebarPermission('createPenerimaanKas', 'updatePenerimaanKas', 'deletePenerimaanKas'))
                        <li>
                            <a href="{{ route('reportingkas.penerimaan.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Laporan Penerimaan Kas/Bank' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            <!-- Laporan Kas/Bank -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>{{ 'Laporan Kas/Bank' }}</span>
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

                    @if ($hasSidebarPermission('viewreportingpelunasancustomer'))
                        <li>
                            <a href="{{ route('reportingpelunasancustomer.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Laporan Pelunasan Customer' }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($hasSidebarPermission('viewreportingpelunasansupplier'))
                        <li>
                            <a href="{{ route('reportingpelunasansupplier.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-banknotes class="w-5 h-5" />
                                <span class="ml-3">{{ 'Laporan Bayar Supplier' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
            <!-- Utility -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>{{ 'Utility' }}</span>
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

                    @if ($hasSidebarPermission('viewSysuser', 'createSysuser', 'updateSysuser', 'deleteSysuser', 'roleaccess'))
                        <li>
                            <a href="{{ route('sysuser.index') }}"
                                class="flex items-center p-2 rounded hover:bg-gray-700">
                                <x-heroicon-o-user-circle class="w-5 h-5" />
                                <span class="ml-3">{{ 'Wewenang User' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            <!-- Posting -->
            <li x-data="{ open: false }" x-effect="if(!openSidebar) open = false">
                <button @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg hover:bg-gray-700 focus:outline-none">
                    <x-heroicon-o-folder class="w-5 h-5 flex-shrink-0" />
                    <span class="ml-3 flex-1 text-left" x-show="openSidebar"
                        x-transition.opacity.duration.150>{{ 'Posting' }}</span>
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
                    @if (
                        $hasSidebarPermission(
                            'viewEditperiode',
                            'createEditperiode',
                            'updateEditperiode',
                            'deleteEditperiode',
                            'roleaccess'))
                        <li>
                            <a href="{{ route('editperiode.edit') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-calendar-days class="w-5 h-5 flex-shrink-0" />
                                <span class="ml-3" x-show="openSidebar"
                                    x-transition.opacity.duration.150>{{ 'Proses Posting' }}</span>
                            </a>
                        </li>
                    @endif
                    @if (
                        $hasSidebarPermission(
                            'viewEditperiode',
                            'createEditperiode',
                            'updateEditperiode',
                            'deleteEditperiode',
                            'roleaccess'))
                        <li>
                            <a href="{{ route('editperiode.edit') }}"
                                class="flex items-center p-2 rounded-lg hover:bg-gray-700">
                                <x-heroicon-o-calendar-days class="w-5 h-5 flex-shrink-0" />
                                <span class="ml-3" x-show="openSidebar"
                                    x-transition.opacity.duration.150>{{ 'Edit Periode' }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        </ul>
    </nav>
</div>
