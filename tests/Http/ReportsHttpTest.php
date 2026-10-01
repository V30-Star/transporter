<?php

namespace Tests\Http;


/**
 * Smoke test semua halaman laporan/listing (index, print, excel): hanya baca, tanpa mengubah data.
 * Gagal bila ada respons 5xx atau 403 (permission tidak terpetakan). Redirect (validasi filter) dianggap wajar.
 */
class ReportsHttpTest extends LiveDbTestCase
{
    private const ROUTES = [
        'reporting.index', 'reporting.printPoh',
        'reportingpr.index', 'reportingpr.printPrh',
        'reportingpenerimaanbarang.index', 'reportingpenerimaanbarang.printPenerimaanBarang',
        'reportingfakturpembelian.index', 'reportingfakturpembelian.printFakturPembelian',
        'reportingkas.pengeluaran.index', 'reportingkas.pengeluaran.print', 'reportingkas.pengeluaran.excel',
        'reportingkas.penerimaan.index', 'reportingkas.penerimaan.print', 'reportingkas.penerimaan.excel',
        'reportingadjstock.index', 'reportingadjstock.printAdjStock',
        'reportingpemakaianbarang.index', 'reportingpemakaianbarang.printPemakaianBarang',
        'reportingassembling.index', 'reportingassembling.printAssembling',
        'reportingaccount.index', 'reportingaccount.exportExcel', 'reportingaccount.printAccount', 'reportingaccount.excel',
        'reportingsubaccount.index', 'reportingsubaccount.print', 'reportingsubaccount.excel',
        'reportingsupplier.index', 'reportingsupplier.print', 'reportingsupplier.excel',
        'reportingcustomer.index', 'reportingcustomer.print', 'reportingcustomer.excel',
        'reportingproduct.index', 'reportingproduct.print', 'reportingproduct.excel',
        'listingso.index', 'listingso.print', 'listingso.excel',
        'listingsobelum.index', 'listingsobelum.printCustomer', 'listingsobelum.printProduct', 'listingsobelum.excelCustomer', 'listingsobelum.excelProduct',
        'listingpr.index', 'listingpr.print', 'listingpr.excel',
        'listingpo.index', 'listingpo.print', 'listingpo.excel',
        'listingreturpembelian.index', 'listingreturpembelian.print', 'listingreturpembelian.excel',
        'listingpenerimaanbarang.index', 'listingpenerimaanbarang.print', 'listingpenerimaan.excel',
        'listingmutasistok.index', 'listingmutasistok.print', 'listingmutasistok.excel',
        'laporankartustok.index', 'laporankartustok.print', 'laporankartustok.excel',
        'stokdalamrupiah.index', 'stokdalamrupiah.print', 'stokdalamrupiah.excel',
        'listingpenerimaankasbank.index', 'listingpenerimaankasbank.print', 'listingpenerimaankasbank.excel',
        'listingpengeluarankasbank.index', 'listingpengeluarankasbank.print', 'listingpengeluarankasbank.excel',
        'laporanuangkasir.index', 'laporanuangkasir.print', 'laporanuangkasir.show', 'laporanuangkasir.export', 'laporanuangkasir.print-raw',
        'laporan.uang-kasir.index', 'laporan.uang-kasir.show', 'laporan.uang-kasir.export', 'laporan.uang-kasir.print-raw',
        'listingfakturpembelian.index', 'listingfakturpembelian.print', 'listingfakturpembelian.excel',
        'listingfakturpajakpenjualan.index', 'listingfakturpajakpenjualan.print', 'listingfakturpajakpenjualan.excel',
        'listingpenjualan.index', 'listingpenjualan.print', 'listingpenjualan.excel',
        'listingpenjualanhpp.index', 'listingpenjualanhpp.print', 'listingpenjualanhpp.excel',
        'listingpenjualanretail.index', 'listingpenjualanretail.print', 'listingpenjualanretail.excel',
        'listingpiutangpenjualan.index', 'listingpiutangpenjualan.print', 'listingpiutangpenjualan.excel',
        'listinghutangdagang.index', 'listinghutangdagang.print', 'listinghutangdagang.excel',
        'reportingrekappenjualan.index', 'reportingrekappenjualan.print', 'reportingrekappenjualan.excel',
        'reportingrekappenjualancustomerproduk.index', 'reportingrekappenjualancustomerproduk.print', 'reportingrekappenjualancustomerproduk.excel',
        'reportingrekappenjualansalesproduk.index', 'reportingrekappenjualansalesproduk.print', 'reportingrekappenjualansalesproduk.excel',
        'reportingrekappenjualansalescustomer.index', 'reportingrekappenjualansalescustomer.print', 'reportingrekappenjualansalescustomer.excel',
        'reportingpenjualandp.index', 'reportingpenjualandp.print', 'reportingpenjualandp.excel',
        'analisaumurpiutang.index', 'analisaumurpiutang.print', 'analisaumurpiutang.excel',
        'analisaumurhutang.index', 'analisaumurhutang.print', 'analisaumurhutang.excel',
        'bukupiutang.index', 'bukupiutang.print', 'bukupiutang.excel',
        'bukuhutang.index', 'bukuhutang.print', 'bukuhutang.excel',
        'bukubesar.index', 'bukubesar.print', 'bukubesar.excel',
        'trialbalance.index', 'trialbalance.print', 'trialbalance.excel',
        'listingsuratjalan.index', 'listingsuratjalan.print', 'listingsuratjalan.excel',
        'listingreturpenjualan.index', 'listingreturpenjualan.print', 'listingreturpenjualan.excel',
        'listingjurnal.index', 'listingjurnal.print',
        'reportingpelunasancustomer.index', 'reportingpelunasancustomer.print',
        'reportingpelunasansupplier.index', 'reportingpelunasansupplier.print',
        'loguser.index', 'loguser.print', 'loguser.excel',
        'dashboardwewenang.index', 'dashboardwewenang.print', 'dashboardwewenang.excel',
        'dashboard', 'profile', 'barcode.index', 'tr_prh.index', 'tr_prh.create',
    ];

    /** Export Excel yang memanggil exit(): dijalankan di proses PHP terpisah (tests/Http/bin/excel_probe.php). */
    private const EXIT_EXPORTS = [
        'reporting.exportExcel', 'reportingpr.exportExcel', 'reportingpenerimaanbarang.exportExcel', 'reportingfakturpembelian.exportExcel',
        'reportingadjstock.exportExcel', 'reportingpemakaianbarang.exportExcel', 'reportingassembling.exportExcel',
        'listingjurnal.excel', 'listingjurnal.exportExcel', 'reportingpelunasancustomer.exportExcel', 'reportingpelunasansupplier.exportExcel',
    ];

    /** Semua permission yang tampil di form roleaccess, supaya 403 berarti pemetaan route, bukan role uji yang kurang. */
    private function grantAllListedPermissions(): void
    {
        preg_match_all('/name="permission\[\]"\s+value="([^"]+)"/', (string) file_get_contents(resource_path('views/roleaccess/index.blade.php')), $m);
        $this->grantPermissions(...array_unique($m[1]));
    }

    private function openSpoutInstalled(): bool
    {
        return is_file(base_path('vendor/openspout/openspout/src/Writer/XLSX/Writer.php'));
    }

    public function test_the_excel_library_is_installed(): void
    {
        $this->assertTrue($this->openSpoutInstalled(), 'Package openspout/openspout belum terpasang (tidak ada di composer.json/lock/vendor), padahal dipakai 39 controller: semua export Excel akan 500.');
    }

    public function test_excel_exports_that_stream_directly_produce_a_workbook(): void
    {
        set_time_limit(0);
        if (! $this->openSpoutInstalled()) {
            $this->markTestSkipped('openspout/openspout belum terpasang.');
        }

        $env = array_merge(getenv(), \Dotenv\Dotenv::parse(file_get_contents(base_path('.env'))), ['APP_ENV' => 'local']);
        $script = base_path('tests/Http/bin/excel_probe.php');
        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $problems = [];
        foreach (self::EXIT_EXPORTS as $name) {
            $process = proc_open([PHP_BINARY, $script, $name, $from, $to], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $env);
            stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            proc_close($process);

            $result = json_decode(trim($stderr), true);
            if (! is_array($result)) {
                $problems[] = "$name => tidak ada hasil probe: " . mb_substr(trim($stderr), 0, 200);
            } elseif ($result['status'] >= 400 || ($result['head'] !== 'PK' && $result['status'] === 200 && $result['bytes'] > 0)) {
                $problems[] = sprintf('%s => %d head=%s bytes=%d %s', $name, $result['status'], bin2hex($result['head']), $result['bytes'], mb_substr($result['error'], 0, 160));
            }
        }

        $this->assertSame([], $problems, "Export Excel bermasalah:\n" . implode("\n", $problems));
    }

    public function test_every_report_page_responds_without_a_server_error(): void
    {
        set_time_limit(0);

        $query = [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];

        $this->grantAllListedPermissions();

        $routes = self::ROUTES;
        if (! $this->openSpoutInstalled()) {
            // Excel butuh openspout/openspout; tanpa itu setiap export 500 (dicatat di test export).
            $routes = array_values(array_filter($routes, fn ($n) => ! preg_match('/(excel|export)/i', $n)));
        }

        $problems = [];
        foreach ($routes as $name) {
            $start = microtime(true);
            $level = ob_get_level();
            ob_start();
            try {
                $response = $this->atomic(fn () => $this->get(route($name, $query)));
                $status = $response->getStatusCode();
                $detail = property_exists($response, 'exception') ? ($response->exception?->getMessage() ?? '') : '';
                if ($response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
                    $response->baseResponse->sendContent();
                }
            } catch (\Throwable $e) {
                $status = 500;
                $detail = $e->getMessage();
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            }
            $seconds = round(microtime(true) - $start, 1);

            if ($status >= 500 || $status === 403 || $status === 404) {
                $problems[] = sprintf('%s => %d (%ss) %s', $name, $status, $seconds, mb_substr(preg_replace('/\s+/', ' ', $detail), 0, 160));
            }
        }

        $this->assertSame([], $problems, "Halaman laporan bermasalah:\n" . implode("\n", $problems));
    }
}
