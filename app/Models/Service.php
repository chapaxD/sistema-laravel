<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'servicio';
    protected $primaryKey = 'id_servicio';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'descripcion', 'precio_base', 'estado'
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
    ];

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'id_servicio');
    }
}
