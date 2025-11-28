<template>
  <AuthenticatedLayout title="Recibos">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h5">Recibos</span>
        <v-btn color="primary" @click="router.visit(route('receipts.create'))">
          <v-icon left>mdi-plus</v-icon>
          Nuevo Recibo
        </v-btn>
      </v-card-title>

      <v-card-text>
        <!-- Buscador -->
        <v-row class="mb-4">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="searchQuery"
              prepend-inner-icon="mdi-magnify"
              label="Buscar recibos por numero de venta..."
              variant="outlined"
              density="compact"
              clearable
              hide-details
              @input="handleSearch"
            />
          </v-col>
        </v-row>

        <!-- Tabla -->
        <v-data-table
          :headers="headers"
          :items="receipts.data"
          hide-default-footer
          class="elevation-1"
        >

          <template v-slot:item.fecha_emision="{ item }">
            {{ new Date(item.fecha || item.fecha_emision).toLocaleDateString() }}
          </template>

          <template v-slot:item.monto_total="{ item }">
            Bs. {{ parseFloat(item.total || item.monto_total || 0).toFixed(2) }}
          </template>

          <template v-slot:item.sale="{ item }">
            Venta #{{ item.sale?.id_venta }}
          </template>

          <template v-slot:item.numero_recibo="{ item }">
            {{ item.numero_recibo || 'REC-' + String(item.id_recibo).padStart(6, '0') }}
          </template>

          <template v-slot:item.observacion="{ item }">
            <span class="text-caption">{{ item.observacion || '-' }}</span>
          </template>

          <template v-slot:item.actions="{ item }">
            <v-btn icon size="small" @click="router.visit(route('receipts.show', item.id_recibo))">
              <v-icon>mdi-eye</v-icon>
            </v-btn>
          </template>
        </v-data-table>

        <!-- Paginación -->
        <div class="d-flex justify-center mt-4">
          <v-pagination
            v-model="pageNumber"
            :length="receipts.last_page"
            @update:modelValue="changePage"
            total-visible="7"
          />
        </div>

      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  receipts: Object,
});

const headers = [
  { title: 'ID', key: 'id_recibo' },
  { title: 'Número', key: 'numero_recibo' },
  { title: 'Fecha de Pago', key: 'fecha_emision' },
  { title: 'Venta', key: 'sale' },
  { title: 'Monto Total', key: 'monto_total' },
  { title: 'Observación', key: 'observacion' },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const pageNumber = ref(props.receipts.current_page);
const searchQuery = ref('');
let searchTimeout = null;

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;
    router.get(
      route('receipts.index'),
      { search: searchQuery.value, page: 1 },
      { preserveScroll: true, preserveState: true }
    );
  }, 500);
};

const changePage = (page) => {
  router.get(
    route('receipts.index'),
    { page, search: searchQuery.value },
    { preserveScroll: true, preserveState: true }
  );
};
</script>
