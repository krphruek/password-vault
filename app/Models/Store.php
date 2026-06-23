<?php
// database/migrations/2024_01_01_000002_create_stores_table.php
namespace App\Models;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $primaryKey = 'ID';
    protected $keyType    = 'string';
    public    $incrementing = false;
 
    protected $fillable = [
        'ID', 'Name', 'BU', 'RM', 'AM',
        'ช่องทางการขาย', 'BM', 'Mobile',
        'address', 'Mail', 'am_user_id',
    ];
 
    public function amUser()
    {
        return $this->belongsTo(User::class, 'am_user_id', 'id');
    }
 
    public function credentials()
    {
        return $this->hasMany(UserPassword::class, 'store_id', 'ID');
    }
    
}