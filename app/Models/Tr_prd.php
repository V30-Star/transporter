<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_prd extends Model
{
  use HasFactory;

  protected $table = 'tr_prd';
  protected $primaryKey = 'fprdid';
  protected $guarded = ['fprdid'];
  public $timestamps = false;


  public function header()
  {
    return $this->belongsTo(Tr_prh::class, 'fprnoid', 'fprid');
  }

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fprnoid', 'fprdcode'], 'like', '%' . $search . '%');
      });
    });
  }
}
