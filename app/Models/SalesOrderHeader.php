<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderHeader extends Model
{
  use HasFactory;

  protected $table = 'trsomt';
  protected $primaryKey = 'ftrsomtid'; // Sesuaikan dengan DB (S bukan O)
  protected $guarded = ['ftrsomtid'];

  // Jika tidak ada kolom fupdatedat di DB, matikan timestamps atau ganti kolomnya
  public $timestamps = false;

  public function scopeSearch($query, $search)
  {
    return $query->when($search, function ($query, $search) {
      $query->where('fsono', 'like', '%' . $search . '%')
        ->orWhere('fcustno', 'like', '%' . $search . '%');
    });
  }

  public function details()
  {
    return $this->hasMany(SalesOrderDetail::class, 'ftrsomtid', 'ftrsomtid');
  }
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'fcustno', 'fcustomerid');
  }
}
