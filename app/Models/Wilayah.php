<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    use HasFactory;

    protected $table = 'mswilayah';
    protected $primaryKey = 'fwilayahid';
    protected $guarded = ['fwilayahid'];
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    public function scopeSearch($query, $search) {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereAny(['fwilayahcode', 'fwilayahname'], 'like', '%' . $search . '%');
            });
        });
    }
}

