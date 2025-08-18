<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'msgudang';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fgudangid';

    // Guard the 'fgudangid' as it is the primary key and should not be mass-assigned
    protected $guarded = ['fgudangid'];

    // Define custom timestamps if using custom field names for created_at and updated_at
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    // Relationship to the Cabang model (assuming it's called Cabang)
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'fcabangkode', 'fcabangkode');
    }
    public function scopeSearch($query, $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('fgudangcode', 'ILIKE', '%' . $search . '%')
                    ->orWhere('fgudangname', 'ILIKE', '%' . $search . '%');
            });
        });
    }
}
