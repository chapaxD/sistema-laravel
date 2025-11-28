<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::withCount('users')
            ->orderBy('id_rol', 'desc')
            ->paginate(15);

        return Inertia::render('UsersModule/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return Inertia::render('UsersModule/Roles/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre',
            'descripcion' => 'nullable|string|max:200',
        ]);

        Rol::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('users-module.roles.index')
            ->with('message', 'Rol creado correctamente')
            ->with('type', 'success');
    }

    public function edit($id)
    {
        $rol = Rol::withCount('users')->findOrFail($id);

        return Inertia::render('UsersModule/Roles/Edit', [
            'rol' => $rol,
        ]);
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre,' . $id . ',id_rol',
            'descripcion' => 'nullable|string|max:200',
        ]);

        $rol->update($request->only(['nombre', 'descripcion']));

        return redirect()->route('users-module.roles.index')
            ->with('message', 'Rol actualizado correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $rol = Rol::withCount('users')->findOrFail($id);

        if ($rol->users_count > 0) {
            return redirect()->route('users-module.roles.index')
                ->with('message', 'No se puede eliminar el rol porque tiene usuarios asociados')
                ->with('type', 'error');
        }

        $rol->delete();

        return redirect()->route('users-module.roles.index')
            ->with('message', 'Rol eliminado correctamente')
            ->with('type', 'success');
    }
}
