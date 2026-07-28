<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SurfaceModeToggle from '@/Components/SurfaceModeToggle.vue';

/*
 * Estructura de docs/04-DISENO-VISUAL.md §9 y §14: barra lateral fija en
 * escritorio, barra inferior en móvil. Los 5 destinos de la navegación
 * principal son fijos (Inicio, Hábitos, Pomodoro, Misiones, Avatar), pero
 * solo "Inicio" y "Perfil" tienen página real hoy — el resto se agrega en
 * su propia fase de docs/13-ROADMAP.md. No apuntar un nav item a una ruta
 * que no existe: Ziggy revienta en tiempo de ejecución si `route()` no la
 * conoce.
 */
const navItems = [
    { label: 'Inicio', routeName: 'dashboard', icon: 'home' },
    // Hábitos (Fase 3), Pomodoro (Fase 5), Misiones (Fase 6): se agregan
    // acá cuando exista su ruta real.
    { label: 'Perfil', routeName: 'profile.edit', icon: 'user' },
];

const page = usePage();
const { theme, setTheme } = useTheme();
const mobileMenuOpen = ref(false);

function toggleTheme() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <div class="min-h-screen bg-bg lg:flex">
        <!-- Barra lateral — solo escritorio -->
        <aside class="hidden w-[260px] shrink-0 border-r border-border lg:flex lg:flex-col">
            <div class="flex h-16 items-center gap-2 px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-auto fill-current text-primary-strong" />
                    <span class="font-display text-lg font-semibold text-content-primary">Epycus</span>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 px-4" aria-label="Navegación principal">
                <Link
                    v-for="item in navItems"
                    :key="item.routeName"
                    :href="route(item.routeName)"
                    class="flex min-h-[44px] items-center gap-3 rounded px-3 text-sm font-semibold transition-colors duration-150"
                    :class="
                        route().current(item.routeName)
                            ? 'bg-primary text-on-primary'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-border p-4">
                <div class="mb-2 truncate text-sm font-semibold text-content-primary">
                    {{ page.props.auth.user.name }}
                </div>
                <SurfaceModeToggle class="mb-2" />
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded border border-border-interactive px-3 text-sm text-content-secondary hover:text-content-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                        @click="toggleTheme"
                    >
                        {{ theme === 'dark' ? 'Modo claro' : 'Modo oscuro' }}
                    </button>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex min-h-[44px] items-center justify-center rounded border border-border-interactive px-3 text-sm text-content-secondary hover:text-content-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                    >
                        Salir
                    </Link>
                </div>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <!-- Cabecera — solo móvil -->
            <header class="flex h-16 items-center justify-between border-b border-border px-4 lg:hidden">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-7 w-auto fill-current text-primary-strong" />
                </Link>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-content-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                        :aria-label="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        @click="toggleTheme"
                    >
                        {{ theme === 'dark' ? '☀' : '☾' }}
                    </button>
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

            <div v-if="mobileMenuOpen" class="border-b border-border px-4 py-3 lg:hidden">
                <div class="mb-1 text-sm font-semibold text-content-primary">{{ page.props.auth.user.name }}</div>
                <div class="mb-3 text-sm text-content-secondary">{{ page.props.auth.user.email }}</div>
                <SurfaceModeToggle class="mb-3" />
                <Link :href="route('profile.edit')" class="block min-h-[44px] py-2 text-sm text-content-secondary">
                    Perfil
                </Link>
                <Link :href="route('logout')" method="post" as="button" class="block min-h-[44px] py-2 text-sm text-content-secondary">
                    Salir
                </Link>
            </div>

            <main class="mx-auto w-full max-w-[1200px] flex-1 px-4 pb-24 pt-6 lg:px-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- Barra inferior — solo móvil -->
        <nav
            class="panel-raised fixed inset-x-0 bottom-0 z-40 flex justify-around border-t border-border py-2 lg:hidden"
            aria-label="Navegación principal"
        >
            <Link
                v-for="item in navItems"
                :key="item.routeName"
                :href="route(item.routeName)"
                class="flex min-h-[44px] min-w-[44px] flex-col items-center justify-center gap-0.5 rounded px-3 text-xs font-semibold"
                :class="
                    route().current(item.routeName)
                        ? 'text-primary-strong'
                        : 'text-content-secondary'
                "
            >
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>
