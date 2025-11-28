# Flujo de Pagos Implementado

## Resumen de cambios

Se ha implementado el nuevo flujo de generación de venta, plan de pagos y recibos con actualización automática de estados de la orden de trabajo.

## Flujo completo

### 1️⃣ Crear y aprobar cotización
- El usuario crea una cotización con productos/servicios
- La cotización se aprueba (estado: `APROBADA`)
- Se genera una orden de trabajo (estado inicial: `PROGRAMADA`)

### 2️⃣ Programar orden y generar venta
- En la vista de edición de la orden (`WorkOrders/Edit.vue`):
  - El usuario programa la fecha de trabajo
  - Aparece el botón **"Generar Venta"** (solo visible cuando `estado === 'PROGRAMADA'` y no tiene venta asociada)
  - Al pulsar el botón:
    - Se crea la venta basada en la cotización
    - El estado de la orden cambia automáticamente a `EN_PROGRESO`
    - Se redirige a la creación del plan de pagos

### 3️⃣ Crear plan de pagos
- El usuario define:
  - Número de cuotas (2-24)
  - Fecha de inicio
- El sistema calcula automáticamente el monto por cuota
- Se crean todas las cuotas con estado `PENDIENTE`

### 4️⃣ Pagar cuotas y generar recibos
- Por cada cuota pagada:
  - Se marca la cuota como `PAGADA`
  - Se genera automáticamente un **recibo** con:
    - Número de recibo auto-incremental (formato: `REC-000001`)
    - Monto de la cuota
    - Método de pago: `EFECTIVO` (por defecto)
    - Fecha de pago
  - El recibo aparece en la sección **Recibos**

### 5️⃣ Finalización automática
- Cuando se paga la **última cuota**:
  - El plan de pagos cambia a estado `COMPLETADO`
  - La orden de trabajo cambia automáticamente a estado `FINALIZADA`

## Archivos modificados

### 1. `resources/js/Pages/WorkOrders/Edit.vue`
- Agregado botón "Generar Venta" con condición `order.estado === 'PROGRAMADA'`
- Corregida estructura del DOM (eliminados errores de lint)
- Actualizado mensaje de confirmación

### 2. `app/Http/Controllers/SaleController.php`
**Método `storeFromOrder`:**
- Permite generar venta desde órdenes con estado `PROGRAMADA` o `FINALIZADA`
- Actualiza el estado de la orden a `EN_PROGRESO` después de crear la venta
- Mantiene la redirección a `payment-plans.create`

### 3. `app/Http/Controllers/PaymentPlanController.php`
**Método `payInstallment`:**
- Genera automáticamente un recibo por cada cuota pagada
- Crea el detalle de pago con método `EFECTIVO`
- Actualiza la orden de trabajo a `FINALIZADA` cuando se completa el plan
- Mensaje actualizado: "Cuota pagada correctamente y recibo generado"

## Estados de la orden de trabajo

| Estado | Cuándo se establece |
|--------|---------------------|
| `PROGRAMADA` | Al generar la orden desde una cotización aprobada |
| `EN_PROGRESO` | Al generar la venta y plan de pagos |
| `FINALIZADA` | Al pagar la última cuota del plan de pagos |
| `CANCELADA` | Manualmente por el usuario |

## Próximos pasos (opcional)

- [ ] Permitir seleccionar método de pago al pagar cuota (actualmente fijo en EFECTIVO)
- [ ] Agregar notificaciones por email al generar recibos
- [ ] Implementar vista de impresión de recibos
- [ ] Agregar validación de stock antes de generar venta
- [ ] Dashboard con métricas de órdenes en progreso y planes de pago activos

## Pruebas recomendadas

1. Crear cotización → Aprobar → Generar orden
2. Editar orden → Programar fecha → Generar venta
3. Verificar que orden cambia a `EN_PROGRESO`
4. Crear plan de pagos (ej. 3 cuotas)
5. Pagar cuota 1 → Verificar recibo generado
6. Pagar cuota 2 → Verificar recibo generado
7. Pagar cuota 3 → Verificar que orden cambia a `FINALIZADA`
8. Revisar sección Recibos → Confirmar 3 recibos creados
