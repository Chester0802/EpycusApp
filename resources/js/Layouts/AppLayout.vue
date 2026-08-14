<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NavIcon from '@/Components/NavIcon.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import EpaPretestModal from '@/Components/EpaPretestModal.vue';
import { useTheme } from '@/composables/useTheme';

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

const page = usePage();
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
                    <Link
                        v-if="item.routeName"
                        :href="route(item.routeName)"
                        class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150"
                        :class="
                            route().current(item.routeName)
                                ? 'bg-primary text-on-primary'
                                : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                        "
                    >
                        <NavIcon :name="item.icon" />
                        {{ item.label }}
                    </Link>
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
                <Link
                    :href="route('settings.edit')"
                    class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150"
                    :class="
                        route().current('settings.edit')
                            ? 'bg-primary text-on-primary'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                >
                    <NavIcon name="settings" />
                    Ajustes
                </Link>
                <Link
                    v-if="page.props.auth.user.role === 'admin'"
                    :href="route('admin.index')"
                    class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150 border border-primary-strong/30 bg-primary-strong/10 text-primary-strong hover:bg-primary-strong hover:text-white"
                    :class="{ '!bg-primary-strong !text-white': route().current('admin.index') }"
                >
                    <NavIcon name="ranking" />
                    Panel Investigación
                </Link>
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
                        <Link
                            v-if="item.routeName"
                            :href="route(item.routeName)"
                            class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150"
                            :class="
                                route().current(item.routeName)
                                    ? 'bg-primary text-on-primary'
                                    : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                            "
                            @click="mobileMenuOpen = false"
                        >
                            <NavIcon :name="item.icon" />
                            {{ item.label }}
                        </Link>
                    </template>

                    <Link
                        :href="route('settings.edit')"
                        class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-semibold transition-colors duration-150"
                        :class="
                            route().current('settings.edit')
                                ? 'bg-primary text-on-primary'
                                : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                        "
                        @click="mobileMenuOpen = false"
                    >
                        <NavIcon name="settings" />
                        Ajustes
                    </Link>

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
                <Link
                    v-if="item.routeName"
                    :href="route(item.routeName)"
                    class="flex min-h-[44px] min-w-[44px] flex-col items-center justify-center gap-0.5 rounded-xl px-3 text-xs font-semibold"
                    :class="
                        route().current(item.routeName)
                            ? 'bg-primary text-on-primary'
                            : 'text-content-secondary'
                    "
                >
                    <NavIcon :name="item.icon" />
                    {{ item.label }}
                </Link>
            </template>
        </nav>
    </div>
</template>
