<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categoria';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    public function products()
    {
        return $this->hasMany(Product::class, 'id_categoria');
    }
}
