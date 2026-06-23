<?php
// app/Models/SystemType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemType extends Model
{
    protected $table    = 'systems';
    protected $fillable = ['name', 'color'];
}