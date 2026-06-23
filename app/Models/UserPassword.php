<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserPassword extends Model
{
    protected $table    = 'userpassword';
    protected $fillable = ['store_id', 'system', 'username', 'password', 'note'];

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'ID');
    }
}