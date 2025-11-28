<template>
  <AuthenticatedLayout title="Planes de Pago">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Planes de Pago</span>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="router.visit(route('payment-plans.create'))"
            >
              Nuevo Plan
            </v-btn>
          </v-card-title>

          <v-card-text>
            <!-- Buscador -->
            <v-row class="mb-4">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="searchQuery"
                  prepend-inner-icon="mdi-magnify"
                  label="Buscar planes de pago..."
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
              :items="paymentPlans.data"
              :items-per-page="-1"
              hide-default-footer
              class="elevation-1"
            >
              <template v-slot:item.cliente="{ item }">
                {{ item.sale?.client?.nombre || '-' }} {{ item.sale?.client?.apellido || '' }}
              </template>

              <template v-slot:item.monto_total="{ item }">
                Bs. {{ parseFloat(item.monto_total).toFixed(2) }}
              </template>

              <template v-slot:item.monto_adelanto="{ item }">
                Bs. {{ parseFloat(item.monto_adelanto || 0).toFixed(2) }}
              </template>

              <template v-slot:item.sale.id_venta="{ item }">
                {{ item.sale?.id_venta || item.id_venta || '-' }}
              </template>

              <template v-slot:item.progreso="{ item }">
                <v-progress-linear
                  :model-value="(item.cuotas_pagadas / item.cantidad_cuotas) * 100"
                  :color="item.cuotas_pagadas === item.cantidad_cuotas ? 'success' : 'primary'"
                  height="20"
                >
                  <template v-slot:default>
                    <strong>{{ item.cuotas_pagadas }}/{{ item.cantidad_cuotas }}</strong>
                  </template>
                </v-progress-linear>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="router.visit(route('payment-plans.show', item.id_plan))"
                ></v-btn>
              </template>
            </v-data-table>

            <!-- Paginación estilo Laravel -->
            <div class="d-flex justify-center mt-4">
              <v-pagination
                v-model="pageNumber"
                :length="paymentPlans.last_page"
                @update:modelValue="changePage"
                total-visible="7"
              />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  paymentPlans: Object,
});

const pageNumber = ref(props.paymentPlans.current_page);
const searchQuery = ref('');
let searchTimeout = null;

const headers = [
  { title: 'ID', key: 'id_plan', sortable: true },
  { title: 'Cliente', key: 'cliente', sortable: false },
  { title: 'Venta ID', key: 'sale.id_venta', sortable: false },
  { title: 'Monto Total', key: 'monto_total', sortable: true },
  // { title: 'Adelanto', key: 'monto_adelanto', sortable: true },
  { title: 'Cuotas', key: 'cantidad_cuotas', sortable: true },
  { title: 'Progreso', key: 'progreso', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;
    router.get(route('payment-plans.index'), { 
      search: searchQuery.value,
      page: 1 
    }, {
      preserveScroll: true,
      preserveState: true,
    });
  }, 500);
};

const changePage = (page) => {
  router.get(route('quotations.index'), { 
    page,
    search: searchQuery.value 
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>