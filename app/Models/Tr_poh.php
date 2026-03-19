<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_poh extends Model
{
  use HasFactory;

  protected $table = 'tr_poh';
  protected $primaryKey = 'fpohid';
  protected $guarded = ['fpohid'];
  public $timestamps = false;


  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fpohid', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }
  public function details()
  {
    return $this->hasMany(Tr_pod::class, 'fpohid', 'fpohid');
  }
  public function supplier()
  {
    return $this->belongsTo(Supplier::class, 'fsupplier', 'fsupplierid');
  }
}
