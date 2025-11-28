<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $table = 'recibo';
    protected $primaryKey = 'id_recibo';
    public $timestamps = false;

    protected $fillable = [
        'id_venta', 'fecha', 'total', 'observacion'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
    ];

    // Accessors para compatibilidad con vistas
    public function getFechaEmisionAttribute()
    {
        return $this->fecha;
    }

    public function getMontoTotalAttribute()
    {
        return $this->total;
    }

    public function getNumeroReciboAttribute()
    {
        return 'REC-' . str_pad($this->id_recibo, 6, '0', STR_PAD_LEFT);
    }

    public function getEstadoAttribute()
    {
        // No hay estado en la BD, retornamos un valor por defecto
        return 'EMITIDO';
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'id_venta');
    }

    public function paymentDetails()
    {
        return $this->hasMany(ReceiptPaymentDetail::class, 'id_recibo');
    }
}
