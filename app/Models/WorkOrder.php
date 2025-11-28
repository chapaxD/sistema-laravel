<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $table = 'ordentrabajo';
    protected $primaryKey = 'id_orden';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion', 'descripcion', 'fecha_programada',
        'fecha_inicio', 'fecha_fin', 'estado'
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_cotizacion');
    }

    public function technicians()
    {
        return $this->belongsToMany(
            User::class,
            'orden_trabajo_tecnico',
            'id_orden',
            'id_tecnico'
        );
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'id_orden');
    }
}
