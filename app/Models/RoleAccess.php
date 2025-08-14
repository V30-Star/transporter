<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleAccess extends Model
{
    use HasFactory;

    protected $table = 'msroleaccess';  // Correct table name in lowercase
    protected $primaryKey = 'froleaccessid';  // The primary key of the table
    protected $guarded = ['froleaccessid'];  // Fields that are not mass assignable
}
