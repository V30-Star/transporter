<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subaccount extends Model
{
    use HasFactory;

    protected $table = 'mssubaccount';
    protected $primaryKey = 'fsubaccountid';
    protected $guarded = ['fsubaccountid']; // Prevent mass-assignment for the primary key
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    // Define a scope for searching
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('fsubaccountid', 'like', '%' . $search . '%')
                    ->orWhere('fsubaccountcode', 'like', '%' . $search . '%')
                    ->orWhere('fsubaccountname', 'like', '%' . $search . '%');
            });
        });
    }
}
