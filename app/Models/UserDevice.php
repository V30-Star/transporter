<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $table = 'user_device';
    protected $primaryKey = 'fdeviceid';
    public $timestamps = false;
    protected $guarded = ['fdeviceid'];
}
