<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesman extends Model
{
    use HasFactory;

    protected $table = 'mssalesman';
    protected $primaryKey = 'fsalesmanid';
    protected $guarded = ['fsalesmanid'];
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    public function scopeSearch($query, $search) {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereAny(['fsalesmanid', 'fsalesmanname'], 'like', '%' . $search . '%');
            });
        });
    }
}

