<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NavIcon from '@/Components/NavIcon.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
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
const navItems = [
    { label: 'Inicio', routeName: 'dashboard', icon: 'home' },
    { label: 'Hábitos', routeName: 'habits.index', icon: 'habits' },
    { label: 'Pomodoro', routeName: 'pomodoro.index', icon: 'pomodoro' },
    { label: 'Misiones', routeName: 'missions.index', icon: 'missions' },
    { label: 'Calendario', routeName: 'calendar.index', icon: 'calendar' },
    { label: 'Bienestar', routeName: 'wellbeing.index', icon: 'wellbeing' },
    { label: 'Villanos', routeName: 'villains.index', icon: 'villains' },
    { label: 'Perfil', routeName: 'profile.edit', icon: 'user' },
];

const page = usePage();
const mobileMenuOpen = ref(false);
const { surface } = useTheme();
</script>

<template>
    <div class="min-h-screen bg-bg lg:flex">
        <!-- Fondo de pantalla — solo modo Vidrio (skill epycus-ui §2, §6) -->
        <div v-if="surface === 'glass'" class="app-background" aria-hidden="true" />
        <!-- Barra lateral — solo escritorio -->
        <aside class="panel-nav relative z-10 hidden w-[260px] shrink-0 lg:flex lg:flex-col">
            <div class="flex h-16 items-center justify-between gap-2 px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <!-- Insignia del logo: cuadrado rounded-lg con el logo real
                         adentro (skill epycus-ui, referencia visual del usuario).
                         `bg-primary-strong p-1` deja un marco visible alrededor
                         de la imagen, que ya es cuadrada con esquinas propias.
                         rounded-xl (24px) se ve como círculo perfecto en una
                         caja de 36px — probado, no asumido; rounded-lg (16px)
                         sí deja ver las esquinas. -->
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-strong p-1"
                    >
                        <ApplicationLogo class="h-full w-full rounded" />
                    </span>
                    <span class="font-display text-lg font-semibold text-content-primary"
                        >Epycus</span
                    >
                </Link>
                <ThemeToggle v-if="surface !== 'glass'" />
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
                <!-- Ajustes va con el resto de la navegación, debajo de Perfil
                     (corrección del usuario: no debe compartir el footer con
                     Salir) -->
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
            </nav>

            <!-- Solo Salir acá — tono más marcado que un nav item normal,
                 porque es la única acción que queda en esta zona separada
                 (corrección del usuario tras ver Ajustes al lado). -->
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
            <!-- Cabecera — solo móvil. `panel-nav relative z-10`: sin esto queda
                 detrás de `.app-background` (position:fixed) en Vidrio — un
                 elemento estático sin posición propia pinta antes que uno
                 posicionado con z-index:0, aunque venga después en el DOM
                 (encontrado probando en navegador, no en el código a simple
                 vista: `elementFromPoint` seguía encontrando el logo y los
                 botones porque `.app-background` tiene pointer-events:none,
                 pero visualmente quedaban tapados). -->
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
                    <ThemeToggle v-if="surface !== 'glass'" />
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

            <div v-if="mobileMenuOpen" class="panel-nav relative z-10 px-4 py-3 lg:hidden">
                <div class="mb-1 text-sm font-semibold text-content-primary">
                    {{ page.props.auth.user.name }}
                </div>
                <div class="mb-3 text-sm text-content-secondary">
                    {{ page.props.auth.user.email }}
                </div>
                <!-- Perfil no va acá: ya está en la barra inferior, sería
                     redundante (corrección del usuario). -->
                <Link
                    :href="route('settings.edit')"
                    class="block min-h-[44px] py-2 text-sm text-content-secondary"
                >
                    Ajustes
                </Link>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="block min-h-[44px] py-2 text-sm text-content-secondary"
                >
                    Salir
                </Link>
            </div>

            <main class="mx-auto w-full max-w-[1200px] flex-1 px-4 pb-24 pt-6 lg:px-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- Barra inferior — solo móvil -->
        <nav
            class="panel-nav fixed inset-x-0 bottom-0 z-40 flex justify-around py-2 lg:hidden"
            aria-label="Navegación principal"
        >
            <template v-for="item in navItems" :key="item.label">
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
                <span
                    v-else
                    class="flex min-h-[44px] min-w-[44px] cursor-not-allowed flex-col items-center justify-center gap-0.5 rounded-xl px-3 text-xs font-semibold text-content-muted opacity-50"
                    aria-disabled="true"
                >
                    <NavIcon :name="item.icon" />
                    {{ item.label }}
                </span>
            </template>
        </nav>
    </div>
</template>
