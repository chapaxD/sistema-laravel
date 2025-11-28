<template>
  <AuthenticatedLayout title="Inventario - Categorías">
    <v-row>
      <v-col cols="12" sm="6" md="4">
        <v-select
          v-model="perPage"
          :items="perPageOptions"
          label="Items por página"
          dense
          hide-details
          @change="() => router.get(route('inventory.categories.index'), { per_page: perPage }, { preserveState: true, replace: true })"
        ></v-select>
      </v-col>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <div>
              <span class="text-h5">Categorías</span>
              <v-breadcrumbs :items="breadcrumbs" class="pa-0 mt-1"></v-breadcrumbs>
            </div>
            <div>
              <v-btn
                color="secondary"
                prepend-icon="mdi-package-variant"
                :href="route('inventory.products.index')"
                class="mr-2"
              >
                Productos
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                :href="route('inventory.categories.create')"
              >
                Nueva Categoría
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="categories.data"
              :items-per-page="perPage"
              :server-items-length="categories.total"
              :page="categories.current_page"
              class="elevation-1"
            >
              <template v-slot:item.products_count="{ item }">
                <v-chip color="info" small>
                  {{ item.products_count }} productos
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  :href="route('inventory.categories.edit', item.id_categoria)"
                ></v-btn>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteCategoria(item.id_categoria)"
                ></v-btn>
              </template>
            </v-data-table>
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

defineProps({
  categories: Object,
});

const perPageOptions = [15, 30, 50, 100];

const perPage = ref(new URLSearchParams(window.location.search).get('per_page') ? parseInt(new URLSearchParams(window.location.search).get('per_page')) : 15);

const breadcrumbs = [
  { title: 'Inventario', disabled: false },
  { title: 'Categorías', disabled: true },
];

const headers = [
  { title: 'ID', key: 'id_categoria', sortable: true },
  { title: 'Nombre', key: 'nombre', sortable: true },
  { title: 'Descripción', key: 'descripcion', sortable: false },
  { title: 'Productos', key: 'products_count', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const deleteCategoria = (id) => {
  if (confirm('¿Está seguro de eliminar esta categoría?')) {
    router.delete(route('inventory.categories.destroy', id));
  }
};
</script>
