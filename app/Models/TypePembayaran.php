<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypePembayaran extends Model
{
    use HasFactory;

    protected $table = 'tbmaster';
    protected $primaryKey = 'fmasterid';
    protected $guarded = ['fmasterid'];
    public $timestamps = false;

    public function account()
    {
        return $this->belongsTo(Account::class, 'fnote1', 'faccount');
    }

    public function getFtblcodeAttribute($value)
    {
        return trim((string) $value);
    }

    public function getFmasternameAttribute($value)
    {
        return trim((string) $value);
    }

    public function getFnote1Attribute($value)
    {
        return trim((string) $value);
    }

    public function getFtypepembayaranidAttribute()
    {
        return $this->fmasterid;
    }

    public function getFtypepembayarankodeAttribute()
    {
        return $this->fmastername;
    }

    public function getFtypepembayarannameAttribute()
    {
        return $this->fmastername;
    }

    public function getFaccountAttribute()
    {
        return $this->fnote1;
    }
}
