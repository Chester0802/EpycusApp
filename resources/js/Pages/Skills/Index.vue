<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { triggerConfetti, playSuccessChime, triggerHapticVibration } from '@/utils/celebration';

const props = defineProps({
    skills: { type: Array, default: () => [] },
    recentLogs: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ total_skills: 0, total_hours: 0, mastered_skills: 0, highest_level: 1 }),
    },
});

const activeCategory = ref('all');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showLogModal = ref(false);
const selectedSkill = ref(null);
const xpToast = ref(null);

const categoryOptions = [
    { value: 'technical', label: '💻 Habilidad Técnica / Programación / Herramientas' },
    { value: 'soft', label: '🗣️ Habilidad Blanda / Oratoria / Liderazgo' },
    { value: 'language', label: '🌍 Idioma / Lengua Extranjera' },
    { value: 'creative', label: '🎨 Creativa / Música / Diseño / Arte' },
    { value: 'physical', label: '🏃 Física / Deporte / Coordinación' },
    { value: 'other', label: '✨ Otra Destreza' },
];

const categoryLabels = {
    technical: 'Técnica',
    soft: 'Blanda',
    language: 'Idioma',
    creative: 'Creativa',
    physical: 'Física',
    other: 'Destreza',
};

const categoryBadges = {
    technical: 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30',
    soft: 'bg-amber-500/15 text-amber-400 border border-amber-500/30',
    language: 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30',
    creative: 'bg-purple-500/15 text-purple-400 border border-purple-500/30',
    physical: 'bg-orange-500/15 text-orange-400 border border-orange-500/30',
    other: 'bg-surface-raised text-content-muted border border-border',
};

const levelTitles = [
    'Principiante',
    'Iniciado',
    'Aprendiz',
    'Practicante',
    'Competente',
    'Avanzado',
    'Experto',
    'Maestro',
    'Gran Maestro',
    'Leyenda',
];

function getLevelTitle(level) {
    const idx = Math.min(levelTitles.length - 1, Math.max(0, level - 1));
    return levelTitles[idx];
}

const filteredSkills = computed(() => {
    if (activeCategory.value === 'all') return props.skills;
    return props.skills.filter((s) => s.category === activeCategory.value);
});

const createForm = useForm({
    name: '',
    category: 'technical',
    icon: 'code',
    description: '',
});

const editForm = useForm({
    name: '',
    category: 'technical',
    icon: 'code',
    description: '',
});

const logForm = ref({
    duration_minutes: 30,
    notes: '',
});

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post(route('skills.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(skill) {
    selectedSkill.value = skill;
    editForm.name = skill.name;
    editForm.category = skill.category;
    editForm.icon = skill.icon || 'code';
    editForm.description = skill.description || '';
    showEditModal.value = true;
}

function submitEdit() {
    if (!selectedSkill.value) return;
    editForm.put(route('skills.update', { id: selectedSkill.value.id }), {
        onSuccess: () => {
            showEditModal.value = false;
            selectedSkill.value = null;
        },
    });
}

function openLogModal(skill) {
    selectedSkill.value = skill;
    logForm.value.duration_minutes = 30;
    logForm.value.notes = '';
    showLogModal.value = true;
}

async function quickLogMinutes(skill, minutes) {
    await submitPracticeLog(skill, minutes, null);
}

async function submitLog() {
    if (!selectedSkill.value) return;
    await submitPracticeLog(selectedSkill.value, logForm.value.duration_minutes, logForm.value.notes);
    showLogModal.value = false;
}

async function submitPracticeLog(skill, minutes, notes) {
    try {
        const response = await axios.post(route('skills.practice', { id: skill.id }), {
            duration_minutes: minutes,
            notes,
        });

        const data = response.data;
        skill.current_level = data.skill.current_level;
        skill.current_xp = data.skill.current_xp;
        skill.target_xp = data.skill.target_xp;
        skill.total_minutes_practiced = data.skill.total_minutes_practiced;
        skill.total_hours_practiced = Math.round((data.skill.total_minutes_practiced / 60) * 10) / 10;
        skill.progress_percent = data.skill.target_xp > 0
            ? Math.min(100, Math.round((data.skill.current_xp / data.skill.target_xp) * 100))
            : 0;

        if (data.leveled_up) {
            triggerConfetti();
            playSuccessChime();
        }

        triggerHapticVibration([50, 40, 60]);
        xpToast.value = data.message;
        setTimeout(() => {
            xpToast.value = null;
        }, 4000);

        // Reload logs
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('Error al registrar práctica: ' + (e.response?.data?.message || e.message));
    }
}

function startPomodoroForSkill(skill) {
    router.visit(route('pomodoro.index'));
}

function deleteSkill(skillId) {
    if (confirm('¿Eliminar esta habilidad de tu árbol de destrezas? Se borrará su bitácora de práctica.')) {
        router.delete(route('skills.destroy', { id: skillId }), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Árbol de Habilidades - Epycus" />

    <AppLayout>
        <!-- Toast de XP / Subida de Nivel -->
        <div
            v-if="xpToast"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-2xl bg-surface-raised border border-primary/50 px-4 py-3 text-sm font-bold text-primary-strong shadow-xl animate-bounce"
        >
            <span>⚡</span>
            <span>{{ xpToast }}</span>
        </div>

        <div class="space-y-6 pb-12">
            <!-- Header -->
            <BaseCard class="p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">⚡</span>
                            <h1 class="text-xl sm:text-2xl font-black text-content-primary tracking-tight">
                                Árbol de Habilidades & Destrezas
                            </h1>
                        </div>
                        <p class="mt-1 text-xs sm:text-sm text-content-secondary max-w-2xl">
                            Domina competencias técnicas, blandas, idiomas o talentos creativos mediante práctica deliberada. Cada minuto suma puntos de experiencia y eleva tu nivel.
                        </p>
                    </div>

                    <BaseButton variant="primary" @click="openCreateModal">
                        <AppIcon name="plus" :size="16" /> + Nueva Habilidad
                    </BaseButton>
                </div>
            </BaseCard>

            <!-- Métricas Clave -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-primary-strong">{{ stats.total_skills }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Destrezas en Desarrollo</p>
                </BaseCard>
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-content-primary">{{ stats.total_hours }}h</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Horas de Práctica</p>
                </BaseCard>
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-success">{{ stats.mastered_skills }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Dominadas (Nivel ≥ 5)</p>
                </BaseCard>
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-primary">Nv. {{ stats.highest_level }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Nivel Más Alto</p>
                </BaseCard>
            </div>

            <!-- Filtros por Categoría -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeCategory === 'all' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeCategory === 'all'"
                >
                    ⚡ Todas ({{ skills.length }})
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeCategory === 'technical' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeCategory === 'technical'"
                >
                    💻 Técnicas
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeCategory === 'soft' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeCategory === 'soft'"
                >
                    🗣️ Blandas
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeCategory === 'language' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeCategory === 'language'"
                >
                    🌍 Idiomas
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeCategory === 'creative' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeCategory === 'creative'"
                >
                    🎨 Creativas
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeCategory === 'physical' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeCategory === 'physical'"
                >
                    🏃 Físicas
                </button>
            </div>

            <!-- Grid de Habilidades -->
            <div v-if="filteredSkills.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <BaseCard
                    v-for="skill in filteredSkills"
                    :key="skill.id"
                    class="p-4 sm:p-5 flex flex-col justify-between transition hover:border-primary/40 group"
                >
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                    <span
                                        class="rounded px-1.5 py-0.2 text-[10px] font-bold"
                                        :class="categoryBadges[skill.category]"
                                    >
                                        {{ categoryLabels[skill.category] || skill.category }}
                                    </span>
                                    <span class="rounded bg-primary/10 border border-primary/20 px-1.5 py-0.2 text-[10px] font-bold text-primary-strong">
                                        Nivel {{ skill.current_level }} · {{ getLevelTitle(skill.current_level) }}
                                    </span>
                                </div>

                                <h3 class="font-bold text-base text-content-primary group-hover:text-primary-strong transition truncate">
                                    {{ skill.name }}
                                </h3>
                                <p v-if="skill.description" class="text-xs text-content-secondary line-clamp-2 mt-0.5">
                                    {{ skill.description }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-content-muted hover:text-content-primary hover:bg-surface-raised transition cursor-pointer"
                                    title="Editar Habilidad"
                                    @click="openEditModal(skill)"
                                >
                                    <AppIcon name="pencil" :size="14" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-content-muted hover:text-danger hover:bg-danger/10 transition cursor-pointer"
                                    title="Eliminar"
                                    @click="deleteSkill(skill.id)"
                                >
                                    <AppIcon name="trash" :size="14" />
                                </button>
                            </div>
                        </div>

                        <!-- Barra de XP de Nivel de Habilidad -->
                        <div class="mt-4 space-y-1.5">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-content-secondary">
                                    {{ skill.current_xp }} / {{ skill.target_xp }} XP
                                </span>
                                <span class="text-primary-strong font-bold">
                                    {{ skill.progress_percent }}% para Nv. {{ skill.current_level + 1 }}
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-surface-sunken overflow-hidden border border-border-interactive">
                                <div
                                    class="h-full bg-gradient-to-r from-amber-500 via-primary to-primary-strong transition-all duration-300 rounded-full"
                                    :style="{ width: `${skill.progress_percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Estadísticas de Práctica -->
                        <div class="mt-3 flex items-center justify-between text-xs text-content-muted font-medium bg-surface-raised/60 p-2 rounded-xl border border-border">
                            <span class="flex items-center gap-1">
                                <span>⏱️</span> {{ skill.total_hours_practiced }} hrs totales
                            </span>
                            <span class="flex items-center gap-1">
                                <span>📝</span> {{ skill.logs_count }} sesiones
                            </span>
                        </div>
                    </div>

                    <!-- Botones de Acción Rápida -->
                    <div class="mt-4 pt-3 border-t border-border flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-1.5">
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="px-2 py-1 rounded-lg bg-surface-raised text-[11px] font-bold text-content-primary border border-border hover:border-primary transition cursor-pointer"
                                    title="Práctica rápida de 15 min (+23 XP)"
                                    @click="quickLogMinutes(skill, 15)"
                                >
                                    +15m
                                </button>
                                <button
                                    type="button"
                                    class="px-2 py-1 rounded-lg bg-surface-raised text-[11px] font-bold text-content-primary border border-border hover:border-primary transition cursor-pointer"
                                    title="Práctica rápida de 30 min (+45 XP)"
                                    @click="quickLogMinutes(skill, 30)"
                                >
                                    +30m
                                </button>
                                <button
                                    type="button"
                                    class="px-2 py-1 rounded-lg bg-surface-raised text-[11px] font-bold text-content-primary border border-border hover:border-primary transition cursor-pointer"
                                    title="Práctica rápida de 60 min (+90 XP)"
                                    @click="quickLogMinutes(skill, 60)"
                                >
                                    +60m
                                </button>
                            </div>

                            <button
                                type="button"
                                class="p-1.5 rounded-lg bg-surface-raised text-primary-strong border border-border hover:border-primary transition cursor-pointer text-xs font-bold flex items-center gap-1"
                                title="Iniciar temporizador Pomodoro enfocado en esta habilidad"
                                @click="startPomodoroForSkill(skill)"
                            >
                                <AppIcon name="timer" :size="14" /> Pomodoro
                            </button>
                        </div>

                        <BaseButton size="sm" variant="secondary" class="w-full" @click="openLogModal(skill)">
                            <span>📝</span> Registrar Práctica Detallada
                        </BaseButton>
                    </div>
                </BaseCard>
            </div>

            <!-- Estado Vacío -->
            <BaseCard v-else class="p-12 text-center flex flex-col items-center">
                <span class="text-5xl mb-3">⚡</span>
                <h3 class="text-base font-bold text-content-primary">No hay habilidades en esta categoría</h3>
                <p class="text-xs text-content-secondary max-w-sm mt-1">
                    Crea habilidades que desees desarrollar y registra tus sesiones de práctica para forjar maestría.
                </p>
                <BaseButton class="mt-4" variant="primary" @click="openCreateModal">
                    + Añadir mi primera habilidad
                </BaseButton>
            </BaseCard>

            <!-- Historial Reciente de Prácticas -->
            <div v-if="recentLogs.length > 0" class="mt-8">
                <h2 class="text-sm font-bold text-content-primary mb-3 flex items-center gap-1.5">
                    <span>📜</span> Bitácora Reciente de Práctica Deliberada
                </h2>

                <div class="space-y-2">
                    <BaseCard
                        v-for="log in recentLogs"
                        :key="log.id"
                        class="p-3 sm:p-4 flex items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="h-9 w-9 rounded-xl bg-primary/15 border border-primary/30 flex items-center justify-center text-primary-strong shrink-0 font-bold text-xs">
                                ⚡
                            </div>
                            <div class="truncate">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-bold text-sm text-content-primary truncate">
                                        {{ log.skill_name }}
                                    </p>
                                    <span class="text-xs font-bold text-success bg-success/15 px-2 py-0.2 rounded-full border border-success/30">
                                        +{{ log.xp_earned }} XP
                                    </span>
                                </div>
                                <p v-if="log.notes" class="text-xs text-content-muted truncate mt-0.5">
                                    {{ log.notes }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0 text-xs">
                            <p class="font-bold text-content-primary">{{ log.duration_minutes }} minutos</p>
                            <p class="text-[10px] text-content-muted">{{ log.logged_at }}</p>
                        </div>
                    </BaseCard>
                </div>
            </div>
        </div>

        <!-- MODAL: CREAR HABILIDAD -->
        <BaseModal :show="showCreateModal" title="Nueva Habilidad o Destreza" @close="showCreateModal = false">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <BaseInput
                    id="skill-name"
                    v-model="createForm.name"
                    label="Nombre de la Habilidad"
                    placeholder="Ej. Python & Machine Learning / Guitarra Clásica / Oratoria y Debate"
                    required
                />

                <BaseSelect
                    id="skill-category"
                    v-model="createForm.category"
                    label="Categoría"
                    :options="categoryOptions"
                    required
                />

                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">Descripción / Objetivos (Opcional)</label>
                    <textarea
                        v-model="createForm.description"
                        rows="2"
                        placeholder="Ej. Dominar estructuras de datos y resolver problemas complejos en LeetCode..."
                        class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary outline-none"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showCreateModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Añadir al Árbol ⚡</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: EDITAR HABILIDAD -->
        <BaseModal :show="showEditModal" :title="`Editar: ${selectedSkill?.name || ''}`" @close="showEditModal = false">
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput
                    id="edit-skill-name"
                    v-model="editForm.name"
                    label="Nombre de la Habilidad"
                    required
                />

                <BaseSelect
                    id="edit-skill-category"
                    v-model="editForm.category"
                    label="Categoría"
                    :options="categoryOptions"
                    required
                />

                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">Descripción</label>
                    <textarea
                        v-model="editForm.description"
                        rows="2"
                        class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary outline-none"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showEditModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Actualizar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: REGISTRAR PRÁCTICA DELIBERADA -->
        <BaseModal :show="showLogModal" :title="`Registrar Práctica: ${selectedSkill?.name || ''}`" @close="showLogModal = false">
            <form class="space-y-4" @submit.prevent="submitLog">
                <div class="p-3 rounded-xl bg-surface-raised border border-border text-xs text-content-secondary">
                    Cada minuto de práctica deliberada genera <strong>1.5 XP</strong> que aporta a subir de nivel tu habilidad y tu personaje.
                </div>

                <div>
                    <BaseInput
                        id="log-duration"
                        v-model="logForm.duration_minutes"
                        label="Duración de la sesión (en minutos)"
                        type="number"
                        min="1"
                        max="720"
                        required
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">Notas de la sesión / Aprendizajes</label>
                    <textarea
                        v-model="logForm.notes"
                        rows="2"
                        placeholder="Ej. Practiqué escalas menores a 120 bpm / Resolví 2 problemas de recursión..."
                        class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary outline-none"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showLogModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Guardar Práctica ⚡</BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
