<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('categoria')->orderBy('id_producto', 'desc');

        // Búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Buscar por ID
                $q->where('id_producto', 'LIKE', "%{$search}%")
                  // Buscar por nombre
                  ->orWhere('nombre', 'ILIKE', "%{$search}%")
                  // Buscar por descripción
                  ->orWhere('descripcion', 'ILIKE', "%{$search}%")
                  // Buscar por categoría
                  ->orWhereHas('categoria', function($q) use ($search) {
                      $q->where('nombre', 'ILIKE', "%{$search}%");
                  })
                  // Buscar por estado
                  ->orWhere('estado', 'ILIKE', "%{$search}%");
            });
        }

        $products = $query->paginate(15);

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        $categorias = Categoria::all();

        return Inertia::render('Inventory/Products/Create', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'precio_unit' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'id_categoria' => 'required|exists:categoria,id_categoria',
        ]);

        Product::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio_unit' => $request->precio_unit,
            'stock' => $request->stock,
            'id_categoria' => $request->id_categoria,
            'estado' => 'ACTIVO',
        ]);

        return redirect()->route('inventory.products.index')
            ->with('message', 'Producto creado correctamente')
            ->with('type', 'success');
    }

    public function edit($id)
    {
        $product = Product::with('categoria')->findOrFail($id);
        $categorias = Categoria::all();

        return Inertia::render('Inventory/Products/Edit', [
            'product' => $product,
            'categorias' => $categorias,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'precio_unit' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'id_categoria' => 'required|exists:categoria,id_categoria',
            'estado' => 'required|in:ACTIVO,AGOTADO,DESCONTINUADO',
        ]);

        $product->update($request->only([
            'nombre', 'descripcion', 'precio_unit', 'stock', 'id_categoria', 'estado'
        ]));

        return redirect()->route('inventory.products.index')
            ->with('message', 'Producto actualizado correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('inventory.products.index')
            ->with('message', 'Producto eliminado correctamente')
            ->with('type', 'success');
    }
}
