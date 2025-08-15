<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'msproduct';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fproductid';

    // Guard the 'fproductid' as it is the primary key and should not be mass-assigned
    protected $guarded = ['fproductid'];

    // Define custom timestamps if using custom field names for created_at and updated_at
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    // Scope function to perform search query
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('fproductcode', 'like', '%' . $search . '%')
                    ->orWhere('fproductname', 'like', '%' . $search . '%');
            });
        });
    }
}
