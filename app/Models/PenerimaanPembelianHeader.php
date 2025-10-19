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
  const CREATED_AT = 'fdatetime';
  const UPDATED_AT = 'fupdatedat';

  protected $casts = [
    'fstockmtid' => 'string',
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
    return $this->hasMany(PenerimaanPembelianDetail::class, 'fstockmtno', 'fstockmtid');
  }
}
