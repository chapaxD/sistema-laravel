<template>
  <AuthenticatedLayout title="Nuevo Producto">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h5">Nuevo Producto</span>
      </v-card-title>
      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field v-model="form.nombre" label="Nombre*" required></v-text-field>
          <v-textarea v-model="form.descripcion" label="Descripción"></v-textarea>
          <v-text-field v-model.number="form.precio_unit" label="Precio Unitario*" type="number" step="0.01" required></v-text-field>
          <v-text-field v-model.number="form.stock" label="Stock*" type="number" min="0" required></v-text-field>
          <v-select
            v-model="form.id_categoria"
            :items="categorias"
            item-title="nombre"
            item-value="id_categoria"
            label="Categoría*"
            required
          ></v-select>
          <v-select
            v-model="form.estado"
            :items="estados"
            label="Estado*"
            required
          ></v-select>
          <v-btn type="submit" color="primary" :loading="form.processing" class="mt-4">
            Guardar
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { defineProps } from 'vue';
const props = defineProps({ categorias: Array });
const estados = ['ACTIVO','AGOTADO','DESCONTINUADO'];
const form = useForm({
  nombre: '',
  descripcion: '',
  precio_unit: '',
  stock: 0,
  id_categoria: null,
  estado: 'ACTIVO'
});
const submit = () => {
  form.post(route('products.store'));
};
</script>
