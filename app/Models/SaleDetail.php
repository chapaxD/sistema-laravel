<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    protected $table = 'venta_detalle';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_venta', 'id_producto', 'cantidad',
        'precio_unit', 'descuento', 'subtotal', 'descripcion'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unit' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
     // Accessor para compatibilidad
   // En SaleDetail.php, agrega estos accessors:
    public function getPrecioAttribute()
    {
        return $this->precio_unit;
    }

    public function getDescripcionAttribute($value)
    {
        // Si no hay descripción, usar el nombre del producto
        if (empty($value) && $this->product) {
            return $this->product->nombre;
        }
        return $value ?? 'Producto/Servicio';
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'id_venta');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_producto');
    }
}
