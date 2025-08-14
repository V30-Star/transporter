<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'mscabang';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fcabangid';

    // Disable timestamps if not used
    public $timestamps = false;
}
