<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    wallpapers: { type: Array, default: () => [] },
    unlockedWallpapers: { type: Array, default: () => ['Fondo_1'] },
    activeWallpaperKey: { type: String, default: 'Fondo_1' },
    userCoins: { type: Number, default: 0 },
});

const page = usePage();

const currentCoins = computed(() => {
    return props.userCoins ?? page.props.auth?.user?.coins ?? 0;
});

function isUnlocked(key) {
    return key === 'Fondo_1' || key === 'atardecer' || props.unlockedWallpapers.includes(key);
}

function isActive(key) {
    const current = props.activeWallpaperKey || page.props.preferences?.wallpaperKey || 'Fondo_1';
    return current === key || (current === 'atardecer' && key === 'Fondo_1');
}

function handleImageError(e, file) {
    e.target.src = `/assets/wallpapers/full/${file}`;
}

function selectWallpaper(key) {
    router.post(route('preferences.wallpaper.select'), { wallpaper_key: key }, {
        preserveScroll: true,
        onSuccess: () => {
            const wallpaperItem = props.wallpapers.find(w => w.key === key);
            if (wallpaperItem) {
                document.documentElement.style.setProperty('--user-wallpaper', `url('/assets/wallpapers/full/${wallpaperItem.file}')`);
            }
        },
    });
}

function unlockWallpaper(key, cost) {
    if (currentCoins.value < cost) {
        alert(`Monedas insuficientes. Tienes 🪙 ${currentCoins.value} y necesitas 🪙 ${cost}.`);
        return;
    }

    if (confirm(`¿Deseas desbloquear este fondo por 🪙 ${cost} monedas?`)) {
        router.post(route('preferences.wallpaper.unlock'), { wallpaper_key: key }, {
            preserveScroll: true,
            onSuccess: () => {
                const wallpaperItem = props.wallpapers.find(w => w.key === key);
                if (wallpaperItem) {
                    document.documentElement.style.setProperty('--user-wallpaper', `url('/assets/wallpapers/full/${wallpaperItem.file}')`);
                }
            },
        });
    }
}
</script>

<template>
    <BaseCard>
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl text-content-primary">Fondo de pantalla (Modo Vidrio)</h2>
                <p class="text-sm text-content-secondary">
                    Personaliza el fondo de la pantalla cuando tengas la superficie en modo Vidrio.
                </p>
            </div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-warning/20 px-3 py-1.5 text-xs font-semibold text-warning-text border border-warning/30 self-start sm:self-auto">
                <span>🪙 Monedas:</span>
                <span class="text-sm font-bold">{{ currentCoins }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="item in wallpapers"
                :key="item.key"
                class="group relative flex flex-col overflow-hidden rounded-xl border p-3 transition"
                :class="[
                    isActive(item.key) ? 'border-primary ring-2 ring-primary bg-primary/5' : 'border-border bg-surface-raised hover:border-border-strong'
                ]"
            >
                <!-- Cabecera del fondo: Muestra únicamente el código (ej. Fondo_1) -->
                <div class="mb-2 flex items-center justify-between">
                    <span class="font-mono text-xs font-bold text-content-primary">{{ item.key }}</span>
                </div>

                <div class="relative mb-3 aspect-video w-full overflow-hidden rounded-lg bg-surface-sunken">
                    <img
                        :src="`/assets/wallpapers/thumbs/${item.file}`"
                        :alt="item.key"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                        loading="lazy"
                        @error="(e) => handleImageError(e, item.file)"
                    />

                    <!-- Badge de estado -->
                    <div class="absolute top-2 right-2">
                        <span v-if="isActive(item.key)" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-on-primary shadow">
                            Activo
                        </span>
                        <span v-else-if="isUnlocked(item.key)" class="rounded-full bg-success/80 px-2 py-0.5 text-[10px] font-semibold text-white backdrop-blur-sm">
                            Desbloqueado
                        </span>
                        <span v-else class="rounded-full bg-surface-sunken/90 px-2 py-0.5 text-[10px] font-semibold text-content-secondary backdrop-blur-sm">
                            🪙 {{ item.cost }}
                        </span>
                    </div>
                </div>

                <div class="mt-auto">
                    <button
                        v-if="isActive(item.key)"
                        type="button"
                        disabled
                        class="w-full rounded-lg bg-primary/20 py-1.5 text-xs font-semibold text-primary-strong cursor-default"
                    >
                        ✓ Seleccionado
                    </button>

                    <BaseButton
                        v-else-if="isUnlocked(item.key)"
                        variant="secondary"
                        class="w-full text-xs py-1.5"
                        @click="selectWallpaper(item.key)"
                    >
                        Usar este fondo
                    </BaseButton>

                    <BaseButton
                        v-else
                        variant="primary"
                        class="w-full text-xs py-1.5"
                        :disabled="currentCoins < item.cost"
                        @click="unlockWallpaper(item.key, item.cost)"
                    >
                        {{ currentCoins < item.cost ? 'Monedas insuficientes' : `Desbloquear (🪙 ${item.cost})` }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </BaseCard>
</template>

