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
  const CREATED_AT = 'fcreatedat';
  const UPDATED_AT = 'fupdatedat';

  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->whereAny(['fprnoid', 'fprdcode'], 'like', '%' . $search . '%');
      });
    });
  }
}
