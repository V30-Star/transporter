<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== SEARCH IN TRSTOCKDT (Surat Jalan Details) ===\n";
    $sjDetails = DB::table('trstockdt')
        ->where('fprdcode', 'like', '%009.031.000003%')
        ->get();
    foreach ($sjDetails as $d) {
        echo "SjNo: {$d->fstockmtno}, Code: {$d->fprdcode}, Satuan: {$d->fsatuan}, Qty: {$d->fqty}, QtyKecil: {$d->fqtykecil}, Remain: {$d->fqtyremain}, Acak: '{$d->fnoacak}', RefAcak: '{$d->frefnoacak}'\n";
    }

    echo "\n=== SEARCH IN TRANDT (Sales / Retur Details) ===\n";
    $salesDetails = DB::table('trandt')
        ->where('fprdcode', 'like', '%009.031.000003%')
        ->get();
    foreach ($salesDetails as $d) {
        echo "Sono/RejNo: {$d->fsono}, Code: {$d->fprdcode}, Satuan: {$d->fsatuan}, Qty: {$d->fqty}, QtyKecil: {$d->fqtykecil}, RefSO: '{$d->frefso}', RefSRJ: '{$d->frefsrj}', Acak: '{$d->fnoacak}', RefAcak: '{$d->frefnoacak}'\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
