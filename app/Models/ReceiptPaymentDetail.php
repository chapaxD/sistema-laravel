<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptPaymentDetail extends Model
{
    protected $table = 'recibo_detalle_pago';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_recibo', 'metodo', 'monto', 'referencia'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'id_recibo');
    }
}
