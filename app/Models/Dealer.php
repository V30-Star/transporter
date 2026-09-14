<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    use HasFactory;

    protected $table = 'msdealer';
    protected $primaryKey = 'fdealerid';
    protected $guarded = ['fdealerid'];
    public $timestamps = false;

    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('fdealerid', 'like', '%' . $search . '%')
                    ->orWhere('fdealercode', 'like', '%' . $search . '%')
                    ->orWhere('fdealername', 'like', '%' . $search . '%');
            });
        });
    }
}
