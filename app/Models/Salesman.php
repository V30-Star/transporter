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
    public $timestamps = false;

    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereAny(['fsalesmancode', 'fsalesmanname'], 'like', '%' . $search . '%');
            });
        });
    }
}
