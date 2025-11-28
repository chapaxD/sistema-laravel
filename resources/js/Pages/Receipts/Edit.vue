<template>
  <AuthenticatedLayout title="Editar Recibo">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Recibo #{{ receipt.numero_recibo || 'REC-' + String(receipt.id_recibo).padStart(6, '0') }}</span>
          <v-btn color="grey" @click="router.visit(route('receipts.index'))">
            Volver
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text>
        <v-alert
          v-if="Object.keys(form.errors).length > 0"
          type="error"
          class="mb-4"
        >
          Por favor corrija los errores en el formulario.
          <ul>
            <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
          </ul>
        </v-alert>

        <v-form @submit.prevent="submit">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.fecha_emision"
                label="Fecha de Pago"
                type="date"
                :error-messages="form.errors.fecha_emision"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.observacion"
                label="Observación"
                :error-messages="form.errors.observacion"
                hint="Descripción del recibo"
                persistent-hint
              ></v-text-field>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <div class="d-flex justify-space-between align-center mb-4">
            <h3>Detalles de Pago</h3>
            <div class="text-h6">
              Total Venta: Bs. {{ parseFloat(receipt.sale?.total || 0).toFixed(2) }}
            </div>
          </div>

          <v-table>
            <thead>
              <tr>
                <th>Método de Pago</th>
                <th>Referencia</th>
                <th>Monto</th>
                <th style="width: 60px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(detail, index) in form.detalles_pago" :key="index">
                <td>
                  <v-select
                    v-model="detail.metodo_pago"
                    :items="metodosPago"
                    density="compact"
                    hide-details
                  ></v-select>
                </td>
                <td>
                  <v-text-field
                    v-model="detail.referencia"
                    placeholder="Referencia (opcional)"
                    density="compact"
                    hide-details
                  ></v-text-field>
                </td>
                <td>
                  <v-text-field
                    v-model.number="detail.monto"
                    type="number"
                    step="0.01"
                    density="compact"
                    hide-details
                  ></v-text-field>
                </td>
                <td>
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    color="error"
                    @click="removeDetail(index)"
                  ></v-btn>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="text-right font-weight-bold">Total Pagado:</td>
                <td class="font-weight-bold" :class="{'text-error': receipt.sale && totalPagado !== parseFloat(receipt.sale.total)}">
                  Bs. {{ totalPagado.toFixed(2) }}
                </td>
                <td></td>
              </tr>
            </tfoot>
          </v-table>

          <v-btn
            color="success"
            prepend-icon="mdi-plus"
            class="mt-4"
            @click="addDetail"
          >
            Agregar Pago
          </v-btn>

          <v-alert
            v-if="receipt.sale && Math.abs(totalPagado - parseFloat(receipt.sale.total)) > 0.01"
            type="warning"
            class="mt-4"
          >
            El total pagado ({{ totalPagado.toFixed(2) }}) no coincide con el total de la venta ({{ parseFloat(receipt.sale.total).toFixed(2) }}).
          </v-alert>

          <v-divider class="my-4"></v-divider>

          <div class="text-right">
            <v-btn
              type="submit"
              color="primary"
              :loading="form.processing"
              size="large"
            >
              Actualizar Recibo
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  receipt: Object,
});

const metodosPago = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'CHEQUE'];

const form = useForm({
  fecha_emision: (props.receipt.fecha || props.receipt.fecha_emision || new Date()).toISOString().split('T')[0],
  observacion: props.receipt.observacion || '',
  detalles_pago: (props.receipt.payment_details || props.receipt.paymentDetails || []).map(d => ({
    metodo_pago: d.metodo,
    monto: parseFloat(d.monto),
    referencia: d.referencia || ''
  }))
});

const totalPagado = computed(() => {
  return form.detalles_pago.reduce((sum, detail) => sum + (parseFloat(detail.monto) || 0), 0);
});

const addDetail = () => {
  form.detalles_pago.push({
    metodo_pago: 'EFECTIVO',
    monto: 0,
    referencia: ''
  });
};

const removeDetail = (index) => {
  form.detalles_pago.splice(index, 1);
};

const submit = () => {
  form.put(route('receipts.update', props.receipt.id_recibo));
};
</script>
