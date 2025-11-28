<template>
  <AuthenticatedLayout title="Editar Producto">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h5">Editar Producto</span>
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
            Actualizar
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
const props = defineProps({ product: Object, categorias: Array });
const estados = ['ACTIVO','AGOTADO','DESCONTINUADO'];
const form = useForm({
  nombre: props.product.nombre,
  descripcion: props.product.descripcion,
  precio_unit: props.product.precio_unit,
  stock: props.product.stock,
  id_categoria: props.product.id_categoria,
  estado: props.product.estado
});
const submit = () => {
  form.put(route('products.update', props.product.id_producto));
};
</script>
