<?php

namespace Tests\Http;

class PengeluaranKasHttpTest extends KasHttpTestCase
{
    protected string $route = 'pengeluarankas';

    protected string $perm = 'PengeluaranKas';

    protected string $tran = 'BKK';

    protected string $headerDk = 'K';

    protected string $detailDk = 'D';
}
