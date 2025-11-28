<template>
  <AuthenticatedLayout title="Usuarios - Editar Usuario">
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title>
            <span class="text-h5">Editar Usuario</span>
            <v-breadcrumbs :items="breadcrumbs" class="pa-0 mt-1"></v-breadcrumbs>
          </v-card-title>

          <v-card-text>
            <v-form @submit.prevent="submit">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.nombre"
                    label="Nombre *"
                    :error-messages="form.errors.nombre"
                    required
                    variant="outlined"
                    density="comfortable"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.apellido"
                    label="Apellido *"
                    :error-messages="form.errors.apellido"
                    required
                    variant="outlined"
                    density="comfortable"
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-text-field
                v-model="form.email"
                label="Email *"
                :error-messages="form.errors.email"
                type="email"
                required
                variant="outlined"
                density="comfortable"
              ></v-text-field>

              <v-text-field
                v-model="form.telefono"
                label="Teléfono"
                :error-messages="form.errors.telefono"
                variant="outlined"
                density="comfortable"
              ></v-text-field>

              <v-text-field
                v-model="form.password"
                label="Nueva Contraseña"
                :error-messages="form.errors.password"
                type="password"
                variant="outlined"
                density="comfortable"
                hint="Dejar en blanco para mantener la contraseña actual"
              ></v-text-field>

              <v-select
                v-model="form.id_rol"
                :items="roles"
                item-title="nombre"
                item-value="id_rol"
                label="Rol *"
                :error-messages="form.errors.id_rol"
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
                  :href="route('users-module.users.index')"
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
  user: Object,
  roles: Array,
});

const breadcrumbs = [
  { title: 'Usuarios', disabled: false },
  { title: 'Gestión de Usuarios', disabled: false, href: route('users-module.users.index') },
  { title: 'Editar', disabled: true },
];

const estados = ['ACTIVO', 'INACTIVO'];

const form = useForm({
  nombre: props.user.nombre,
  apellido: props.user.apellido,
  email: props.user.email,
  telefono: props.user.telefono,
  password: '',
  id_rol: props.user.id_rol,
  estado: props.user.estado,
});

const submit = () => {
  form.put(route('users-module.users.update', props.user.id_usuario));
};
</script>
