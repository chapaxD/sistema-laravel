<template>
  <AuthenticatedLayout title="Usuarios - Editar Rol">
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <span class="text-h5">Editar Rol</span>
            <v-breadcrumbs :items="breadcrumbs" class="pa-0 mt-1"></v-breadcrumbs>
          </v-card-title>

          <v-card-text>
            <v-alert
              v-if="rol.users_count > 0"
              type="info"
              variant="tonal"
              class="mb-4"
            >
              Este rol tiene {{ rol.users_count }} usuario(s) asociado(s).
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
                  :href="route('users-module.roles.index')"
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
  rol: Object,
});

const breadcrumbs = [
  { title: 'Usuarios', disabled: false },
  { title: 'Roles', disabled: false, href: route('users-module.roles.index') },
  { title: 'Editar', disabled: true },
];

const form = useForm({
  nombre: props.rol.nombre,
  descripcion: props.rol.descripcion,
});

const submit = () => {
  form.put(route('users-module.roles.update', props.rol.id_rol));
};
</script>
