<template>
  <AuthenticatedLayout title="Ventas">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h5">Ventas</span>
        <v-btn color="primary" @click="router.visit(route('sales.create'))">
          <v-icon left>mdi-plus</v-icon>
          Nueva Venta
        </v-btn>
      </v-card-title>

      <v-card-text>

        <!-- Buscador -->
        <v-row class="mb-4">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="searchQuery"
              prepend-inner-icon="mdi-magnify"
              label="Buscar ventas..."
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
          :items="sales.data"
          :items-per-page="-1"
          hide-default-footer
          class="elevation-1"
        >
          <template v-slot:item.fecha_venta="{ item }">
            {{ new Date(item.fecha_venta).toLocaleDateString() }}
          </template>

          <template v-slot:item.total="{ item }">
            Bs. {{ parseFloat(item.total).toFixed(2) }}
          </template>

          <template v-slot:item.client="{ item }">
            {{ item.client?.nombre }} {{ item.client?.apellido }}
          </template>

          <template v-slot:item.estado="{ item }">
            <v-chip 
              :color="item.estado === 'PAGADA' ? 'success' : item.estado === 'PENDIENTE' ? 'warning' : 'error'" 
              size="small"
            >
              {{ item.estado }}
            </v-chip>
          </template>

          <template v-slot:item.actions="{ item }">
            <v-btn icon size="small" @click="router.visit(route('sales.show', item.id_venta))">
              <v-icon>mdi-eye</v-icon>
            </v-btn>
            <v-btn icon size="small" @click="router.visit(route('sales.edit', item.id_venta))">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
          </template>
        </v-data-table>

        <!-- Paginador -->
        <div class="d-flex justify-center mt-4">
          <v-pagination
            v-model="pageNumber"
            :length="sales.last_page"
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
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  sales: Object,
});

const pageNumber = ref(props.sales.current_page);
const searchQuery = ref('');
let searchTimeout = null;

const headers = [
  { title: 'ID', key: 'id_venta' },
  { title: 'Fecha', key: 'fecha_venta' },
  { title: 'Cliente', key: 'client' },
  { title: 'Total', key: 'total' },
  { title: 'Estado', key: 'estado' },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;
    router.get(route('sales.index'), { 
      search: searchQuery.value,
      page: 1 
    }, {
      preserveScroll: true,
      preserveState: true,
    });
  }, 500);
};

const changePage = (page) => {
  router.get(route('sales.index'), { 
    page,
    search: searchQuery.value 
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
