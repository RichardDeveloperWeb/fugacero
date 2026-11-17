<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { logout } from '@/routes';

defineOptions({
    layout: {
        title: 'Verificación de correo',
        description:
            'Por favor, verifique su dirección de correo electrónico haciendo clic en el enlace que acabamos de enviarle.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Verificación de correo" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        Se ha enviado un nuevo enlace de verificación a la dirección de correo
        electrónico que proporcionó durante el registro.
    </div>

    <div class="space-y-6 text-center">
        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Cerrar sesión
        </TextLink>
    </div>
</template>
