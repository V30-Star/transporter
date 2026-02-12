<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_pod extends Model
{
  use HasFactory;

  protected $table = 'tr_pod';
  protected $primaryKey = 'fpodid';
  protected $guarded = ['fpodid'];
  public $timestamps = false;

  public function header()
  {
    return $this->belongsTo(Tr_poh::class, 'fpono', 'fpohdid');
  }

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fpodid', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }
}
