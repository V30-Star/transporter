<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wh extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'mswh';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'fwhid';

    // Guard the 'fwhid' as it is the primary key and should not be mass-assigned
    protected $guarded = ['fwhid'];

    // Define custom timestamps if using custom field names for created_at and updated_at
    const CREATED_AT = 'fcreatedat';
    const UPDATED_AT = 'fupdatedat';

    // Relationship to the Cabang model (assuming it's called Cabang)
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'fbranchcode', 'fcabangkode');
    }
    public function scopeSearch($query, $search)
    {
        return $query->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('fwhcode', 'ILIKE', '%' . $search . '%')
                    ->orWhere('fwhname', 'ILIKE', '%' . $search . '%');
            });
        });
    }
}
