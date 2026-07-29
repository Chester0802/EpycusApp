<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import { useTelemetry } from '@/Composables/useTelemetry';

const props = defineProps({
    habits: {
        type: Array,
        default: () => [],
    },
    todayDate: {
        type: String,
        required: true,
    },
    // Personaje fijo de este módulo (orden=2), fase al azar — ver
    // App\Shared\Domain\Services\AvatarAssetResolver.
    avatarImage: {
        type: String,
        default: null,
    },
});

const { track } = useTelemetry();

const showCreateModal = ref(false);
const xpNotification = ref(null);

const categoryOptions = [
    { value: 'estudio', label: '📖 Estudio' },
    { value: 'sueno', label: '😴 Sueño' },
    { value: 'ejercicio', label: '🏃 Ejercicio' },
    { value: 'alimentacion', label: '🥗 Alimentación' },
    { value: 'otro', label: '✨ Otro' },
];

const form = useForm({
    title: '',
    category: 'estudio',
    frequency: { type: 'daily' },
    icon: '⚡',
});

const categoryBadges = {
    estudio: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    sueno: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
    ejercicio: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    alimentacion: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    otro: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
};

const openModal = () => {
    showCreateModal.value = true;
    track('open_create_habit_modal', 'habits');
};

const closeModal = () => {
    showCreateModal.value = false;
    form.reset();
};

const submitHabit = () => {
    form.post(route('habits.store'), {
        onSuccess: () => {
            track('create_habit_success', 'habits', { category: form.category });
            closeModal();
        },
    });
};

const toggleHabit = (habit) => {
    const originalState = habit.is_completed_today;
    habit.is_completed_today = !originalState;

    router.post(
        route('habits.toggle', { id: habit.id }),
        { date: props.todayDate },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const wasCompleted = habit.is_completed_today;
                const xp = wasCompleted ? 10 : 0;

                track('toggle_habit_completion', 'habits', {
                    habit_id: habit.id,
                    completed: wasCompleted,
                    xp_awarded: xp,
                });

                if (wasCompleted) {
                    triggerXpToast('+10 XP ¡Excelente hábito!');
                }
            },
            onError: () => {
                habit.is_completed_today = originalState;
            },
        },
    );
};

const deleteHabit = (habitId) => {
    if (confirm('¿Deseas eliminar este hábito?')) {
        track('delete_habit', 'habits', { habit_id: habitId });
        router.delete(route('habits.destroy', { id: habitId }));
    }
};

const triggerXpToast = (msg) => {
    xpNotification.value = msg;
    setTimeout(() => {
        xpNotification.value = null;
    }, 3000);
};
</script>

<template>
    <Head title="Mis Hábitos — Epycus" />

    <AppLayout>
        <!-- Toast XP -->
        <Transition
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="xpNotification"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary shadow-lg backdrop-blur-md"
            >
                <span class="text-xl">⭐</span>
                <span>{{ xpNotification }}</span>
            </div>
        </Transition>

        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        v-if="avatarImage"
                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-1"
                    >
                        <img :src="avatarImage" alt="" class="h-full w-full object-contain" />
                    </div>
                    <div>
                        <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">
                            Hábitos Diarios
                        </h1>
                        <p class="mt-1 text-sm text-content-secondary">
                            Construye consistencia día a día en tus metas.
                        </p>
                    </div>
                </div>
                <BaseButton @click="openModal"> + Nuevo Hábito </BaseButton>
            </header>

            <!-- Lista de hábitos -->
            <div v-if="habits.length > 0" class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="habit in habits"
                    :key="habit.id"
                    class="panel-raised relative flex items-center justify-between gap-4 p-5 transition-all hover:scale-[1.01]"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <button
                            type="button"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border text-lg transition-all"
                            :class="[
                                habit.is_completed_today
                                    ? 'bg-primary text-on-primary border-primary shadow-md scale-105'
                                    : 'bg-bg border-border-interactive text-content-muted hover:border-primary',
                            ]"
                            @click="toggleHabit(habit)"
                        >
                            <span v-if="habit.is_completed_today">✓</span>
                            <span v-else>{{ habit.icon || '⚡' }}</span>
                        </button>

                        <div class="truncate">
                            <h3
                                class="font-semibold text-content-primary truncate"
                                :class="{ 'line-through opacity-60': habit.is_completed_today }"
                            >
                                {{ habit.title }}
                            </h3>
                            <span
                                class="inline-block mt-1 rounded-md border px-2 py-0.5 text-xs font-medium capitalize"
                                :class="categoryBadges[habit.category] || categoryBadges.otro"
                            >
                                {{ habit.category }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="text-content-muted hover:text-danger-text text-sm p-1 rounded transition"
                        title="Eliminar hábito"
                        @click="deleteHabit(habit.id)"
                    >
                        🗑️
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="panel-raised flex flex-col items-center justify-center p-12 text-center"
            >
                <span class="text-4xl mb-3">🌱</span>
                <h2 class="text-lg font-semibold text-content-primary">
                    No tienes hábitos registrados
                </h2>
                <p class="mt-1 text-sm text-content-secondary max-w-sm">
                    Crea tu primer hábito diario para comenzar a acumular racha y ganar experiencia.
                </p>
                <BaseButton class="mt-6" @click="openModal"> Crear mi primer hábito </BaseButton>
            </div>
        </div>

        <!-- Modal de creación -->
        <BaseModal :show="showCreateModal" title="Nuevo Hábito" @close="closeModal">
            <form class="space-y-4" @submit.prevent="submitHabit">
                <BaseInput
                    id="title"
                    v-model="form.title"
                    label="Título del hábito"
                    placeholder="Ej. Estudiar 45 min de Algoritmos"
                    :error="form.errors.title"
                    required
                />

                <BaseSelect
                    id="category"
                    v-model="form.category"
                    label="Categoría"
                    :options="categoryOptions"
                    :error="form.errors.category"
                    required
                />

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeModal">
                        Cancelar
                    </BaseButton>
                    <BaseButton type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando…' : 'Crear Hábito' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
