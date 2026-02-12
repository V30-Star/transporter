<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merek extends Model
{
    use HasFactory;

    protected $table = 'msmerek';
    protected $primaryKey = 'fmerekid';
    protected $guarded = ['fmerekid'];
    public $timestamps = false;
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereAny(['fmerekid', 'fmerekcode', 'fmerekname'], 'like', '%' . $search . '%');
            });
        });
    }
}
