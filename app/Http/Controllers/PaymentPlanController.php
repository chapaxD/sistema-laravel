<?php

namespace App\Http\Controllers;

use App\Models\PaymentPlan;
use App\Models\Installment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class PaymentPlanController extends Controller
{
    public function index(Request $request)
{
    $user = auth()->user();
    $role = $user->rol->nombre;

    $query = PaymentPlan::orderBy('id_plan', 'desc');

    // Filtrar según rol
    if ($role === 'Vendedor') {
        $query->whereHas('sale', function($q) use ($user) {
            $q->where('id_vendedor', $user->id_usuario);
        });
    } elseif ($role === 'Cliente') {
        $query->whereHas('sale', function($q) use ($user) {
            $q->where('id_cliente', $user->id_usuario);
        });
    } elseif ($role === 'Técnico') {
        // Técnico no ve planes de pago
        $query->whereRaw('1 = 0');
    }

    // Búsqueda
    if ($request->has('search') && $request->search != '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            // Buscar por ID del plan
            $q->where('id_plan', 'LIKE', "%{$search}%")
            // Buscar por nombre o apellido del cliente
            ->orWhereHas('sale.client', function($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('apellido', 'ILIKE', "%{$search}%");
            })
            // Buscar por ID de venta
            ->orWhereHas('sale', function($q) use ($search) {
                $q->where('id_venta', 'LIKE', "%{$search}%");
            })
            // Buscar por monto total
            ->orWhere('monto_total', 'LIKE', "%{$search}%");
        });
    }

    $perPage = $request->input('per_page', 15);

    if ($perPage == -1) {
        $paymentPlans = $query->paginate(9999);
    } else {
        $paymentPlans = $query->paginate($perPage);
    }

    // Cargar relaciones DESPUÉS de paginar
    $paymentPlans->load(['sale.client', 'installments']);

    // Agregar información de cuotas pagadas
    $paymentPlans->getCollection()->transform(function ($plan) {
        $plan->cuotas_pagadas = $plan->installments->where('estado', 'PAGADA')->count();
        return $plan;
    });

    return Inertia::render('PaymentPlans/Index', [
        'paymentPlans' => $paymentPlans,
    ]);
}


    public function create(Request $request)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        if ($role === 'Cliente' || $role === 'Técnico') {
            abort(403, 'No tienes permiso para crear planes de pago');
        }

        $salesQuery = Sale::with(['client', 'workOrder.quotation.service'])
            ->where('estado', 'PENDIENTE') // Solo ventas pendientes pueden tener plan de pagos
            ->whereDoesntHave('paymentPlan');
            
        if ($role === 'Vendedor') {
            $salesQuery->where('id_vendedor', $user->id_usuario);
        }

        $sales = $salesQuery->get();

        return Inertia::render('PaymentPlans/Create', [
            'sales' => $sales,
            'sale_id' => $request->sale_id,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_venta' => 'required|exists:venta,id_venta',
            'numero_cuotas' => 'required|integer|min:2|max:24',
            'fecha_inicio' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::findOrFail($request->id_venta);
            
            // Verificar permisos
            $user = auth()->user();
            $role = $user->rol->nombre;
            
            if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
                abort(403, 'No tienes permiso para crear plan de pago para esta venta');
            }
            
            // Crear plan de pago
            $paymentPlan = PaymentPlan::create([
                'id_venta' => $request->id_venta,
                'monto_total' => $sale->total,
                'cantidad_cuotas' => $request->numero_cuotas,
                'fecha_inicio' => $request->fecha_inicio,
            ]);

            // Calcular monto por cuota
            $montoPorCuota = round($sale->total / $request->numero_cuotas, 2);
            $fechaInicio = Carbon::parse($request->fecha_inicio);

            // Crear cuotas
            for ($i = 1; $i <= $request->numero_cuotas; $i++) {
                $monto = $montoPorCuota;
                
                // Ajustar última cuota para compensar redondeos
                if ($i == $request->numero_cuotas) {
                    $totalCuotas = $montoPorCuota * ($request->numero_cuotas - 1);
                    $monto = $sale->total - $totalCuotas;
                }

                Installment::create([
                    'id_plan' => $paymentPlan->id_plan,
                    'numero_cuota' => $i,
                    'monto' => $monto,
                    'fecha_vencimiento' => $fechaInicio->copy()->addMonths($i - 1),
                    'estado' => 'PENDIENTE',
                ]);
            }

            DB::commit();

            return redirect()->route('payment-plans.index')
                ->with('message', 'Plan de pago creado correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear el plan de pago: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $paymentPlan = PaymentPlan::with(['sale.client', 'sale.details.product', 'installments'])
            ->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $paymentPlan->sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver este plan de pago');
        }
        if ($role === 'Cliente' && $paymentPlan->sale->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver este plan de pago');
        }
        if ($role === 'Técnico') {
            abort(403, 'No tienes permiso para ver planes de pago');
        }

        return Inertia::render('PaymentPlans/Show', [
            'paymentPlan' => $paymentPlan,
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $paymentPlan = PaymentPlan::with(['sale', 'installments'])
            ->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $paymentPlan->sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para editar este plan de pago');
        }
        if ($role === 'Cliente' || $role === 'Técnico') {
            abort(403, 'No tienes permiso para editar planes de pago');
        }

        return Inertia::render('PaymentPlans/Edit', [
            'paymentPlan' => $paymentPlan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $paymentPlan = PaymentPlan::findOrFail($id);
        
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        // Verificar permisos
        $sale = Sale::find($paymentPlan->id_venta);
        if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para actualizar este plan de pago');
        }

        // La tabla plan_pago no tiene columna estado, así que solo actualizamos observación si viene
        $updateData = [];
        
        if ($request->has('observacion')) {
            $updateData['observacion'] = $request->observacion;
        }

        if (!empty($updateData)) {
            $paymentPlan->update($updateData);
        }

        return redirect()->route('payment-plans.index')
            ->with('message', 'Plan de pago actualizado correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        // Solo Admin, Secretaria y Contador pueden eliminar planes
        if (!in_array($role, ['Administrador', 'Secretaria', 'Contador'])) {
            abort(403, 'No tienes permiso para eliminar planes de pago');
        }

        $paymentPlan = PaymentPlan::findOrFail($id);
        
        // Verificar que no haya cuotas pagadas
        $cuotasPagadas = $paymentPlan->installments()->where('estado', 'PAGADA')->count();
        if ($cuotasPagadas > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar un plan con cuotas pagadas']);
        }

        DB::beginTransaction();
        try {
            $paymentPlan->installments()->delete();
            $paymentPlan->delete();
            
            DB::commit();

            return redirect()->route('payment-plans.index')
                ->with('message', 'Plan de pago eliminado correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar el plan de pago: ' . $e->getMessage()]);
        }
    }

    public function payInstallment(Request $request, $id)
    {
        $request->validate([
            'id_cuota' => 'required|exists:cuota,id_cuota',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:EFECTIVO,TARJETA,TRANSFERENCIA,CHEQUE',
            'referencia' => 'nullable|string|max:80',
        ]);

        DB::beginTransaction();
        try {
            $installment = Installment::findOrFail($request->id_cuota);
            $paymentPlan = PaymentPlan::findOrFail($id);
            
            // Verificar permisos
            $user = auth()->user();
            $role = $user->rol->nombre;
            
            if ($role === 'Cliente' || $role === 'Técnico') {
                abort(403, 'No tienes permiso para registrar pagos');
            }
            
            if ($role === 'Vendedor') {
                $sale = Sale::find($paymentPlan->id_venta);
                if ($sale->id_vendedor !== $user->id_usuario) {
                    abort(403, 'No tienes permiso para registrar pagos en este plan');
                }
            }
            
            if ($installment->estado === 'PAGADA') {
                return back()->withErrors(['error' => 'Esta cuota ya ha sido pagada']);
            }

            $installment->update([
                'fecha_pago' => $request->fecha_pago,
                'estado' => 'PAGADA',
            ]);

            // Verificar si todas las cuotas están pagadas
            $cuotasPendientes = $paymentPlan->installments()->where('estado', 'PENDIENTE')->count();
            
            if ($cuotasPendientes === 0) {
                \Log::info('Todas las cuotas pagadas, actualizando orden y venta', [
                    'plan_id' => $paymentPlan->id_plan,
                    'venta_id' => $paymentPlan->sale->id_venta
                ]);
                
                // Actualizar estado de la venta a PAGADA
                $paymentPlan->sale->update(['estado' => 'PAGADA']);
                
                // Si el plan está COMPLETADO (todas las cuotas pagadas), marcar la orden de trabajo como FINALIZADA
                $order = $paymentPlan->sale->workOrder;
                if ($order) {
                    $order->estado = 'FINALIZADA';
                    $order->save();
                    
                    \Log::info('Orden de trabajo actualizada a FINALIZADA', [
                        'orden_id' => $order->id_orden
                    ]);
                }
                
                \Log::info('Venta actualizada a PAGADA', [
                    'venta_id' => $paymentPlan->sale->id_venta
                ]);
            }

            // Generar referencia automática para el recibo
            $referenciaRecibo = 'Cuota #' . $installment->numero_cuota . ' - Plan #' . $paymentPlan->id_plan;
            
            // Generar recibo para la cuota pagada usando DB directamente porque la tabla tiene estructura diferente
            $receiptId = DB::table('recibo')->insertGetId([
                'id_venta' => $paymentPlan->sale->id_venta,
                'fecha' => $request->fecha_pago,
                'total' => $installment->monto,
                'observacion' => $referenciaRecibo,
            ], 'id_recibo');

            // Crear detalle de pago del recibo con referencia automática
            $referenciaDetalle = $request->referencia ?: 'Pago de cuota #' . $installment->numero_cuota . ' del plan #' . $paymentPlan->id_plan;
            
            DB::table('recibo_detalle_pago')->insert([
                'id_recibo' => $receiptId,
                'metodo' => $request->metodo_pago,
                'monto' => $installment->monto,
                'referencia' => $referenciaDetalle,
            ]);

            // Crear relación entre cuota y recibo en la tabla cuota_pago
            DB::table('cuota_pago')->insert([
                'id_cuota' => $installment->id_cuota,
                'id_recibo' => $receiptId,
                'monto_pagado' => $installment->monto,
            ]);

            DB::commit();

            // Determinar desde dónde se llamó para redirigir correctamente
            $fromSale = $request->input('from_sale', false);
            
            if ($fromSale) {
                return redirect()->route('sales.show', $paymentPlan->sale->id_venta)
                    ->with('message', 'Cuota pagada correctamente y recibo generado')
                    ->with('type', 'success');
            }

            return redirect()->route('payment-plans.show', $id)
                ->with('message', 'Cuota pagada correctamente y recibo generado')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al pagar cuota: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }
    public function storeFromSale(Request $request, $saleId)
    {
        $sale = Sale::findOrFail($saleId);

        if ($sale->estado !== 'COMPLETADA') {
            return back()->withErrors(['error' => 'La venta debe estar completada para generar un plan de pagos']);
        }

        if ($sale->paymentPlan) {
            return back()->withErrors(['error' => 'Esta venta ya tiene un plan de pagos asociado']);
        }

        $request->validate([
            'numero_cuotas' => 'required|integer|min:2|max:24',
            'fecha_inicio' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            // Crear plan de pago (sin estado porque la tabla no tiene esa columna)
            $paymentPlan = PaymentPlan::create([
                'id_venta' => $sale->id_venta,
                'monto_total' => $sale->total,
                'cantidad_cuotas' => $request->numero_cuotas,
                'fecha_inicio' => $request->fecha_inicio,
            ]);

            // Calcular monto por cuota
            $montoPorCuota = round($sale->total / $request->numero_cuotas, 2);
            $fechaInicio = Carbon::parse($request->fecha_inicio);

            // Crear cuotas
            for ($i = 1; $i <= $request->numero_cuotas; $i++) {
                $monto = $montoPorCuota;
                
                // Ajustar última cuota para compensar redondeos
                if ($i == $request->numero_cuotas) {
                    $totalCuotas = $montoPorCuota * ($request->numero_cuotas - 1);
                    $monto = $sale->total - $totalCuotas;
                }

                Installment::create([
                    'id_plan' => $paymentPlan->id_plan,
                    'numero_cuota' => $i,
                    'monto' => $monto,
                    'fecha_vencimiento' => $fechaInicio->copy()->addMonths($i - 1),
                    'estado' => 'PENDIENTE',
                ]);
            }

            DB::commit();

            return redirect()->route('payment-plans.show', $paymentPlan->id_plan)
                ->with('message', 'Plan de pago generado correctamente')
                ->with('type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al generar el plan de pago: ' . $e->getMessage()]);
        }
    }

}
