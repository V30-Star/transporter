<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
  use HasFactory;

  protected $table = 'mscurrency';  // Correct table name in lowercase
  protected $primaryKey = 'fcurrid';  // The primary key of the table
  protected $guarded = ['fcurrid'];  // Fields that are not mass assignable

  public $timestamps = false;

  // Scope function to search records
  public function scopeSearch($query, $search)
  {
    $query->when($search ?? false, function ($query, $search) {
      $query->where(function ($query) use ($search) {
        $query->where('fcurrcode', 'like', '%' . $search . '%')
          ->orWhere('fcurrname', 'like', '%' . $search . '%');
      });
    });
  }
}
