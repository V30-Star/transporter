<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trandt extends Model
{
  use HasFactory;

  protected $table = 'trandt';
  protected $primaryKey = 'ftrandtid'; // Tambahkan 'f' di depan
  protected $guarded = ['ftrandtid'];

  public $timestamps = false;

  public function header()
  {
    return $this->belongsTo(Tranmt::class, 'ftranmtid', 'ftranmtid');
  }
}
