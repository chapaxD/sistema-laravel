<template>
  <AuthenticatedLayout title="Dashboard">
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h3 mb-2">Dashboard</h1>
            <p class="text-subtitle-1 text-grey">Bienvenido, {{ $page.props.auth.user.name }}</p>
          </div>
          <!-- <v-chip :color="getRoleColor(userRole)" variant="flat" size="large">
            <v-icon start :icon="getRoleIcon(userRole)" />{{ userRole }}
          </v-chip> -->
        </div>
      </v-col>
    </v-row>

    <!-- Quick Stats Row -->
    <v-row v-if="quickStats && Object.keys(quickStats).length > 0" class="mb-4">
      <v-col cols="12">
        <v-card variant="outlined">
          <v-card-text class="pa-4">
            <div class="d-flex justify-space-around text-center">
              <div v-for="(value, key) in quickStats" :key="key" class="px-4">
                <div class="text-h6 font-weight-bold text-primary">{{ value }}</div>
                <div class="text-caption text-grey text-capitalize">
                  {{ key }} hoy
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Statistics Cards -->
    <v-row>
      <v-col
        v-for="(card, index) in cards"
        :key="index"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <v-card 
          :color="card.color" 
          dark 
          class="cursor-pointer"
          @click="handleCardClick(card)"
          :class="{ 'cursor-default': !card.route }"
        >
          <v-card-text class="pa-4">
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-subtitle-2 opacity-75">{{ card.title }}</div>
                <div class="text-h5 font-weight-bold mt-2">{{ card.value }}</div>
              </div>
              <v-icon size="40" class="opacity-50">{{ card.icon }}</v-icon>
            </div>
            <v-divider class="my-3 opacity-25" />
            <div class="text-caption opacity-75 d-flex align-center">
              <v-icon size="16" class="mr-1">mdi-open-in-new</v-icon>
              {{ card.route ? 'Click para ver detalles' : 'Información general' }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts Section -->
    <v-row class="mt-6" v-if="hasCharts">
      <!-- Sales Chart -->
      <v-col cols="12" md="8" v-if="chartData.sales">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>{{ chartData.sales.title }}</span>
            <v-btn 
              variant="text" 
              size="small" 
              @click="router.visit(route('sales.index'))"
              prepend-icon="mdi-chart-bar"
            >
              Ver Reportes
            </v-btn>
          </v-card-title>
          <v-card-text style="height: 300px; position: relative;">
            <Bar :data="salesChartData" :options="barOptions" />
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Orders Chart -->
      <v-col cols="12" md="4" v-if="chartData.orders">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>{{ chartData.orders.title }}</span>
            <v-btn 
              variant="text" 
              size="small" 
              @click="router.visit(route('work-orders.index'))"
              prepend-icon="mdi-clipboard-list"
            >
              Ver Órdenes
            </v-btn>
          </v-card-title>
          <v-card-text style="height: 300px; position: relative;">
            <Doughnut :data="ordersChartData" :options="doughnutOptions" />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Bottom Section -->
    <v-row class="mt-4">
      <!-- Recent Activity -->
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Actividad Reciente</span>
            <v-btn 
              variant="text" 
              size="small" 
              @click="refreshActivity"
              :loading="refreshing"
              prepend-icon="mdi-refresh"
            >
              Actualizar
            </v-btn>
          </v-card-title>
          <v-card-text class="pa-0">
            <v-list lines="two" class="pa-0">
              <v-list-item
                v-for="(activity, index) in recentActivity"
                :key="index"
                :prepend-icon="activity.icon"
                :color="activity.color"
              >
                <template v-slot:prepend>
                  <v-icon :color="activity.color">{{ activity.icon }}</v-icon>
                </template>
                <v-list-item-title class="font-weight-medium">
                  {{ activity.title }}
                </v-list-item-title>
                <v-list-item-subtitle>
                  {{ activity.description }}
                </v-list-item-subtitle>
                <template v-slot:append>
                  <div class="text-caption text-grey">
                    {{ formatDate(activity.date) }}
                  </div>
                </template>
              </v-list-item>
              
              <v-list-item v-if="recentActivity.length === 0">
                <v-list-item-title class="text-center text-grey py-4">
                  <v-icon size="48" class="mb-2">mdi-inbox</v-icon>
                  <div>No hay actividad reciente</div>
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Quick Actions & System Info -->
      <v-col cols="12" md="4">
        <!-- Quick Actions -->
        <v-card class="mb-4">
          <v-card-title>Acciones Rápidas</v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="12" v-if="canPerformAction('create', 'quotations')">
                <v-btn
                  color="primary"
                  block
                  variant="flat"
                  @click="router.visit(route('quotations.create'))"
                  prepend-icon="mdi-file-document-plus"
                  size="large"
                >
                  Nueva Cotización
                </v-btn>
              </v-col>
              <v-col cols="12" v-if="canAccessModule('work-orders')">
                <v-btn
                  color="success"
                  block
                  variant="flat"
                  @click="router.visit(route('work-orders.index'))"
                  prepend-icon="mdi-clipboard-text-clock"
                  size="large"
                >
                  Ver Órdenes
                </v-btn>
              </v-col>
              <v-col cols="12" v-if="canPerformAction('create', 'sales')">
                <v-btn
                  color="info"
                  block
                  variant="flat"
                  @click="router.visit(route('sales.create'))"
                  prepend-icon="mdi-cash-plus"
                  size="large"
                >
                  Nueva Venta
                </v-btn>
              </v-col>
              <v-col cols="12" v-if="canAccessModule('payment-plans')">
                <v-btn
                  color="warning"
                  block
                  variant="flat"
                  @click="router.visit(route('payment-plans.index'))"
                  prepend-icon="mdi-calendar-check"
                  size="large"
                >
                  Planes de Pago
                </v-btn>
              </v-col>
              <v-col cols="12" v-if="canAccessModule('products')">
                <v-btn
                  color="deep-purple"
                  block
                  variant="flat"
                  @click="router.visit(route('products.index'))"
                  prepend-icon="mdi-package-variant"
                  size="large"
                >
                  Inventario
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- System Info -->
        <v-card>
          <v-card-title>Información del Sistema</v-card-title>
          <v-card-text>
            <v-list density="compact" class="pa-0">
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="primary">mdi-calendar</v-icon>
                </template>
                <v-list-item-title>Fecha Actual</v-list-item-title>
                <v-list-item-subtitle>{{ currentDate }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="success">mdi-clock</v-icon>
                </template>
                <v-list-item-title>Hora del Servidor</v-list-item-title>
                <v-list-item-subtitle>{{ currentTime }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="info">mdi-account</v-icon>
                </template>
                <v-list-item-title>Tu Rol</v-list-item-title>
                <v-list-item-subtitle class="text-capitalize">{{ userRole }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement);

const { canPerformAction, canAccessModule } = usePermissions();

const props = defineProps({
  cards: {
    type: Array,
    default: () => [],
  },
  recentActivity: {
    type: Array,
    default: () => [],
  },
  chartData: {
    type: Object,
    default: () => ({}),
  },
  userRole: {
    type: String,
    default: 'Usuario'
  },
  quickStats: {
    type: Object,
    default: () => ({})
  }
});

const refreshing = ref(false);
const currentTime = ref('');
const currentDate = ref('');
let timeInterval = null;

// Computed properties
const hasCharts = computed(() => {
  return props.chartData && (props.chartData.sales || props.chartData.orders);
});

const salesChartData = computed(() => ({
  labels: props.chartData?.sales?.labels || [],
  datasets: [
    {
      label: 'Ventas (Bs)',
      backgroundColor: '#1976D2',
      borderColor: '#1976D2',
      borderWidth: 1,
      data: props.chartData?.sales?.data || []
    }
  ]
}));

const ordersChartData = computed(() => {
  const labels = props.chartData?.orders?.labels || [];
  const data = props.chartData?.orders?.data || [];
  
  const backgroundColors = labels.map(status => {
    switch(status) {
      case 'FINALIZADA': return '#4CAF50';
      case 'EN_PROGRESO': return '#2196F3';
      case 'PROGRAMADA': return '#FFC107';
      case 'CANCELADA': return '#F44336';
      case 'PENDIENTE': return '#FF9800';
      default: return '#9E9E9E';
    }
  });

  return {
    labels: labels,
    datasets: [
      {
        backgroundColor: backgroundColors,
        borderColor: backgroundColors.map(color => color),
        borderWidth: 2,
        data: data
      }
    ]
  };
});

// Chart options
const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `Bs. ${context.parsed.y.toLocaleString('es-ES', { minimumFractionDigits: 2 })}`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function(value) {
          return 'Bs. ' + value.toLocaleString('es-ES');
        }
      }
    }
  }
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `${context.label}: ${context.parsed}`;
        }
      }
    }
  }
};

// Methods
const handleCardClick = (card) => {
  if (card.route) {
    router.visit(route(card.route, card.params || {}));
  }
};

const refreshActivity = () => {
  refreshing.value = true;
  router.reload({
    only: ['recentActivity'],
    preserveScroll: true,
    onFinish: () => {
      refreshing.value = false;
    }
  });
};

const getActivityIcon = (type) => {
  const icons = {
    'sale': 'mdi-cash-multiple',
    'order': 'mdi-clipboard-text-clock',
    'quotation': 'mdi-file-document-outline'
  };
  return icons[type] || 'mdi-information';
};

const getRoleColor = (role) => {
  const colors = {
    'Administrador': 'primary',
    'Vendedor': 'success',
    'Técnico': 'info',
    'Contador': 'warning',
    'Cliente': 'deep-purple',
    'Secretaria': 'pink',
    'Gerente': 'orange'
  };
  return colors[role] || 'grey';
};

const getRoleIcon = (role) => {
  const icons = {
    'Administrador': 'mdi-shield-account',
    'Vendedor': 'mdi-account-tie',
    'Técnico': 'mdi-account-hard-hat',
    'Contador': 'mdi-calculator',
    'Cliente': 'mdi-account',
    'Secretaria': 'mdi-account-details',
    'Gerente': 'mdi-badge-account'
  };
  return icons[role] || 'mdi-account';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-ES', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  } catch {
    return dateString;
  }
};

const updateTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('es-ES');
  currentDate.value = now.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Lifecycle
onMounted(() => {
  updateTime();
  timeInterval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval);
  }
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  transition: transform 0.2s ease-in-out;
}

.cursor-pointer:hover {
  transform: translateY(-2px);
}

.cursor-default {
  cursor: default;
}

.opacity-50 {
  opacity: 0.5;
}

.opacity-75 {
  opacity: 0.75;
}

.opacity-25 {
  opacity: 0.25;
}
</style>