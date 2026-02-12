<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Groupproduct extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'ms_groupprd';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fgroupid';

    // Guard the 'fgroupid' as it is the primary key and should not be mass-assigned
    protected $guarded = ['fgroupid'];

    // Define custom timestamps if using custom field names for created_at and updated_at
    public $timestamps = false;

    // Scope function to perform search query
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
