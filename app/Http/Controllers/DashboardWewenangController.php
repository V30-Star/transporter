<?php

namespace App\Http\Controllers;

use App\Models\RoleAccess;
use App\Models\Sysuser;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class DashboardWewenangController extends Controller
{
    public static function getMenuDefinitions(): array
    {
        return [
            // Master Data
            'Wilayah' => [
                'group' => 'Master Data',
                'view' => ['viewWilayah'],
                'create' => ['createWilayah'],
                'update' => ['updateWilayah'],
                'delete' => ['deleteWilayah'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Customer' => [
                'group' => 'Master Data',
                'view' => ['viewCustomer'],
                'create' => ['createCustomer'],
                'update' => ['updateCustomer'],
                'delete' => ['deleteCustomer'],
                'print' => ['printLaporanCustomer'],
                'approve' => [],
                'other' => ['Customereditadmin', 'customereditadmin'],
            ],
            'Group Customer' => [
                'group' => 'Master Data',
                'view' => ['viewGroupCustomer'],
                'create' => ['createGroupCustomer'],
                'update' => ['updateGroupCustomer'],
                'delete' => ['deleteGroupCustomer'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Salesman' => [
                'group' => 'Master Data',
                'view' => ['viewSalesman'],
                'create' => ['createSalesman'],
                'update' => ['updateSalesman'],
                'delete' => ['deleteSalesman'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Satuan' => [
                'group' => 'Master Data',
                'view' => ['viewSatuan'],
                'create' => ['createSatuan'],
                'update' => ['updateSatuan'],
                'delete' => ['deleteSatuan'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Merek' => [
                'group' => 'Master Data',
                'view' => ['viewMerek'],
                'create' => ['createMerek'],
                'update' => ['updateMerek'],
                'delete' => ['deleteMerek'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Gudang' => [
                'group' => 'Master Data',
                'view' => ['viewGudang'],
                'create' => ['createGudang'],
                'update' => ['updateGudang'],
                'delete' => ['deleteGudang'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Group Product' => [
                'group' => 'Master Data',
                'view' => ['viewGroupProduct'],
                'create' => ['createGroupProduct'],
                'update' => ['updateGroupProduct'],
                'delete' => ['deleteGroupProduct'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Product' => [
                'group' => 'Master Data',
                'view' => ['viewProduct'],
                'create' => ['createProduct'],
                'update' => ['updateProduct'],
                'delete' => ['deleteProduct'],
                'print' => ['printLaporanProduk', 'printStokDalamRupiah', 'printLaporanKartuStok'],
                'approve' => ['approveProduct'],
                'other' => ['viewProductHpp'],
            ],
            'Supplier' => [
                'group' => 'Master Data',
                'view' => ['viewSupplier'],
                'create' => ['createSupplier'],
                'update' => ['updateSupplier'],
                'delete' => ['deleteSupplier'],
                'print' => ['printLaporanSupplier'],
                'approve' => [],
                'other' => [],
            ],
            'Rekening' => [
                'group' => 'Master Data',
                'view' => ['viewRekening'],
                'create' => ['createRekening'],
                'update' => ['updateRekening'],
                'delete' => ['deleteRekening'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'Sub Account' => [
                'group' => 'Master Data',
                'view' => ['viewSubAccount'],
                'create' => ['createSubAccount'],
                'update' => ['updateSubAccount'],
                'delete' => ['deleteSubAccount'],
                'print' => ['printSubAccount'],
                'approve' => [],
                'other' => [],
            ],
            'Account' => [
                'group' => 'Master Data',
                'view' => ['viewAccount'],
                'create' => ['createAccount'],
                'update' => ['updateAccount'],
                'delete' => ['deleteAccount'],
                'print' => ['printChartOfAccount'],
                'approve' => [],
                'other' => [],
            ],
            'Currency' => [
                'group' => 'Master Data',
                'view' => ['viewCurrency'],
                'create' => ['createCurrency'],
                'update' => ['updateCurrency'],
                'delete' => ['deleteCurrency'],
                'print' => [],
                'approve' => [],
                'other' => [],
            ],
            'User & Wewenang' => [
                'group' => 'Master Data',
                'view' => ['viewSysuser'],
                'create' => ['createSysuser'],
                'update' => ['updateSysuser'],
                'delete' => ['deleteSysuser'],
                'print' => [],
                'approve' => [],
                'other' => ['roleaccess'],
            ],

            // Transaksi Penjualan
            'Sales Order (SO)' => [
                'group' => 'Penjualan',
                'view' => ['viewSalesOrder'],
                'create' => ['createSalesOrder'],
                'update' => ['updateSalesOrder'],
                'delete' => ['deleteSalesOrder'],
                'print' => ['printListingSalesOrder', 'printSoBelumTerkirim', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approveSalesOrder'],
                'other' => [],
            ],
            'Surat Jalan' => [
                'group' => 'Penjualan',
                'view' => ['viewSuratJalan'],
                'create' => ['createSuratJalan'],
                'update' => ['updateSuratJalan'],
                'delete' => ['deleteSuratJalan'],
                'print' => ['printListingSuratJalan', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approveSuratJalan'],
                'other' => [],
            ],
            'Faktur Penjualan' => [
                'group' => 'Penjualan',
                'view' => ['viewInvoice'],
                'create' => ['createInvoice'],
                'update' => ['updateInvoice'],
                'delete' => ['deleteInvoice'],
                'print' => ['printListingPenjualan', 'printListingPenjualanHpp', 'printListingPiutangPenjualan', 'printListingFakturPajakPenjualan', 'printLaporanRekapPenjualan', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approveFakturPenjualan'],
                'other' => [],
            ],
            'Penjualan Retail' => [
                'group' => 'Penjualan',
                'view' => ['viewPenjualanRetail'],
                'create' => ['createPenjualanRetail'],
                'update' => ['updatePenjualanRetail'],
                'delete' => ['deletePenjualanRetail'],
                'print' => ['BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => [],
                'other' => ['BolehPenjualanTunai'],
            ],
            'Retur Penjualan' => [
                'group' => 'Penjualan',
                'view' => ['viewReturPenjualan'],
                'create' => ['createReturPenjualan'],
                'update' => ['updateReturPenjualan'],
                'delete' => ['deleteReturPenjualan'],
                'print' => ['printListingReturPenjualan', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approveReturPenjualan'],
                'other' => [],
            ],
            'Lembar Penagihan' => [
                'group' => 'Penjualan',
                'view' => ['viewLembarPenagihan'],
                'create' => ['createLembarPenagihan'],
                'update' => ['updateLembarPenagihan'],
                'delete' => ['deleteLembarPenagihan'],
                'print' => ['printAnalisaUmurPiutang', 'printBukuPiutang'],
                'approve' => [],
                'other' => [],
            ],
            'Pelunasan Customer' => [
                'group' => 'Penjualan',
                'view' => ['viewPelunasanCustomer'],
                'create' => ['createPelunasanCustomer'],
                'update' => ['updatePelunasanCustomer'],
                'delete' => ['deletePelunasanCustomer'],
                'print' => ['printLaporanPelunasanCustomer'],
                'approve' => [],
                'other' => [],
            ],

            // Transaksi Pembelian
            'Permintaan Pembelian (PR)' => [
                'group' => 'Pembelian',
                'view' => ['viewTr_prh'],
                'create' => ['createTr_prh'],
                'update' => ['updateTr_prh'],
                'delete' => ['deleteTr_prh'],
                'print' => ['printListingPermintaanPembelian', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approvePR'],
                'other' => [],
            ],
            'Order Pembelian (PO)' => [
                'group' => 'Pembelian',
                'view' => ['viewTr_poh'],
                'create' => ['createTr_poh'],
                'update' => ['updateTr_poh'],
                'delete' => ['deleteTr_poh'],
                'print' => ['printListingOrderPembelian', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approvePO'],
                'other' => [],
            ],
            'Penerimaan Barang' => [
                'group' => 'Pembelian',
                'view' => ['viewPenerimaanBarang'],
                'create' => ['createPenerimaanBarang'],
                'update' => ['updatePenerimaanBarang'],
                'delete' => ['deletePenerimaanBarang'],
                'print' => ['printListingPenerimaanBarang', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => [],
                'other' => [],
            ],
            'Faktur Pembelian' => [
                'group' => 'Pembelian',
                'view' => ['viewFakturPembelian'],
                'create' => ['createFakturPembelian'],
                'update' => ['updateFakturPembelian'],
                'delete' => ['deleteFakturPembelian'],
                'print' => ['printListingFakturPembelian', 'printListingHutangDagang', 'printAnalisaUmurHutang', 'printBukuHutang', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approveFakturPembelian'],
                'other' => [],
            ],
            'Retur Pembelian' => [
                'group' => 'Pembelian',
                'view' => ['viewReturPembelian'],
                'create' => ['createReturPembelian'],
                'update' => ['updateReturPembelian'],
                'delete' => ['deleteReturPembelian'],
                'print' => ['printListingReturPembelian', 'BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => ['approveReturPembelian'],
                'other' => [],
            ],
            'Bayar Supplier' => [
                'group' => 'Pembelian',
                'view' => ['viewBayarSupplier'],
                'create' => ['createBayarSupplier'],
                'update' => ['updateBayarSupplier'],
                'delete' => ['deleteBayarSupplier'],
                'print' => ['printLaporanBayarSupplier'],
                'approve' => [],
                'other' => [],
            ],

            // Persediaan & Gudang
            'Adjustment Stok' => [
                'group' => 'Persediaan & Gudang',
                'view' => ['viewAdjstock'],
                'create' => ['createAdjstock'],
                'update' => ['updateAdjstock'],
                'delete' => ['deleteAdjstock'],
                'print' => ['printListingAdjustmentStok'],
                'approve' => [],
                'other' => [],
            ],
            'Mutasi Stok' => [
                'group' => 'Persediaan & Gudang',
                'view' => ['viewMutasi'],
                'create' => ['createMutasi'],
                'update' => ['updateMutasi'],
                'delete' => ['deleteMutasi'],
                'print' => ['printListingMutasiStok'],
                'approve' => [],
                'other' => [],
            ],
            'Pemakaian Barang' => [
                'group' => 'Persediaan & Gudang',
                'view' => ['viewPemakaianbarang'],
                'create' => ['createPemakaianbarang'],
                'update' => ['updatePemakaianBarang'],
                'delete' => ['deletePemakaianBarang'],
                'print' => ['printListingPemakaianBarang'],
                'approve' => [],
                'other' => [],
            ],
            'Assembling' => [
                'group' => 'Persediaan & Gudang',
                'view' => ['viewAssembling'],
                'create' => ['createAssembling'],
                'update' => ['updateAssembling'],
                'delete' => ['deleteAssembling'],
                'print' => ['printListingAssembling'],
                'approve' => [],
                'other' => [],
            ],

            // Kas & Bank
            'Penerimaan Kas/Bank' => [
                'group' => 'Kas & Bank',
                'view' => ['viewPenerimaanKas'],
                'create' => ['createPenerimaanKas'],
                'update' => ['updatePenerimaanKas'],
                'delete' => ['deletePenerimaanKas'],
                'print' => ['printListingPenerimaanKasBank'],
                'approve' => [],
                'other' => [],
            ],
            'Pengeluaran Kas/Bank' => [
                'group' => 'Kas & Bank',
                'view' => ['viewPengeluaranKas'],
                'create' => ['createPengeluaranKas'],
                'update' => ['updatePengeluaranKas'],
                'delete' => ['deletePengeluaranKas'],
                'print' => ['printListingPengeluaranKasBank'],
                'approve' => [],
                'other' => [],
            ],
            'Jurnal Transaksi' => [
                'group' => 'Kas & Bank',
                'view' => ['viewjurnaltransaksi'],
                'create' => ['createjurnaltransaksi'],
                'update' => ['updatejurnaltransaksi'],
                'delete' => ['deletejurnaltransaksi'],
                'print' => ['printListingJurnalTransaksi', 'printBukuBesar', 'printTrialBalance'],
                'approve' => [],
                'other' => [],
            ],

            // Konfigurasi & Khusus
            'System Setting & Utility' => [
                'group' => 'Utility & Setting',
                'view' => ['systemSetting', 'editSystemSetting'],
                'create' => ['penamaanPerusahaan'],
                'update' => ['editSystemSetting', 'editPeriode', 'penamaanPerusahaan', 'BolehGantiTanggal'],
                'delete' => [],
                'print' => ['BOLEHPRINTLAGI', 'bolehprintlagi'],
                'approve' => [],
                'other' => ['semuacabang', 'roleaccess', 'BolehGantiTanggal', 'editPeriode'],
            ],
        ];
    }

    public function index(Request $request)
    {
        $allMenus = self::getMenuDefinitions();
        $selectedMenuKey = $request->input('menu', 'Customer');
        $selectedUserId = $request->input('user_id');
        $search = $request->input('search');

        $users = Sysuser::orderBy('fsysuserid')->get();
        $roleAccessMap = RoleAccess::all()->keyBy('fusercreate');

        $rows = $this->buildDashboardRows($users, $roleAccessMap, $allMenus, $selectedMenuKey, $selectedUserId, $search);

        // Stats summary
        $totalUsers = count($users);
        $activeAccessCount = collect($rows)->filter(fn($r) => $r['can_view'] || $r['can_create'] || $r['can_update'] || $r['can_delete'] || $r['can_print'] || $r['can_approve'] || $r['can_other'])->count();

        return view('dashboardwewenang.index', compact(
            'allMenus',
            'selectedMenuKey',
            'selectedUserId',
            'search',
            'users',
            'rows',
            'totalUsers',
            'activeAccessCount'
        ));
    }

    public function exportExcel(Request $request)
    {
        $allMenus = self::getMenuDefinitions();
        $selectedMenuKey = $request->input('menu', 'Customer');
        $selectedUserId = $request->input('user_id');
        $search = $request->input('search');

        $users = Sysuser::orderBy('fsysuserid')->get();
        $roleAccessMap = RoleAccess::all()->keyBy('fusercreate');

        $rows = $this->buildDashboardRows($users, $roleAccessMap, $allMenus, $selectedMenuKey, $selectedUserId, $search);

        $filename = 'Dashboard_Wewenang_User_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $styleTitle = new Style(fontBold: true, fontSize: 14);
        $styleHeader = new Style(fontBold: true, backgroundColor: 'D3D3D3');
        $styleRow = new Style(fontBold: false);
        $styleGrandTotal = new Style(fontBold: true, backgroundColor: '333333', fontColor: 'FFFFFF');

        $makeRow = function (array $values, ?Style $style = null): Row {
            $cells = array_map(
                fn ($value) => $style ? Cell::fromValue($value, $style) : Cell::fromValue($value),
                $values
            );
            return new Row($cells);
        };

        $writer->addRow($makeRow(['DASHBOARD WEWENANG USER'], $styleTitle));
        $writer->addRow($makeRow(['Parameter Menu:', $selectedMenuKey === 'ALL' ? 'Semua Menu' : $selectedMenuKey]));
        $writer->addRow($makeRow(['Tanggal Cetak:', date('d/m/Y H:i')]));
        $writer->addRow($makeRow([]));

        $writer->addRow($makeRow([
            'No',
            'Id',
            'Nama Lengkap',
            'List Menu',
            'View',
            'Tambah',
            'Edit',
            'Delete',
            'Print',
            'Approve',
            'Lain-lain',
        ], $styleHeader));

        $no = 1;
        foreach ($rows as $row) {
            $writer->addRow($makeRow([
                $no++,
                $row['user_id'],
                $row['user_name'],
                $row['menu_name'],
                $row['can_view'] ? 'V' : '-',
                $row['can_create'] ? 'V' : '-',
                $row['can_update'] ? 'V' : '-',
                $row['can_delete'] ? 'V' : '-',
                $row['can_print'] ? 'V' : '-',
                $row['can_approve'] ? 'V' : '-',
                $row['can_other'] ? 'V' : '-',
            ], $styleRow));
        }

        $writer->addRow($makeRow([]));
        $writer->addRow($makeRow(['Total Baris: ' . count($rows)], $styleGrandTotal));
        $writer->addRow($makeRow(['*** End of Report ***'], $styleGrandTotal));

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        $allMenus = self::getMenuDefinitions();
        $selectedMenuKey = $request->input('menu', 'Customer');
        $selectedUserId = $request->input('user_id');
        $search = $request->input('search');

        $users = Sysuser::orderBy('fsysuserid')->get();
        $roleAccessMap = RoleAccess::all()->keyBy('fusercreate');

        $rows = $this->buildDashboardRows($users, $roleAccessMap, $allMenus, $selectedMenuKey, $selectedUserId, $search);

        return view('dashboardwewenang.print', [
            'selectedMenuKey' => $selectedMenuKey,
            'rows' => $rows,
            'user_session' => auth('sysuser')->user() ?? auth()->user(),
        ]);
    }

    private function buildDashboardRows($users, $roleAccessMap, $allMenus, $selectedMenuKey, $selectedUserId, $search): array
    {
        $rows = [];

        // Filter users
        if ($selectedUserId) {
            $users = $users->where('fuid', $selectedUserId);
        }

        // Filter menus
        $targetMenus = ($selectedMenuKey === 'ALL' || !isset($allMenus[$selectedMenuKey]))
            ? $allMenus
            : [$selectedMenuKey => $allMenus[$selectedMenuKey]];

        foreach ($users as $user) {
            if ($search) {
                $q = strtolower($search);
                if (!str_contains(strtolower($user->fsysuserid), $q) && !str_contains(strtolower((string) $user->fname), $q)) {
                    continue;
                }
            }

            $ra = $roleAccessMap->get($user->fuid);
            $isUnrestricted = is_null($ra) || is_null($ra->fpermission);
            $userPerms = $ra && filled($ra->fpermission)
                ? array_filter(array_map('trim', explode(',', $ra->fpermission)))
                : [];

            $hasPerm = function (array $perms) use ($isUnrestricted, $userPerms): bool {
                if (empty($perms)) return false;
                if ($isUnrestricted) return true;
                foreach ($perms as $p) {
                    if (in_array($p, $userPerms, true)) return true;
                }
                return false;
            };

            foreach ($targetMenus as $menuName => $def) {
                $canView = $hasPerm($def['view']);
                $canCreate = $hasPerm($def['create']);
                $canUpdate = $hasPerm($def['update']);
                $canDelete = $hasPerm($def['delete']);
                $canPrint = $hasPerm($def['print']);
                $canApprove = $hasPerm($def['approve']);
                $canOther = $hasPerm($def['other']);

                $rows[] = [
                    'user_id' => $user->fsysuserid,
                    'user_name' => $user->fname ?? '-',
                    'user_fuid' => $user->fuid,
                    'menu_name' => $menuName,
                    'menu_group' => $def['group'],
                    'can_view' => $canView,
                    'can_create' => $canCreate,
                    'can_update' => $canUpdate,
                    'can_delete' => $canDelete,
                    'can_print' => $canPrint,
                    'can_approve' => $canApprove,
                    'can_other' => $canOther,
                    'is_unrestricted' => $isUnrestricted,
                ];
            }
        }

        return $rows;
    }
}
