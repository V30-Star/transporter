<?php

namespace Tests\Http;

use App\Models\RoleAccess;
use App\Models\Sysuser;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test HTTP tanpa browser terhadap DB asli (.env). Semua tulisan di-rollback (DatabaseTransactions).
 * JANGAN pakai RefreshDatabase di sini: itu akan menghapus DB live.
 */
abstract class LiveDbTestCase extends TestCase
{
    use DatabaseTransactions;

    public function createApplication()
    {
        $app = parent::createApplication();

        // phpunit.xml memaksa sqlite :memory:; pakai pgsql dari .env.
        $env = Dotenv::parse(file_get_contents($app->basePath('.env')));
        $app['config']->set('database.default', 'pgsql');
        foreach (['host' => 'DB_HOST', 'port' => 'DB_PORT', 'database' => 'DB_DATABASE', 'username' => 'DB_USERNAME', 'password' => 'DB_PASSWORD'] as $key => $var) {
            $app['config']->set("database.connections.pgsql.$key", $env[$var]);
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Sysuser::where('fsysuserid', 'admin')->firstOrFail(), 'sysuser');
    }

    /**
     * Jalankan $fn; jika query di dalamnya gagal (controller menelan error) transaksi Postgres jadi "aborted".
     * Savepoint memulihkannya supaya langkah test berikutnya tetap bisa jalan.
     */
    protected $lastResponse;

    protected function atomic(callable $fn)
    {
        DB::unprepared('SAVEPOINT dusk_step');
        $result = $this->lastResponse = $fn();

        try {
            DB::select('select 1');
            DB::unprepared('RELEASE SAVEPOINT dusk_step');
        } catch (\Throwable $e) {
            DB::unprepared('ROLLBACK TO SAVEPOINT dusk_step');
        }

        return $result;
    }

    /**
     * Tambahkan izin yang belum dimiliki admin (hanya di dalam transaksi test, ikut di-rollback).
     * Dipakai untuk menu yang memang belum diberi izin ke admin; tidak mengubah data asli.
     */
    /** Pesan flash/validasi terakhir + status respons, untuk pesan kegagalan test. */
    protected function lastFlash(): string
    {
        return json_encode([
            'error' => session('error'),
            'errors' => session('errors')?->all(),
            'status' => $this->lastResponse?->status(),
            'to' => $this->lastResponse?->headers->get('Location'),
        ]);
    }

    protected function sessionHasError(string $field): bool
    {
        return session('errors')?->has($field) ?? false;
    }

    protected function grantPermissions(string ...$permissions): void
    {
        $role = RoleAccess::where('fusercreate', auth('sysuser')->user()->fuid)->first();
        if (! $role) {
            return;
        }

        $have = array_map('strtolower', array_filter(array_map('trim', explode(',', (string) $role->fpermission))));
        $missing = array_filter($permissions, fn ($p) => ! in_array(strtolower($p), $have, true));
        if ($missing) {
            $role->fpermission = trim((string) $role->fpermission, ', ') . ',' . implode(',', $missing);
            $role->save();
        }
    }
}
