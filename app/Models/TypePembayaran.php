<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypePembayaran extends Model
{
    use HasFactory;

    protected $table = 'msttypepembayaran';
    protected $primaryKey = 'ftypepembayaranid';
    protected $guarded = ['ftypepembayaranid'];
    public $timestamps = false;

    public function account()
    {
        return $this->belongsTo(Account::class, 'faccount', 'faccount');
    }
}
