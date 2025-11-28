<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    protected $table = 'plan_pago';
    protected $primaryKey = 'id_plan';
    public $timestamps = false;

    protected $fillable = [
        'id_venta', 'monto_total', 'cantidad_cuotas',
        'fecha_inicio', 'tipo_pago', 'monto_adelanto', 'observacion'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'cantidad_cuotas' => 'integer',
        'monto_adelanto' => 'decimal:2',
        'fecha_inicio' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'id_venta');
    }

    public function installments()
    {
        return $this->hasMany(Installment::class, 'id_plan');
    }
}
