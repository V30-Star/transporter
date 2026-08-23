<?php

if (!function_exists('format_number')) {
  function format_number($value, int $decimals = 2, string $decimalSeparator = ',', string $thousandsSeparator = '.')
  {
    if ($value === null || $value === '') {
      $value = 0;
    }

    return number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
  }
}

if (!function_exists('format_currency')) {
  function format_currency($value, string $prefix = 'Rp ')
  {
    return $prefix . format_number($value, 2, ',', '.');
  }
}

if (!function_exists('stock_boleh_minus')) {
  function stock_boleh_minus(): bool
  {
    static $allow = null;

    if ($allow !== null) {
      return $allow;
    }

    $allow = trim((string) \Illuminate\Support\Facades\DB::table('setini')->value('fstokbolehminus')) === '1';

    return $allow;
  }
}

if (!function_exists('terbilang')) {
  function terbilang($number)
  {
    $number = number_format($number, 2, '.', ''); // format jadi string dengan 2 desimal
    [$integerPart, $decimalPart] = explode('.', $number);

    $integerPart = (int) $integerPart;
    $decimalPart = (int) $decimalPart;

    $result = trim(terbilangInteger($integerPart)) . " Rupiah";

    if ($decimalPart > 0) {
      $result .= " " . trim(terbilangInteger($decimalPart)) . " Sen";
    }

    return $result;
  }

  function terbilangInteger($number)
  {
    $words = [
      "",
      "Satu",
      "Dua",
      "Tiga",
      "Empat",
      "Lima",
      "Enam",
      "Tujuh",
      "Delapan",
      "Sembilan",
      "Sepuluh",
      "Sebelas"
    ];

    if ($number < 12) {
      return " " . $words[$number];
    } elseif ($number < 20) {
      return terbilangInteger($number - 10) . " Belas";
    } elseif ($number < 100) {
      return terbilangInteger(intval($number / 10)) . " Puluh" . terbilangInteger($number % 10);
    } elseif ($number < 200) {
      return " Seratus" . terbilangInteger($number - 100);
    } elseif ($number < 1000) {
      return terbilangInteger(intval($number / 100)) . " Ratus" . terbilangInteger($number % 100);
    } elseif ($number < 2000) {
      return " Seribu" . terbilangInteger($number - 1000);
    } elseif ($number < 1000000) {
      return terbilangInteger(intval($number / 1000)) . " Ribu" . terbilangInteger($number % 1000);
    } elseif ($number < 1000000000) {
      return terbilangInteger(intval($number / 1000000)) . " Juta" . terbilangInteger($number % 1000000);
    } elseif ($number < 1000000000000) {
      return terbilangInteger(intval($number / 1000000000)) . " Milyar" . terbilangInteger($number % 1000000000);
    } elseif ($number < 1000000000000000) {
      return terbilangInteger(intval($number / 1000000000000)) . " Triliun" . terbilangInteger($number % 1000000000000);
    }

    return "";
  }
}

if (!function_exists('decrypt_value')) {
  function decrypt_value(?string $value): string
  {
    if ($value === null || $value === '') {
      return '';
    }

    try {
      return \Illuminate\Support\Facades\Crypt::decryptString($value);
    } catch (\Throwable $e) {
      $knownKeys = [
        'base64:Zc/6jQQwdTPeQcGRz4fbq1JkFGhbSoMB5FZ3LNNQGoo=',
      ];
      foreach ($knownKeys as $k) {
        try {
          $keyBytes = str_starts_with($k, 'base64:') ? base64_decode(substr($k, 7)) : $k;
          $encrypter = new \Illuminate\Encryption\Encrypter($keyBytes, config('app.cipher', 'AES-256-CBC'));
          return $encrypter->decryptString($value);
        } catch (\Throwable $t) {
          // continue
        }
      }
      return $value;
    }
  }
}

if (!function_exists('company_setting')) {
  function company_setting(): object
  {
    static $cached = null;

    if ($cached !== null) {
      return $cached;
    }

    try {
      $s = \Illuminate\Support\Facades\DB::table('setini')->first();
    } catch (\Throwable $e) {
      $s = null;
    }

    if (!$s) {
      $cached = (object)[
        'fproject' => 'PT. M-Trade',
        'fcity' => '',
        'falamat1' => '',
        'falamat2' => '',
        'ftelp' => '',
        'ffax' => '',
        'fnpwp' => '',
        'falamat1npwp' => '',
        'falamat2npwp' => '',
        'fnamattdpo' => '',
        'fnamattdpo2' => '',
        'fnamattdfakturpenjualan' => '',
        'fnamattdfakturpenjualan2' => '',
        'fppntarif' => 11.0,
      ];
      return $cached;
    }

    $rawAddr1 = decrypt_value($s->falamat1 ?? '');
    $rawAddr2 = decrypt_value($s->falamat2 ?? '');
    $addr1 = preg_replace('/^alamat\s*1?\s*:\s*/i', '', trim($rawAddr1));
    $addr2 = preg_replace('/^alamat\s*2?\s*:\s*/i', '', trim($rawAddr2));
    $projectName = decrypt_value($s->fproject ?? '');

    $cached = (object)[
      'fproject' => $projectName !== '' ? $projectName : 'PT. M-Trade',
      'fcity' => $s->fcity ?? '',
      'falamat1' => $addr1,
      'falamat2' => $addr2,
      'ftelp' => $s->ftelp ?? '',
      'ffax' => $s->ffax ?? '',
      'fnpwp' => decrypt_value($s->fnpwp ?? ''),
      'falamat1npwp' => decrypt_value($s->falamat1npwp ?? ''),
      'falamat2npwp' => decrypt_value($s->falamat2npwp ?? ''),
      'fnamattdpo' => $s->fnamattdpo ?? '',
      'fnamattdpo2' => $s->fnamattdpo2 ?? '',
      'fnamattdfakturpenjualan' => $s->fnamattdfakturpenjualan ?? '',
      'fnamattdfakturpenjualan2' => $s->fnamattdfakturpenjualan2 ?? '',
      'fppntarif' => $s->fppntarif ?? 11.0,
    ];

    return $cached;
  }
}

if (!function_exists('company_name')) {
  function company_name(): string
  {
    return company_setting()->fproject;
  }
}

if (!function_exists('log_print_transaction')) {
  function log_print_transaction(?string $trxNo): void
  {
    if (empty($trxNo)) {
      return;
    }

    try {
      $user = auth('sysuser')->user() ?? auth()->user();
      $userId = $user->fsysuserid ?? $user->fuserid ?? session('user_session')->fsysuserid ?? session('user_id') ?? 'ADMIN';

      \Illuminate\Support\Facades\DB::table('trprintlog')->insert([
        'ftrxno' => $trxNo,
        'fdatetime' => now(),
        'fuserid' => $userId,
      ]);
    } catch (\Throwable $e) {
      // Avoid breaking print flow if logging fails
    }
  }
}

if (!function_exists('can_print_again')) {
  function can_print_again(): bool
  {
    $user = auth('sysuser')->user() ?? auth()->user();
    if ($user && isset($user->fuid)) {
      $dbPerm = \App\Models\RoleAccess::where('fusercreate', $user->fuid)->value('fpermission');
      if ($dbPerm !== null) {
        session(['user_restricted_permissions' => $dbPerm]);
      }
    }

    $raw = (string) session('user_restricted_permissions', '');
    $permissions = array_map('strtolower', array_filter(array_map('trim', explode(',', $raw))));
    return in_array('bolehprintlagi', $permissions, true);
  }
}

if (!function_exists('sysuser_name')) {
  function sysuser_name(?string $usercode): string
  {
    $code = trim((string) $usercode);
    if ($code === '') {
      return '';
    }
    static $userNames = [];
    if (isset($userNames[$code])) {
      return $userNames[$code];
    }
    $name = \Illuminate\Support\Facades\DB::table('sysuser')
      ->where('fsysuserid', $code)
      ->orWhere('fname', $code)
      ->value('fname');

    $userNames[$code] = $name ?: $code;
    return $userNames[$code];
  }
}
