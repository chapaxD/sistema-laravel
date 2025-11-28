<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\WorkOrder;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        $query = Sale::with(['workOrder.quotation.service', 'client', 'details.product'])
            ->orderBy('id_venta', 'desc');

        // Filtrar según el rol
        if ($role === 'Vendedor') {
            $query->where('id_vendedor', $user->id_usuario);
        } elseif ($role === 'Cliente') {
            $query->where('id_cliente', $user->id_usuario);
        } elseif ($role === 'Técnico') {
            // Técnico no ve ventas
            $query->whereRaw('1 = 0');
        }

        // Búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Buscar por ID de venta
                $q->where('id_venta', 'LIKE', "%{$search}%")
                  // Buscar por nombre o apellido del cliente
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('nombre', 'ILIKE', "%{$search}%")
                        ->orWhere('apellido', 'ILIKE', "%{$search}%");
                  })
                  // Buscar por nombre del servicio
                  ->orWhereHas('workOrder.quotation.service', function($q) use ($search) {
                      $q->where('nombre', 'ILIKE', "%{$search}%");
                  })
                  // Buscar por estado
                  ->orWhere('estado', 'ILIKE', "%{$search}%");
            });
        }

        $sales = $query->paginate(15);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
        ]);
    }

    public function create()
    {
        $workOrders = WorkOrder::with(['quotation.client', 'quotation.service'])
            ->where('estado', 'FINALIZADA')
            ->whereDoesntHave('sale')
            ->get();
        
        $clients = User::whereHas('rol', function($q) {
            $q->where('nombre', 'Cliente');
        })->get();
        
        $products = Product::where('estado', 'ACTIVO')->get();

        return Inertia::render('Sales/Create', [
            'workOrders' => $workOrders,
            'clients' => $clients,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_orden' => 'nullable|exists:ordentrabajo,id_orden',
            'id_cliente' => 'required|exists:usuario,id_usuario',
            'fecha_venta' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:producto,id_producto',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calcular monto total
            $montoTotal = 0;
            foreach ($request->detalles as $detalle) {
                $montoTotal += $detalle['cantidad'] * $detalle['precio_unitario'];
            }

            // Crear venta
            $sale = Sale::create([
                'id_orden' => $request->id_orden,
                'id_cliente' => $request->id_cliente,
                'id_vendedor' => auth()->id(),
                'fecha_venta' => $request->fecha_venta,
                'subtotal' => $montoTotal,
                'descuento' => 0,
                'total' => $montoTotal,
                'estado' => 'PAGADA',
            ]);

            // Crear detalles y actualizar stock
            foreach ($request->detalles as $detalle) {
                SaleDetail::create([
                    'id_venta' => $sale->id_venta,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unit' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                ]);

                // Actualizar stock del producto
                $product = Product::find($detalle['id_producto']);
                $product->stock -= $detalle['cantidad'];
                if ($product->stock <= 0) {
                    $product->estado = 'AGOTADO';
                }
                $product->save();
            }

            DB::commit();

            return redirect()->route('sales.index')
                ->with('message', 'Venta registrada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar la venta: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $sale = Sale::with(['workOrder.quotation', 'client', 'details.product', 'receipt', 'paymentPlan.installments'])
            ->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta venta');
        }
        if ($role === 'Cliente' && $sale->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver esta venta');
        }

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $sale = Sale::with(['workOrder', 'client', 'details.product'])
            ->findOrFail($id);
        
        // Verificar permisos
        if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para editar esta venta');
        }
        if ($role === 'Cliente') {
            abort(403, 'No tienes permiso para editar ventas');
        }
        
        $clients = User::whereHas('rol', function($q) {
            $q->where('nombre', 'Cliente');
        })->get();
        
        $products = Product::where('estado', 'ACTIVO')->get();

        return Inertia::render('Sales/Edit', [
            'sale' => $sale,
            'clients' => $clients,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        
        // Verificar permisos
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para actualizar esta venta');
        }

        $request->validate([
            'id_cliente' => 'required|exists:usuario,id_usuario',
            'fecha_venta' => 'required|date',
            'estado' => 'required|in:PENDIENTE,PAGADA,ANULADA',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:producto,id_producto',
            'detalles.*.cantidad' => 'required|numeric|min:0.001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Restaurar stock de productos anteriores
            foreach ($sale->details as $oldDetail) {
                $product = Product::find($oldDetail->id_producto);
                $product->stock += $oldDetail->cantidad;
                $product->save();
            }

            // Calcular monto total
            $montoTotal = 0;
            foreach ($request->detalles as $detalle) {
                $montoTotal += $detalle['cantidad'] * $detalle['precio_unitario'];
            }

            // Actualizar venta
            $sale->update([
                'id_cliente' => $request->id_cliente,
                'fecha_venta' => $request->fecha_venta,
                'subtotal' => $montoTotal,
                'descuento' => 0,
                'total' => $montoTotal,
                'estado' => $request->estado,
            ]);

            // Eliminar detalles anteriores y crear nuevos
            $sale->details()->delete();
            foreach ($request->detalles as $detalle) {
                SaleDetail::create([
                    'id_venta' => $sale->id_venta,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unit' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                ]);

                // Actualizar stock del producto
                $product = Product::find($detalle['id_producto']);
                $product->stock -= $detalle['cantidad'];
                if ($product->stock <= 0) {
                    $product->estado = 'AGOTADO';
                }
                $product->save();
            }

            DB::commit();

            return redirect()->route('sales.index')
                ->with('message', 'Venta actualizada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la venta: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        
        // Verificar permisos
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para eliminar esta venta');
        }
        
        DB::beginTransaction();
        try {
            // Restaurar stock de productos
            foreach ($sale->details as $detail) {
                $product = Product::find($detail->id_producto);
                $product->stock += $detail->cantidad;
                $product->save();
            }

            $sale->details()->delete();
            $sale->delete();
            
            DB::commit();

            return redirect()->route('sales.index')
                ->with('message', 'Venta eliminada correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar la venta: ' . $e->getMessage()]);
        }
    }

    public function storeFromOrder($orderId)
    {
        $order = WorkOrder::with(['quotation.details', 'quotation.client'])->findOrFail($orderId);

        if ($order->estado !== 'PROGRAMADA' && $order->estado !== 'FINALIZADA') {
            return back()->withErrors(['error' => 'La orden debe estar programada o finalizada para generar una venta']);
        }

        if ($order->sale) {
            return back()->withErrors(['error' => 'Esta orden ya tiene una venta asociada']);
        }

        \Log::info('Intentando generar venta para orden: ' . $orderId);
        
        DB::beginTransaction();
        try {
            // Crear venta basada en la cotización
            $sale = Sale::create([
                'id_orden' => $order->id_orden,
                'id_cliente' => $order->quotation->id_cliente,
                'id_vendedor' => $order->quotation->id_vendedor,
                'fecha_venta' => now(),
                'subtotal' => $order->quotation->subtotal,
                'descuento' => $order->quotation->descuento,
                'total' => $order->quotation->total,
                'estado' => 'PENDIENTE',
            ]);

            // Cambiar estado de la orden a EN_PROGRESO
            $order->estado = 'EN_PROGRESO';
            $order->save();

            // Copiar detalles de la cotización a la venta
            foreach ($order->quotation->details as $detail) {
                SaleDetail::create([
                    'id_venta' => $sale->id_venta,
                    'id_producto' => $detail->id_producto,
                    'cantidad' => $detail->cantidad,
                    'precio_unit' => $detail->precio_unit,
                    'subtotal' => $detail->subtotal,
                ]);

                // Actualizar stock
                $product = Product::find($detail->id_producto);
                if ($product) {
                    $product->stock -= $detail->cantidad;
                    if ($product->stock <= 0) {
                        $product->estado = 'AGOTADO';
                    }
                    $product->save();
                }
            }

            DB::commit();

            return redirect()->route('payment-plans.create', ['sale_id' => $sale->id_venta])
                ->with('message', 'Venta generada correctamente')
                ->with('type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al generar la venta: ' . $e->getMessage()]);
        }
    }
}
