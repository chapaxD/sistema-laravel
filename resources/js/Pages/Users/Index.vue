<template>
  <AuthenticatedLayout title="Usuarios">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Usuarios</span>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              :to="route('users.create')"
            >
              Nuevo Usuario
            </v-btn>
          </v-card-title>

          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="users.data"
              :items-per-page="15"
              class="elevation-1"
            >
              <template v-slot:item.rol="{ item }">
                <v-chip
                  :color="getRoleColor(item.rol_nombre)"
                  dark
                  small
                >
                  {{ item.rol_nombre }}
                </v-chip>
              </template>

              <template v-slot:item.estado="{ item }">
                <v-chip
                  :color="item.estado === 'ACTIVO' ? 'success' : 'error'"
                  dark
                  small
                >
                  {{ item.estado }}
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  :to="route('users.edit', item.id_usuario)"
                ></v-btn>
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteUser(item.id_usuario)"
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
  users: Object,
});

const headers = [
  { title: 'ID', key: 'id_usuario', sortable: true },
  { title: 'Nombre', key: 'nombre', sortable: true },
  { title: 'Apellido', key: 'apellido', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Teléfono', key: 'telefono', sortable: false },
  { title: 'Rol', key: 'rol', sortable: true },
  { title: 'Estado', key: 'estado', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];

const getRoleColor = (role) => {
  const colors = {
    'Administrador': 'purple',
    'Técnico': 'blue',
    'Secretaria': 'teal',
    'Cliente': 'orange',
  };
  return colors[role] || 'grey';
};

const deleteUser = (id) => {
  if (confirm('¿Está seguro de eliminar este usuario?')) {
    router.delete(route('users.destroy', id));
  }
};
</script>
