<template>
  <AuthenticatedLayout title="Detalle de Cotización">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Cotización #{{ quotation.id_cotizacion }}</span>
          <div>
            <!-- Botones de PDF -->
            <v-btn 
              color="success" 
              @click="downloadPDF"
              class="mr-2"
            >
              <v-icon left>mdi-file-pdf-box</v-icon>
              Descargar PDF
            </v-btn>
            <v-btn 
              color="info" 
              @click="viewPDF"
              class="mr-2"
            >
              <v-icon left>mdi-eye</v-icon>
              Ver PDF
            </v-btn>

            <v-btn 
              v-if="quotation.estado === 'PENDIENTE'" 
              color="primary" 
              @click="router.visit(route('quotations.edit', quotation.id_cotizacion))"
              class="mr-2"
            >
              <v-icon left>mdi-pencil</v-icon>
              Editar
            </v-btn>
            <v-btn color="grey" @click="router.visit(route('quotations.index'))">
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
                  {{ quotation.client.nombre }} {{ quotation.client.apellido }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Email</v-list-item-title>
                <v-list-item-subtitle>{{ quotation.client.email }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Teléfono</v-list-item-title>
                <v-list-item-subtitle>{{ quotation.client.telefono }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>

          <v-col cols="12" md="6">
            <v-list>
              <v-list-item>
                <v-list-item-title class="text-grey">Servicio</v-list-item-title>
                <v-list-item-subtitle class="text-h6">{{ quotation.service.nombre }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Fecha</v-list-item-title>
                <v-list-item-subtitle>{{ formatDate(quotation.fecha_creacion) }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Estado</v-list-item-title>
                <v-list-item-subtitle>
                  <v-chip :color="getStatusColor(quotation.estado)" size="small">
                    {{ quotation.estado }}
                  </v-chip>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <h3 class="mb-4">Productos / Materiales</h3>

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
            <tr v-for="detail in quotation.details" :key="detail.id_detalle">
              <td>{{ detail.product.nombre }}</td>
              <td class="text-right">{{ detail.cantidad }}</td>
              <td class="text-right">Bs. {{ parseFloat(detail.precio_unit).toFixed(2) }}</td>
              <td class="text-right">Bs. {{ parseFloat(detail.subtotal).toFixed(2) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="text-right font-weight-bold">TOTAL:</td>
              <td class="text-right font-weight-bold text-h6">
                Bs. {{ parseFloat(quotation.total).toFixed(2) }}
              </td>
            </tr>
          </tfoot>
        </v-table>

        <v-divider class="my-4"></v-divider>

        <div v-if="quotation.estado === 'APROBADA' && !quotation.work_order" class="text-center">
          <v-btn
            color="success"
            size="large"
            @click="generateOrder"
          >
            <v-icon left>mdi-clipboard-text</v-icon>
            Generar Orden de Trabajo
          </v-btn>
        </div>

        <v-alert v-if="quotation.work_order" type="info" class="mt-4">
          Esta cotización ya tiene una orden de trabajo generada: 
          <strong>Orden #{{ quotation.work_order.id_orden }}</strong>
        </v-alert>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  quotation: Object,
});

const downloadPDF = () => {
  window.open(route('quotations.pdf', props.quotation.id_cotizacion), '_blank');
};

const viewPDF = () => {
  window.open(route('quotations.view-pdf', props.quotation.id_cotizacion), '_blank');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getStatusColor = (status) => {
  const colors = {
    'PENDIENTE': 'orange',
    'APROBADA': 'green',
    'RECHAZADA': 'red',
  };
  return colors[status] || 'grey';
};

const generateOrder = () => {
  if (confirm('¿Generar orden de trabajo para esta cotización?')) {
    router.post(route('quotations.generate-order', props.quotation.id_cotizacion));
  }
};
</script>
