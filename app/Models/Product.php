<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'msprd';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fprdid';

    // Guard the 'fprdid' as it is the primary key and should not be mass-assigned
    protected $guarded = ['fprdid'];

    // Define custom timestamps if using custom field names for created_at and updated_at
    public $timestamps = false;

    // Scope function to perform search query
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('fprdcode', 'like', '%' . $search . '%')
                    ->orWhere('fprdname', 'like', '%' . $search . '%');
            });
        });
    }
    public function trPods()
    {
        return $this->hasMany(Tr_pod::class, 'fprdcode', 'fprdid');
    }

    public function trPrds()
    {
        return $this->hasMany(Tr_prd::class, 'fprdcode', 'fprdid');
    }
    public function trstockdts()
    {
        return $this->hasMany(PenerimaanPembelianDetail::class, 'fprdcode', 'fprdid');
    }

    public function merek()
    {
        // fmerek adalah foreign key lokal di tabel products (msprd)
        // 'Merek::class' adalah model tujuan (msmerek)
        // 'id' adalah primary key di tabel merek (msmerek)
        return $this->belongsTo(Merek::class, 'fmerek', 'fmerekid');
    }
}
