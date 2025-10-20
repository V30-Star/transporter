<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_prh extends Model
{
  use HasFactory;

  protected $table = 'tr_prh';
  protected $primaryKey = 'fprid';
  protected $guarded = ['fprid'];
  const CREATED_AT = 'fcreatedat';
  const UPDATED_AT = 'fupdatedat';

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fprno', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }

  public function details()
  {
    return $this->hasMany(Tr_prd::class, 'fprnoid', 'fprid');
  }
}
