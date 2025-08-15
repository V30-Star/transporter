<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Groupcustomer extends Model
{
    use HasFactory;

    protected $table = 'msgroupcustomer';
    protected $primaryKey = 'fgroupid';
    protected $guarded = ['fgroupid'];
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    // Scope untuk pencarian
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('fgroupid', 'like', '%' . $search . '%')
                      ->orWhere('fgroupcode', 'like', '%' . $search . '%')
                      ->orWhere('fgroupname', 'like', '%' . $search . '%');
            });
        });
    }
}
