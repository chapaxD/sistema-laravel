<template>
  <AuthenticatedLayout title="Gestionar Orden de Trabajo">
    <v-card>
      <v-card-title>
        <div class="d-flex justify-space-between align-center w-100">
          <span class="text-h5">Orden de Trabajo #{{ order.id_orden }}</span>
          <v-btn color="grey" @click="router.visit(route('work-orders.index'))">
            Volver
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text>
        <v-alert
          v-if="Object.keys($page.props.errors).length > 0"
          type="error"
          class="mb-4"
        >
          <ul>
            <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
          </ul>
        </v-alert>

        <v-form @submit.prevent="submit">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="form.fecha_programada"
                label="Fecha Programada"
                type="date"
                :error-messages="form.errors.fecha_programada"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="form.estado"
                :items="estados"
                label="Estado"
                :error-messages="form.errors.estado"
                required
              ></v-select>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="form.descripcion"
                label="Descripción / Notas"
                rows="3"
                :error-messages="form.errors.descripcion"
              ></v-textarea>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h3 class="mb-4">Técnicos Asignados</h3>

          <v-table>
            <thead>
              <tr>
                <th>Técnico</th>
                <th>Rol</th>
                <th>Horas Est.</th>
                <th style="width: 60px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(tec, index) in form.tecnicos" :key="index">
                <td>
                  <v-select
                    v-model="tec.id_tecnico"
                    :items="techniciansWithNames"
                    item-title="full_name"
                    item-value="id_usuario"
                    density="compact"
                    hide-details
                    label="Seleccione Técnico"
                  ></v-select>
                </td>
                <td>
                  <v-text-field
                    v-model="tec.rol"
                    placeholder="Ej. Instalador"
                    density="compact"
                    hide-details
                  ></v-text-field>
                </td>
                <td>
                  <v-text-field
                    v-model.number="tec.horas"
                    type="number"
                    step="0.5"
                    density="compact"
                    hide-details
                  ></v-text-field>
                </td>
                <td>
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    color="error"
                    @click="removeTechnician(index)"
                  ></v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>

          <v-btn
            color="success"
            prepend-icon="mdi-plus"
            class="mt-4"
            @click="addTechnician"
          >
            Asignar Técnico
          </v-btn>

          <v-divider class="my-4"></v-divider>

          <div class="d-flex justify-end align-center">
            <v-btn
              v-if="order.estado === 'PROGRAMADA' && !order.sale"
              color="purple"
              class="mr-4"
              @click="generateSale"
            >
              Generar Venta
            </v-btn>

            <v-btn
              type="submit"
              color="primary"
              :loading="form.processing"
              size="large"
            >
              Guardar Cambios
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
  order: Object,
  technicians: Array,
});

const estados = ['PROGRAMADA', 'EN_PROGRESO', 'FINALIZADA', 'CANCELADA'];

const techniciansWithNames = computed(() => {
  return props.technicians.map(t => ({
    ...t,
    full_name: `${t.nombre} ${t.apellido}`
  }));
});

const form = useForm({
  fecha_programada: props.order.fecha_programada,
  estado: props.order.estado,
  descripcion: props.order.descripcion,
  tecnicos: props.order.technicians.map(t => ({
    id_tecnico: t.id_usuario,
    rol: t.pivot.rol_en_orden,
    horas: parseFloat(t.pivot.horas_estimadas)
  }))
});

const addTechnician = () => {
  form.tecnicos.push({
    id_tecnico: null,
    rol: '',
    horas: 0
  });
};

const removeTechnician = (index) => {
  form.tecnicos.splice(index, 1);
};

const submit = () => {
  form.put(route('work-orders.update', props.order.id_orden));
};

const generateSale = () => {
  if (confirm('¿Generar venta y plan de pagos a partir de esta orden?')) {
    router.post(route('sales.store-from-order', props.order.id_orden));
  }
};
</script>
