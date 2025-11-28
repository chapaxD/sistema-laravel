<template>
  <AuthenticatedLayout title="Inventario - Nuevo Producto">
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <span class="text-h5">Nuevo Producto</span>
            <v-breadcrumbs :items="breadcrumbs" class="pa-0 mt-1"></v-breadcrumbs>
          </v-card-title>

          <v-card-text>
            <v-form @submit.prevent="submit">
              <v-text-field
                v-model="form.nombre"
                label="Nombre *"
                :error-messages="form.errors.nombre"
                required
                variant="outlined"
                density="comfortable"
              ></v-text-field>

              <v-textarea
                v-model="form.descripcion"
                label="Descripción"
                :error-messages="form.errors.descripcion"
                variant="outlined"
                density="comfortable"
                rows="3"
              ></v-textarea>

              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.number="form.precio_unit"
                    label="Precio Unitario *"
                    :error-messages="form.errors.precio_unit"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    variant="outlined"
                    density="comfortable"
                    prefix="Bs."
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.number="form.stock"
                    label="Stock *"
                    :error-messages="form.errors.stock"
                    type="number"
                    min="0"
                    required
                    variant="outlined"
                    density="comfortable"
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-select
                v-model="form.id_categoria"
                :items="categorias"
                item-title="nombre"
                item-value="id_categoria"
                label="Categoría *"
                :error-messages="form.errors.id_categoria"
                required
                variant="outlined"
                density="comfortable"
              ></v-select>

              <v-select
                v-model="form.estado"
                :items="estados"
                label="Estado *"
                :error-messages="form.errors.estado"
                required
                variant="outlined"
                density="comfortable"
              ></v-select>

              <v-card-actions>
                <v-btn
                  color="grey"
                  variant="text"
                  :href="route('inventory.products.index')"
                >
                  Cancelar
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  type="submit"
                  :loading="form.processing"
                >
                  Guardar
                </v-btn>
              </v-card-actions>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  categorias: Array,
});

const breadcrumbs = [
  { title: 'Inventario', disabled: false },
  { title: 'Productos', disabled: false, href: route('inventory.products.index') },
  { title: 'Nuevo', disabled: true },
];

const estados = ['ACTIVO', 'AGOTADO', 'DESCONTINUADO'];

const form = useForm({
  nombre: '',
  descripcion: '',
  precio_unit: '',
  stock: 0,
  id_categoria: null,
  estado: 'ACTIVO',
});

const submit = () => {
  form.post(route('inventory.products.store'));
};
</script>
