<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    if (!Schema::hasColumn('cotizacion', 'id_vendedor')) {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->integer('id_vendedor')->nullable()->after('id_cliente');
            $table->foreign('id_vendedor')->references('id_usuario')->on('usuario');
        });
        echo "Columna id_vendedor agregada a cotizacion.\n";
    } else {
        echo "Columna id_vendedor ya existe en cotizacion.\n";
    }

    if (!Schema::hasColumn('venta', 'id_vendedor')) {
        Schema::table('venta', function (Blueprint $table) {
            $table->integer('id_vendedor')->nullable()->after('id_cliente');
            $table->foreign('id_vendedor')->references('id_usuario')->on('usuario');
        });
        echo "Columna id_vendedor agregada a venta.\n";
    } else {
        echo "Columna id_vendedor ya existe en venta.\n";
    }
    
    // Registrar la migración manualmente para evitar que se ejecute de nuevo
    \DB::table('migrations')->insertOrIgnore([
        'migration' => '2025_11_25_235737_add_id_vendedor_to_cotizacion_and_venta_tables',
        'batch' => 1
    ]);
    echo "Migración registrada manualmente.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
