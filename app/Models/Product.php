<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'producto';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'descripcion', 'precio_unit', 'stock',
        'id_categoria', 'estado'
    ];

    protected $casts = [
        'precio_unit' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function quotationDetails()
    {
        return $this->hasMany(QuotationDetail::class, 'id_producto');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'id_producto');
    }
}
