<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    mission: { type: Object, required: true },
});

const difficultyConfig = {
    easy: { label: 'Fácil', class: 'bg-success/20 text-success' },
    medium: { label: 'Media', class: 'bg-accent/20 text-accent' },
    hard: { label: 'Difícil', class: 'bg-danger/20 text-danger' },
};

const priorityConfig = {
    baja: { label: '↓ Baja', class: 'text-content-muted' },
    normal: { label: '— Normal', class: 'text-content-secondary' },
    alta: { label: '↑ Alta', class: 'text-danger-text' },
};

function toggleSubtask(subtaskId) {
    router.post(
        route('missions.subtasks.toggle', { id: props.mission.id, subtaskId }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

function completeMission() {
    router.post(route('missions.complete', { id: props.mission.id }), {}, { preserveScroll: true });
}

function startPomodoro() {
    router.visit(route('pomodoro.index'), {
        data: { mission_id: props.mission.id },
    });
}
</script>

<template>
    <Head :title="`${mission.title} — Misiones — Epycus`" />

    <AppLayout>
        <div class="mx-auto max-w-3xl">
            <a :href="route('missions.index')" class="mb-4 inline-block text-sm text-content-secondary hover:text-content-primary">← Volver a misiones</a>

            <BaseCard class="mb-6 p-6" :class="mission.is_overdue ? 'border-l-4 border-l-danger' : 'border-l-4 border-l-primary'">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <h1 class="font-display text-2xl text-content-primary">{{ mission.title }}</h1>
                        <p v-if="mission.description" class="mt-2 text-sm text-content-secondary">{{ mission.description }}</p>

                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            <span :class="difficultyConfig[mission.difficulty]?.class + ' rounded px-2 py-0.5'">{{ difficultyConfig[mission.difficulty]?.label }}</span>
                            <span :class="priorityConfig[mission.priority]?.class">{{ priorityConfig[mission.priority]?.label }}</span>
                            <span v-if="mission.due_date" class="text-content-muted">Vence: {{ mission.due_date }}</span>
                            <span v-if="mission.is_overdue" class="text-danger-text font-semibold">Vencida</span>
                            <span v-if="mission.completed_at" class="text-success">Completada {{ mission.completed_at }}</span>
                            <span v-if="mission.xp_awarded > 0" class="text-accent font-semibold">+{{ mission.xp_awarded }} XP</span>
                            <span v-if="mission.days_early_or_late !== null" class="text-content-muted">
                                {{ mission.days_early_or_late < 0 ? `${Math.abs(mission.days_early_or_late)} días antes` : `${mission.days_early_or_late} días tarde` }}
                            </span>
                        </div>
                    </div>

                    <div v-if="!mission.is_completed" class="flex shrink-0 flex-col gap-2">
                        <BaseButton variant="primary" @click="completeMission">✅ Completar</BaseButton>
                        <BaseButton variant="ghost" @click="startPomodoro">⏱ Enfocarme</BaseButton>
                    </div>
                </div>
            </BaseCard>

            <BaseCard v-if="mission.subtasks.length > 0" class="p-6">
                <h2 class="mb-3 font-display text-lg text-content-primary">Subtareas ({{ mission.subtask_done }}/{{ mission.subtask_count }})</h2>
                <div class="space-y-2">
                    <div
v-for="s in mission.subtasks" :key="s.id"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 transition"
                        :class="{ 'bg-surface-sunken line-through text-content-muted': s.is_completed }">
                        <input
type="checkbox" :checked="s.is_completed"
                            class="h-4 w-4 accent-primary"
                            @change="toggleSubtask(s.id)" />
                        {{ s.title }}
                    </div>
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>
