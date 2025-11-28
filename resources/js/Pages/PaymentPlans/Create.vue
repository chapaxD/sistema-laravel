<template>
  <AuthenticatedLayout title="Crear Plan de Pago">
    <v-card>
      <v-card-title>
        <span class="text-h5">Nuevo Plan de Pago</span>
      </v-card-title>

      <v-card-text>
        <v-alert
          v-if="Object.keys($page.props.errors).length > 0"
          type="error"
          class="mb-4"
        >
          <ul>
            <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
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
                v-model.number="form.numero_cuotas"
                label="Número de Cuotas"
                type="number"
                min="2"
                max="24"
                :error-messages="form.errors.numero_cuotas"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.fecha_inicio"
                label="Fecha de Inicio"
                type="date"
                :error-messages="form.errors.fecha_inicio"
                required
              ></v-text-field>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <div v-if="selectedSale && form.fecha_inicio && form.numero_cuotas" class="mb-4">
            <h3>Resumen del Plan de Pagos</h3>
            <v-alert type="info" class="mb-3">
              <p><strong>Monto Total:</strong> Bs. {{ parseFloat(selectedSale.total).toFixed(2) }}</p>
              <p><strong>Número de Cuotas:</strong> {{ form.numero_cuotas }}</p>
              <p><strong>Monto por Cuota:</strong> Bs. {{ (selectedSale.total / form.numero_cuotas).toFixed(2) }}</p>
              <p><strong>Fecha de Inicio:</strong> {{ formatDate(form.fecha_inicio) }}</p>
            </v-alert>
            
            <h4 class="mb-2">Calendario de Vencimientos:</h4>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Cuota #</th>
                  <th>Fecha de Vencimiento</th>
                  <th class="text-right">Monto</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="i in parseInt(form.numero_cuotas)" :key="i">
                  <td>{{ i }}</td>
                  <td>{{ calculateDueDate(i) }}</td>
                  <td class="text-right">Bs. {{ calculateInstallmentAmount(i).toFixed(2) }}</td>
                </tr>
              </tbody>
            </v-table>
          </div>

          <div class="d-flex justify-end">
            <v-btn
              color="grey"
              class="mr-4"
              @click="router.visit(route('payment-plans.index'))"
            >
              Cancelar
            </v-btn>
            <v-btn
              type="submit"
              color="primary"
              :loading="form.processing"
              :disabled="!form.id_venta"
            >
              Crear Plan
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
  sale_id: [String, Number],
});

const selectedSale = ref(null);

const salesWithDetails = computed(() => {
  return props.sales.map(sale => ({
    ...sale,
    full_description: `Venta #${sale.id_venta} - ${sale.client?.nombre} ${sale.client?.apellido} - Bs. ${sale.total}`
  }));
});

const form = useForm({
  id_venta: props.sale_id ? parseInt(props.sale_id) : null,
  numero_cuotas: 3,
  fecha_inicio: new Date().toISOString().substr(0, 10),
});

const updateSaleDetails = () => {
  selectedSale.value = props.sales.find(s => s.id_venta === form.id_venta);
};

// Inicializar detalles si hay venta seleccionada
if (props.sale_id) {
  updateSaleDetails();
}

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const calculateDueDate = (installmentNumber) => {
  if (!form.fecha_inicio) return '-';
  const startDate = new Date(form.fecha_inicio);
  const dueDate = new Date(startDate);
  dueDate.setMonth(startDate.getMonth() + (installmentNumber - 1));
  return formatDate(dueDate.toISOString().split('T')[0]);
};

const calculateInstallmentAmount = (installmentNumber) => {
  if (!selectedSale.value || !form.numero_cuotas) return 0;
  const total = parseFloat(selectedSale.value.total);
  const numCuotas = parseInt(form.numero_cuotas);
  const montoPorCuota = total / numCuotas;
  
  // La última cuota puede tener un ajuste por redondeo
  if (installmentNumber === numCuotas) {
    const totalAnterior = montoPorCuota * (numCuotas - 1);
    return total - totalAnterior;
  }
  
  return montoPorCuota;
};

const submit = () => {
  form.post(route('payment-plans.store'));
};
</script>
