<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    protected $table = 'cotizacion_detalle';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion', 'id_producto', 'cantidad',
        'precio_unit', 'descuento', 'subtotal', 'descripcion'
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_unit' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_cotizacion');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_producto');
    }
}
