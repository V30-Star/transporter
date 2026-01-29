<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tranmt extends Model
{
  use HasFactory;

  protected $table = 'tranmt';
  protected $primaryKey = 'ftranmtid'; // Sesuaikan dengan DB (S bukan O)
  protected $guarded = ['ftranmtid'];

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
    // Parameter 2: Foreign key di tabel trandt
    // Parameter 3: Local key di tabel tranmt
    return $this->hasMany(Trandt::class, 'fsono', 'fsono');
  }
  public function customer()
  {
    return $this->belongsTo(Customer::class, 'fcustno', 'fcustomerid');
  }
}
