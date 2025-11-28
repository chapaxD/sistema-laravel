<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('rol')->orderBy('id_usuario', 'desc');

        // Búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Buscar por ID
                $q->where('id_usuario', 'LIKE', "%{$search}%")
                  // Buscar por nombre o apellido
                  ->orWhere('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('apellido', 'ILIKE', "%{$search}%")
                  // Buscar por email
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  // Buscar por teléfono
                  ->orWhere('telefono', 'LIKE', "%{$search}%")
                  // Buscar por nombre del rol
                  ->orWhereHas('rol', function($q) use ($search) {
                      $q->where('nombre', 'ILIKE', "%{$search}%");
                  })
                  // Buscar por estado
                  ->orWhere('estado', 'ILIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        return Inertia::render('UsersModule/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        $roles = Rol::all();

        return Inertia::render('UsersModule/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuario,email',
            'password' => 'required|min:6',
            'telefono' => 'nullable|string|max:20',
            'id_rol' => 'required|exists:rol,id_rol',
        ]);

        User::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'telefono' => $request->telefono,
            'id_rol' => $request->id_rol,
            'estado' => 'ACTIVO',
        ]);

        return redirect()->route('users-module.users.index')
            ->with('message', 'Usuario creado correctamente')
            ->with('type', 'success');
    }

    public function edit($id)
    {
        $user = User::with('rol')->findOrFail($id);
        $roles = Rol::all();

        return Inertia::render('UsersModule/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuario,email,' . $id . ',id_usuario',
            'password' => 'nullable|min:6',
            'telefono' => 'nullable|string|max:20',
            'id_rol' => 'required|exists:rol,id_rol',
            'estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        $data = $request->only(['nombre', 'apellido', 'email', 'telefono', 'id_rol', 'estado']);

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users-module.users.index')
            ->with('message', 'Usuario actualizado correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users-module.users.index')
            ->with('message', 'Usuario eliminado correctamente')
            ->with('type', 'success');
    }
}
