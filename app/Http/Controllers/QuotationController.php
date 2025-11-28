<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\User;
use App\Models\Service;
use App\Models\Product;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $query = Quotation::orderBy('id_cotizacion', 'desc');

        // Filtrar según el rol
        if ($role === 'Vendedor') {
            $query->where('id_vendedor', $user->id_usuario);
        } elseif ($role === 'Cliente') {
            $query->where('id_cliente', $user->id_usuario);
        } elseif ($role === 'Técnico') {
            $query->whereRaw('1 = 0');
        }

        // Búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Buscar por ID de cotización
                $q->where('id_cotizacion', 'LIKE', "%{$search}%")
                // Buscar por nombre o apellido del cliente
                ->orWhereHas('client', function($q) use ($search) {
                    $q->where('nombre', 'ILIKE', "%{$search}%")
                        ->orWhere('apellido', 'ILIKE', "%{$search}%");
                })
                // Buscar por nombre del servicio
                ->orWhereHas('service', function($q) use ($search) {
                    $q->where('nombre', 'ILIKE', "%{$search}%");
                })
                // Buscar por estado
                ->orWhere('estado', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);

        if ($perPage == -1) {
            $quotations = $query->paginate(9999);
        } else {
            $quotations = $query->paginate($perPage);
        }

        // MUY IMPORTANTE → cargar relaciones DESPUÉS de paginar
        $quotations->load(['client', 'service', 'details.product']);

        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations,
        ]);
    }


    public function create()
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $clientsQuery = User::whereHas('rol', function($q) {
            $q->where('nombre', 'Cliente');
        });

        // Si es cliente, solo se ve a sí mismo
        if ($role === 'Cliente') {
            $clientsQuery->where('id_usuario', $user->id_usuario);
        }

        $clients = $clientsQuery->get();
        
        $services = Service::all();
        $products = Product::where('estado', 'ACTIVO')->get();

        return Inertia::render('Quotations/Create', [
            'clients' => $clients,
            'services' => $services,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        // Validación básica
        $rules = [
            'id_servicio' => 'required|exists:servicio,id_servicio',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:producto,id_producto',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ];

        // Si no es cliente, debe especificar el cliente
        if ($role !== 'Cliente') {
            $rules['id_cliente'] = 'required|exists:usuario,id_usuario';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            // Determinar IDs
            $idCliente = $request->id_cliente;
            $idVendedor = null;

            if ($role === 'Cliente') {
                $idCliente = $user->id_usuario; // Forzar ID del cliente logueado
            } elseif ($role === 'Vendedor') {
                $idVendedor = $user->id_usuario; // Forzar ID del vendedor logueado
            } else {
                // Admin/Secretaria: id_vendedor queda null o se podría asignar el usuario actual si se desea registrar quién lo creó
                $idVendedor = $user->id_usuario; 
            }

            // Calcular monto total
            $montoTotal = 0;
            foreach ($request->detalles as $detalle) {
                $montoTotal += $detalle['cantidad'] * $detalle['precio_unitario'];
            }

            // Crear cotización
            $quotation = Quotation::create([
                'id_cliente' => $idCliente,
                'id_vendedor' => $idVendedor,
                'id_servicio' => $request->id_servicio,
                'fecha_creacion' => now(),
                'subtotal' => $montoTotal,
                'descuento' => 0,
                'total' => $montoTotal,
                'estado' => 'PENDIENTE',
            ]);

            // Crear detalles
            foreach ($request->detalles as $detalle) {
                QuotationDetail::create([
                    'id_cotizacion' => $quotation->id_cotizacion,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unit' => $detalle['precio_unitario'],
                    'descuento' => 0,
                    'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                ]);
            }

            DB::commit();

            return redirect()->route('quotations.index')
                ->with('message', 'Cotización creada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear la cotización: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $quotation = Quotation::with(['client', 'service', 'details.product', 'workOrder'])
            ->findOrFail($id);

        // Verificar permisos de visualización
        if ($role === 'Vendedor' && $quotation->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }
        if ($role === 'Cliente' && $quotation->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }

        return Inertia::render('Quotations/Show', [
            'quotation' => $quotation,
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $quotation = Quotation::with(['client', 'service', 'details.product'])
            ->findOrFail($id);
        
        // Verificar permisos de edición
        if ($role === 'Vendedor' && $quotation->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para editar esta cotización');
        }
        if ($role === 'Cliente') {
            // Cliente puede editar si es su cotización y está PENDIENTE (opcional, pero buena práctica)
            if ($quotation->id_cliente !== $user->id_usuario) {
                abort(403, 'No tienes permiso para editar esta cotización');
            }
        }
        
        $clientsQuery = User::whereHas('rol', function($q) {
            $q->where('nombre', 'Cliente');
        });

        if ($role === 'Cliente') {
            $clientsQuery->where('id_usuario', $user->id_usuario);
        }

        $clients = $clientsQuery->get();
        
        $services = Service::all();
        $products = Product::where('estado', 'ACTIVO')->get();

        return Inertia::render('Quotations/Edit', [
            'quotation' => $quotation,
            'clients' => $clients,
            'services' => $services,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);
        
        // Verificar permisos
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        if ($role === 'Vendedor' && $quotation->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para actualizar esta cotización');
        }
        if ($role === 'Cliente' && $quotation->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para actualizar esta cotización');
        }

        $rules = [
            'id_servicio' => 'required|exists:servicio,id_servicio',
            'estado' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:producto,id_producto',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ];

        if ($role !== 'Cliente') {
            $rules['id_cliente'] = 'required|exists:usuario,id_usuario';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            // Determinar ID Cliente
            $idCliente = $request->id_cliente;
            if ($role === 'Cliente') {
                $idCliente = $user->id_usuario; // Mantener ID del cliente
            }

            // Calcular monto total
            $montoTotal = 0;
            foreach ($request->detalles as $detalle) {
                $montoTotal += $detalle['cantidad'] * $detalle['precio_unitario'];
            }

            // Actualizar cotización
            $quotation->update([
                'id_cliente' => $idCliente,
                'id_servicio' => $request->id_servicio,
                'subtotal' => $montoTotal,
                'descuento' => 0,
                'total' => $montoTotal,
                'estado' => $request->estado,
            ]);

            // Eliminar detalles anteriores y crear nuevos
            $quotation->details()->delete();
            foreach ($request->detalles as $detalle) {
                QuotationDetail::create([
                    'id_cotizacion' => $quotation->id_cotizacion,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unit' => $detalle['precio_unitario'],
                    'descuento' => 0,
                    'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                ]);
            }

            DB::commit();

            return redirect()->route('quotations.index')
                ->with('message', 'Cotización actualizada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la cotización: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $quotation = Quotation::findOrFail($id);
        
        // Verificar permisos
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        if ($role === 'Vendedor' && $quotation->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para eliminar esta cotización');
        }
        
        DB::beginTransaction();
        try {
            $quotation->details()->delete();
            $quotation->delete();
            
            DB::commit();

            return redirect()->route('quotations.index')
                ->with('message', 'Cotización eliminada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar la cotización: ' . $e->getMessage()]);
        }
    }

    public function generateOrder($id)
    {
        $quotation = Quotation::findOrFail($id);

        if ($quotation->estado !== 'APROBADA') {
            return back()->withErrors(['error' => 'Solo se pueden generar órdenes de cotizaciones aprobadas']);
        }

        if ($quotation->workOrder) {
            return back()->withErrors(['error' => 'Esta cotización ya tiene una orden de trabajo generada']);
        }

        DB::beginTransaction();
        try {
            $workOrder = WorkOrder::create([
                'id_cotizacion' => $quotation->id_cotizacion,
                'descripcion' => 'Orden generada desde cotización #' . $quotation->id_cotizacion,
                'fecha_programada' => now()->addDays(3),
                'estado' => 'PROGRAMADA',
            ]);

            DB::commit();

            return redirect()->route('work-orders.edit', $workOrder->id_orden)
                ->with('message', 'Orden de trabajo generada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al generar la orden: ' . $e->getMessage()]);
        }
    }

    /**
     * Generar PDF de la cotización
     */
    public function generatePDF($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $quotation = Quotation::with(['client', 'service', 'details.product'])->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $quotation->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }
        if ($role === 'Cliente' && $quotation->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }

        $data = [
            'quotation' => $quotation,
            'company' => [
                'name' => 'GRUPO 08 SA',
                'address' => 'Santa Cruz, Bolivia',
                'phone' => '+591 78562356',
                'email' => 'info@grupo08.com',
            ]
        ];

        $pdf = PDF::loadView('quotations.pdf', $data);
        
        // Formato carta
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->download("cotizacion-{$quotation->id_cotizacion}.pdf");
    }

    /**
     * Ver PDF en el navegador
     */
    public function viewPDF($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $quotation = Quotation::with(['client', 'service', 'details.product'])->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $quotation->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }
        if ($role === 'Cliente' && $quotation->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }

        $data = [
            'quotation' => $quotation,
            'company' => [
                'name' => 'GRUPO 08 SA',
                'address' => 'Santa Cruz, Bolivia',
                'phone' => '+591 78562356',
                'email' => 'info@grupo08.com',
            ]
        ];

        $pdf = PDF::loadView('quotations.pdf', $data);
        $pdf->setPaper('letter', 'portrait');
        
        return $pdf->stream("cotizacion-{$quotation->id_cotizacion}.pdf");
    }
}
