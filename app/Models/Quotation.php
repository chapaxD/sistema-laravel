<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'cotizacion';
    protected $primaryKey = 'id_cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente', 'id_servicio', 'fecha_creacion',
        'subtotal', 'descuento', 'total', 'estado'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'id_cliente');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_servicio');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'id_cotizacion');
    }

    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class, 'id_cotizacion');
    }
}
