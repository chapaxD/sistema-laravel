<template>
  <AuthenticatedLayout title="Detalle de Plan de Pagos">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Plan de Pagos #{{ paymentPlan.id_plan }}</span>
          <div>
            <v-btn color="grey" @click="router.visit(route('payment-plans.index'))">
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
                  {{ paymentPlan.sale.client.nombre }} {{ paymentPlan.sale.client.apellido }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Venta Asociada</v-list-item-title>
                <v-list-item-subtitle>
                  <a href="#" @click.prevent="router.visit(route('sales.show', paymentPlan.id_venta))">
                    Venta #{{ paymentPlan.id_venta }}
                  </a>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>

          <v-col cols="12" md="6">
            <v-list>
              <v-list-item>
                <v-list-item-title class="text-grey">Monto Total</v-list-item-title>
                <v-list-item-subtitle class="text-h6">Bs. {{ parseFloat(paymentPlan.monto_total).toFixed(2) }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Estado</v-list-item-title>
                <v-list-item-subtitle>
                  <v-chip :color="getStatusColor(paymentPlan.estado)" size="small">
                    {{ paymentPlan.estado }}
                  </v-chip>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <h3 class="mb-4">Cuotas</h3>

        <v-table>
          <thead>
            <tr>
              <th>#</th>
              <th>Vencimiento</th>
              <th>Monto</th>
              <th>Estado</th>
              <th>Fecha Pago</th>
              <th style="width: 120px">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="installment in paymentPlan.installments" :key="installment.id_cuota">
              <td>{{ installment.numero_cuota }}</td>
              <td>{{ formatDate(installment.fecha_vencimiento) }}</td>
              <td>Bs. {{ parseFloat(installment.monto).toFixed(2) }}</td>
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
                  Pagar
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- Diálogo de Pago -->
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
  paymentPlan: Object,
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
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getStatusColor = (status) => {
  const colors = {
    'ACTIVO': 'blue',
    'COMPLETADO': 'green',
    'CANCELADO': 'red',
  };
  return colors[status] || 'grey';
};

const getInstallmentColor = (status) => {
  return status === 'PAGADA' ? 'green' : 'orange';
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
  payForm.post(route('payment-plans.pay-installment', props.paymentPlan.id_plan), {
    preserveScroll: true,
    onSuccess: (page) => {
      payDialog.value = false;
      payForm.reset();
      // El controlador redirigirá automáticamente
    },
    onError: (errors) => {
      console.error('Errores al pagar cuota:', errors);
      // Los errores se mostrarán automáticamente en los campos
    }
  });
};
</script>
