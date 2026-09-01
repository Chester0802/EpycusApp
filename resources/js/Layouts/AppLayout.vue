<script setup>
import { computed, ref } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import { useTelemetry } from '@/Composables/useTelemetry';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NavIcon from '@/Components/NavIcon.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import EpaPretestModal from '@/Components/EpaPretestModal.vue';
import GlobalPomodoroWidget from '@/Components/Pomodoro/GlobalPomodoroWidget.vue';
import { MoreHorizontal, X, LogOut, ChevronLeft, ChevronRight } from '@lucide/vue';

const page = usePage();
const { track } = useTelemetry();

const showMobileMore = ref(false);
const isSidebarCollapsed = ref(false);

const navSections = [
    {
        title: 'Estudio',
        items: [
            { label: 'Inicio', routeName: 'dashboard', icon: 'home' },
            { label: 'Calendario', routeName: 'calendar.index', icon: 'calendar' },
            { label: 'Cursos', routeName: 'courses.index', icon: 'book', matchRoutes: ['courses.*'] },
            { label: 'Misiones', routeName: 'missions.index', icon: 'missions' },
            { label: 'Pomodoro', routeName: 'pomodoro.index', icon: 'pomodoro' },
        ],
    },
    {
        title: 'Aprendizaje',
        items: [
            { label: 'Lecturas', routeName: 'readings.index', icon: 'readings', matchRoutes: ['readings.*'] },
            { label: 'Habilidades', routeName: 'skills.index', icon: 'skills', matchRoutes: ['skills.*'] },
        ],
    },
    {
        title: 'Vida & Salud',
        items: [
            { label: 'Bienestar & Hábitos', routeName: 'habits.index', icon: 'habits', matchRoutes: ['habits.index', 'wellbeing.index', 'wellbeing.day', 'fitness.index'] },
            { label: 'Finanzas', routeName: 'finance.index', icon: 'finance' },
            { label: 'Tienda', routeName: 'shop.index', icon: 'shop' },
        ],
    },
    {
        title: 'Aventura & Comunidad',
        items: [
            { label: 'Edy AI', routeName: 'ai-assistant.index', icon: 'ai' },
            { label: 'Villanos', routeName: 'villains.index', icon: 'villains' },
            { label: 'Ranking', routeName: 'ranking.index', icon: 'ranking' },
            { label: 'Grupos', routeName: 'study-groups.index', icon: 'groups' },
        ],
    },
    {
        title: 'Cuenta',
        items: [
            { label: 'Perfil & Logros', routeName: 'gamification.index', icon: 'user' },
            { label: 'Ajustes', routeName: 'settings.edit', icon: 'settings' },
        ],
    },
];

// 5 botones exactos para la barra inferior en móvil (Opción B)
const mobileBottomNavItems = [
    { label: 'Inicio', routeName: 'dashboard', icon: 'home' },
    { label: 'Calendario', routeName: 'calendar.index', icon: 'calendar' },
    { label: 'Pomodoro', routeName: 'pomodoro.index', icon: 'pomodoro', matchRoutes: ['pomodoro.*'] },
    { label: 'Misiones', routeName: 'missions.index', icon: 'missions' },
    { label: 'Más', action: 'more', icon: 'settings' },
];

const mobileDrawerItems = [
    { label: 'Mi Perfil & Logros', routeName: 'gamification.index', icon: 'user', desc: 'Avatar, XP y Medallas' },
    { label: 'Pomodoro', routeName: 'pomodoro.index', icon: 'pomodoro', desc: 'Temporizador de estudio' },
    { label: 'Cursos', routeName: 'courses.index', icon: 'book', desc: 'Asignaturas, sílabos y apuntes' },
    { label: 'Lecturas', routeName: 'readings.index', icon: 'readings', desc: 'Libros, artículos y biblioteca' },
    { label: 'Habilidades', routeName: 'skills.index', icon: 'skills', desc: 'Árbol de destrezas y práctica' },
    { label: 'Bienestar & Hábitos', routeName: 'habits.index', icon: 'habits', desc: 'Rutinas, diario y salud' },
    { label: 'Finanzas', routeName: 'finance.index', icon: 'finance', desc: 'Presupuesto y ahorro' },
    { label: 'Tienda', routeName: 'shop.index', icon: 'shop', desc: 'Canje de recompensas' },
    { label: 'Edy AI', routeName: 'ai-assistant.index', icon: 'ai', desc: 'Tutor y consejero' },
    { label: 'Villanos', routeName: 'villains.index', icon: 'villains', desc: 'Jefe semanal' },
    { label: 'Ranking', routeName: 'ranking.index', icon: 'ranking', desc: 'Tabla de posiciones' },
    { label: 'Grupos', routeName: 'study-groups.index', icon: 'groups', desc: 'Salas de estudio' },
    { label: 'Ajustes', routeName: 'settings.edit', icon: 'settings', desc: 'Tema y personalización' },
];

const { surface } = useTheme();

const showEpaModal = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (page.url.includes('/profile/complete')) return false;
    if (!user.career || !user.cycle) return false;
    if (page.props.auth?.hasCompletedEpaPretest === true) return false;

    if (typeof window !== 'undefined' && window.localStorage) {
        if (
            localStorage.getItem(`epycus_epa_completed_${user.id}`) === '1' ||
            localStorage.getItem('epycus_epa_completed_latest') === '1'
        ) {
            return false;
        }
    }
    return true;
});

function isItemActive(item) {
    if (item.matchRoutes) {
        return item.matchRoutes.some((r) => route().current(r));
    }
    return item.routeName ? route().current(item.routeName) : false;
}

function navigate(routeName) {
    showMobileMore.value = false;
    track('navigation.clicked', 'layout', { target_route: routeName });
    router.visit(route(routeName));
}

const flashMessage = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);
const flashWarning = computed(() => page.props.flash?.warning ?? null);
const flashXp = computed(() => page.props.flash?.xp_awarded ?? null);
const toastMessage = ref(null);
const toastType = ref('success');

import { watch } from 'vue';
watch(
    [flashMessage, flashError, flashWarning, flashXp],
    ([newSuccess, newError, newWarning, newXp]) => {
        if (newXp > 0) {
            toastMessage.value = `¡+${newXp} XP obtenidos!`;
            toastType.value = 'xp';
        } else if (newError) {
            toastMessage.value = newError;
            toastType.value = 'error';
        } else if (newWarning) {
            toastMessage.value = newWarning;
            toastType.value = 'warning';
        } else if (newSuccess) {
            toastMessage.value = newSuccess;
            toastType.value = 'success';
        }
        if (toastMessage.value) {
            setTimeout(() => {
                toastMessage.value = null;
            }, 4500);
        }
    },
    { immediate: true },
);

if (typeof window !== 'undefined') {
    window.addEventListener('epycus-toast', (e) => {
        if (e.detail?.message) {
            toastMessage.value = e.detail.message;
            toastType.value = e.detail.type || 'info';
            setTimeout(() => {
                toastMessage.value = null;
            }, 4500);
        }
    });
}
</script>

<template>
    <div class="relative min-h-screen lg:flex">
        <!-- Toast de Notificaciones Globales -->
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
                          : toastType === 'xp'
                          ? 'border-accent/40 bg-accent/20 text-accent font-bold'
                          : 'border-primary-strong/40 bg-surface-raised/95 text-content-primary shadow-primary-strong/10',
                ]"
                role="alert"
            >
                <div class="flex items-center gap-3">
                    <span class="text-xl shrink-0">{{
                        toastType === 'error' ? '⚠️' : toastType === 'warning' ? '⚡' : toastType === 'xp' ? '⭐' : '🎉'
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

        <!-- Fondo de pantalla — solo modo Vidrio -->
        <div v-if="surface === 'glass'" class="app-background" aria-hidden="true" />

        <!-- Botón Flotante para Mostrar Barra Lateral cuando está Oculta -->
        <button
            v-if="isSidebarCollapsed"
            type="button"
            class="fixed left-3 top-3.5 z-40 px-3 py-2 rounded-2xl bg-surface border border-border shadow-xl text-content-secondary hover:text-primary-strong hover:scale-105 transition-all hidden lg:flex items-center gap-2 text-xs font-black cursor-pointer animate-fade-in"
            title="Mostrar barra lateral"
            @click="isSidebarCollapsed = false"
        >
            <ChevronRight class="w-4 h-4 text-primary-strong" />
            <span>Menú</span>
        </button>

        <!-- Barra lateral — solo escritorio (Organizada por Secciones Semánticas con Toggle) -->
        <aside
            class="panel-nav relative z-10 hidden shrink-0 lg:flex lg:flex-col border-r border-border h-screen sticky top-0 transition-all duration-300 ease-in-out"
            :class="isSidebarCollapsed ? 'w-0 -translate-x-full border-r-0 overflow-hidden opacity-0 pointer-events-none' : 'w-[250px] translate-x-0 opacity-100'"
        >
            <div class="flex h-16 items-center justify-between gap-2 px-4 border-b border-border/50">
                <button type="button" class="flex items-center gap-2.5 cursor-pointer" @click="navigate('dashboard')">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-strong p-1 shadow-sm">
                        <ApplicationLogo class="h-full w-full rounded" />
                    </span>
                    <span class="font-display text-lg font-bold text-content-primary">Epycus</span>
                </button>
                
                <div class="flex items-center gap-1">
                    <ThemeToggle />
                    <!-- Flecha para Ocultar Barra Lateral -->
                    <button
                        type="button"
                        class="p-1.5 rounded-xl text-content-muted hover:text-content-primary hover:bg-surface-raised transition cursor-pointer"
                        title="Ocultar barra de navegación"
                        @click="isSidebarCollapsed = true"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto space-y-4 px-3 py-3" aria-label="Navegación principal">
                <div v-for="section in navSections" :key="section.title" class="space-y-1">
                    <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-content-muted">
                        {{ section.title }}
                    </div>
                    <template v-for="item in section.items" :key="item.label">
                        <button
                            type="button"
                            class="flex w-full min-h-[38px] items-center gap-2.5 rounded-xl px-3 text-xs font-bold transition-all duration-150 text-left cursor-pointer"
                            :class="
                                isItemActive(item)
                                    ? 'bg-primary-strong text-on-primary-strong shadow-sm'
                                    : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                            "
                            @click="navigate(item.routeName)"
                        >
                            <NavIcon :name="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ item.label }}</span>
                        </button>
                    </template>
                </div>

                <button
                    v-if="page.props.auth.user.role === 'admin'"
                    type="button"
                    class="flex w-full min-h-[38px] items-center gap-2.5 rounded-xl px-3 text-xs font-bold transition-colors duration-150 border border-primary-strong/30 bg-primary-strong/10 text-primary-strong hover:bg-primary-strong hover:text-white text-left cursor-pointer"
                    :class="{ '!bg-primary-strong !text-white': route().current('admin.index') }"
                    @click="navigate('admin.index')"
                >
                    <NavIcon name="ranking" class="h-4 w-4 shrink-0" />
                    <span>Panel Investigación</span>
                </button>
            </nav>

            <div class="border-t border-border p-3.5 bg-surface/40">
                <div class="flex items-center justify-between mb-2">
                    <div class="truncate text-xs font-bold text-content-primary">
                        {{ page.props.auth.user.name }}
                    </div>
                    <span class="text-[10px] text-content-muted font-bold">Lvl {{ page.props.auth.user.level || 1 }}</span>
                </div>
                <button
                    type="button"
                    class="flex min-h-[36px] w-full items-center justify-center gap-1.5 rounded-xl border border-danger-text/40 px-3 text-xs font-bold text-danger-text transition-colors duration-150 hover:bg-danger-text/10 cursor-pointer"
                    @click="router.post(route('logout'))"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>Cerrar Sesión</span>
                </button>
            </div>
        </aside>

        <!-- Contenedor Principal -->
        <div class="relative z-10 flex min-h-screen flex-1 flex-col">
            <!-- Cabecera Móvil -->
            <header class="panel-nav relative z-10 flex h-14 items-center justify-between px-4 lg:hidden border-b border-border">
                <button type="button" class="flex items-center gap-2 cursor-pointer" @click="navigate('dashboard')">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-strong p-1 shadow-sm">
                        <ApplicationLogo class="h-full w-full rounded" />
                    </span>
                    <span class="font-display text-base font-bold text-content-primary">Epycus</span>
                </button>
                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <button
                        type="button"
                        class="flex min-h-[38px] min-w-[38px] items-center justify-center rounded-xl bg-surface-raised border border-border text-content-secondary"
                        aria-label="Abrir menú"
                        @click="showMobileMore = !showMobileMore"
                    >
                        <MoreHorizontal class="h-5 w-5" />
                    </button>
                </div>
            </header>

            <!-- Main Content Slot -->
            <main class="mx-auto w-full max-w-[1200px] flex-1 px-3.5 pb-24 pt-5 lg:px-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- Barra Inferior Móvil (EXACTAMENTE 5 BOTONES) -->
        <nav
            class="panel-nav fixed inset-x-0 bottom-0 z-40 flex items-center justify-around py-1.5 px-2 lg:hidden border-t border-border bg-surface/95 backdrop-blur-md shadow-lg"
            aria-label="Navegación principal móvil"
        >
            <template v-for="item in mobileBottomNavItems" :key="item.label">
                <button
                    v-if="item.action === 'more'"
                    type="button"
                    class="flex min-h-[46px] min-w-[46px] flex-col items-center justify-center gap-0.5 rounded-xl px-2.5 text-[11px] font-bold cursor-pointer transition-all"
                    :class="
                        showMobileMore
                            ? 'bg-primary-strong text-on-primary-strong shadow-sm'
                            : 'text-content-secondary hover:text-content-primary'
                    "
                    @click="showMobileMore = !showMobileMore"
                >
                    <MoreHorizontal class="h-5 w-5 shrink-0" />
                    <span>Más</span>
                </button>
                <button
                    v-else-if="item.routeName"
                    type="button"
                    class="flex min-h-[46px] min-w-[46px] flex-col items-center justify-center gap-0.5 rounded-xl px-2.5 text-[11px] font-bold cursor-pointer transition-all"
                    :class="
                        isItemActive(item)
                            ? 'bg-primary-strong text-on-primary-strong shadow-sm'
                            : 'text-content-secondary hover:text-content-primary'
                    "
                    @click="navigate(item.routeName)"
                >
                    <NavIcon :name="item.icon" class="h-5 w-5 shrink-0" />
                    <span>{{ item.label }}</span>
                </button>
            </template>
        </nav>

        <!-- Drawer / Modal Móvil "Más" -->
        <div
            v-if="showMobileMore"
            class="fixed inset-0 z-50 lg:hidden flex flex-col justify-end bg-black/60 backdrop-blur-sm transition-opacity"
            @click.self="showMobileMore = false"
        >
            <div class="panel-nav rounded-t-3xl border-t border-border p-5 max-h-[85vh] overflow-y-auto space-y-4 shadow-2xl bg-surface animate-in slide-in-from-bottom duration-200">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-primary/20 flex items-center justify-center text-lg font-bold text-primary-strong">
                            👤
                        </div>
                        <div>
                            <div class="font-display text-sm font-bold text-content-primary">
                                {{ page.props.auth.user.name }}
                            </div>
                            <div class="text-xs text-content-muted">
                                {{ page.props.auth.user.email }}
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="p-2 rounded-xl bg-surface-raised border border-border text-content-muted hover:text-content-primary cursor-pointer"
                        @click="showMobileMore = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <button
                        v-for="item in mobileDrawerItems"
                        :key="item.label"
                        type="button"
                        class="flex flex-col items-start gap-1 p-3 rounded-2xl border transition-all text-left cursor-pointer"
                        :class="
                            route().current(item.routeName)
                                ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-sm'
                                : 'bg-surface-raised border-border text-content-primary hover:border-primary/40'
                        "
                        @click="navigate(item.routeName)"
                    >
                        <div class="flex items-center gap-2">
                            <NavIcon :name="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="font-bold text-xs">{{ item.label }}</span>
                        </div>
                        <span class="text-[10px] opacity-75 leading-tight">{{ item.desc }}</span>
                    </button>
                </div>

                <div class="pt-2 border-t border-border">
                    <button
                        type="button"
                        class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-2xl border border-danger-text/40 bg-danger-text/10 px-4 text-xs font-bold text-danger-text hover:bg-danger-text/20 transition cursor-pointer"
                        @click="router.post(route('logout'))"
                    >
                        <LogOut class="w-4 h-4" />
                        <span>Cerrar Sesión</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Widget Global de Pomodoro (Flotante en cualquier pantalla + Sonido y Notificación) -->
        <GlobalPomodoroWidget />
    </div>
</template>
