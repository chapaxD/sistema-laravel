<template>
  <AuthenticatedLayout title="Categorías">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Categorías</span>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              :to="route('categorias.create')"
            >
              Nueva Categoría
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="categorias.data"
              :items-per-page="15"
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
                  :to="route('categorias.edit', item.id_categoria)"
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

defineProps({
  categorias: Object,
});

const headers = [
  { title: 'ID', key: 'id_categoria', sortable: true },
  { title: 'Nombre', key: 'nombre', sortable: true },
  { title: 'Descripción', key: 'descripcion', sortable: false },
  { title: 'Productos', key: 'products_count', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const deleteCategoria = (id) => {
  if (confirm('¿Está seguro de eliminar esta categoría?')) {
    router.delete(route('categorias.destroy', id));
  }
};
</script>
