<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trkasmt extends Model
{
    use HasFactory;

    protected $table = 'trkasmt';
    protected $primaryKey = 'fkasmtid';
    protected $guarded = [];
    public $timestamps = false;
    public $incrementing = false;

    protected $casts = [
        'fkasmtid' => 'integer',
        'fkasmtdate' => 'date',
        'ftgljatuhtempo' => 'date',
        'ftglcair' => 'date',
        'fdatetime' => 'datetime',
        'famountpay' => 'decimal:2',
        'famountpay_rp' => 'decimal:2',
        'frate' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(Trkasdt::class, 'fkasmtid', 'fkasmtid')->orderBy('fnou');
    }

    public function headerAccount()
    {
        return $this->belongsTo(Account::class, 'faccountheader', 'faccount');
    }
}
