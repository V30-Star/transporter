<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogUser extends Model
{
  protected $table = 'log_user';
  protected $primaryKey = 'floguserid';
  public $timestamps = false;
  protected $fillable = [
    'ip',
    'akun',
    'komp',
    'login_date',
    'log_out_date',
  ];
}
