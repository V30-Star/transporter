<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Membuat nota Faktur Penjualan dalam mode teks untuk Epson LX-310 (ESC/P).
 * Dioptimalkan untuk kertas continuous form 5.5 inci (33 baris pada 6 LPI)
 * dan mode condensed 17 CPI (56 kolom).
 */
class NotaTextBuilder
{
    // ---- Pengaturan layout (disesuaikan dengan kertas 5.5 inci) ----
    const W           = 56;  // lebar kolom, condensed 17 cpi (area isi ~3,35 in)
    const PAGE_LINES  = 33;  // panjang form: 5,5 in x 6 lpi = 33 baris
    const LEFT_MARGIN = 4;   // margin kiri (kolom condensed, ~0,25 in)
    const BOLD        = "\x01"; // penanda baris tebal (dibuang di preview / diubah ke ESC E di raw)

    public function __construct(
        private object $hdr,
        private $dt,
        private array $opt = []
    ) {
    }

    /** Terima array yang sama persis dengan yang dikirim ke view print. */
    public static function fromData(array $d): self
    {
        $h   = (object) ($d['hdr'] ?? []);
        $fmt = $d['fmt'] ?? null;

        $signer = !empty($d['namattdfakturpenjualan'])
            ? $d['namattdfakturpenjualan']
            : (!empty($d['namattdpo']) ? $d['namattdpo'] : '');

        return new self($h, $d['dt'] ?? [], [
            'company_name' => $d['company_name'] ?? '',
            'company_city' => $d['company_city'] ?? '',
            'no'           => $d['displayFsono'] ?? ($h->fsono ?? '-'),
            'date'         => is_callable($fmt) ? $fmt($h->fsodate ?? null) : ($h->fsodate ?? ''),
            'signer'       => $signer,
        ]);
    }

    // ------------------------------------------------------------------
    // Output
    // ------------------------------------------------------------------

    /** Teks polos per halaman, untuk preview di browser. */
    public function plainPages(): array
    {
        return array_map(
            fn ($lines) => str_replace(self::BOLD, '', implode("\n", $lines)),
            $this->pages()
        );
    }

    /** Data mentah siap kirim ke printer (ESC/P Epson LX-310). */
    public function raw(): string
    {
        $esc = chr(27);

        $out  = $esc . '@';                          // reset printer
        $out .= $esc . 'C' . chr(self::PAGE_LINES);  // panjang form (baris)
        $out .= chr(15);                             // condensed 17 cpi
        $out .= $esc . 'l' . chr(self::LEFT_MARGIN); // margin kiri

        foreach ($this->pages() as $lines) {
            foreach ($lines as $line) {
                if (str_starts_with($line, self::BOLD)) {
                    $line = $esc . 'E' . substr($line, 1) . $esc . 'F';
                }
                $out .= $line . "\r\n";
            }
            $out .= chr(12); // form feed: lempar pas ke perforasi form berikutnya
        }

        return $out;
    }

    /** Kirim ke printer: nama share (\\localhost\LX310TEXT), LPT1, atau PRN. */
    public function printTo(?string $target = null): void
    {
        $target = $target ?: config('app.printer_target', env('PRINTER_LX310_TARGET', '\\\\localhost\\LX310TEXT'));

        $prevHandler = set_error_handler(function ($severity, $message) {
            throw new \ErrorException($message);
        });

        try {
            $bytes = file_put_contents($target, $this->raw());
            if ($bytes === false) {
                throw new \RuntimeException("Gagal menulis data ke printer target: {$target}");
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Tidak dapat menulis ke target printer [{$target}]. Pastikan printer terhubung/dishare. Detail: " . $e->getMessage(), 0, $e);
        } finally {
            restore_error_handler();
        }
    }

    // ------------------------------------------------------------------
    // Layout
    // ------------------------------------------------------------------

    /** @return array<int, array<int, string>> baris per halaman */
    public function pages(): array
    {
        $items = $this->items();

        $head  = count($this->header(1, 1));
        $sum   = count($this->summary());
        $cont  = 3;
        $avail = self::PAGE_LINES - $head;

        $pages = [];
        $cur   = [];
        $used  = 0;
        foreach ($items as $it) {
            $n = count($it);
            if ($cur && $used + $n > $avail - $cont) {
                $pages[] = $cur;
                $cur  = [];
                $used = 0;
            }
            $cur[] = $it;
            $used += $n;
        }
        if ($cur || !$pages) {
            $pages[] = $cur;
        }

        // Halaman terakhir harus muat untuk ringkasan/tanda tangan
        $lastIdx  = count($pages) - 1;
        $lastUsed = array_sum(array_map('count', $pages[$lastIdx]));
        if ($lastUsed + $sum > $avail) {
            if (count($pages[$lastIdx]) > 1) {
                $moved = array_pop($pages[$lastIdx]);
                $pages[] = [$moved];
            } else {
                $pages[] = [];
            }
        }

        $total = count($pages);
        $out   = [];
        foreach ($pages as $i => $blocks) {
            $p     = $i + 1;
            $lines = $this->header($p, $total);
            foreach ($blocks as $block) {
                foreach ($block as $l) {
                    $lines[] = $l;
                }
            }
            $out[] = array_merge($lines, $p === $total ? $this->summary() : $this->cont($p + 1));
        }

        return $out;
    }

    private function header(int $page, int $total): array
    {
        $h = $this->hdr;
        $W = self::W;

        $cust = $this->t(!empty(trim((string) ($h->customer_name ?? ''))) ? $h->customer_name : ($h->fcustno ?? '-'));
        $addr = $this->t(!empty(trim((string) ($h->falamatkirim ?? ''))) ? $h->falamatkirim : ($h->customer_address ?? '-'));
        $sales = $this->t($h->salesman_name ?? ($h->fsalesname ?? '-'));
        $a = $this->wrap($addr, $W - 11);

        return [
            self::BOLD . $this->lr(strtoupper($this->t($this->opt['company_name'] ?? '')), 'FAKTUR PENJUALAN'),
            $this->lr($this->t($this->opt['company_city'] ?? ''), 'No. ' . $this->t($this->opt['no'] ?? '-')),
            $this->sep(),
            'Customer : ' . substr($cust, 0, $W - 11),
            'Alamat   : ' . ($a[0] ?? ''),
            '           ' . ($a[1] ?? ''),
            $this->lr('Tanggal  : ' . $this->t($this->opt['date'] ?? ''), 'Hal: ' . $page . ' / ' . $total),
            'Sales    : ' . substr($sales, 0, $W - 11),
            $this->sep(),
            'No. Nama Produk',
            str_pad('Kode', 10) . str_pad('Qty', 16, ' ', STR_PAD_LEFT)
                . str_pad('@ Harga', 14, ' ', STR_PAD_LEFT) . str_pad('Total Harga', 16, ' ', STR_PAD_LEFT),
            $this->sep(),
        ];
    }

    /** Tiap item = satu blok baris (nama bisa lebih dari 1 baris + 1 baris detail). */
    private function items(): array
    {
        $items = [];
        $no = 1;

        foreach ($this->dt as $r) {
            $name = function_exists('format_product_name')
                ? (format_product_name($r->product_name ?? '', $r->fspecification ?? $r->product_specification ?? '') ?: (trim((string) ($r->fdesc ?? '')) ?: '-'))
                : (trim((string) ($r->product_name ?? ($r->fdesc ?? '-'))));

            $lines = [];
            foreach (preg_split('/\r\n|\r|\n/', (string) $name) as $part) {
                $part = $this->t($part);
                if ($part === '') {
                    continue;
                }
                foreach ($this->wrap($part, self::W - 4) as $w) {
                    $lines[] = (count($lines) === 0 ? str_pad($no . '.', 4) : '    ') . $w;
                }
            }
            if (!$lines) {
                $lines[] = str_pad($no . '.', 4) . '-';
            }

            $qty = $this->n($r->fqty ?? 0) . ' ' . substr($this->t($r->fsatuan ?? ''), 0, 4);
            $lines[] = str_pad(substr($this->t($r->fprdcode ?? '-'), 0, 10), 10)
                . str_pad($qty, 16, ' ', STR_PAD_LEFT)
                . str_pad($this->n($r->fprice ?? 0), 14, ' ', STR_PAD_LEFT)
                . str_pad($this->n($r->famount ?? 0), 16, ' ', STR_PAD_LEFT);

            $items[] = $lines;
            $no++;
        }

        return $items;
    }

    private function summary(): array
    {
        $h    = $this->hdr;
        $qty  = collect($this->dt)->sum(fn ($r) => (float) ($r->fqty ?? 0));
        $qtyK = collect($this->dt)->sum(fn ($r) => (float) ($r->fqtykecil ?? 0));

        $row = fn ($label, $val) => str_pad($label, 13) . ': ' . str_pad($this->n($val), 15, ' ', STR_PAD_LEFT);

        $left = [
            'Tot.Qty : ' . $this->n($qty),
            'Tot.Isi : ' . $this->n($qtyK),
            '',
            'Dibuat Oleh,',
            '',
            '',
            '( ' . substr(strtoupper($this->t($this->opt['signer'] ?? '')), 0, 20) . ' )',
        ];
        $right = [
            $row('Total', (float) ($h->famountgross ?? 0)),
            $row('Discount', (float) ($h->fdiscount ?? 0)),
            $row('Biaya/Charge', (float) ($h->fongkosangkut ?? 0)),
            str_repeat('-', 30),
            $row('G.Total', (float) ($h->famountso ?? 0)),
            '',
            'Dicetak: ' . now()->format('d/m/y H:i'),
        ];

        $lines = [$this->sep()];
        foreach ($left as $i => $l) {
            $lines[] = str_pad($l, 26) . $right[$i];
        }

        return $lines;
    }

    private function cont(int $next): array
    {
        return [
            $this->sep(),
            $this->lr('', 'Bersambung ke halaman ' . $next),
            $this->lr('', 'Dicetak: ' . now()->format('d/m/y H:i')),
        ];
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    private function sep(): string
    {
        return str_repeat('-', self::W);
    }

    private function n($v): string
    {
        $val = (float) $v;
        return (floor($val) == $val)
            ? number_format($val, 0, ',', '.')
            : number_format($val, 2, ',', '.');
    }

    /** ASCII only: printer text mode tidak mengenal karakter khusus. */
    private function t($s): string
    {
        return trim(preg_replace('/[^\x20-\x7E]+/', ' ', Str::ascii((string) $s)));
    }

    private function wrap(string $s, int $w): array
    {
        return explode("\n", wordwrap($s, $w, "\n", true));
    }

    /** Kiri dan kanan dalam satu baris selebar W. */
    private function lr(string $l, string $r): string
    {
        $l = substr($l, 0, max(0, self::W - strlen($r) - 1));
        return $l . str_repeat(' ', max(1, self::W - strlen($l) - strlen($r))) . $r;
    }
}
