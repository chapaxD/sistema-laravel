<template>
  <GuestLayout>
    <v-card>
      <v-card-title class="text-h5 text-center">
        Iniciar Sesión
      </v-card-title>
      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field
            v-model="form.email"
            label="Email"
            type="email"
            prepend-icon="mdi-email"
            :error-messages="form.errors.email"
            required
          ></v-text-field>

          <v-text-field
            v-model="form.password"
            label="Contraseña"
            type="password"
            prepend-icon="mdi-lock"
            :error-messages="form.errors.password"
            required
          ></v-text-field>

          <v-checkbox
            v-model="form.remember"
            label="Recordarme"
          ></v-checkbox>

          <v-btn
            type="submit"
            color="primary"
            block
            :loading="form.processing"
            size="large"
          >
            Ingresar
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
