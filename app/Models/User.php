<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    
    protected $fillable = [
        'nombre', 'apellido', 'email', 'password_hash', 
        'telefono', 'estado', 'id_rol'
    ];

    protected $hidden = [
        'password_hash', 'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'id_cliente');
    }
}
