<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelianHeader extends Model
{
  use HasFactory;

  protected $table = 'trstockmt';
  protected $primaryKey = 'fstockmtid';
  protected $guarded = ['fstockmtid'];
  public $timestamps = false;

  protected $casts = [
    'fstockmtid' => 'integer',
    'fstockmtdate' => 'date',
  ];

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fstockmtid', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }
  public function details()
  {
    return $this->hasMany(PenerimaanPembelianDetail::class, 'fstockmtid', 'fstockmtid');
  }
}
