<?php

return [
    'sales_order' => [
        'stage1' => env('APPROVALEMAILSO1'),
        'stage2' => env('APPROVALEMAILSO2'),
    ],
    'invoice' => [
        'stage1' => env('APPROVALEMAILFAKTURPENJUALAN1'),
        'stage2' => env('APPROVALEMAILFAKTURPENJUALAN2'),
    ],
    'purchase_request' => [
        'stage1' => env('APPROVALEMAILPR1'),
        'stage2' => env('APPROVALEMAILPR2'),
    ],
    'purchase_order' => [
        'stage1' => env('APPROVALEMAILPO1'),
        'stage2' => env('APPROVALEMAILPO2'),
    ],
    'product' => [
        'stage1' => env('APPROVALEMAILPRODUK1'),
        'stage2' => env('APPROVALEMAILPRODUK2'),
    ],
];
