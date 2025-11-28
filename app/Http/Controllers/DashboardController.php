<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\WorkOrder;
use App\Models\Sale;
use App\Models\PaymentPlan;
use App\Models\Product;
use App\Models\User;
use App\Models\Installment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->rol->nombre;
        
        return Inertia::render('Dashboard', [
            'cards' => $this->getCards($user, $role),
            'userRole' => $role,
            'chartData' => $this->getChartData($user, $role),
            'recentActivity' => $this->getRecentActivity($user, $role),
            'quickStats' => $this->getQuickStats($user, $role),
        ]);
    }

    private function getCards($user, $role)
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return match($role) {
            'Administrador', 'Secretaria', 'Técnico', 'Contador' => [
                [
                    'title' => 'Cotizaciones Pendientes',
                    'value' => Quotation::where('estado', 'PENDIENTE')->count(),
                    'color' => 'warning',
                    'icon' => 'mdi-file-document-outline',
                    'route' => 'quotations.index',
                    'params' => ['estado' => 'PENDIENTE']
                ],
                [
                    'title' => 'Órdenes Activas',
                    'value' => WorkOrder::whereIn('estado', ['PROGRAMADA', 'EN_PROGRESO'])->count(),
                    'color' => 'info',
                    'icon' => 'mdi-clipboard-text-clock',
                    'route' => 'work-orders.index',
                    'params' => ['estado' => 'activas']
                ],
                [
                    'title' => 'Ventas del Mes',
                    'value' => 'Bs. ' . number_format($this->getMonthlySales($currentMonth, $currentYear), 2),
                    'color' => 'success',
                    'icon' => 'mdi-cash-multiple',
                    'route' => 'sales.index'
                ],
                [
                    'title' => 'Stock Bajo',
                    'value' => Product::where('stock', '<', 10)->where('stock', '>', 0)->count(),
                    'color' => 'orange',
                    'icon' => 'mdi-alert-circle-outline',
                    'route' => 'products.index',
                    'params' => ['stock' => 'bajo']
                ],
                [
                    'title' => 'Stock Agotado',
                    'value' => Product::where('stock', 0)->count(),
                    'color' => 'error',
                    'icon' => 'mdi-cancel',
                    'route' => 'products.index',
                    'params' => ['stock' => 'agotado']
                ],
                [
                    'title' => 'Pagos Hoy',
                    'value' => Installment::whereDate('fecha_vencimiento', $today)
                        ->where('estado', 'PENDIENTE')->count(),
                    'color' => 'primary',
                    'icon' => 'mdi-calendar-today',
                    'route' => 'installments.index',
                    'params' => ['vencimiento' => 'hoy']
                ],
            ],

            'Vendedor' => [
                [
                    'title' => 'Mis Cotizaciones',
                    'value' => Quotation::where('id_vendedor', $user->id_usuario)->count(),
                    'color' => 'primary',
                    'icon' => 'mdi-file-document-outline',
                    'route' => 'quotations.index'
                ],
                [
                    'title' => 'Cotiz. Pendientes',
                    'value' => Quotation::where('id_vendedor', $user->id_usuario)
                        ->where('estado', 'PENDIENTE')->count(),
                    'color' => 'warning',
                    'icon' => 'mdi-clock-outline',
                    'route' => 'quotations.index',
                    'params' => ['estado' => 'PENDIENTE']
                ],
                [
                    'title' => 'Ventas del Mes',
                    'value' => 'Bs. ' . number_format(
                        Sale::where('id_vendedor', $user->id_usuario)
                            ->whereMonth('fecha_venta', $currentMonth)
                            ->sum('total'), 2
                    ),
                    'color' => 'success',
                    'icon' => 'mdi-cash',
                    'route' => 'sales.index'
                ],
                [
                    'title' => 'Comisión Estimada',
                    'value' => 'Bs. ' . number_format(
                        Sale::where('id_vendedor', $user->id_usuario)
                            ->whereMonth('fecha_venta', $currentMonth)
                            ->sum('total') * 0.10, 2 // 10% de comisión
                    ),
                    'color' => 'info',
                    'icon' => 'mdi-chart-line',
                    'route' => 'sales.index'
                ],
            ],

            'Técnico' => [
                [
                    'title' => 'Órdenes Asignadas',
                    'value' => WorkOrder::whereHas('technicians', function($q) use ($user) {
                        $q->where('id_tecnico', $user->id_usuario);
                    })->count(),
                    'color' => 'primary',
                    'icon' => 'mdi-clipboard-account',
                    'route' => 'work-orders.index'
                ],
                [
                    'title' => 'Pendientes Hoy',
                    'value' => WorkOrder::whereHas('technicians', function($q) use ($user) {
                        $q->where('id_tecnico', $user->id_usuario);
                    })->where('estado', 'PROGRAMADA')
                      ->whereDate('fecha_inicio', $today)->count(),
                    'color' => 'warning',
                    'icon' => 'mdi-clock-start',
                    'route' => 'work-orders.index',
                    'params' => ['estado' => 'PROGRAMADA']
                ],
                [
                    'title' => 'En Progreso',
                    'value' => WorkOrder::whereHas('technicians', function($q) use ($user) {
                        $q->where('id_tecnico', $user->id_usuario);
                    })->where('estado', 'EN_PROGRESO')->count(),
                    'color' => 'info',
                    'icon' => 'mdi-progress-wrench',
                    'route' => 'work-orders.index',
                    'params' => ['estado' => 'EN_PROGRESO']
                ],
                [
                    'title' => 'Finalizadas (Mes)',
                    'value' => WorkOrder::whereHas('technicians', function($q) use ($user) {
                        $q->where('id_tecnico', $user->id_usuario);
                    })->where('estado', 'FINALIZADA')
                      ->whereMonth('fecha_fin', $currentMonth)->count(),
                    'color' => 'success',
                    'icon' => 'mdi-check-circle-outline',
                    'route' => 'work-orders.index',
                    'params' => ['estado' => 'FINALIZADA']
                ],
            ],

            'Contador' => [
                [
                    'title' => 'Ingresos del Mes',
                    'value' => 'Bs. ' . number_format($this->getMonthlySales($currentMonth, $currentYear), 2),
                    'color' => 'success',
                    'icon' => 'mdi-cash-multiple',
                    'route' => 'sales.index'
                ],
                [
                    'title' => 'Pagos Pendientes',
                    'value' => Installment::where('estado', 'PENDIENTE')->count(),
                    'color' => 'warning',
                    'icon' => 'mdi-calendar-clock',
                    'route' => 'installments.index',
                    'params' => ['estado' => 'PENDIENTE']
                ],
                [
                    'title' => 'Pagos Atrasados',
                    'value' => Installment::where('estado', 'ATRASADA')->count(),
                    'color' => 'error',
                    'icon' => 'mdi-alert-octagon',
                    'route' => 'installments.index',
                    'params' => ['estado' => 'ATRASADA']
                ],
                [
                    'title' => 'Pagos del Día',
                    'value' => Installment::whereDate('fecha_pago', $today)
                        ->where('estado', 'PAGADA')->count(),
                    'color' => 'info',
                    'icon' => 'mdi-cash-check',
                    'route' => 'installments.index',
                    'params' => ['fecha' => 'hoy']
                ],
            ],

            'Cliente' => [
                [
                    'title' => 'Mis Cotizaciones',
                    'value' => Quotation::where('id_cliente', $user->id_usuario)->count(),
                    'color' => 'primary',
                    'icon' => 'mdi-file-document-outline',
                    'route' => 'quotations.index'
                ],
                [
                    'title' => 'Mis Órdenes',
                    'value' => WorkOrder::whereHas('quotation', function($q) use ($user) {
                        $q->where('id_cliente', $user->id_usuario);
                    })->count(),
                    'color' => 'info',
                    'icon' => 'mdi-clipboard-text',
                    'route' => 'work-orders.index'
                ],
                [
                    'title' => 'Pagos Pendientes',
                    'value' => Installment::whereHas('paymentPlan.sale', function($q) use ($user) {
                        $q->where('id_cliente', $user->id_usuario);
                    })->where('estado', 'PENDIENTE')->count(),
                    'color' => 'warning',
                    'icon' => 'mdi-credit-card-clock',
                    'route' => 'payment-plans.index'
                ],
                [
                    'title' => 'Próximo Pago',
                    'value' => $this->getNextPayment($user) ?: 'No hay pagos',
                    'color' => 'success',
                    'icon' => 'mdi-calendar-arrow-right',
                    'route' => 'payment-plans.index'
                ],
            ],

            default => [
                [
                    'title' => 'Bienvenido',
                    'value' => 'Sistema Grupo 08',
                    'color' => 'primary',
                    'icon' => 'mdi-home'
                ]
            ]
        };
    }

    private function getChartData($user, $role)
    {
        if (!in_array($role, ['Administrador', 'Secretaria', 'Gerente', 'Contador'])) {
            return [];
        }

        $currentYear = Carbon::now()->year;

        // Ventas mensuales
        $salesData = Sale::selectRaw("EXTRACT(MONTH FROM fecha_venta) as month, SUM(total) as total")
            ->whereYear('fecha_venta', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $salesChart = array_fill(0, 12, 0);

        foreach ($salesData as $month => $total) {
            $salesChart[(int)$month - 1] = (float)$total;
        }

        // Estado de órdenes
        $ordersData = WorkOrder::selectRaw("estado, count(*) as total")
            ->groupBy('estado')
            ->get();

        return [
            'sales' => [
                'labels' => $months,
                'data' => $salesChart,
                'title' => 'Ventas Mensuales ' . $currentYear
            ],
            'orders' => [
                'labels' => $ordersData->pluck('estado'),
                'data' => $ordersData->pluck('total'),
                'title' => 'Estado de Órdenes'
            ]
        ];
    }

    private function getRecentActivity($user, $role)
    {
        if (!in_array($role, ['Administrador', 'Secretaria', 'Vendedor', 'Técnico', 'Contador'])) {
            return collect();
        }

        $activities = collect();

        // Últimas ventas (últimos 3 días)
        $latestSales = Sale::with('client')
            ->where('fecha_venta', '>=', Carbon::now()->subDays(3))
            ->orderBy('fecha_venta', 'desc')
            ->take(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'title' => "Nueva venta #{$sale->id_venta}",
                    'description' => "Cliente: {$sale->client->nombre} {$sale->client->apellido} - Bs. " . number_format($sale->total, 2),
                    'date' => $sale->fecha_venta->format('d/m/Y H:i'),
                    'type' => 'sale',
                    'icon' => 'mdi-cash',
                    'color' => 'success'
                ];
            });

        // Últimas órdenes
        $latestOrders = WorkOrder::with('quotation.client')
            ->where('fecha_inicio', '>=', Carbon::now()->subDays(3))
            ->orderBy('fecha_inicio', 'desc')
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'title' => "Orden #{$order->id_orden} - {$order->estado}",
                    'description' => "Cliente: {$order->quotation->client->nombre} - {$order->servicio}",
                    'date' => $order->fecha_inicio->format('d/m/Y H:i'),
                    'type' => 'order',
                    'icon' => 'mdi-clipboard-text',
                    'color' => 'info'
                ];
            });

        // Últimas cotizaciones
        $latestQuotations = Quotation::with('client')
            ->where('fecha_creacion', '>=', Carbon::now()->subDays(3))
            ->orderBy('fecha_creacion', 'desc')
            ->take(5)
            ->get()
            ->map(function ($quote) {
                return [
                    'title' => "Cotización #{$quote->id_cotizacion} - {$quote->estado}",
                    'description' => "Cliente: {$quote->client->nombre} - Bs. " . number_format($quote->total, 2),
                    'date' => $quote->fecha_creacion->format('d/m/Y H:i'),
                    'type' => 'quotation',
                    'icon' => 'mdi-file-document',
                    'color' => 'primary'
                ];
            });

        return $latestSales->concat($latestOrders)->concat($latestQuotations)
            ->sortByDesc('date')
            ->take(8)
            ->values();
    }

    private function getQuickStats($user, $role)
    {
        $today = Carbon::today();

        return match($role) {
            'Administrador', 'Secretaria', 'Gerente' => [
                'cotizaciones' => Quotation::whereDate('fecha_creacion', $today)->count(),
                'ventas' => Sale::whereDate('fecha_venta', $today)->count(),
                'ordenes' => WorkOrder::whereDate('fecha_inicio', $today)->count(),
            ],
            'Vendedor' => [
                'cotizaciones' => Quotation::where('id_vendedor', $user->id_usuario)
                    ->whereDate('fecha_creacion', $today)->count(),
                'ventas' => Sale::where('id_vendedor', $user->id_usuario)
                    ->whereDate('fecha_venta', $today)->count(),
            ],
            default => []
        };
    }

    private function getMonthlySales($month, $year)
    {
        return Sale::whereMonth('fecha_venta', $month)
            ->whereYear('fecha_venta', $year)
            ->sum('total');
    }

    private function getNextPayment($user)
    {
        $nextPayment = Installment::whereHas('paymentPlan.sale', function($q) use ($user) {
                $q->where('id_cliente', $user->id_usuario);
            })
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '>=', Carbon::today())
            ->orderBy('fecha_vencimiento')
            ->first();

        return $nextPayment ? 
            'Bs. ' . number_format($nextPayment->monto, 2) . ' - ' . 
            $nextPayment->fecha_vencimiento->format('d/m/Y') : null;
    }
}