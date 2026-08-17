<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useTelemetry } from '@/Composables/useTelemetry';

const props = defineProps({
    summary: { type: Object, required: true },
    achievements: { type: Array, default: () => [] },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
    progress: { type: Object, default: () => ({ phase: 1 }) },
});

const { track } = useTelemetry();

const activeCategory = ref('all');
const searchQuery = ref('');
const filterStatus = ref('all'); // 'all' | 'unlocked' | 'locked'

onMounted(() => {
    track('achievements.viewed', 'achievements', {
        total_achievements: props.summary.total,
        unlocked_count: props.summary.unlocked,
        percent: props.summary.percent,
    });
});

function setCategory(catId) {
    activeCategory.value = catId;
    track('achievements.filtered', 'achievements', {
        category: catId,
        status: filterStatus.value,
    });
}

const rawCategories = [
    { id: 'all', label: 'Todos', icon: '🏆' },
    { id: 'constancia', label: 'Constancia', icon: '🔥' },
    { id: 'volumen', label: 'Pomodoro', icon: '⏱️' },
    { id: 'misiones', label: 'Misiones & Matriz', icon: '🎯' },
    { id: 'habitos', label: 'Hábitos', icon: '🌱' },
    { id: 'estudio_grupal', label: 'Grupos', icon: '👥' },
    { id: 'villanos', label: 'Villanos', icon: '⚔️' },
    { id: 'bienestar', label: 'Diario', icon: '🧘' },
    { id: 'progresion', label: 'Nivel & Avatar', icon: '🥋' },
];

const categories = computed(() => {
    return rawCategories.map((cat) => {
        if (cat.id === 'all') {
            return {
                ...cat,
                count: `${props.summary.unlocked}/${props.summary.total}`,
            };
        }
        const inCat = props.achievements.filter((a) => a.category === cat.id);
        const unlockedInCat = inCat.filter((a) => a.is_unlocked).length;
        return {
            ...cat,
            count: `${unlockedInCat}/${inCat.length}`,
        };
    });
});

const filteredAchievements = computed(() => {
    return props.achievements.filter((ach) => {
        const matchesCat = activeCategory.value === 'all' || ach.category === activeCategory.value;
        const matchesStatus =
            filterStatus.value === 'all' ||
            (filterStatus.value === 'unlocked' && ach.is_unlocked) ||
            (filterStatus.value === 'locked' && !ach.is_unlocked);
        const matchesSearch =
            !searchQuery.value.trim() ||
            ach.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            ach.description.toLowerCase().includes(searchQuery.value.toLowerCase());

        return matchesCat && matchesStatus && matchesSearch;
    });
});

const nextUnlockable = computed(() => {
    const locked = props.achievements.filter((a) => !a.is_unlocked && a.progress_percent > 0);
    if (locked.length === 0) return null;
    return locked.sort((a, b) => b.progress_percent - a.progress_percent)[0];
});
</script>

<template>
    <Head title="Logros e Insignias — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Tip dinámico del módulo -->
            <UsageTipBanner module="achievements" />

            <!-- Header Card Principal -->
            <BaseCard class="p-6 overflow-hidden relative border-border-interactive">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-2 shadow-sm"
                        >
                            <img
                                src="/assets/gifs/achievements.gif"
                                alt="Logros e Insignias"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">
                                Logros e Insignias 🏆
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Celebra tu constancia, hábitos sostenidos y maestría académica en Epycus.
                            </p>
                        </div>
                    </div>

                    <!-- Métricas globales del usuario -->
                    <div class="flex flex-col items-end shrink-0 min-w-[200px] space-y-2 bg-surface-raised/40 p-3.5 rounded-xl border border-border-interactive/50">
                        <div class="flex items-center justify-between w-full text-xs font-semibold text-content-primary">
                            <span>Progreso General:</span>
                            <span class="font-bold text-primary-strong">
                                {{ summary.unlocked }} / {{ summary.total }} ({{ summary.percent }}%)
                            </span>
                        </div>
                        <ProgressBar
                            :value="summary.unlocked"
                            :max="summary.total"
                            color="bg-primary-strong"
                            size="h-2.5"
                            class="w-full"
                        />
                        <div class="flex items-center justify-between w-full text-[11px] text-content-muted font-medium pt-1 border-t border-border-interactive/40">
                            <span>Recompensa Total:</span>
                            <span class="text-accent font-bold">+{{ summary.total_xp_earned || 0 }} XP ganado</span>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <!-- Banner de Próximo Logro Cercano (Si existe) -->
            <BaseCard
                v-if="nextUnlockable"
                class="p-4 border-l-4 border-l-accent bg-accent/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent/15 text-xl border border-accent/30">
                        {{ nextUnlockable.icon }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-accent">¡Casi conseguido!</span>
                            <h2 class="font-bold text-sm text-content-primary">{{ nextUnlockable.name }}</h2>
                        </div>
                        <p class="text-xs text-content-secondary mt-0.5">{{ nextUnlockable.description }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <span class="text-xs font-bold text-content-primary">
                            {{ nextUnlockable.current_value }} / {{ nextUnlockable.target_value }} {{ nextUnlockable.unit }}
                        </span>
                        <p class="text-[10px] text-accent font-semibold">{{ nextUnlockable.progress_percent }}% completado</p>
                    </div>
                    <div class="w-20 sm:w-24">
                        <ProgressBar
                            :value="nextUnlockable.current_value"
                            :max="nextUnlockable.target_value"
                            color="bg-accent"
                            size="h-2"
                        />
                    </div>
                </div>
            </BaseCard>

            <!-- Filtros: Categorías + Buscador + Estado -->
            <div class="space-y-3">
                <!-- Buscador y filtro de estado -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <div class="relative flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar insignias por nombre o descripción..."
                            class="w-full rounded-xl border border-border-interactive bg-surface px-3.5 py-2 pl-9 text-xs text-content-primary placeholder:text-content-muted outline-none focus:border-primary-strong shadow-xs"
                        />
                        <AppIcon
                            name="search"
                            :size="14"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-content-muted"
                        />
                    </div>

                    <div class="inline-flex rounded-xl bg-surface-raised p-1 border border-border-interactive shadow-xs shrink-0">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition cursor-pointer"
                            :class="filterStatus === 'all' ? 'bg-primary-strong text-white shadow-xs' : 'text-content-secondary hover:text-content-primary'"
                            @click="filterStatus = 'all'"
                        >
                            Todas ({{ props.achievements.length }})
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition cursor-pointer"
                            :class="filterStatus === 'unlocked' ? 'bg-success text-white shadow-xs' : 'text-content-secondary hover:text-content-primary'"
                            @click="filterStatus = 'unlocked'"
                        >
                            ✓ Desbloqueadas ({{ props.summary.unlocked }})
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition cursor-pointer"
                            :class="filterStatus === 'locked' ? 'bg-surface text-content-primary shadow-xs border border-border-interactive' : 'text-content-secondary hover:text-content-primary'"
                            @click="filterStatus = 'locked'"
                        >
                            🔒 Bloqueadas ({{ props.summary.total - props.summary.unlocked }})
                        </button>
                    </div>
                </div>

                <!-- Tabs de Categoría con Contadores -->
                <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        class="flex items-center gap-1.5 whitespace-nowrap px-3.5 py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer shrink-0 border"
                        :class="[
                            activeCategory === cat.id
                                ? 'bg-primary-strong text-white border-primary-strong shadow-sm'
                                : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised hover:text-content-primary',
                        ]"
                        @click="setCategory(cat.id)"
                    >
                        <span>{{ cat.icon }}</span>
                        <span>{{ cat.label }}</span>
                        <span
                            class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                            :class="activeCategory === cat.id ? 'bg-white/20 text-white' : 'bg-surface-raised text-content-muted'"
                        >
                            {{ cat.count }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Grid de Logros -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <BaseCard
                    v-for="ach in filteredAchievements"
                    :key="ach.id"
                    class="p-4 flex flex-col justify-between transition-all duration-200"
                    :class="[
                        ach.is_unlocked
                            ? 'border-primary-strong/40 bg-surface-raised/90 shadow-sm ring-1 ring-primary-strong/20 hover:shadow-md'
                            : 'bg-surface/60 border-border-interactive opacity-80 hover:opacity-100',
                    ]"
                >
                    <div>
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl shadow-xs border transition-transform group-hover:scale-105"
                                :class="
                                    ach.is_unlocked
                                        ? 'bg-primary-strong/15 border-primary-strong/30 text-primary-strong ring-2 ring-primary-strong/20'
                                        : 'bg-surface-raised border-border-interactive opacity-60'
                                "
                            >
                                {{ ach.icon }}
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <h3 class="font-bold text-sm text-content-primary truncate">
                                        {{ ach.name }}
                                    </h3>
                                    <span
                                        class="rounded-full px-2 py-0.2 text-[10px] font-black shrink-0"
                                        :class="
                                            ach.is_unlocked
                                                ? 'bg-amber-400/20 text-amber-500 dark:text-amber-300 border border-amber-400/30'
                                                : 'bg-surface-raised text-content-muted border border-border-interactive'
                                        "
                                    >
                                        +{{ ach.xp_reward }} XP
                                    </span>
                                </div>
                                <p class="text-xs text-content-secondary leading-relaxed line-clamp-2">
                                    {{ ach.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Barra de progreso para logros bloqueados -->
                        <div v-if="!ach.is_unlocked && ach.target_value > 1" class="mt-3.5 space-y-1">
                            <div class="flex items-center justify-between text-[11px] font-medium text-content-secondary">
                                <span>Progreso</span>
                                <span class="font-bold text-content-primary">
                                    {{ ach.current_value }} / {{ ach.target_value }} {{ ach.unit }}
                                </span>
                            </div>
                            <ProgressBar
                                :value="ach.current_value"
                                :max="ach.target_value"
                                color="bg-primary-strong"
                                size="h-1.5"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <!-- Footer de Estado de Desbloqueo -->
                    <div class="mt-3.5 pt-2.5 border-t border-border-interactive/40 flex items-center justify-between text-xs">
                        <span
                            v-if="ach.is_unlocked"
                            class="text-[11px] text-success font-semibold flex items-center gap-1"
                        >
                            <AppIcon name="check-circle" :size="12" /> Desbloqueado ({{ ach.unlocked_at || 'Completado' }})
                        </span>
                        <span v-else class="text-[11px] text-content-muted font-medium flex items-center gap-1">
                            <AppIcon name="lock" :size="12" /> Bloqueado ({{ ach.progress_percent }}%)
                        </span>

                        <span class="text-[10px] uppercase font-bold tracking-wider text-content-muted">
                            {{ ach.category }}
                        </span>
                    </div>
                </BaseCard>
            </div>

            <!-- Estado Vacío en Búsqueda -->
            <BaseCard
                v-if="filteredAchievements.length === 0"
                class="flex flex-col items-center p-12 text-center"
            >
                <AppIcon name="search" :size="40" class="mb-2 text-content-muted" />
                <h3 class="text-base font-semibold text-content-primary">No se encontraron insignias</h3>
                <p class="mt-1 text-xs text-content-secondary">
                    Prueba ajustando los filtros de categoría o el término de búsqueda.
                </p>
            </BaseCard>
        </div>
    </AppLayout>
</template>
