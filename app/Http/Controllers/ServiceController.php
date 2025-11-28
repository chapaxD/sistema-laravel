<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Service::orderBy('id_servicio', 'desc');

        if ($search) {
            $query->where('nombre', 'ILIKE', "%$search%")
                ->orWhere('descripcion', 'ILIKE', "%$search%");
        }

        $services = $query->paginate(15);

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => [
                'search' => $search
            ]
        ]);
    }



    public function create()
    {
        return Inertia::render('Services/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'precio_base' => 'required|numeric|min:0',
        ]);

        Service::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio_base' => $request->precio_base,
            'estado' => 'ACTIVO',
        ]);

        return redirect()->route('services.index')
            ->with('message', 'Servicio creado correctamente')
            ->with('type', 'success');
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);

        return Inertia::render('Services/Edit', [
            'service' => $service,
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'precio_base' => 'required|numeric|min:0',
            'estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        $service->update($request->only([
            'nombre', 'descripcion', 'precio_base', 'estado'
        ]));

        return redirect()->route('services.index')
            ->with('message', 'Servicio actualizado correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->route('services.index')
            ->with('message', 'Servicio eliminado correctamente')
            ->with('type', 'success');
    }
}
