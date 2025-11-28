<template>
  <AuthenticatedLayout title="Nuevo Servicio">
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h5">Nuevo Servicio</span>
        <v-btn color="grey" @click="router.visit(route('services.index'))">Volver</v-btn>
      </v-card-title>
      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field v-model="form.nombre" label="Nombre*" required></v-text-field>
          <v-textarea v-model="form.descripcion" label="Descripción"></v-textarea>
          <v-text-field v-model="form.tipo" label="Tipo*" required></v-text-field>
          <v-text-field v-model="form.subtipo" label="Subtipo"></v-text-field>
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
const form = useForm({
  nombre: '',
  descripcion: '',
  tipo: '',
  subtipo: ''
});
const submit = () => {
  form.post(route('services.store'));
};
</script>
