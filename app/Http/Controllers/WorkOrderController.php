<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        $query = WorkOrder::with(['quotation.client', 'quotation.service', 'sale'])
            ->orderBy('id_orden', 'desc');

        // Filtrar según el rol
        if ($role === 'Técnico') {
            $query->whereHas('technicians', function($q) use ($user) {
                $q->where('id_tecnico', $user->id_usuario);
            });
        } elseif ($role === 'Cliente') {
            $query->whereHas('quotation', function($q) use ($user) {
                $q->where('id_cliente', $user->id_usuario);
            });
        } elseif ($role === 'Vendedor') {
            // Vendedor no ve órdenes de trabajo
            $query->whereRaw('1 = 0');
        }

        // Búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Buscar por ID de orden
                $q->where('id_orden', 'LIKE', "%{$search}%")
                  // Buscar por nombre o apellido del cliente
                  ->orWhereHas('quotation.client', function($q) use ($search) {
                      $q->where('nombre', 'ILIKE', "%{$search}%")
                        ->orWhere('apellido', 'ILIKE', "%{$search}%");
                  })
                  // Buscar por nombre del servicio
                  ->orWhereHas('quotation.service', function($q) use ($search) {
                      $q->where('nombre', 'ILIKE', "%{$search}%");
                  })
                  // Buscar por estado
                  ->orWhere('estado', 'ILIKE', "%{$search}%");
            });
        }

        $orders = $query->paginate(15);

        return Inertia::render('WorkOrders/Index', [
            'workOrders' => $orders,
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $order = WorkOrder::with(['quotation.client', 'quotation.service', 'technicians'])
            ->findOrFail($id);

        // Verificar permisos
        if ($role === 'Técnico') {
            $isAssigned = $order->technicians->contains('id_usuario', $user->id_usuario);
            if (!$isAssigned) {
                abort(403, 'No tienes permiso para ver esta orden de trabajo');
            }
        } elseif ($role === 'Cliente') {
             // Cliente solo ve, no edita (debería ser show, pero si usa edit para ver detalles...)
             if ($order->quotation->id_cliente !== $user->id_usuario) {
                abort(403, 'No tienes permiso para ver esta orden de trabajo');
             }
             // Si es cliente, redirigir a una vista de solo lectura o manejar en frontend
        } elseif ($role === 'Vendedor') {
            abort(403, 'No tienes permiso para ver órdenes de trabajo');
        }

        $technicians = User::whereHas('rol', function($q) {
            $q->where('nombre', 'Técnico');
        })->get();

        return Inertia::render('WorkOrders/Edit', [
            'order' => $order,
            'technicians' => $technicians,
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = WorkOrder::findOrFail($id);
        
        $user = auth()->user();
        $role = $user->rol->nombre;

        // Verificar permisos
        if ($role === 'Técnico') {
            $isAssigned = $order->technicians->contains('id_usuario', $user->id_usuario);
            if (!$isAssigned) {
                abort(403, 'No tienes permiso para actualizar esta orden de trabajo');
            }
            // Técnico solo puede actualizar estado y sus horas (validación adicional podría ir aquí)
        } elseif ($role === 'Cliente' || $role === 'Vendedor') {
            abort(403, 'No tienes permiso para actualizar órdenes de trabajo');
        }

        $request->validate([
            'estado' => 'required|in:PROGRAMADA,EN_PROGRESO,FINALIZADA,CANCELADA',
            'fecha_programada' => 'required|date',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'tecnicos' => 'array',
            'tecnicos.*.id_tecnico' => 'required|exists:usuario,id_usuario',
            'tecnicos.*.rol' => 'nullable|string',
            'tecnicos.*.horas' => 'nullable|numeric',
        ]);

        $order->update($request->only([
            'estado', 'fecha_programada', 'fecha_inicio', 
            'fecha_fin', 'descripcion'
        ]));

        // Si la orden se marca como FINALIZADA, actualizar estado de la venta a PAGADA
        if ($request->estado === 'FINALIZADA') {
            \Log::info('Orden marcada como FINALIZADA', ['orden_id' => $order->id_orden]);
            
            // Recargar la relación sale
            $order->load('sale');
            
            if ($order->sale) {
                \Log::info('Actualizando venta a PAGADA', [
                    'venta_id' => $order->sale->id_venta,
                    'estado_anterior' => $order->sale->estado
                ]);
                
                $order->sale->update(['estado' => 'PAGADA']);
                
                \Log::info('Venta actualizada', [
                    'venta_id' => $order->sale->id_venta,
                    'estado_nuevo' => $order->sale->fresh()->estado
                ]);
            } else {
                \Log::warning('Orden FINALIZADA sin venta asociada', ['orden_id' => $order->id_orden]);
            }
        }

        // Preparar datos para sync con pivote
        // Solo Admin y Secretaria pueden reasignar técnicos
        if (($role === 'Administrador' || $role === 'Secretaria') && $request->tecnicos) {
            $syncData = [];
            foreach ($request->tecnicos as $tec) {
                $syncData[$tec['id_tecnico']] = [
                    'rol_en_orden' => $tec['rol'],
                    'horas_estimadas' => $tec['horas']
                ];
            }
            $order->technicians()->sync($syncData);
        }
        
        return redirect()->route('work-orders.index')
            ->with('message', 'Orden actualizada correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        if ($role !== 'Administrador' && $role !== 'Secretaria') {
            abort(403, 'No tienes permiso para eliminar órdenes de trabajo');
        }

        $order = WorkOrder::findOrFail($id);
        $order->technicians()->detach();
        $order->delete();

        return redirect()->route('work-orders.index')
            ->with('message', 'Orden eliminada correctamente')
            ->with('type', 'success');
    }
}
