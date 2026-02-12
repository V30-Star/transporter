<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'mssupplier';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fsupplierid';

    // Guard the 'fsupplierid' as it is the primary key and should not be mass-assigned
    protected $guarded = ['fsupplierid'];

    // Define custom timestamps if using custom field names for created_at and updated_at
    public $timestamps = false;

    // Scope function to perform search query
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('fsuppliercode', 'like', '%' . $search . '%')
                    ->orWhere('fsuppliername', 'like', '%' . $search . '%');
            });
        });
    }
}
