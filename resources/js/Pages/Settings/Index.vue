<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import SurfaceModeToggle from '@/Components/SurfaceModeToggle.vue';
import PaletteToggle from '@/Components/PaletteToggle.vue';
import WallpaperSelector from '@/Components/WallpaperSelector.vue';
import { useTheme } from '@/composables/useTheme';
import { Head } from '@inertiajs/vue3';

defineProps({
    wallpapers: { type: Array, default: () => [] },
    unlockedWallpapers: { type: Array, default: () => ['atardecer'] },
    activeWallpaperKey: { type: String, default: 'atardecer' },
    userCoins: { type: Number, default: 0 },
});

const { theme } = useTheme();
</script>

<template>
    <Head title="Ajustes" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Ajustes</h1>

        <div class="space-y-6">
            <BaseCard>
                <h2 class="mb-1 text-xl text-content-primary">Tema</h2>
                <p class="mb-4 text-sm text-content-secondary">Claro u oscuro.</p>
                <div class="flex items-center gap-3">
                    <ThemeToggle />
                    <span class="text-sm text-content-secondary">
                        {{ theme === 'dark' ? 'Oscuro' : 'Claro' }}
                    </span>
                </div>
            </BaseCard>

            <BaseCard>
                <h2 class="mb-1 text-xl text-content-primary">Superficie</h2>
                <p class="mb-4 text-sm text-content-secondary">
                    Neumorfismo no usa fondo de pantalla; vidrio sí.
                </p>
                <SurfaceModeToggle />
            </BaseCard>

            <BaseCard>
                <h2 class="mb-1 text-xl text-content-primary">Paleta de colores</h2>
                <p class="mb-4 text-sm text-content-secondary">
                    Cada paleta funciona en modo claro y oscuro.
                </p>
                <PaletteToggle />
            </BaseCard>

            <WallpaperSelector
                :wallpapers="wallpapers"
                :unlocked-wallpapers="unlockedWallpapers"
                :active-wallpaper-key="activeWallpaperKey"
                :user-coins="userCoins"
            />
        </div>
    </AppLayout>
</template>
