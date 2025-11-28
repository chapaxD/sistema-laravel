# Sistema Laravel - Guía de Configuración

Este documento contiene las instrucciones para completar la configuración del sistema Laravel + Inertia.js + Vue 3 + Vuetify.

## ✅ Lo que ya está configurado

### Frontend (Vue 3 + Inertia.js + Vuetify)
- ✅ Vite configurado con Vue 3 y SSR
- ✅ Vuetify 3 instalado con tema oscuro por defecto
- ✅ Layouts: AuthenticatedLayout y GuestLayout
- ✅ Páginas creadas:
  - Dashboard con estadísticas
  - Login
  - Work Orders (Index, Edit)
  - Users (Index)
  - Products (Index)
  - Quotations (Index)
  - Payment Plans (Index)
- ✅ Navegación con sidebar responsive
- ✅ Toggle de tema claro/oscuro
- ✅ Componentes Vuetify (data tables, forms, chips, etc.)

### Backend (Laravel)
- ✅ Laravel 11 instalado
- ✅ Middleware de Inertia (HandleInertiaRequests)
- ✅ Middleware de roles (CheckRole)
- ✅ Blade template para Inertia (app.blade.php)
- ✅ Configuración de base de datos PostgreSQL en .env.example

## 🔧 Pasos para completar la configuración

### 1. Configurar el archivo .env

Copie `.env.example` a `.env` y genere la clave de aplicación:

```bash
copy .env.example .env
php artisan key:generate
```

El archivo `.env` ya tiene la configuración de PostgreSQL:
```env
DB_CONNECTION=pgsql
DB_HOST=mail.tecnoweb.org.bo
DB_PORT=5432
DB_DATABASE=db_grupo08sa
DB_USERNAME=grupo08sa
DB_PASSWORD=grup008grup008*
```

### 2. Instalar dependencias de Composer

Debido a problemas con el token de GitHub, instale manualmente el paquete de Inertia:

```bash
composer require inertiajs/inertia-laravel
```

Luego publique el middleware de Inertia:

```bash
php artisan inertia:middleware
```

**Nota:** El middleware ya está creado en `app/Http/Middleware/HandleInertiaRequests.php`

### 3. Registrar el middleware en bootstrap/app.php

Edite `bootstrap/app.php` y agregue el middleware de Inertia:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
    
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

### 4. Crear las migraciones de base de datos

Como la base de datos ya existe, NO necesita ejecutar migraciones. Sin embargo, debe crear los modelos Eloquent.

### 5. Crear los modelos Eloquent

Cree los siguientes modelos en `app/Models/`:

#### User.php (ya existe, modificar)
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    
    protected $fillable = [
        'nombre', 'apellido', 'email', 'password_hash', 
        'telefono', 'estado', 'id_rol'
    ];

    protected $hidden = [
        'password_hash', 'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'id_cliente');
    }
}
```

#### Rol.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    public function users()
    {
        return $this->hasMany(User::class, 'id_rol');
    }
}
```

#### WorkOrder.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $table = 'ordentrabajo';
    protected $primaryKey = 'id_orden';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion', 'descripcion', 'fecha_programada',
        'fecha_inicio', 'fecha_fin', 'estado'
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_cotizacion');
    }

    public function technicians()
    {
        return $this->belongsToMany(
            User::class,
            'orden_trabajo_tecnico',
            'id_orden',
            'id_tecnico'
        );
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'id_orden');
    }
}
```

**Cree modelos similares para:**
- Product.php
- Categoria.php
- Service.php
- Quotation.php
- QuotationDetail.php
- Sale.php
- SaleDetail.php
- Receipt.php
- ReceiptPaymentDetail.php
- PaymentPlan.php
- Installment.php (Cuota)

### 6. Crear los controladores

Cree los controladores en `app/Http/Controllers/`:

#### AuthController.php
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password_hash)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
```

#### DashboardController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\WorkOrder;
use App\Models\Sale;
use App\Models\PaymentPlan;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'quotations' => Quotation::count(),
            'activeOrders' => WorkOrder::whereIn('estado', ['PROGRAMADA', 'EN_PROGRESO'])->count(),
            'monthlySales' => Sale::whereMonth('fecha_venta', now()->month)->count(),
            'pendingPayments' => PaymentPlan::whereHas('installments', function($q) {
                $q->where('estado', 'PENDIENTE');
            })->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivity' => [],
        ]);
    }
}
```

#### WorkOrderController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkOrderController extends Controller
{
    public function index()
    {
        $orders = WorkOrder::with(['quotation.client', 'quotation.service', 'sale'])
            ->orderBy('id_orden', 'desc')
            ->paginate(15);

        return Inertia::render('WorkOrders/Index', [
            'workOrders' => $orders,
        ]);
    }

    public function edit($id)
    {
        $order = WorkOrder::with(['quotation.client', 'quotation.service'])
            ->findOrFail($id);

        $technicians = User::whereHas('rol', function($q) {
            $q->where('nombre', 'Técnico');
        })->get();

        $assignedTechIds = $order->technicians()->pluck('id_usuario')->toArray();

        return Inertia::render('WorkOrders/Edit', [
            'order' => $order,
            'technicians' => $technicians,
            'assignedTechIds' => $assignedTechIds,
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = WorkOrder::findOrFail($id);

        $request->validate([
            'estado' => 'required|in:PROGRAMADA,EN_PROGRESO,FINALIZADA,CANCELADA',
            'fecha_programada' => 'required|date',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'tecnicos' => 'array',
        ]);

        $order->update($request->only([
            'estado', 'fecha_programada', 'fecha_inicio', 
            'fecha_fin', 'descripcion'
        ]));

        $order->technicians()->sync($request->tecnicos ?? []);

        return redirect()->route('work-orders.index')
            ->with('message', 'Orden actualizada correctamente')
            ->with('type', 'success');
    }

    public function destroy($id)
    {
        $order = WorkOrder::findOrFail($id);
        $order->technicians()->detach();
        $order->delete();

        return redirect()->route('work-orders.index')
            ->with('message', 'Orden eliminada correctamente')
            ->with('type', 'success');
    }
}
```

**Cree controladores similares para:**
- UserController
- ProductController
- ServiceController
- QuotationController
- SaleController
- ReceiptController
- PaymentPlanController

### 7. Configurar las rutas

Edite `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\PaymentPlanController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('work-orders', WorkOrderController::class);
    Route::post('work-orders/{id}/generate-sale', [WorkOrderController::class, 'generateSale'])
        ->name('work-orders.generate-sale');
    
    Route::resource('users', UserController::class)->middleware('role:Administrador');
    Route::resource('products', ProductController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('quotations', QuotationController::class);
    Route::post('quotations/{id}/generate-order', [QuotationController::class, 'generateOrder'])
        ->name('quotations.generate-order');
    Route::resource('sales', SaleController::class);
    Route::resource('receipts', ReceiptController::class);
    Route::resource('payment-plans', PaymentPlanController::class);
    Route::post('payment-plans/{id}/pay-installment', [PaymentPlanController::class, 'payInstallment'])
        ->name('payment-plans.pay-installment');
});
```

### 8. Compilar assets y ejecutar el servidor

```bash
# Compilar assets de Vue
npm run build

# O en modo desarrollo con hot reload
npm run dev

# En otra terminal, ejecutar el servidor Laravel
php artisan serve
```

### 9. Acceder a la aplicación

Abra su navegador en: `http://localhost:8000`

**Credenciales de prueba:** (debe crear un usuario en la base de datos primero)

```sql
INSERT INTO usuario (nombre, apellido, email, password_hash, id_rol, estado)
VALUES ('Admin', 'Sistema', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'ACTIVO');
-- Password: password
```

## 📝 Notas importantes

1. **PHP Extensions:** Si tiene problemas con `php artisan`, verifique que la extensión `oci8_19` esté deshabilitada en `php.ini` o instale las dependencias necesarias.

2. **SSR:** Para habilitar SSR en producción, ejecute:
   ```bash
   npm run build
   node bootstrap/ssr/ssr.js
   ```

3. **Base de datos:** El sistema usa la misma base de datos que `sistema_web`, por lo que todos los datos existentes estarán disponibles.

4. **Tema oscuro:** El tema oscuro está activado por defecto. Los usuarios pueden cambiarlo desde el menú lateral.

5. **Roles:** El sistema implementa control de acceso basado en roles:
   - Administrador: Acceso completo
   - Técnico: Órdenes de trabajo
   - Secretaria: Ventas y cotizaciones
   - Cliente: Datos propios

## 🚀 Próximos pasos

1. Crear los modelos Eloquent faltantes
2. Crear los controladores faltantes
3. Crear las páginas de creación/edición para cada módulo
4. Implementar validaciones de formularios
5. Agregar pruebas automatizadas
6. Optimizar consultas con eager loading
7. Implementar caché para mejorar rendimiento

## 🐛 Solución de problemas

### Error: "Class 'Inertia' not found"
```bash
composer require inertiajs/inertia-laravel
```

### Error: "Vite manifest not found"
```bash
npm run build
```

### Error de conexión a PostgreSQL
Verifique las credenciales en `.env` y que el servidor PostgreSQL esté accesible.

### Páginas en blanco
Revise la consola del navegador (F12) para ver errores de JavaScript.

## 📚 Recursos

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Vue 3 Documentation](https://vuejs.org/)
- [Vuetify 3 Documentation](https://vuetifyjs.com/)
