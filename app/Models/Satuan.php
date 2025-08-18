<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    use HasFactory;

    protected $table = 'mssatuan';
    protected $primaryKey = 'fsatuanid';
    protected $guarded = ['fsatuanid'];
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereAny(['fsatuanid', 'fsatuancode', 'fsatuanname'], 'like', '%' . $search . '%');
            });
        });
    }
}
