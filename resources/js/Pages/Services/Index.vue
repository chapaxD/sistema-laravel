<template>
  <AuthenticatedLayout title="Servicios">
    <v-card>

      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h5">Servicios</span>

        <v-btn color="primary" @click="router.visit(route('services.create'))">
          <v-icon left>mdi-plus</v-icon>
          Nuevo Servicio
        </v-btn>
      </v-card-title>

      <v-card-text>

        <!-- BUSCADOR -->
        <v-row class="mb-4">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="searchQuery"
              prepend-inner-icon="mdi-magnify"
              label="Buscar servicios..."
              variant="outlined"
              density="compact"
              clearable
              hide-details
              @input="handleSearch"
            />
          </v-col>
        </v-row>

        <!-- TABLA -->
        <v-data-table
          :headers="headers"
          :items="services.data"
          :items-per-page="-1"
          hide-default-footer
          class="elevation-1"
        >
          <template #item.precio_base="{ item }">
            Bs. {{ parseFloat(item.precio_base).toFixed(2) }}
          </template>

          <template #item.actions="{ item }">
            <v-btn icon size="small"
                   @click="router.visit(route('services.edit', item.id_servicio))">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
          </template>
        </v-data-table>

        <!-- PAGINADOR -->
        <div class="d-flex justify-center mt-4">
          <v-pagination
            v-model="pageNumber"
            :length="services.last_page"
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
  services: Object,
  filters: Object
});

// Estado
const pageNumber = ref(props.services.current_page);
const searchQuery = ref(props.filters.search || "");
let searchTimeout = null;

// Headers
const headers = [
  { title: 'ID', key: 'id_servicio' },
  { title: 'Nombre', key: 'nombre' },
  { title: 'Descripción', key: 'descripcion' },
  { title: 'Tipo', key: 'tipo' },
  { title: 'Subtipo', key: 'subtipo' },
  { title: 'Acciones', key: 'actions', sortable: false },
];

// Métodos
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;

    router.get(route('services.index'), {
      search: searchQuery.value,
      page: 1
    }, {
      preserveScroll: true,
      preserveState: true,
    });
  }, 500);
};

const changePage = (page) => {
  router.get(route('services.index'), {
    page,
    search: searchQuery.value
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
