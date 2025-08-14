<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    use HasFactory;

    protected $table = 'msrekening';  // Correct table name in lowercase
    protected $primaryKey = 'frekeningid';  // The primary key of the table
    protected $guarded = ['frekeningid'];  // Fields that are not mass assignable
    const CREATED_AT = 'fcreatedat';  // Custom created_at field
    const UPDATED_AT = 'fupdatedat';  // Custom updated_at field

    // Scope function to search records
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('frekeningcode', 'like', '%' . $search . '%')
                      ->orWhere('frekeningname', 'like', '%' . $search . '%');
            });
        });
    }
}
