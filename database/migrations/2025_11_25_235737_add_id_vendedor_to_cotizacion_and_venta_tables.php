<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->integer('id_vendedor')->nullable()->after('id_cliente');
            $table->foreign('id_vendedor')->references('id_usuario')->on('usuario');
        });

        Schema::table('venta', function (Blueprint $table) {
            $table->integer('id_vendedor')->nullable()->after('id_cliente');
            $table->foreign('id_vendedor')->references('id_usuario')->on('usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropForeign(['id_vendedor']);
            $table->dropColumn('id_vendedor');
        });

        Schema::table('venta', function (Blueprint $table) {
            $table->dropForeign(['id_vendedor']);
            $table->dropColumn('id_vendedor');
        });
    }
};
