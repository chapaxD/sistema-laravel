<template>
  <AuthenticatedLayout title="Editar Venta">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Editar Venta #{{ sale.id_venta }}</span>
          <v-btn color="grey" @click="router.visit(route('sales.index'))">
            Cancelar
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
                v-model="form.id_cliente"
                :items="clientsWithNames"
                item-title="full_name"
                item-value="id_usuario"
                label="Cliente"
                :error-messages="form.errors.id_cliente"
                required
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.fecha_venta"
                label="Fecha de Venta"
                type="date"
                :error-messages="form.errors.fecha_venta"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.estado"
                :items="['PENDIENTE', 'PAGADA', 'ANULADA']"
                label="Estado"
                :error-messages="form.errors.estado"
                required
              ></v-select>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-4">Productos</h3>

          <v-table>
            <thead>
              <tr>
                <th>Producto</th>
                <th style="width: 120px">Cantidad</th>
                <th style="width: 120px">Precio</th>
                <th style="width: 120px">Subtotal</th>
                <th style="width: 60px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(detail, index) in form.detalles" :key="index">
                <td>
                  <v-select
                    v-model="detail.id_producto"
                    :items="products"
                    item-title="nombre"
                    item-value="id_producto"
                    density="compact"
                    @update:model-value="updatePrice(index)"
                    hide-details
                  ></v-select>
                </td>
                <td>
                  <v-text-field
                    v-model.number="detail.cantidad"
                    type="number"
                    min="1"
                    density="compact"
                    @input="updateSubtotal(index)"
                    hide-details
                  ></v-text-field>
                </td>
                <td>
                  <v-text-field
                    v-model.number="detail.precio_unitario"
                    type="number"
                    readonly
                    density="compact"
                    hide-details
                  ></v-text-field>
                </td>
                <td>
                  <v-text-field
                    v-model.number="detail.subtotal"
                    type="number"
                    readonly
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
          </v-table>

          <v-btn
            color="success"
            prepend-icon="mdi-plus"
            class="mt-4"
            @click="addDetail"
          >
            Agregar Producto
          </v-btn>

          <v-divider class="my-4"></v-divider>

          <div class="text-right">
            <h3>Total: Bs. {{ totalAmount.toFixed(2) }}</h3>
          </div>

          <div class="d-flex justify-end align-center mt-4">
            <v-btn
              v-if="sale.estado === 'PAGADA' && !sale.receipt && !sale.payment_plan"
              color="info"
              class="mr-4"
              @click="generateReceipt"
            >
              Generar Recibo
            </v-btn>

            <v-btn
              v-if="sale.estado === 'PAGADA' && !sale.payment_plan && !sale.receipt"
              color="warning"
              class="mr-4"
              @click="generatePaymentPlan"
            >
              Crear Plan de Pagos
            </v-btn>

            <v-btn
              type="submit"
              color="primary"
              :loading="form.processing"
              size="large"
            >
              Actualizar Venta
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>

  <!-- Diálogo para Plan de Pagos -->
  <v-dialog v-model="paymentPlanDialog" max-width="500px">
    <v-card>
      <v-card-title>Crear Plan de Pagos</v-card-title>
      <v-card-text>
        <v-form @submit.prevent="submitPaymentPlan">
          <v-text-field
            v-model.number="paymentPlanForm.numero_cuotas"
            label="Número de Cuotas"
            type="number"
            min="2"
            max="24"
            required
          ></v-text-field>
          
          <v-text-field
            v-model="paymentPlanForm.fecha_inicio"
            label="Fecha de Inicio"
            type="date"
            required
          ></v-text-field>
          
          <div class="d-flex justify-end mt-4">
            <v-btn color="grey" class="mr-2" @click="paymentPlanDialog = false">Cancelar</v-btn>
            <v-btn color="primary" type="submit">Crear Plan</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  sale: Object,
  clients: Array,
  products: Array,
});

const paymentPlanDialog = ref(false);
const paymentPlanForm = useForm({
  numero_cuotas: 3,
  fecha_inicio: new Date().toISOString().split('T')[0]
});

const clientsWithNames = computed(() => {
  return props.clients.map(client => ({
    ...client,
    full_name: `${client.nombre} ${client.apellido}`
  }));
});

const form = useForm({
  id_cliente: props.sale.id_cliente,
  fecha_venta: props.sale.fecha_venta.split('T')[0], // Formato YYYY-MM-DD
  estado: props.sale.estado,
  detalles: props.sale.details.map(d => ({
    id_producto: d.id_producto,
    cantidad: parseFloat(d.cantidad),
    precio_unitario: parseFloat(d.precio_unit || d.precio_unitario || 0),
    subtotal: parseFloat(d.subtotal)
  }))
});

const totalAmount = computed(() => {
  return form.detalles.reduce((sum, detail) => sum + (detail.subtotal || 0), 0);
});

const addDetail = () => {
  form.detalles.push({
    id_producto: null,
    cantidad: 1,
    precio_unitario: 0,
    subtotal: 0
  });
};

const removeDetail = (index) => {
  form.detalles.splice(index, 1);
};

const updatePrice = (index) => {
  const detail = form.detalles[index];
  const product = props.products.find(p => p.id_producto === detail.id_producto);
  if (product) {
    detail.precio_unitario = parseFloat(product.precio_unit);
    updateSubtotal(index);
  }
};

const updateSubtotal = (index) => {
  const detail = form.detalles[index];
  detail.subtotal = detail.cantidad * detail.precio_unitario;
};

const submit = () => {
  form.put(route('sales.update', props.sale.id_venta));
};

const generateReceipt = () => {
  if (confirm('¿Generar recibo para esta venta?')) {
    router.post(route('receipts.store-from-sale', props.sale.id_venta));
  }
};

const generatePaymentPlan = () => {
  paymentPlanDialog.value = true;
};

const submitPaymentPlan = () => {
  paymentPlanForm.post(route('payment-plans.store-from-sale', props.sale.id_venta), {
    onSuccess: () => paymentPlanDialog.value = false
  });
};
</script>
