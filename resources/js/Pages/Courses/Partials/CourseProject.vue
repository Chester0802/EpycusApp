<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
});

const project = computed(() => {
    return props.course.projects && props.course.projects.length > 0 
        ? props.course.projects[0] 
        : null;
});

const isCreatingProject = ref(false);

const createProjectForm = useForm({
    title: '',
    description: '',
    phases: [
        { name: 'Fase 1: Propuesta', color: '#3b82f6' },
        { name: 'Fase 2: Desarrollo', color: '#10b981' },
    ],
});

function addPhase() {
    createProjectForm.phases.push({ name: `Fase ${createProjectForm.phases.length + 1}`, color: '#8b5cf6' });
}

function removePhase(index) {
    createProjectForm.phases.splice(index, 1);
}

function submitProject() {
    createProjectForm.post(route('course.projects.store', props.course.id), {
        onSuccess: () => {
            isCreatingProject.value = false;
        },
    });
}

// Kanban Logic
const allProjectMissions = computed(() => {
    if (!project.value || !project.value.phases) return [];
    
    let missions = [];
    project.value.phases.forEach(phase => {
        if (phase.missions) {
            phase.missions.forEach(mission => {
                missions.push({
                    ...mission,
                    phase_name: phase.name,
                    phase_color: phase.color
                });
            });
        }
    });
    return missions;
});

const selectedPhaseFilter = ref('all');

const filteredMissions = computed(() => {
    if (selectedPhaseFilter.value === 'all') return allProjectMissions.value;
    return allProjectMissions.value.filter(m => m.project_phase_id === selectedPhaseFilter.value);
});

// Kanban columns
const kanbanBacklog = computed(() => filteredMissions.value.filter(m => !m.completed_at && m.subtask_done === 0));
const kanbanInProgress = computed(() => filteredMissions.value.filter(m => !m.completed_at && m.subtask_done > 0 && m.subtask_done < m.subtask_count));
const kanbanInReview = computed(() => filteredMissions.value.filter(m => !m.completed_at && m.subtask_done > 0 && m.subtask_done === m.subtask_count));
const completedMissions = computed(() => filteredMissions.value.filter(m => m.completed_at));

const totalPomodoroMinutes = computed(() => {
    // Para simplificar ahora, supongamos que cada mision completada da minutos (ej. xp_awarded * 5)
    // En Fase 5 conectaremos la tabla pomodoro_sessions real.
    return allProjectMissions.value.reduce((total, m) => total + (m.xp_awarded || 0) * 5, 0);
});

// UI helpers
const difficultyConfig = {
    easy: { label: 'Fácil', class: 'bg-success/20 text-success' },
    medium: { label: 'Media', class: 'bg-warning/20 text-warning' },
    hard: { label: 'Difícil', class: 'bg-danger/20 text-danger' },
};

function goToMission(id) {
    // Redirige al modulo global de misiones pero abriendo esta misión
    router.visit(route('calendar.index') + `?mission=${id}`);
}
</script>

<template>
    <div>
        <!-- Pantalla de Bienvenida / Creación de Proyecto -->
        <div v-if="!project" class="max-w-2xl mx-auto">
            <BaseCard v-if="!isCreatingProject" class="p-8 text-center flex flex-col items-center">
                <div class="w-20 h-20 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-500 mb-6">
                    <AppIcon name="trello" :size="40" />
                </div>
                <h3 class="text-2xl font-black text-content-primary mb-2">Proyecto de Asignatura</h3>
                <p class="text-content-secondary mb-8">
                    Organiza todo tu proyecto de aprendizaje basado en proyectos (ABP) utilizando la metodología Kanban. Agrupa tus misiones en fases personalizadas.
                </p>
                <BaseButton variant="primary" @click="isCreatingProject = true" class="shadow-lg shadow-indigo-500/20">
                    Iniciar Proyecto del Curso
                </BaseButton>
            </BaseCard>

            <BaseCard v-else class="p-6 md:p-8">
                <h3 class="text-xl font-bold text-content-primary mb-6 flex items-center gap-2">
                    <AppIcon name="plus-circle" :size="20" /> Nuevo Proyecto
                </h3>

                <form @submit.prevent="submitProject" class="space-y-6">
                    <BaseInput
                        id="title"
                        v-model="createProjectForm.title"
                        label="Título del Proyecto"
                        placeholder="Ej. Desarrollo de App Móvil, Trabajo Final de Investigación..."
                        required
                    />

                    <div>
                        <label class="block text-xs font-semibold text-content-secondary mb-1">
                            Descripción / Objetivo (Opcional)
                        </label>
                        <textarea
                            v-model="createProjectForm.description"
                            rows="3"
                            class="w-full rounded-xl border border-border-interactive bg-surface px-3 py-2 text-sm text-content-primary outline-none focus:border-primary-strong"
                            placeholder="¿Qué se espera lograr con este proyecto?"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-content-primary mb-3 border-b border-border-interactive pb-2">
                            Fases del Proyecto
                        </label>
                        <div class="space-y-3">
                            <div 
                                v-for="(phase, index) in createProjectForm.phases" 
                                :key="index"
                                class="flex items-center gap-3 p-3 rounded-xl border border-border-interactive bg-surface-raised"
                            >
                                <div class="w-8 h-8 shrink-0 rounded flex items-center justify-center font-bold text-xs bg-surface shadow-sm border border-border-interactive">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <input 
                                        v-model="phase.name"
                                        class="w-full bg-transparent outline-none text-sm font-semibold text-content-primary placeholder:text-content-muted"
                                        placeholder="Nombre de la fase (ej. Investigación)"
                                        required
                                    />
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    <input type="color" v-model="phase.color" class="w-8 h-8 rounded cursor-pointer border-0 p-0" title="Color de fase" />
                                    <button type="button" @click="removePhase(index)" class="p-1.5 text-content-muted hover:text-danger transition cursor-pointer" title="Eliminar fase">
                                        <AppIcon name="trash-2" :size="16" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="addPhase" class="mt-3 text-xs font-semibold text-primary-strong hover:underline flex items-center gap-1 cursor-pointer">
                            + Añadir otra fase
                        </button>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-border-interactive">
                        <BaseButton variant="ghost" type="button" @click="isCreatingProject = false">Cancelar</BaseButton>
                        <BaseButton variant="primary" type="submit" :disabled="createProjectForm.processing">
                            Crear Proyecto
                        </BaseButton>
                    </div>
                </form>
            </BaseCard>
        </div>

        <!-- Tablero de Proyecto -->
        <div v-else>
            <!-- Header del Proyecto -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-black text-content-primary flex items-center gap-2">
                        <AppIcon name="trello" :size="24" class="text-indigo-500" />
                        {{ project.title }}
                    </h3>
                    <p v-if="project.description" class="text-sm text-content-secondary mt-1">
                        {{ project.description }}
                    </p>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="bg-surface-raised px-4 py-2 rounded-xl border border-border-interactive flex items-center gap-3">
                        <div class="p-1.5 rounded-lg bg-danger/10 text-danger">
                            <AppIcon name="timer" :size="18" />
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-content-muted">Tiempo de Enfoque</div>
                            <div class="text-sm font-black text-content-primary">{{ totalPomodoroMinutes }} min</div>
                        </div>
                    </div>
                    <BaseButton variant="primary" @click="router.visit(route('calendar.index') + '?new_mission=true&course_id=' + course.id)">
                        + Añadir Misión
                    </BaseButton>
                </div>
            </div>

            <!-- Filtros de Fases -->
            <div class="mb-6 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide">
                <button
                    type="button"
                    class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all cursor-pointer"
                    :class="selectedPhaseFilter === 'all' ? 'bg-primary-strong/10 border-primary-strong text-primary-strong' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="selectedPhaseFilter = 'all'"
                >
                    Todas las fases
                </button>
                <button
                    v-for="phase in project.phases"
                    :key="phase.id"
                    type="button"
                    class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all flex items-center gap-2 cursor-pointer"
                    :class="selectedPhaseFilter === phase.id ? 'ring-2 ring-offset-1 dark:ring-offset-slate-900 border-transparent text-content-primary' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    :style="selectedPhaseFilter === phase.id ? { backgroundColor: `${phase.color}20`, borderColor: phase.color } : {}"
                    @click="selectedPhaseFilter = phase.id"
                >
                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: phase.color }"></span>
                    {{ phase.name }}
                </button>
            </div>

            <!-- KANBAN UNIFICADO -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
                
                <!-- Columna 1: Por Hacer -->
                <div class="rounded-2xl bg-surface-raised/90 border border-border-interactive/90 p-3.5 flex flex-col min-h-[420px]">
                    <div class="flex items-center justify-between pb-2.5 border-b border-border-interactive mb-3">
                        <h2 class="font-bold text-xs text-content-primary flex items-center gap-1.5">
                            📋 Por Hacer
                        </h2>
                        <span class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-bold border">{{ kanbanBacklog.length }}</span>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        <div v-if="kanbanBacklog.length === 0" class="py-12 text-center text-xs text-content-muted">
                            Sin misiones por hacer.
                        </div>
                        <div v-for="m in kanbanBacklog" :key="m.id" class="relative rounded-xl bg-surface border p-3.5 shadow-sm transition hover:shadow-md cursor-pointer" @click="goToMission(m.id)">
                            <!-- Indicador de Color de Fase -->
                            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl" :style="{ backgroundColor: m.phase_color }"></div>
                            
                            <div class="pl-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider mb-1 block" :style="{ color: m.phase_color }">{{ m.phase_name }}</span>
                                <h4 class="font-bold text-xs text-content-primary line-clamp-2 leading-tight">{{ m.title }}</h4>
                                <div class="mt-2 text-[10px] font-semibold opacity-70 flex justify-between">
                                    <span>Pasos: {{ m.subtask_done }}/{{ m.subtask_count }}</span>
                                    <span v-if="m.due_date">📅 {{ m.due_date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: En Proceso -->
                <div class="rounded-2xl bg-primary-strong/10 border border-primary-strong/30 p-3.5 flex flex-col min-h-[420px]">
                    <div class="flex items-center justify-between pb-2.5 border-b border-primary-strong/20 mb-3">
                        <h2 class="font-bold text-xs text-primary-strong flex items-center gap-1.5">
                            ⚡ En Proceso
                        </h2>
                        <span class="rounded-full bg-primary-strong/20 px-2 py-0.5 text-[11px] font-bold text-primary-strong">{{ kanbanInProgress.length }}</span>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        <div v-if="kanbanInProgress.length === 0" class="py-12 text-center text-xs text-content-muted">
                            Empieza a trabajar en una misión.
                        </div>
                        <div v-for="m in kanbanInProgress" :key="m.id" class="relative rounded-xl bg-surface border p-3.5 shadow-sm transition hover:shadow-md cursor-pointer" @click="goToMission(m.id)">
                            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl" :style="{ backgroundColor: m.phase_color }"></div>
                            
                            <div class="pl-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider mb-1 block" :style="{ color: m.phase_color }">{{ m.phase_name }}</span>
                                <h4 class="font-bold text-xs text-content-primary line-clamp-2 leading-tight">{{ m.title }}</h4>
                                
                                <div class="mt-2">
                                    <div class="h-1.5 w-full rounded-full bg-current/15 overflow-hidden">
                                        <div class="h-full bg-primary-strong transition-all" :style="{ width: m.subtask_count > 0 ? (m.subtask_done / m.subtask_count) * 100 + '%' : '0%' }"></div>
                                    </div>
                                    <div class="text-[9px] font-semibold text-right mt-1 text-content-secondary">{{ m.subtask_done }}/{{ m.subtask_count }} completados</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: En Revisión -->
                <div class="rounded-2xl bg-warning/10 border border-warning/30 p-3.5 flex flex-col min-h-[420px]">
                    <div class="flex items-center justify-between pb-2.5 border-b border-warning/20 mb-3">
                        <h2 class="font-bold text-xs text-warning flex items-center gap-1.5">
                            🔍 En Revisión
                        </h2>
                        <span class="rounded-full bg-warning/20 px-2 py-0.5 text-[11px] font-bold text-warning">{{ kanbanInReview.length }}</span>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        <div v-if="kanbanInReview.length === 0" class="py-12 text-center text-xs text-content-muted">
                            Misiones listas para cerrar.
                        </div>
                        <div v-for="m in kanbanInReview" :key="m.id" class="relative rounded-xl bg-surface border p-3.5 shadow-sm transition hover:shadow-md cursor-pointer" @click="goToMission(m.id)">
                            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl" :style="{ backgroundColor: m.phase_color }"></div>
                            
                            <div class="pl-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider mb-1 block" :style="{ color: m.phase_color }">{{ m.phase_name }}</span>
                                <h4 class="font-bold text-xs text-content-primary line-clamp-2 leading-tight">{{ m.title }}</h4>
                                <div class="mt-2 text-xs font-bold text-success flex items-center gap-1">
                                    <AppIcon name="check-circle" :size="12" /> 100% Listo
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 4: Terminado -->
                <div class="rounded-2xl bg-success/10 border border-success/30 p-3.5 flex flex-col min-h-[420px]">
                    <div class="flex items-center justify-between pb-2.5 border-b border-success/20 mb-3">
                        <h2 class="font-bold text-xs text-success flex items-center gap-1.5">
                            ✅ Terminado
                        </h2>
                        <span class="rounded-full bg-success/20 px-2 py-0.5 text-[11px] font-bold text-success">{{ completedMissions.length }}</span>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        <div v-if="completedMissions.length === 0" class="py-12 text-center text-xs text-content-muted">
                            Aún no has completado misiones del proyecto.
                        </div>
                        <div v-for="m in completedMissions" :key="m.id" class="relative rounded-xl bg-surface border p-3.5 shadow-sm transition hover:shadow-md cursor-pointer opacity-80" @click="goToMission(m.id)">
                            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl" :style="{ backgroundColor: m.phase_color }"></div>
                            
                            <div class="pl-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider mb-1 block" :style="{ color: m.phase_color }">{{ m.phase_name }}</span>
                                <h4 class="font-bold text-xs text-content-secondary line-through line-clamp-2 leading-tight">{{ m.title }}</h4>
                                <div class="mt-2 text-[10px] text-content-muted">📅 {{ m.completed_at }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
