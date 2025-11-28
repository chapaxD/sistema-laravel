<template>
  <AuthenticatedLayout title="Editar Categoría">
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <span class="text-h5">Editar Categoría</span>
          </v-card-title>

          <v-card-text>
            <v-alert
              v-if="categoria.products_count > 0"
              type="info"
              variant="tonal"
              class="mb-4"
            >
              Esta categoría tiene {{ categoria.products_count }} producto(s) asociado(s).
            </v-alert>

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
                  :to="route('categorias.index')"
                >
                  Cancelar
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  type="submit"
                  :loading="form.processing"
                >
                  Actualizar
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
  categoria: Object,
});

const form = useForm({
  nombre: props.categoria.nombre,
  descripcion: props.categoria.descripcion,
});

const submit = () => {
  form.put(route('categorias.update', props.categoria.id_categoria));
};
</script>
