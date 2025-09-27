<?php

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
