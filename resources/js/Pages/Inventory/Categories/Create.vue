<template>
  <AuthenticatedLayout title="Inventario - Nueva Categoría">
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <span class="text-h5">Nueva Categoría</span>
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

              <v-card-actions>
                <v-btn
                  color="grey"
                  variant="text"
                  :href="route('inventory.categories.index')"
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

const breadcrumbs = [
  { title: 'Inventario', disabled: false },
  { title: 'Categorías', disabled: false, href: route('inventory.categories.index') },
  { title: 'Nueva', disabled: true },
];

const form = useForm({
  nombre: '',
  descripcion: '',
});

const submit = () => {
  form.post(route('inventory.categories.store'));
};
</script>
