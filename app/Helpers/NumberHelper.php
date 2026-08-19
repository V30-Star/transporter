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

