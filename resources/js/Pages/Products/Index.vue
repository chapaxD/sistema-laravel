<template>
  <AuthenticatedLayout title="Productos">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Productos</span>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              :to="route('inventory.products.create')"
            >
              Nuevo Producto
            </v-btn>
          </v-card-title>

          <v-card-text>

            <!-- Buscador -->
            <v-row class="mb-4">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="searchQuery"
                  prepend-inner-icon="mdi-magnify"
                  label="Buscar productos..."
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
              :items="products.data"
              :items-per-page="-1"
              hide-default-footer
              class="elevation-1"
            >
              <template v-slot:item.precio_unit="{ item }">
                Bs. {{ parseFloat(item.precio_unit).toFixed(2) }}
              </template>

              <template v-slot:item.estado="{ item }">
                <v-chip
                  :color="item.estado === 'ACTIVO' ? 'success' : 'error'"
                  dark
                  small
                >
                  {{ item.estado }}
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  :to="route('inventory.products.edit', item.id_producto)"
                ></v-btn>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteProduct(item.id_producto)"
                ></v-btn>
              </template>
            </v-data-table>

            <!-- Paginador -->
            <div class="d-flex justify-center mt-4">
              <v-pagination
                v-model="pageNumber"
                :length="products.last_page"
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
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  products: Object,
});

const pageNumber = ref(props.products.current_page);
const searchQuery = ref('');
let searchTimeout = null;

const headers = [
  { title: 'ID', key: 'id_producto', sortable: true },
  { title: 'Nombre', key: 'nombre', sortable: true },
  { title: 'Descripción', key: 'descripcion', sortable: false },
  { title: 'Precio Unitario', key: 'precio_unit', sortable: true },
  { title: 'Stock', key: 'stock', sortable: true },
  { title: 'Categoría', key: 'categoria.nombre', sortable: true },
  { title: 'Estado', key: 'estado', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const deleteProduct = (id) => {
  if (confirm('¿Está seguro de eliminar este producto?')) {
    router.delete(route('inventory.products.destroy', id));
  }
};

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;
    router.get(route('inventory.products.index'), { 
      search: searchQuery.value,
      page: 1 
    }, {
      preserveScroll: true,
      preserveState: true,
    });
  }, 500);
};

const changePage = (page) => {
  router.get(route('inventory.products.index'), { 
    page,
    search: searchQuery.value 
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
