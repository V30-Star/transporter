<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_pod extends Model
{
  use HasFactory;

  protected $table = 'tr_pod';
  protected $primaryKey = 'fpodid';
  protected $guarded = ['fpono'];
  const CREATED_AT = 'fdatetime';
  // const UPDATED_AT = 'fupdatedat';

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
    return $this->hasMany(Tr_poh::class, 'fpono', 'fpono');
  }
}
