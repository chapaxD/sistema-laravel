<template>
  <AuthenticatedLayout title="Detalle de Recibo">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Recibo #{{ receipt.numero_recibo || 'REC-' + String(receipt.id_recibo).padStart(6, '0') }}</span>
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
              color="primary" 
              @click="router.visit(route('receipts.edit', receipt.id_recibo))"
              class="mr-2"
            >
              <v-icon left>mdi-pencil</v-icon>
              Editar
            </v-btn>
            <v-btn color="grey" @click="router.visit(route('receipts.index'))">
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
                  {{ receipt.sale.client.nombre }} {{ receipt.sale.client.apellido }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Venta Asociada</v-list-item-title>
                <v-list-item-subtitle>
                  <a href="#" @click.prevent="router.visit(route('sales.show', receipt.id_venta))">
                    Venta #{{ receipt.id_venta }}
                  </a>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>

          <v-col cols="12" md="6">
            <v-list>
              <v-list-item>
                <v-list-item-title class="text-grey">Fecha de Pago</v-list-item-title>
                <v-list-item-subtitle>{{ formatDate(receipt.fecha || receipt.fecha_emision) }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="text-grey">Total</v-list-item-title>
                <v-list-item-subtitle class="text-h6">
                  Bs. {{ parseFloat(receipt.total || receipt.monto_total || 0).toFixed(2) }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="receipt.observacion">
                <v-list-item-title class="text-grey">Observación</v-list-item-title>
                <v-list-item-subtitle>{{ receipt.observacion }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <h3 class="mb-4">Detalle de Pagos</h3>

        <v-table>
          <thead>
            <tr>
              <th>Método de Pago</th>
              <th>Fecha Pago</th>
              <th class="text-right">Monto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="detail in (receipt.payment_details || receipt.paymentDetails || [])" :key="detail.id_detalle">
              <td>{{ detail.metodo }}</td>
              <td>{{ formatDate(receipt.fecha || receipt.fecha_emision) }}</td>
              <td class="text-right">Bs. {{ parseFloat(detail.monto).toFixed(2) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2" class="text-right font-weight-bold">TOTAL PAGADO:</td>
              <td class="text-right font-weight-bold text-h6">
                Bs. {{ parseFloat(receipt.total || receipt.monto_total || 0).toFixed(2) }}
              </td>
            </tr>
          </tfoot>
        </v-table>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  receipt: Object,
});
const downloadPDF = () => {
  window.open(route('receipts.pdf', props.receipt.id_recibo), '_blank');
};

const viewPDF = () => {
  window.open(route('receipts.view-pdf', props.receipt.id_recibo), '_blank');
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
    'EMITIDO': 'green',
    'ANULADO': 'red',
  };
  return colors[status] || 'grey';
};
</script>
