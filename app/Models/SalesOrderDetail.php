<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
  use HasFactory;

  protected $table = 'trsodt';
  protected $primaryKey = 'ftrsodtid'; // Tambahkan 'f' di depan
  protected $guarded = ['ftrsodtid'];

  public $timestamps = false;

  public function header()
  {
    return $this->belongsTo(SalesOrderHeader::class, 'ftrsomtid', 'ftrsomtid');
  }
}
