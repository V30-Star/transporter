<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_poh extends Model
{
  use HasFactory;

  protected $table = 'tr_poh';
  protected $primaryKey = 'fpohdid';
  protected $guarded = ['fpohdid'];
  const CREATED_AT = 'fdatetime';
  const UPDATED_AT = 'fupdatedat';

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fpohdid', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }
  public function details()
  {
    return $this->hasMany(Tr_pod::class, 'fpono', 'fpono');
  }
}
