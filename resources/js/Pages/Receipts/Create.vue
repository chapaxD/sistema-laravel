<template>
  <AuthenticatedLayout title="Nuevo Recibo">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Nuevo Recibo</span>
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
              <v-select
                v-model="form.id_venta"
                :items="salesWithDetails"
                item-title="full_description"
                item-value="id_venta"
                label="Seleccionar Venta"
                :error-messages="form.errors.id_venta"
                required
                @update:model-value="updateSaleDetails"
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.fecha_emision"
                label="Fecha de Pago"
                type="date"
                :error-messages="form.errors.fecha_emision"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12">
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
            <div v-if="selectedSale" class="text-h6">
              Total Venta: Bs. {{ parseFloat(selectedSale.total).toFixed(2) }}
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
                <td class="font-weight-bold">
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

          <v-divider class="my-4"></v-divider>

          <v-alert
            v-if="selectedSale && totalPagado !== parseFloat(selectedSale.total)"
            type="warning"
            class="mb-4"
            density="compact"
          >
            El monto total del recibo (Bs. {{ totalPagado.toFixed(2) }}) no coincide con el total de la venta (Bs. {{ parseFloat(selectedSale.total).toFixed(2) }}).
          </v-alert>

          <div class="text-right">
            <v-btn
              type="submit"
              color="primary"
              :loading="form.processing"
              size="large"
              :disabled="!form.id_venta || form.detalles_pago.length === 0 || (selectedSale && totalPagado !== parseFloat(selectedSale.total))"
            >
              Crear Recibo
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  sales: Array,
});

const selectedSale = ref(null);
const metodosPago = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'CHEQUE'];

const salesWithDetails = computed(() => {
  return props.sales.map(sale => ({
    ...sale,
    full_description: `Venta #${sale.id_venta} - ${sale.client?.nombre} ${sale.client?.apellido} - Bs. ${sale.total}`
  }));
});

const form = useForm({
  id_venta: null,
  fecha_emision: new Date().toISOString().split('T')[0],
  observacion: '',
  detalles_pago: [{
    metodo_pago: 'EFECTIVO',
    monto: 0,
    referencia: ''
  }]
});

const totalPagado = computed(() => {
  return form.detalles_pago.reduce((sum, detail) => sum + (parseFloat(detail.monto) || 0), 0);
});

const updateSaleDetails = () => {
  selectedSale.value = props.sales.find(s => s.id_venta === form.id_venta);
  if (selectedSale.value && form.detalles_pago.length === 1 && form.detalles_pago[0].monto === 0) {
    form.detalles_pago[0].monto = parseFloat(selectedSale.value.total);
  }
};

const addDetail = () => {
  form.detalles_pago.push({
    metodo_pago: 'EFECTIVO',
    monto: 0,
    referencia: ''
  });
};

const removeDetail = (index) => {
  if (form.detalles_pago.length > 1) {
    form.detalles_pago.splice(index, 1);
  }
};

const submit = () => {
  form.post(route('receipts.store'));
};
</script>

