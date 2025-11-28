<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int)$request->input('per_page', 15);
        if ($perPage == -1) {
            $perPage = 9999;
        }
        $categories = Categoria::orderBy('id_categoria', 'desc')
            ->paginate($perPage);

        return Inertia::render('Inventory/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventory/Categories/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:80|unique:categoria,nombre',
            'descripcion' => 'nullable|string|max:200',
        ]);

        Categoria::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('inventory.categories.index')
            ->with('message', 'Categoría creada correctamente')
            ->with('type', 'success');
    }

    public function edit($id)
    {
        $categoria = Categoria::withCount('products')->findOrFail($id);

        return Inertia::render('Inventory/Categories/Edit', [
            'categoria' => $categoria,
        ]);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:80|unique:categoria,nombre,' . $id . ',id_categoria',
            'descripcion' => 'nullable|string|max:200',
        ]);

        $categoria->update($request->only(['nombre', 'descripcion']));

        return redirect()->route('inventory.categories.index')
            ->with('message', 'Categoría actualizada correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $categoria = Categoria::withCount('products')->findOrFail($id);

        if ($categoria->products_count > 0) {
            return redirect()->route('inventory.categories.index')
                ->with('message', 'No se puede eliminar la categoría porque tiene productos asociados')
                ->with('type', 'error');
        }

        $categoria->delete();

        return redirect()->route('inventory.categories.index')
            ->with('message', 'Categoría eliminada correctamente')
            ->with('type', 'success');
    }
}

