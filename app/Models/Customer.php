<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'mscustomer';

    // Define the primary key column
    protected $primaryKey = 'fcustomerid';

    // Specify the columns that should not be mass assignable
    protected $guarded = ['fcustomerid'];

    // Define the custom created and updated timestamp columns
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    // Scope method for searching
    public function scopeSearch($query, $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('fcustomercode', 'ILIKE', '%'.$search.'%')
                      ->orWhere('fcustomername', 'ILIKE', '%'.$search.'%');
            });
        });
    }
}
