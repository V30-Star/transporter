<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelianDetail extends Model
{
  use HasFactory;

  protected $table = 'trstockdt';
  protected $primaryKey = 'fstockdtid';
  protected $guarded = ['fstockdtid'];
  public $timestamps = false;

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fstockdtid', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }
  public function header()
  {
    return $this->belongsTo(PenerimaanPembelianHeader::class, 'fstockmtno', 'fstockmtno');
  }
  public function account()
  {
    return $this->belongsTo(Account::class, 'frefdtno', 'faccount');
  }

  public function subaccount()
  {
    return $this->belongsTo(Subaccount::class, 'frefso', 'fsubaccountcode');
  }
}
