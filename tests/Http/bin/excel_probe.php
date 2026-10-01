<?php

/**
 * Probe satu route laporan yang memanggil exit() (export Excel langsung ke output), jadi tidak bisa
 * diuji di dalam proses PHPUnit. Pakai: php excel_probe.php <route-name> <date_from> <date_to>
 * Menulis hasil JSON ke STDERR: {"status":int,"head":"2 byte pertama","bytes":int,"error":"..."}.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$report = function (int $status, string $out, string $error = '') {
    fwrite(STDERR, json_encode(['status' => $status, 'head' => substr($out, 0, 2), 'bytes' => strlen($out), 'error' => $error]));
};

Auth::guard('sysuser')->setUser(App\Models\Sysuser::where('fsysuserid', 'admin')->firstOrFail());

$url = route($argv[1], ['date_from' => $argv[2], 'date_to' => $argv[3]], false);

ob_start();
$done = false;
register_shutdown_function(function () use (&$done, $report) {
    if ($done) {
        return;
    }
    $out = (string) ob_get_contents();
    ob_end_clean();
    $last = error_get_last();
    $fatal = $last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    $report($fatal ? 500 : (http_response_code() ?: 200), $out, $last['message'] ?? '');
});

$response = $kernel->handle($request = Request::create($url, 'GET'));
$done = true;
$out = (string) ob_get_clean();
$streamed = $response instanceof Symfony\Component\HttpFoundation\StreamedResponse || $response instanceof Symfony\Component\HttpFoundation\BinaryFileResponse;
$content = $out !== '' ? $out : ($streamed ? '' : (string) $response->getContent());
$error = $response->getStatusCode() >= 500 ? mb_substr(trim(strip_tags($content)), 0, 200) : '';
$report($response->getStatusCode(), $content, $error);
