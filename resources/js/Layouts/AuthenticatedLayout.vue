<template>
  <v-app :theme="theme" :class="fontClass">
    <v-navigation-drawer
      v-model="drawer"
      app
      :rail="rail"
      @click="rail = false"
    >
      <v-list-item
        :prepend-avatar="auth.user?.nombre ? `https://ui-avatars.com/api/?name=${auth.user.nombre}+${auth.user.apellido}&background=1976D2&color=fff` : undefined"
        :title="auth.user ? `${auth.user.nombre} ${auth.user.apellido}` : 'Usuario'"
        :subtitle="auth.user?.email"
        nav
      >
        <template v-slot:append>
          <v-btn
            icon="mdi-chevron-left"
            variant="text"
            @click.stop="rail = !rail"
          ></v-btn>
        </template>
      </v-list-item>

      <v-divider></v-divider>

      <v-list density="compact" nav>
        <v-list-item
          v-for="item in visibleMenuItems"
          :key="item.value"
          :prepend-icon="item.icon"
          :title="item.title"
          :value="item.value"
          @click="router.visit(item.route)"
        ></v-list-item>
      </v-list>

      <template v-slot:append>
        <v-divider></v-divider>
        <v-list density="compact" nav>
          <v-list-item
            prepend-icon="mdi-theme-light-dark"
            :title="theme === 'dark' ? 'Modo Claro' : 'Modo Oscuro'"
            @click="toggleTheme"
          ></v-list-item>
          <v-list-item
            prepend-icon="mdi-logout"
            title="Cerrar Sesión"
            @click="logout"
          ></v-list-item>
        </v-list>
      </template>
    </v-navigation-drawer>

    <v-app-bar app>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>{{ title || 'Sistema Laravel' }}</v-toolbar-title>
      <v-spacer></v-spacer>
      
      <!-- Font Switcher -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn icon="mdi-format-size" v-bind="props" class="mr-2"></v-btn>
        </template>
        <v-list>
          <v-list-item @click="changeFont('default')" title="Por defecto" :active="currentFont === 'default'"></v-list-item>
          <v-list-item @click="changeFont('arial')" title="Arial" :active="currentFont === 'arial'"></v-list-item>
          <v-list-item @click="changeFont('times')" title="Times New Roman" :active="currentFont === 'times'"></v-list-item>
          <v-list-item @click="changeFont('cursive')" title="Cursiva" :active="currentFont === 'cursive'"></v-list-item>
        </v-list>
      </v-menu>
      <v-chip v-if="auth.user" color="primary" variant="flat">
        {{ auth.user.rol }}
      </v-chip>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <!-- Flash Messages -->
        <v-alert
          v-if="flash.message"
          :type="flash.type === 'success' ? 'success' : 'error'"
          closable
          class="mb-4"
        >
          {{ flash.message }}
        </v-alert>

        <!-- Page Content -->
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';

defineProps({
  title: String,
});

const page = usePage();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);
const { canAccessModule, hasAnyRole } = usePermissions();

const drawer = ref(true);
const rail = ref(false);
const theme = ref('dark');
const currentFont = ref('default');

// Definición de todos los items del menú
const allMenuItems = [
  {
    icon: 'mdi-view-dashboard',
    title: 'Dashboard',
    value: 'dashboard',
    route: route('dashboard'),
    module: 'dashboard',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Técnico', 'Contador', 'Cliente']
  },
  {
    icon: 'mdi-file-document-outline',
    title: 'Cotizaciones',
    value: 'quotations',
    route: route('quotations.index'),
    module: 'quotations',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Contador', 'Cliente']
  },
  {
    icon: 'mdi-clipboard-text',
    title: 'Órdenes de Trabajo',
    value: 'work-orders',
    route: route('work-orders.index'),
    module: 'work-orders',
    roles: ['Administrador', 'Secretaria', 'Técnico', 'Cliente']
  },
  {
    icon: 'mdi-account-multiple',
    title: 'Usuarios',
    value: 'users',
    route: route('users-module.users.index'),
    module: 'users',
    roles: ['Administrador', 'Secretaria']
  },
  {
    icon: 'mdi-cash-register',
    title: 'Ventas',
    value: 'sales',
    route: route('sales.index'),
    module: 'sales',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Contador']
  },
  {
    icon: 'mdi-package-variant-closed',
    title: 'Inventario',
    value: 'inventory',
    route: route('inventory.products.index'),
    module: 'inventory',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Técnico']
  },
  {
    icon: 'mdi-wrench',
    title: 'Servicios',
    value: 'services',
    route: route('services.index'),
    module: 'services',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Técnico']
  },
  {
    icon: 'mdi-receipt',
    title: 'Recibos',
    value: 'receipts',
    route: route('receipts.index'),
    module: 'receipts',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Contador', 'Cliente']
  },
  {
    icon: 'mdi-calendar-month',
    title: 'Planes de Pago',
    value: 'payment-plans',
    route: route('payment-plans.index'),
    module: 'payment-plans',
    roles: ['Administrador', 'Secretaria', 'Vendedor', 'Contador', 'Cliente']
  },
];

// Filtrar items del menú según el rol del usuario
const visibleMenuItems = computed(() => {
  return allMenuItems.filter(item => {
    return hasAnyRole(item.roles) && canAccessModule(item.module);
  });
});

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
  localStorage.setItem('theme', theme.value);
};

const changeFont = (font) => {
  currentFont.value = font;
  localStorage.setItem('font', font);
};

const fontClass = computed(() => {
  switch (currentFont.value) {
    case 'arial': return 'font-arial';
    case 'times': return 'font-times';
    case 'cursive': return 'font-cursive';
    default: return '';
  }
});

// Load theme and font from localStorage
if (typeof window !== 'undefined') {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    theme.value = savedTheme;
  }
  
  const savedFont = localStorage.getItem('font');
  if (savedFont) {
    currentFont.value = savedFont;
  }
}

const logout = () => {
  router.post(route('logout'));
};
</script>
