<template>
  <AuthenticatedLayout title="Usuarios - Roles">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <div>
              <span class="text-h5">Roles</span>
              <v-breadcrumbs :items="breadcrumbs" class="pa-0 mt-1"></v-breadcrumbs>
            </div>
            <div>
              <v-btn
                color="secondary"
                prepend-icon="mdi-account-multiple"
                :href="route('users-module.users.index')"
                class="mr-2"
              >
                Usuarios
              </v-btn>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                :href="route('users-module.roles.create')"
              >
                Nuevo Rol
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="roles.data"
              :items-per-page="15"
              class="elevation-1"
            >
              <template v-slot:item.users_count="{ item }">
                <v-chip color="info" small>
                  {{ item.users_count }} usuarios
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  :href="route('users-module.roles.edit', item.id_rol)"
                ></v-btn>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteRol(item.id_rol)"
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
  roles: Object,
});

const breadcrumbs = [
  { title: 'Usuarios', disabled: false },
  { title: 'Roles', disabled: true },
];

const headers = [
  { title: 'ID', key: 'id_rol', sortable: true },
  { title: 'Nombre', key: 'nombre', sortable: true },
  { title: 'Descripción', key: 'descripcion', sortable: false },
  { title: 'Usuarios', key: 'users_count', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const deleteRol = (id) => {
  if (confirm('¿Está seguro de eliminar este rol?')) {
    router.delete(route('users-module.roles.destroy', id));
  }
};
</script>
