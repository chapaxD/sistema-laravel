<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'venta';
    protected $primaryKey = 'id_venta';
    public $timestamps = false;

    protected $fillable = [
        'id_orden', 'id_cliente', 'fecha_venta',
        'subtotal', 'descuento', 'total', 'estado'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'id_orden');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'id_cliente');
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class, 'id_venta');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'id_venta');
    }

    public function paymentPlan()
    {
        return $this->hasOne(PaymentPlan::class, 'id_venta');
    }
}
