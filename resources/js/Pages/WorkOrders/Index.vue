<template>
  <AuthenticatedLayout title="Órdenes de Trabajo">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Órdenes de Trabajo</span>
          </v-card-title>

          <v-card-text>

            <!-- Buscador -->
            <v-row class="mb-4">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="searchQuery"
                  prepend-inner-icon="mdi-magnify"
                  label="Buscar órdenes de trabajo..."
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
              :items="workOrders.data"
              :items-per-page="-1"
              hide-default-footer
              class="elevation-1"
            >
              <template v-slot:item.estado="{ item }">
                <v-chip :color="getStatusColor(item.estado)" dark small>
                  {{ item.estado }}
                </v-chip>
              </template>

              <template v-slot:item.cliente="{ item }">
                {{ item.quotation?.client?.nombre }} {{ item.quotation?.client?.apellido }}
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn icon="mdi-pencil" size="small" variant="text"
                  @click="router.visit(route('work-orders.edit', item.id_orden))"
                ></v-btn>
                <v-btn v-if="item.estado === 'FINALIZADA' && !item.id_venta"
                  icon="mdi-cash-register" size="small" variant="text"
                  color="success"
                  @click="generateSale(item.id_orden)"
                ></v-btn>
                <v-btn v-if="canDelete"
                  icon="mdi-delete" size="small" variant="text"
                  color="error"
                  @click="deleteOrder(item.id_orden)"
                ></v-btn>
              </template>
            </v-data-table>

            <!-- Paginador -->
            <div class="d-flex justify-center mt-4">
              <v-pagination
                v-model="pageNumber"
                :length="workOrders.last_page"
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
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  workOrders: Object,
});

const pageNumber = ref(props.workOrders.current_page);
const searchQuery = ref('');
let searchTimeout = null;

const page = usePage();
const auth = computed(() => page.props.auth);
const canDelete = computed(() => auth.value.user?.rol === 'Administrador');

const headers = [
  { title: 'ID', key: 'id_orden', sortable: true },
  { title: 'Cliente', key: 'cliente', sortable: false },
  { title: 'Servicio', key: 'quotation.service.nombre', sortable: true },
  { title: 'Fecha Programada', key: 'fecha_programada', sortable: true },
  { title: 'Estado', key: 'estado', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const generateSale = (id) => {
  if (confirm('¿Generar venta para esta orden finalizada?')) {
    router.post(route('sales.store-from-order', id), {
      onSuccess: () => router.visit(route('sales.index')),
    });
  }
};

const deleteOrder = (id) => {
  if (confirm('¿Está seguro de eliminar esta orden de trabajo?')) {
    router.delete(route('work-orders.destroy', id));
  }
};

const getStatusColor = (status) => {
  const colors = {
    PROGRAMADA: 'blue',
    EN_PROGRESO: 'orange',
    FINALIZADA: 'green',
  };
  return colors[status] || 'grey';
};

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;
    router.get(route('work-orders.index'), { 
      search: searchQuery.value,
      page: 1 
    }, {
      preserveScroll: true,
      preserveState: true,
    });
  }, 500);
};

const changePage = (page) => {
  router.get(route('work-orders.index'), { 
    page,
    search: searchQuery.value 
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
