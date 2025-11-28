<template>
  <AuthenticatedLayout title="Editar Cotización">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Editar Cotización #{{ quotation.id_cotizacion }}</span>
          <v-btn color="grey" @click="router.visit(route('quotations.index'))">
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
              <v-select
                v-model="form.id_servicio"
                :items="services"
                item-title="nombre"
                item-value="id_servicio"
                label="Servicio Principal"
                :error-messages="form.errors.id_servicio"
                required
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.estado"
                :items="estados"
                label="Estado"
                :error-messages="form.errors.estado"
                required
              ></v-select>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-4">Productos / Materiales</h3>

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

          <div class="text-right mt-4">
            <v-btn
              type="submit"
              color="primary"
              :loading="form.processing"
              size="large"
            >
              Actualizar Cotización
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
  quotation: Object,
  clients: Array,
  services: Array,
  products: Array,
});

const estados = ['PENDIENTE', 'APROBADA', 'RECHAZADA'];

const clientsWithNames = computed(() => {
  return props.clients.map(client => ({
    ...client,
    full_name: `${client.nombre} ${client.apellido}`
  }));
});

const form = useForm({
  id_cliente: props.quotation.id_cliente,
  id_servicio: props.quotation.id_servicio,
  estado: props.quotation.estado,
  detalles: props.quotation.details.map(d => ({
    id_producto: d.id_producto,
    cantidad: d.cantidad,
    precio_unitario: parseFloat(d.precio_unit),
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
  console.log('Enviando actualización:', form.data());
  form.put(route('quotations.update', props.quotation.id_cotizacion));
};
</script>
