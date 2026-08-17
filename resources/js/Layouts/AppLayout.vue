<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NavIcon from '@/Components/NavIcon.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import EpaPretestModal from '@/Components/EpaPretestModal.vue';
import { useTheme } from '@/composables/useTheme';

import { triggerHapticVibration } from '@/utils/celebration';

const toastMessage = ref(null);
const toastType = ref('success');
let toastTimeout = null;

function showToast(message, type = 'success', duration = 4500) {
    if (toastTimeout) clearTimeout(toastTimeout);
    toastMessage.value = message;
    toastType.value = type;

    // Vibración háptica en móvil cuando se recibe una notificación positiva o recompensa
    if (type === 'success') {
        triggerHapticVibration([50, 40, 60]);
    }

    toastTimeout = setTimeout(() => {
        toastMessage.value = null;
    }, duration);
}

const page = usePage();

function navigate(routeName) {
    mobileMenuOpen.value = false;
    if (route().current(routeName)) return;
    router.visit(route(routeName));
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        } else if (flash?.error) {
            showToast(flash.error, 'error');
        } else if (flash?.warning) {
            showToast(flash.warning, 'warning');
        }
    },
    { deep: true, immediate: true },
);

function handleCustomToast(event) {
    if (event?.detail?.message) {
        showToast(event.detail.message, event.detail.type || 'success', event.detail.duration || 4500);
    }
}

onMounted(() => {
    window.addEventListener('epycus-toast', handleCustomToast);
});

onUnmounted(() => {
    window.removeEventListener('epycus-toast', handleCustomToast);
});

/*
 * Estructura de docs/04-DISENO-VISUAL.md §9 y §14: barra lateral fija en
 * escritorio, barra inferior en móvil. Hábitos (Fase 3) y Pomodoro (Fase 5)
 * ya tienen ruta real; Misiones se muestra como "Pronto" (sin `routeName`,
 * deshabilitado) hasta que exista su ruta — no apuntar un nav item a una
 * ruta que no existe: Ziggy revienta en tiempo de ejecución si `route()`
 * no la conoce.
 *
 * Sin "Avatar" a propósito: no es un destino de navegación propio, es
 * contenido embebido en Perfil (docs/08-PROMPTS-MOCKUPS.md línea 104: el
 * avatar grande va "en perfil", no en su propia pantalla) — corrección del
 * usuario tras ver un módulo entero reservado para algo que ya tenía dueño.
 * Si Gamificación (docs/03-GAMIFICACION.md §2) termina necesitando su
 * propia pantalla de personalización, que viva dentro de Perfil o Ajustes,
 * no acá.
 *
 * Ajustes no está en este arreglo — es un `<Link>` fijo agregado después
 * del `v-for`, no un item más de la lista (ver más abajo).
 *
 * Los controles de tema/superficie/paleta viven en /settings (Ajustes),
 * no acá — corrección del usuario tras ver la Fase 0: la barra de
 * navegación no es el lugar para eso.
 */
const mainNavItems = [
    { label: 'Inicio', routeName: 'dashboard', icon: 'home' },
    { label: 'Hábitos', routeName: 'habits.index', icon: 'habits' },
    { label: 'Pomodoro', routeName: 'pomodoro.index', icon: 'pomodoro' },
    { label: 'Misiones', routeName: 'missions.index', icon: 'missions' },
    { label: 'Perfil', routeName: 'profile.edit', icon: 'user' },
];

const secondaryNavItems = [
    { label: 'Asistente IA', routeName: 'ai-assistant.index', icon: 'ai' },
    { label: 'Ranking', routeName: 'ranking.index', icon: 'ranking' },
    { label: 'Logros', routeName: 'achievements.index', icon: 'achievements' },
    { label: 'Calendario', routeName: 'calendar.index', icon: 'calendar' },
    { label: 'Bienestar', routeName: 'wellbeing.index', icon: 'wellbeing' },
    { label: 'Villanos', routeName: 'villains.index', icon: 'villains' },
    { label: 'Grupos', routeName: 'study-groups.index', icon: 'groups' },
];

const navItems = [
    { label: 'Inicio', routeName: 'dashboard', icon: 'home' },
    { label: 'Hábitos', routeName: 'habits.index', icon: 'habits' },
    { label: 'Pomodoro', routeName: 'pomodoro.index', icon: 'pomodoro' },
    { label: 'Misiones', routeName: 'missions.index', icon: 'missions' },
    { label: 'Ranking', routeName: 'ranking.index', icon: 'ranking' },
    { label: 'Logros', routeName: 'achievements.index', icon: 'achievements' },
    { label: 'Asistente IA', routeName: 'ai-assistant.index', icon: 'ai' },
    { label: 'Calendario', routeName: 'calendar.index', icon: 'calendar' },
    { label: 'Bienestar', routeName: 'wellbeing.index', icon: 'wellbeing' },
    { label: 'Villanos', routeName: 'villains.index', icon: 'villains' },
    { label: 'Grupos', routeName: 'study-groups.index', icon: 'groups' },
    { label: 'Perfil', routeName: 'profile.edit', icon: 'user' },
];

const mobileMenuOpen = ref(false);
const { surface } = useTheme();

const showEpaModal = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // No mostrar el cuestionario EPA en la vista de completar perfil (/profile/complete)
    if (page.url.includes('/profile/complete')) return false;

    // No mostrar el cuestionario si el usuario no ha completado su perfil académico inicial
    if (!user.career || !user.institution_type) return false;

    if (page.props.auth?.hasCompletedEpaPretest === true) return false;

    if (typeof window !== 'undefined' && window.localStorage) {
        if (localStorage.getItem(`epycus_epa_completed_${user.id}`) === '1') {
            return false;
        }
    }
    return true;
});
</script>

<template>
    <div class="min-h-screen bg-bg lg:flex">
        <!-- Toast / Notificaciones Flotantes Globales -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastMessage"
                class="fixed bottom-20 right-4 z-50 max-w-sm rounded-2xl border p-4 shadow-xl backdrop-blur-md transition-all sm:bottom-6 sm:right-6"
                :class="[
                    toastType === 'error'
                        ? 'border-danger/30 bg-danger/15 text-danger-text'
                        : toastType === 'warning'
                          ? 'border-warning/40 bg-warning/20 text-content-primary'
                          : 'border-primary-strong/40 bg-surface-raised/95 text-content-primary shadow-primary-strong/10',
                ]"
                role="alert"
            >
                <div class="flex items-center gap-3">
                    <span class="text-xl shrink-0">{{
                        toastType === 'error' ? '⚠️' : toastType === 'warning' ? '⚡' : '🎉'
                    }}</span>
                    <div class="text-sm font-semibold leading-snug">
                        {{ toastMessage }}
                    </div>
                    <button
                        type="button"
                        class="ml-auto shrink-0 rounded-lg p-1 text-content-muted hover:text-content-primary transition"
                        @click="toastMessage = null"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Modal de Diagnóstico Inicial EPA -->
        <EpaPretestModal :show="showEpaModal" />

        <!-- Fondo de pantalla — solo modo Vidrio (skill epycus-ui §2, §6) -->
        <div v-if="surface === 'glass'" class="app-background" aria-hidden="true" />
        <!-- Barra lateral — solo escritorio -->
        <aside class="panel-nav relative z-10 hidden w-[260px] shrink-0 lg:flex lg:flex-col">
            <div class="flex h-16 items-center justify-between gap-2 px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-strong p-1"
                    >
                        <ApplicationLogo class="h-full w-full rounded" />
                    </span>
                    <span class="font-display text-lg font-semibold text-content-primary"
                        >Epycus</span
                    >
                </Link>
                <ThemeToggle />
            </div>

            <nav class="flex-1 space-y-1 px-4" aria-label="Navegación principal">
                <template v-for="item in navItems" :key="item.label">
                    <button
                        v-if="item.routeName"
                        type="button"
                        class="flex w-full min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150 text-left cursor-pointer"
                        :class="
                            route().current(item.routeName)
                                ? 'bg-primary text-on-primary shadow-sm'
                                : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                        "
                        @click="navigate(item.routeName)"
                    >
                        <NavIcon :name="item.icon" />
                        <span>{{ item.label }}</span>
                    </button>
                    <span
                        v-else
                        class="flex min-h-[44px] cursor-not-allowed items-center justify-between gap-3 rounded-xl px-3 text-sm font-semibold text-content-muted opacity-50"
                        aria-disabled="true"
                    >
                        <span class="flex items-center gap-3">
                            <NavIcon :name="item.icon" />
                            {{ item.label }}
                        </span>
                        <BaseBadge variant="neutral">Pronto</BaseBadge>
                    </span>
                </template>
                <button
                    type="button"
                    class="flex w-full min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150 text-left cursor-pointer"
                    :class="
                        route().current('settings.edit')
                            ? 'bg-primary text-on-primary shadow-sm'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                    @click="navigate('settings.edit')"
                >
                    <NavIcon name="settings" />
                    <span>Ajustes</span>
                </button>
                <button
                    v-if="page.props.auth.user.role === 'admin'"
                    type="button"
                    class="flex w-full min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150 border border-primary-strong/30 bg-primary-strong/10 text-primary-strong hover:bg-primary-strong hover:text-white text-left cursor-pointer"
                    :class="{ '!bg-primary-strong !text-white': route().current('admin.index') }"
                    @click="navigate('admin.index')"
                >
                    <NavIcon name="ranking" />
                    <span>Panel Investigación</span>
                </button>
            </nav>

            <div class="border-t border-border p-4">
                <div class="mb-2 truncate text-sm font-semibold text-content-primary">
                    {{ page.props.auth.user.name }}
                </div>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex min-h-[44px] w-full items-center justify-center rounded-xl border border-danger-text px-3 text-sm font-semibold text-danger-text transition-colors duration-150 hover:bg-surface-raised focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                >
                    Salir
                </Link>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <!-- Cabecera — solo móvil -->
            <header
                class="panel-nav relative z-10 flex h-16 items-center justify-between px-4 lg:hidden"
            >
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-strong p-1"
                    >
                        <ApplicationLogo class="h-full w-full rounded" />
                    </span>
                </Link>
                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <button
                        type="button"
                        class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-content-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                        aria-label="Menú de cuenta"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        ⋮
                    </button>
                </div>
            </header>

            <div
                v-if="mobileMenuOpen"
                class="panel-nav relative z-10 px-4 py-3 lg:hidden space-y-2"
            >
                <div class="border-b border-border pb-2">
                    <div class="text-sm font-semibold text-content-primary">
                        {{ page.props.auth.user.name }}
                    </div>
                    <div class="text-xs text-content-secondary">
                        {{ page.props.auth.user.email }}
                    </div>
                </div>

                <div class="space-y-1">
                    <template v-for="item in secondaryNavItems" :key="item.label">
                        <button
                            v-if="item.routeName"
                            type="button"
                            class="flex w-full min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150 text-left cursor-pointer"
                            :class="
                                route().current(item.routeName)
                                    ? 'bg-primary text-on-primary shadow-sm'
                                    : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                            "
                            @click="navigate(item.routeName)"
                        >
                            <NavIcon :name="item.icon" />
                            <span>{{ item.label }}</span>
                        </button>
                    </template>

                    <button
                        type="button"
                        class="flex w-full min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150 text-left cursor-pointer"
                        :class="
                            route().current('settings.edit')
                                ? 'bg-primary text-on-primary shadow-sm'
                                : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                        "
                        @click="navigate('settings.edit')"
                    >
                        <NavIcon name="settings" />
                        <span>Ajustes</span>
                    </button>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3 text-sm font-semibold text-danger-text hover:bg-surface-raised transition-colors duration-150"
                    >
                        Salir
                    </Link>
                </div>
            </div>

            <main class="mx-auto w-full max-w-[1200px] flex-1 px-4 pb-24 pt-6 lg:px-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- Barra inferior — solo móvil (máximo 5 ítems) -->
        <nav
            class="panel-nav fixed inset-x-0 bottom-0 z-40 flex justify-around py-2 lg:hidden"
            aria-label="Navegación principal"
        >
            <template v-for="item in mainNavItems" :key="item.label">
                <button
                    v-if="item.routeName"
                    type="button"
                    class="flex min-h-[44px] min-w-[44px] flex-col items-center justify-center gap-0.5 rounded-xl px-3 text-xs font-semibold cursor-pointer"
                    :class="
                        route().current(item.routeName)
                            ? 'bg-primary text-on-primary shadow-sm'
                            : 'text-content-secondary'
                    "
                    @click="navigate(item.routeName)"
                >
                    <NavIcon :name="item.icon" />
                    <span>{{ item.label }}</span>
                </button>
            </template>
        </nav>
    </div>
</template>
