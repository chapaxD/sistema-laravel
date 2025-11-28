<template>
  <AuthenticatedLayout title="Cotizaciones">
    <v-row>
      <v-col cols="12">
        <v-card>

          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Cotizaciones</span>

            <v-btn color="primary" prepend-icon="mdi-plus"
                   @click="router.visit(route('quotations.create'))">
              Nueva Cotización
            </v-btn>
          </v-card-title>

          <v-card-text>

            <!-- Buscador -->
            <v-row class="mb-4">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="searchQuery"
                  prepend-inner-icon="mdi-magnify"
                  label="Buscar cotizaciones..."
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
              :items="quotations.data"
              :items-per-page="-1"
              hide-default-footer
              class="elevation-1"
            >
              <template v-slot:item.cliente="{ item }">
                {{ item.client?.nombre }} {{ item.client?.apellido }}
              </template>

              <template v-slot:item.total="{ item }">
                Bs. {{ parseFloat(item.total).toFixed(2) }}
              </template>

              <template v-slot:item.estado="{ item }">
                <v-chip :color="getStatusColor(item.estado)" dark small>
                  {{ item.estado }}
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn icon="mdi-eye" size="small" variant="text"
                       @click="router.visit(route('quotations.show', item.id_cotizacion))"/>

                <v-btn v-if="item.estado === 'PENDIENTE'"
                       icon="mdi-pencil" size="small" variant="text"
                       @click="router.visit(route('quotations.edit', item.id_cotizacion))"/>

                <v-btn v-if="item.estado === 'APROBADA' && !item.tiene_orden"
                       icon="mdi-clipboard-text" size="small" variant="text" color="success"
                       @click="generateOrder(item.id_cotizacion)"/>
              </template>
            </v-data-table>

            <!-- Paginador estilo Laravel -->
            <div class="d-flex justify-center mt-4">
              <v-pagination
                v-model="pageNumber"
                :length="quotations.last_page"
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
  quotations: Object,
});

const pageNumber = ref(props.quotations.current_page); // sincroniza con Laravel
const searchQuery = ref('');
let searchTimeout = null;

const headers = [
  { title: 'ID', key: 'id_cotizacion' },
  { title: 'Cliente', key: 'cliente' },
  { title: 'Servicio', key: 'service.nombre' },
  { title: 'Fecha', key: 'fecha_creacion' },
  { title: 'Total', key: 'total' },
  { title: 'Estado', key: 'estado' },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const getStatusColor = (status) => {
  return {
    'PENDIENTE': 'orange',
    'APROBADA': 'green',
    'RECHAZADA': 'red',
  }[status] || 'grey';
};

const generateOrder = (id) => {
  if (confirm('¿Generar orden de trabajo para esta cotización?')) {
    router.post(route('quotations.generate-order', id));
  }
};

const handleSearch = () => {
  // Debounce: esperar 500ms después de que el usuario deje de escribir
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1; // Resetear a la primera página al buscar
    router.get(route('quotations.index'), { 
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
