# Test HTTP terhadap DB live

Test di folder ini mengirim request HTTP ke aplikasi (tanpa browser) dan memakai database asli dari `.env`
(PostgreSQL `THE`). Semua tulisan dibungkus transaksi (`DatabaseTransactions`) lalu di-rollback, dan data uji memakai
kode `ZZDUSK*` / `ZZ_DUSK_*`. Satu file per menu, ditambah `ReportsHttpTest` untuk halaman laporan dan export Excel.

## Menjalankan

```
php vendor/phpunit/phpunit/phpunit --testsuite=LiveHttp                     # semua (±10 menit)
php vendor/phpunit/phpunit/phpunit tests/Http/AdjstockHttpTest.php          # satu menu
```

Run default (`phpunit` tanpa opsi) hanya menjalankan suite Unit dan Feature, sehingga tidak menyentuh DB live.

## Aturan penting

- **Jangan** menjalankan di server produksi, dan **jangan** mengganti `DatabaseTransactions` dengan `RefreshDatabase`
  di `LiveDbTestCase`: itu akan menghapus database live.
- Test memakai user `admin` dan memberi dirinya permission lewat `grantPermissions()` (ikut di-rollback).
- Batas 15 dokumen per hari per jenis dihitung dari data yang sudah ter-commit; test yang terkena batas akan di-skip.
- Export Excel yang memanggil `exit()` diuji di proses terpisah lewat `bin/excel_probe.php`.
- Beberapa test butuh patch DB dari `storage/app/fix_tg_*.sql` (trigger stok) sudah dijalankan.
- Email, cetak, dan layanan eksternal sengaja tidak diuji.

## Struktur

- `LiveDbTestCase.php` base class (koneksi pgsql dari `.env`, login admin, `atomic()`, `grantPermissions()`).
- `Concerns/` data uji bersama (supplier, customer, produk, gudang, rantai pembelian dan penjualan).
- `bin/excel_probe.php` probe export Excel.
