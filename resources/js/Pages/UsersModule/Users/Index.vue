<template>
  <AuthenticatedLayout title="Usuarios - Gestión">
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <div>
              <span class="text-h5">Usuarios</span>
              <v-breadcrumbs :items="breadcrumbs" class="pa-0 mt-1"></v-breadcrumbs>
            </div>
            <div>
              <v-btn color="secondary" prepend-icon="mdi-shield-account" :href="route('users-module.roles.index')" class="mr-2">
                Roles
              </v-btn>
              <v-btn color="primary" prepend-icon="mdi-plus" :href="route('users-module.users.create')">
                Nuevo Usuario
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text>

            <!-- Buscador -->
            <v-row class="mb-4">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="searchQuery"
                  prepend-inner-icon="mdi-magnify"
                  label="Buscar usuarios..."
                  variant="outlined"
                  density="compact"
                  clearable
                  hide-details
                  @input="handleSearch"
                />
              </v-col>
            </v-row>

            <!-- Tabla -->
            <v-data-table
              :headers="headers"
              :items="users.data"
              :items-per-page="-1"
              hide-default-footer
              class="elevation-1"
            >
              <template v-slot:item.nombre_completo="{ item }">
                {{ item.nombre }} {{ item.apellido }}
              </template>

              <template v-slot:item.rol="{ item }">
                <v-chip color="primary" small v-if="item.rol">{{ item.rol.nombre }}</v-chip>
              </template>

              <template v-slot:item.estado="{ item }">
                <v-chip :color="item.estado === 'ACTIVO' ? 'success' : 'error'" dark small>
                  {{ item.estado }}
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn icon="mdi-pencil" size="small" variant="text" :href="route('users-module.users.edit', item.id_usuario)"></v-btn>
                <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="deleteUser(item.id_usuario)"></v-btn>
              </template>
            </v-data-table>

            <!-- Paginador -->
            <div class="d-flex justify-center mt-4">
              <v-pagination
                v-model="pageNumber"
                :length="users.last_page"
                @update:modelValue="changePage"
                total-visible="7"
              />
            </div>

          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  users: Object,
});

const pageNumber = ref(props.users.current_page);
const searchQuery = ref('');
let searchTimeout = null;

const breadcrumbs = [
  { title: 'Usuarios', disabled: false },
  { title: 'Gestión de Usuarios', disabled: true },
];

const headers = [
  { title: 'ID', key: 'id_usuario', sortable: true },
  { title: 'Nombre Completo', key: 'nombre_completo', sortable: false },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Teléfono', key: 'telefono', sortable: false },
  { title: 'Rol', key: 'rol', sortable: false },
  { title: 'Estado', key: 'estado', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false },
];



const deleteUser = (id) => {
  if (confirm('¿Está seguro de eliminar este usuario?')) {
    router.delete(route('users-module.users.destroy', id));
  }
};

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pageNumber.value = 1;
    router.get(route('users-module.users.index'), { 
      search: searchQuery.value,
      page: 1 
    }, {
      preserveScroll: true,
      preserveState: true,
    });
  }, 500);
};

const changePage = (page) => {
  router.get(route('users-module.users.index'), { 
    page,
    search: searchQuery.value 
  }, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
