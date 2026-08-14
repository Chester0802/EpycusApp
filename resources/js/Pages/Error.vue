<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: Number, required: true },
});

const title = computed(() => {
    return {
        503: '503: Servicio no disponible',
        500: '500: Error del servidor',
        404: '404: Página no encontrada',
        403: '403: Acceso denegado',
        419: '419: Sesión expirada',
    }[props.status] || 'Error';
});

const description = computed(() => {
    return {
        503: 'Estamos realizando mantenimiento programado. Por favor, vuelve a intentarlo en breve.',
        500: 'Ocurrió un inconveniente interno en el servidor. Por favor, recarga la página.',
        404: 'La página que buscas no existe o ha sido movida.',
        403: 'No tienes permisos para acceder a este recurso.',
        419: 'Tu sesión ha expirado. Por favor, recarga la página para ingresar con credenciales frescas.',
    }[props.status] || 'Ocurrió un error inesperado.';
});

function refreshPage() {
    window.location.reload();
}
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6 font-sans">
        <div class="max-w-md w-full rounded-3xl bg-slate-900 border border-slate-800 p-8 text-center shadow-2xl">
            <div class="w-16 h-16 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-3xl mx-auto mb-6">
                {{ status === 419 ? '🔒' : (status === 404 ? '🔍' : '⚠️') }}
            </div>
            <h1 class="text-2xl font-extrabold text-white mb-2">{{ title }}</h1>
            <p class="text-slate-400 text-sm mb-6 leading-relaxed">{{ description }}</p>

            <div class="flex flex-col gap-3">
                <button
                    type="button"
                    class="w-full py-3 px-4 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-sm shadow-lg shadow-purple-600/30 transition-all cursor-pointer"
                    @click="refreshPage"
                >
                    Recargar Página 🔄
                </button>
                <a
                    href="https://app.epycus.es/login"
                    class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-sm transition-all"
                >
                    Ir al Inicio de Sesión 🚀
                </a>
            </div>
        </div>
    </div>
</template>
