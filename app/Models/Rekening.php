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
    public $timestamps = false;

    public function getFrekeningnameAttribute($value)
    {
        return decrypt_value($value);
    }

    public function setFrekeningnameAttribute($value)
    {
        $this->attributes['frekeningname'] = ($value !== null && $value !== '')
            ? \Illuminate\Support\Facades\Crypt::encryptString(strtoupper(trim($value)))
            : $value;
    }

    // Scope function to search records
    public function scopeSearch($query, $search)
    {
        $query->when($search ?? false, function ($query, $search) {
            $query->where('frekeningcode', 'like', '%' . $search . '%');
        });
    }
}
