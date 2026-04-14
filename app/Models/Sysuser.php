<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sysuser extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasFactory;

    protected $table = 'sysuser';

    protected $primaryKey = 'fuid'; // Make sure this is the correct primary key

    protected $fillable = ['fsysuserid', 'fname', 'password', 'fsalesman', 'fuserlevel', 'fcabang', 'fusercreate'];

    protected $hidden = ['password'];

    public $timestamps = true;

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'fsalesman', 'fsalesmanid');
    }
}
