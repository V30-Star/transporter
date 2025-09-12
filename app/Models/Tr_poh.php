<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tr_poh extends Model
{
  use HasFactory;

  protected $table = 'tr_poh';
  protected $primaryKey = 'fpono';
  protected $guarded = ['fpono'];
  const CREATED_AT = 'fdatetime';
  // const UPDATED_AT = 'fupdatedat';

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fpono', 'fprdin'], 'like', '%' . $search . '%');
      });
    });
  }
}
