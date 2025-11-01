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
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

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
        // (Model Tujuan, Foreign Key di tr_pod, Local Key di product)
        return $this->hasMany(Tr_pod::class, 'fprdcode', 'fprdid');
    }

   public function trPrds()
    {
        // !!! PERBAIKAN: Modelnya harus Tr_prd::class, BUKAN Tr_pod::class
        // Asumsi foreign key-nya sama ('fprdcode')
        return $this->hasMany(Tr_prd::class, 'fprdcode', 'fprdid');
    }
    public function trstockdts()
    {
        // Asumsi nama modelnya Trstockdt dan foreign key-nya 'fprdcode'
        return $this->hasMany(PenerimaanPembelianDetail::class, 'fprdcode', 'fprdid');
    }
}
