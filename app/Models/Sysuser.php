<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class Sysuser extends Model implements Authenticatable
{
    use HasFactory, AuthenticatableTrait;

    protected $table = 'sysuser';
    protected $primaryKey = 'fuid'; // Make sure this is the correct primary key

    protected $fillable = ['fsysuserid', 'fname', 'password', 'fsalesman', 'fuserlevel', 'fcabang', 'fuserid'];

    protected $hidden = ['password'];
    public $timestamps = true;
}
