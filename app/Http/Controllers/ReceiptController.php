<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\ReceiptPaymentDetail;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;
        $search = $request->input('search', null);

        $query = Receipt::with(['sale.client', 'paymentDetails'])
            ->orderBy('id_recibo', 'desc');

        // Filtrar según el rol
        if ($role === 'Vendedor') {
            $query->whereHas('sale', fn($q) => $q->where('id_vendedor', $user->id_usuario));
        } elseif ($role === 'Cliente') {
            $query->whereHas('sale', fn($q) => $q->where('id_cliente', $user->id_usuario));
        } elseif ($role === 'Técnico') {
            $query->whereRaw('1 = 0'); // no ve recibos
        }

        // Filtro de búsqueda
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id_recibo', 'like', "%{$search}%")
                ->orWhere('observacion', 'like', "%{$search}%")
                ->orWhereHas('sale', fn($s) => 
                    $s->where('id_venta', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($c) => 
                            $c->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%")
                        )
                );
            });
        }

        $receipts = $query->paginate(15);

        return Inertia::render('Receipts/Index', [
            'receipts' => $receipts,
        ]);
    }


    public function create()
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        if ($role === 'Cliente' || $role === 'Técnico') {
            abort(403, 'No tienes permiso para crear recibos');
        }

        $salesQuery = Sale::with(['client', 'workOrder.quotation.service'])
            ->whereIn('estado', ['PENDIENTE', 'COMPLETADA'])
            ->whereDoesntHave('receipt');
            
        if ($role === 'Vendedor') {
            $salesQuery->where('id_vendedor', $user->id_usuario);
        }

        $sales = $salesQuery->get();

        return Inertia::render('Receipts/Create', [
            'sales' => $sales,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_venta' => 'required|exists:venta,id_venta',
            'fecha_emision' => 'required|date',
            'detalles_pago' => 'required|array|min:1',
            'detalles_pago.*.metodo_pago' => 'required|in:EFECTIVO,TARJETA,TRANSFERENCIA,CHEQUE',
            'detalles_pago.*.monto' => 'required|numeric|min:0',
            'detalles_pago.*.referencia' => 'nullable|string|max:80',
            'observacion' => 'nullable|string|max:200',
        ]);

        DB::beginTransaction();
        try {
            // Calcular monto total
            $montoTotal = 0;
            foreach ($request->detalles_pago as $detalle) {
                $montoTotal += $detalle['monto'];
            }

            // Crear recibo usando DB directamente porque la tabla tiene estructura diferente
            $receiptId = DB::table('recibo')->insertGetId([
                'id_venta' => $request->id_venta,
                'fecha' => $request->fecha_emision,
                'total' => $montoTotal,
                'observacion' => $request->observacion ?? 'Recibo generado manualmente',
            ], 'id_recibo');
            
            // Crear detalles de pago
            foreach ($request->detalles_pago as $detalle) {
                DB::table('recibo_detalle_pago')->insert([
                    'id_recibo' => $receiptId,
                    'metodo' => $detalle['metodo_pago'],
                    'monto' => $detalle['monto'],
                    'referencia' => $detalle['referencia'] ?? null,
                ]);
            }

            // Actualizar estado de la venta a PAGADA
            $sale = Sale::find($request->id_venta);
            $sale->update(['estado' => 'PAGADA']);

            // Actualizar estado de la orden de trabajo a FINALIZADA
            if ($sale->workOrder) {
                $sale->workOrder->update(['estado' => 'FINALIZADA']);
            }

            DB::commit();

            return redirect()->route('receipts.index')
                ->with('message', 'Recibo creado correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear el recibo: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $receipt = Receipt::with(['sale.client', 'sale.details.product', 'paymentDetails'])
            ->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $receipt->sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver este recibo');
        }
        if ($role === 'Cliente' && $receipt->sale->id_cliente !== $user->id_usuario) {
            abort(403, 'No tienes permiso para ver este recibo');
        }
        if ($role === 'Técnico') {
            abort(403, 'No tienes permiso para ver recibos');
        }

        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt,
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;

        $receipt = Receipt::with(['sale', 'paymentDetails'])
            ->findOrFail($id);

        // Verificar permisos
        if ($role === 'Vendedor' && $receipt->sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para editar este recibo');
        }
        if ($role === 'Cliente' || $role === 'Técnico') {
            abort(403, 'No tienes permiso para editar recibos');
        }

        return Inertia::render('Receipts/Edit', [
            'receipt' => $receipt,
        ]);
    }

    public function update(Request $request, $id)
    {
        $receipt = Receipt::findOrFail($id);
        
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        // Verificar permisos (necesitamos cargar la venta para verificar vendedor)
        $sale = Sale::find($receipt->id_venta);
        if ($role === 'Vendedor' && $sale->id_vendedor !== $user->id_usuario) {
            abort(403, 'No tienes permiso para actualizar este recibo');
        }
        if ($role === 'Cliente' || $role === 'Técnico') {
            abort(403, 'No tienes permiso para actualizar recibos');
        }

        $request->validate([
            'fecha_emision' => 'required|date',
            'detalles_pago' => 'required|array|min:1',
            'detalles_pago.*.metodo_pago' => 'required|in:EFECTIVO,TARJETA,TRANSFERENCIA,CHEQUE',
            'detalles_pago.*.monto' => 'required|numeric|min:0',
            'detalles_pago.*.referencia' => 'nullable|string|max:80',
            'observacion' => 'nullable|string|max:200',
        ]);

        DB::beginTransaction();
        try {
            // Calcular monto total
            $montoTotal = 0;
            foreach ($request->detalles_pago as $detalle) {
                $montoTotal += $detalle['monto'];
            }

            // Actualizar recibo usando DB directamente
            DB::table('recibo')
                ->where('id_recibo', $id)
                ->update([
                    'fecha' => $request->fecha_emision,
                    'total' => $montoTotal,
                    'observacion' => $request->observacion ?? $receipt->observacion,
                ]);

            // Eliminar detalles anteriores y crear nuevos
            DB::table('recibo_detalle_pago')->where('id_recibo', $id)->delete();
            foreach ($request->detalles_pago as $detalle) {
                DB::table('recibo_detalle_pago')->insert([
                    'id_recibo' => $id,
                    'metodo' => $detalle['metodo_pago'],
                    'monto' => $detalle['monto'],
                    'referencia' => $detalle['referencia'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('receipts.index')
                ->with('message', 'Recibo actualizado correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar el recibo: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        // Solo Admin, Secretaria y Contador pueden eliminar recibos
        if (!in_array($role, ['Administrador', 'Secretaria', 'Contador'])) {
            abort(403, 'No tienes permiso para eliminar recibos');
        }

        DB::beginTransaction();
        try {
            // Eliminar detalles de pago primero (por las foreign keys)
            DB::table('recibo_detalle_pago')->where('id_recibo', $id)->delete();
            // Eliminar relación con cuotas si existe
            DB::table('cuota_pago')->where('id_recibo', $id)->delete();
            // Eliminar recibo
            DB::table('recibo')->where('id_recibo', $id)->delete();
            
            DB::commit();

            return redirect()->route('receipts.index')
                ->with('message', 'Recibo eliminado correctamente')
                ->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar el recibo: ' . $e->getMessage()]);
        }
    }
    public function storeFromSale($saleId)
    {
        $sale = Sale::findOrFail($saleId);

        if (!in_array($sale->estado, ['PENDIENTE', 'COMPLETADA'])) {
            return back()->withErrors(['error' => 'La venta debe estar pendiente o completada para generar un recibo']);
        }

        if ($sale->receipt) {
            return back()->withErrors(['error' => 'Esta venta ya tiene un recibo asociado']);
        }

        DB::beginTransaction();
        try {
            // Crear recibo usando DB directamente
            $receiptId = DB::table('recibo')->insertGetId([
                'id_venta' => $sale->id_venta,
                'fecha' => now(),
                'total' => $sale->total,
                'observacion' => 'Pago completo de venta #' . $sale->id_venta,
            ], 'id_recibo');

            // Crear un detalle de pago por defecto (EFECTIVO por el total)
            DB::table('recibo_detalle_pago')->insert([
                'id_recibo' => $receiptId,
                'metodo' => 'EFECTIVO',
                'monto' => $sale->total,
                'referencia' => 'Pago completo de venta #' . $sale->id_venta,
            ]);
            
            $receipt = Receipt::find($receiptId);

            // Actualizar estado de la venta a PAGADA
            $sale->update(['estado' => 'PAGADA']);

            // Actualizar estado de la orden de trabajo a FINALIZADA
            if ($sale->workOrder) {
                $sale->workOrder->update(['estado' => 'FINALIZADA']);
            }

            DB::commit();

            return redirect()->route('receipts.edit', $receipt->id_recibo)
                ->with('message', 'Recibo generado correctamente')
                ->with('type', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al generar el recibo: ' . $e->getMessage()]);
        }
    }
    /**
 * Generar PDF del recibo
 */
public function generatePDF($id)
{
    $user = auth()->user();
    $role = $user->rol->nombre;

    $receipt = Receipt::with([
        'sale.client', 
        'sale.details.product', 
        'paymentDetails'
        // Quita 'sale.vendor' ya que no existe
    ])->findOrFail($id);

    // Verificar permisos (igual que en show)
    if ($role === 'Vendedor' && $receipt->sale->id_vendedor !== $user->id_usuario) {
        abort(403, 'No tienes permiso para ver este recibo');
    }
    if ($role === 'Cliente' && $receipt->sale->id_cliente !== $user->id_usuario) {
        abort(403, 'No tienes permiso para ver este recibo');
    }
    if ($role === 'Técnico') {
        abort(403, 'No tienes permiso para ver recibos');
    }

    // Datos para el PDF
    $data = [
        'receipt' => $receipt,
        'company' => [
            'name' => 'GRUPO 08 SA',
            'address' => 'Santa Cruz, Bolivia',
            'phone' => '+591 78562356',
            'email' => 'info@grupo08.com',
        ]
    ];

    $pdf = PDF::loadView('receipts.pdf', $data);
    
    // Configurar papel (ticket/recibo pequeño)
    $pdf->setPaper([0, 0, 226.77, 595.35], 'portrait'); // 80mm x 210mm (tamaño recibo)
    
    return $pdf->download("recibo-{$receipt->id_recibo}.pdf");
}

/**
 * Ver PDF en el navegador
 */
public function viewPDF($id)
{
    $user = auth()->user();
    $role = $user->rol->nombre;

    $receipt = Receipt::with([
        'sale.client', 
        'sale.details.product', 
        'paymentDetails'
        // Quita 'sale.vendor' ya que no existe
    ])->findOrFail($id);

    // Verificar permisos
    if ($role === 'Vendedor' && $receipt->sale->id_vendedor !== $user->id_usuario) {
        abort(403, 'No tienes permiso para ver este recibo');
    }
    if ($role === 'Cliente' && $receipt->sale->id_cliente !== $user->id_usuario) {
        abort(403, 'No tienes permiso para ver este recibo');
    }
    if ($role === 'Técnico') {
        abort(403, 'No tienes permiso para ver recibos');
    }

    $data = [
        'receipt' => $receipt,
        'company' => [
            'name' => 'GRUPO 08 SA',
            'address' => 'Santa Cruz, Bolivia',
            'phone' => '+591 XXX XXX XXX',
            'email' => 'info@grupo08.com',
        ]
    ];

    $pdf = PDF::loadView('receipts.pdf', $data);
    $pdf->setPaper([0, 0, 226.77, 595.35], 'portrait');
    
    return $pdf->stream("recibo-{$receipt->id_recibo}.pdf");
}
}
