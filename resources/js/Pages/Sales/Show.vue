<template>
  <AuthenticatedLayout title="Detalle de Venta">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Venta #{{ sale.id_venta }}</span>
          <div>
            <v-btn 
              color="primary" 
              @click="router.visit(route('sales.edit', sale.id_venta))"
              class="mr-2"
            >
              <v-icon left>mdi-pencil</v-icon>
              Editar
            </v-btn>
            <v-btn color="grey" @click="router.visit(route('sales.index'))">
              Volver
            </v-btn>
          </div>
        </div>
      </v-card-title>

      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-list>
              <v-list-item>
                <v-list-item-title class="text-grey">Cliente</v-list-item-title>
                <v-list-item-subtitle class="text-h6">
                  {{ sale.client.nombre }} {{ sale.client.apellido }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Email</v-list-item-title>
                <v-list-item-subtitle>{{ sale.client.email }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>

          <v-col cols="12" md="6">
            <v-list>
              <v-list-item>
                <v-list-item-title class="text-grey">Fecha</v-list-item-title>
                <v-list-item-subtitle>{{ formatDate(sale.fecha_venta) }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Estado</v-list-item-title>
                <v-list-item-subtitle>
                  <v-chip :color="getStatusColor(sale.estado)" size="small">
                    {{ sale.estado }}
                  </v-chip>
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="sale.work_order">
                <v-list-item-title class="text-grey">Orden de Trabajo</v-list-item-title>
                <v-list-item-subtitle>
                  <a href="#" @click.prevent="router.visit(route('work-orders.show', sale.work_order.id_orden))">
                    Orden #{{ sale.work_order.id_orden }}
                  </a>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <h3 class="mb-4">Productos</h3>

        <v-table>
          <thead>
            <tr>
              <th>Producto</th>
              <th class="text-right">Cantidad</th>
              <th class="text-right">Precio Unit.</th>
              <th class="text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="detail in sale.details" :key="detail.id_detalle">
              <td>{{ detail.product.nombre }}</td>
              <td class="text-right">{{ parseFloat(detail.cantidad) }}</td>
              <td class="text-right">Bs. {{ parseFloat(detail.precio_unit || detail.precio_unitario || 0).toFixed(2) }}</td>
              <td class="text-right">Bs. {{ parseFloat(detail.subtotal).toFixed(2) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="text-right font-weight-bold">TOTAL:</td>
              <td class="text-right font-weight-bold text-h6">
                Bs. {{ parseFloat(sale.total).toFixed(2) }}
              </td>
            </tr>
          </tfoot>
        </v-table>

        <!-- Plan de Pagos y Cuotas -->
        <v-divider class="my-4"></v-divider>

        <div v-if="sale.payment_plan" class="mb-4">
          <div class="d-flex justify-space-between align-center mb-3">
            <h3>Plan de Pagos y Cuotas</h3>
            <v-btn 
              color="primary" 
              @click="router.visit(route('payment-plans.show', sale.payment_plan.id_plan))"
            >
              <v-icon left>mdi-cash-multiple</v-icon>
              Ver Detalle Completo
            </v-btn>
          </div>

          <v-alert type="info" class="mb-4">
            <div class="d-flex justify-space-between align-center">
              <div>
                <strong>Plan #{{ sale.payment_plan.id_plan }}</strong> - 
                {{ sale.payment_plan.cantidad_cuotas }} cuotas de 
                Bs. {{ parseFloat(sale.payment_plan.monto_total / sale.payment_plan.cantidad_cuotas).toFixed(2) }}
              </div>
              <v-chip :color="getPaymentPlanColor(sale.payment_plan.estado)" size="small">
                {{ sale.payment_plan.estado }}
              </v-chip>
            </div>
          </v-alert>

          <h4 class="mb-3">Cuotas - Puedes pagarlas aquí</h4>
          <v-table>
            <thead>
              <tr>
                <th>#</th>
                <th>Vencimiento</th>
                <th class="text-right">Monto</th>
                <th>Estado</th>
                <th>Fecha Pago</th>
                <th style="width: 120px">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="installment in sale.payment_plan.installments" :key="installment.id_cuota">
                <td>{{ installment.numero_cuota }}</td>
                <td>{{ formatDate(installment.fecha_vencimiento) }}</td>
                <td class="text-right">Bs. {{ parseFloat(installment.monto).toFixed(2) }}</td>
                <td>
                  <v-chip :color="getInstallmentColor(installment.estado)" size="small">
                    {{ installment.estado }}
                  </v-chip>
                </td>
                <td>{{ installment.fecha_pago ? formatDate(installment.fecha_pago) : '-' }}</td>
                <td>
                  <v-btn
                    v-if="installment.estado === 'PENDIENTE'"
                    color="success"
                    size="small"
                    @click="openPayDialog(installment)"
                  >
                    <v-icon left small>mdi-cash</v-icon>
                    Pagar
                  </v-btn>
                  <span v-else class="text-grey">Pagada</span>
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>

        <div v-else class="mb-4">
          <v-alert type="warning">
            Esta venta no tiene un plan de pagos asociado. 
            <v-btn 
              color="primary" 
              size="small" 
              class="ml-2"
              @click="router.visit(route('payment-plans.create', { sale_id: sale.id_venta }))"
            >
              Crear Plan de Pagos
            </v-btn>
          </v-alert>
        </div>
      </v-card-text>
    </v-card>

    <!-- Diálogo de Pago de Cuota -->
    <v-dialog v-model="payDialog" max-width="500px">
      <v-card>
        <v-card-title>Registrar Pago de Cuota</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="submitPayment">
            <v-alert type="info" class="mb-4">
              <div>
                <strong>Cuota #{{ selectedInstallment?.numero_cuota }}</strong><br>
                Monto: <strong>Bs. {{ selectedInstallment?.monto }}</strong><br>
                Vencimiento: {{ selectedInstallment ? formatDate(selectedInstallment.fecha_vencimiento) : '' }}
              </div>
            </v-alert>
            
            <v-text-field
              v-model="payForm.fecha_pago"
              label="Fecha de Pago"
              type="date"
              :error-messages="payForm.errors.fecha_pago"
              required
              class="mb-3"
            ></v-text-field>
            
            <v-select
              v-model="payForm.metodo_pago"
              :items="metodosPago"
              label="Método de Pago"
              :error-messages="payForm.errors.metodo_pago"
              required
              class="mb-3"
            ></v-select>

            <v-text-field
              v-model="payForm.referencia"
              label="Referencia (opcional)"
              :error-messages="payForm.errors.referencia"
              hint="Número de transacción, cheque, etc."
              persistent-hint
            ></v-text-field>
            
            <div class="d-flex justify-end mt-4">
              <v-btn color="grey" class="mr-2" @click="payDialog = false" :disabled="payForm.processing">
                Cancelar
              </v-btn>
              <v-btn color="success" type="submit" :loading="payForm.processing">
                Confirmar Pago y Generar Recibo
              </v-btn>
            </div>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  sale: Object,
});

const payDialog = ref(false);
const selectedInstallment = ref(null);
const metodosPago = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'CHEQUE'];
const payForm = useForm({
  id_cuota: null,
  fecha_pago: new Date().toISOString().split('T')[0],
  metodo_pago: 'EFECTIVO',
  referencia: ''
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getStatusColor = (status) => {
  const colors = {
    'PAGADA': 'green',
    'ANULADA': 'red',
    'PENDIENTE': 'orange',
  };
  return colors[status] || 'grey';
};

const getPaymentPlanColor = (status) => {
  const colors = {
    'ACTIVO': 'blue',
    'COMPLETADO': 'green',
    'CANCELADO': 'red',
  };
  return colors[status] || 'grey';
};

const getInstallmentColor = (status) => {
  return status === 'PAGADA' ? 'green' : status === 'ATRASADA' ? 'red' : 'orange';
};

const openPayDialog = (installment) => {
  selectedInstallment.value = installment;
  payForm.id_cuota = installment.id_cuota;
  payForm.fecha_pago = new Date().toISOString().split('T')[0];
  payForm.metodo_pago = 'EFECTIVO';
  payForm.referencia = '';
  payForm.clearErrors();
  payDialog.value = true;
};

const submitPayment = () => {
  if (!props.sale.payment_plan) return;
  
  // Agregar parámetro para identificar que viene desde ventas
  payForm.transform((data) => ({
    ...data,
    from_sale: true
  })).post(route('payment-plans.pay-installment', props.sale.payment_plan.id_plan), {
    preserveScroll: true,
    onSuccess: (page) => {
      payDialog.value = false;
      payForm.reset();
      // El controlador redirigirá automáticamente
    },
    onError: (errors) => {
      console.error('Errores al pagar cuota:', errors);
      // Los errores se mostrarán automáticamente en los campos
    },
    onFinish: () => {
      // Esto se ejecuta siempre al final
    }
  });
};
</script>
