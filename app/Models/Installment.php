<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    protected $table = 'cuota';
    protected $primaryKey = 'id_cuota';
    public $timestamps = false;

    protected $fillable = [
        'id_plan', 'numero_cuota', 'monto',
        'fecha_vencimiento', 'fecha_pago', 'estado'
    ];

    protected $casts = [
        'numero_cuota' => 'integer',
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
    ];

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class, 'id_plan');
    }
}
