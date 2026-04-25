<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trkasdt extends Model
{
    use HasFactory;

    protected $table = 'trkasdt';
    protected $primaryKey = 'fkasdtid';
    protected $guarded = [];
    public $timestamps = false;
    public $incrementing = false;

    protected $casts = [
        'fkasdtid' => 'integer',
        'fkasmtid' => 'integer',
        'fdatetime' => 'datetime',
        'fkasdtvalue' => 'decimal:2',
        'fvalue_rp' => 'decimal:2',
        'fjurnal' => 'decimal:2',
        'fjurnal_rp' => 'decimal:2',
    ];

    public function header()
    {
        return $this->belongsTo(Trkasmt::class, 'fkasmtid', 'fkasmtid');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'faccount', 'faccount');
    }
}
