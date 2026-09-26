<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    use HasFactory;

    protected $table = 'barcode';

    protected $guarded = ['id'];

    protected $casts = [
        'label_width' => 'float',
        'label_height' => 'float',
        'columns' => 'integer',
        'gap_x' => 'float',
        'gap_y' => 'float',
        'barcode_height' => 'integer',
        'font_size' => 'float',
        'show_company' => 'boolean',
        'show_name' => 'boolean',
        'show_code' => 'boolean',
        'show_price' => 'boolean',
    ];
}
