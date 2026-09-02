<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
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
const showAddPhaseModal = ref(false);
const showManagePhasesModal = ref(false);

const createProjectForm = useForm({
    title: '',
    description: '',
    phases: [
        { name: 'Fase 1: Propuesta', color: '#3b82f6' },
        { name: 'Fase 2: Desarrollo', color: '#10b981' },
    ],
});

const newPhaseForm = useForm({
    name: '',
    color: '#8b5cf6',
});

function addInitialPhase() {
    createProjectForm.phases.push({ name: `Fase ${createProjectForm.phases.length + 1}`, color: '#8b5cf6' });
}

function removeInitialPhase(index) {
    createProjectForm.phases.splice(index, 1);
}

function submitProject() {
    createProjectForm.post(route('course.projects.store', props.course.id), {
        onSuccess: () => {
            isCreatingProject.value = false;
        },
    });
}

function submitNewPhase() {
    if (!project.value) return;
    newPhaseForm.post(route('course.projects.phases.store', { courseId: props.course.id, projectId: project.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showAddPhaseModal.value = false;
            newPhaseForm.reset();
        },
    });
}

function deletePhase(phase) {
    if (!project.value) return;
    if (!confirm(`¿Eliminar la fase "${phase.name}"? Las misiones asignadas no se borrarán y pasarán al estado general del proyecto.`)) {
        return;
    }
    router.delete(route('course.projects.phases.destroy', { courseId: props.course.id, projectId: project.value.id, phaseId: phase.id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedPhaseFilter.value === phase.id) {
                selectedPhaseFilter.value = 'all';
            }
        },
    });
}

// Kanban Logic - Une misiones de fases y misiones directas del curso
const allProjectMissions = computed(() => {
    if (!project.value) return [];
    
    const missionsMap = new Map();
    const phases = project.value.phases || [];
    
    // 1. Misiones de las fases del proyecto
    phases.forEach(phase => {
        if (phase.missions && Array.isArray(phase.missions)) {
            phase.missions.forEach(mission => {
                missionsMap.set(mission.id, {
                    ...mission,
                    project_phase_id: mission.project_phase_id || phase.id,
                    phase_name: phase.name,
                    phase_color: phase.color,
                });
            });
        }
    });

    // 2. Misiones asignadas al curso (desde el módulo Misiones)
    if (props.course.missions && Array.isArray(props.course.missions)) {
        props.course.missions.forEach(mission => {
            if (missionsMap.has(mission.id)) {
                return;
            }
            const phase = phases.find(p => p.id === mission.project_phase_id);
            if (phase) {
                missionsMap.set(mission.id, {
                    ...mission,
                    phase_name: phase.name,
                    phase_color: phase.color,
                });
            } else if (mission.mission_type === 'project' || !mission.project_phase_id) {
                missionsMap.set(mission.id, {
                    ...mission,
                    phase_name: 'General',
                    phase_color: '#6366f1',
                });
            }
        });
    }

    return Array.from(missionsMap.values());
});

const selectedPhaseFilter = ref('all');

const filteredMissions = computed(() => {
    if (selectedPhaseFilter.value === 'all') return allProjectMissions.value;
    return allProjectMissions.value.filter(m => m.project_phase_id === selectedPhaseFilter.value);
});

// Columnas Kanban (con salvaguardas contra undefined y null)
const kanbanBacklog = computed(() => filteredMissions.value.filter(m => !m.completed_at && (!m.subtask_done || m.subtask_done === 0)));
const kanbanInProgress = computed(() => filteredMissions.value.filter(m => !m.completed_at && (m.subtask_done || 0) > 0 && (m.subtask_done || 0) < (m.subtask_count || 0)));
const kanbanInReview = computed(() => filteredMissions.value.filter(m => !m.completed_at && (m.subtask_done || 0) > 0 && (m.subtask_done || 0) === (m.subtask_count || 0)));
const completedMissions = computed(() => filteredMissions.value.filter(m => Boolean(m.completed_at)));

const totalPomodoroMinutes = computed(() => {
    return allProjectMissions.value.reduce((total, m) => total + (m.xp_awarded || 0) * 5, 0);
});

function openAddMission() {
    const phaseId = selectedPhaseFilter.value !== 'all' 
        ? selectedPhaseFilter.value 
        : (project.value?.phases?.[0]?.id || '');
    
    let url = route('missions.index') + `?new_mission=true&course_id=${props.course.id}`;
    if (phaseId) {
        url += `&project_phase_id=${phaseId}`;
    }
    router.visit(url);
}

function goToMission(id) {
    router.visit(route('missions.index') + `?mission=${id}`);
}

function toggleMissionComplete(event, mission) {
    event.stopPropagation();
    const endpoint = mission.completed_at 
        ? route('missions.uncomplete', mission.id) 
        : route('missions.complete', mission.id);
        
    router.post(endpoint, {}, {
        preserveScroll: true,
    });
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
                                    <button type="button" @click="removeInitialPhase(index)" class="p-1.5 text-content-muted hover:text-danger transition cursor-pointer" title="Eliminar fase">
                                        <AppIcon name="trash-2" :size="16" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="addInitialPhase" class="mt-3 text-xs font-semibold text-primary-strong hover:underline flex items-center gap-1 cursor-pointer">
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

        <!-- Dashboard del Proyecto Activo -->
        <div v-else class="space-y-6">
            <!-- Header del Proyecto -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                <div>
                    <h3 class="text-2xl font-black text-content-primary flex items-center gap-2">
                        <AppIcon name="trello" :size="24" class="text-indigo-500" />
                        {{ project.title }}
                    </h3>
                    <p v-if="project.description" class="text-sm text-content-secondary mt-1">
                        {{ project.description }}
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="bg-surface-raised px-4 py-2 rounded-xl border border-border-interactive flex items-center gap-3">
                        <div class="p-1.5 rounded-lg bg-danger/10 text-danger">
                            <AppIcon name="timer" :size="18" />
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-content-muted">Tiempo de Enfoque</div>
                            <div class="text-sm font-black text-content-primary">{{ totalPomodoroMinutes }} min</div>
                        </div>
                    </div>
                    <BaseButton variant="primary" @click="openAddMission" class="flex items-center gap-1.5 shadow-sm">
                        <AppIcon name="plus" :size="16" />
                        Añadir Misión
                    </BaseButton>
                </div>
            </div>

            <!-- Barra de Filtros y Gestión de Fases -->
            <div class="flex items-center justify-between gap-3 pb-2 border-b border-border-interactive/60">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide flex-1">
                    <button
                        type="button"
                        class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all cursor-pointer"
                        :class="selectedPhaseFilter === 'all' ? 'bg-primary-strong/15 border-primary-strong text-primary-strong font-bold shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                        @click="selectedPhaseFilter = 'all'"
                    >
                        Todas las fases ({{ allProjectMissions.length }})
                    </button>
                    <div
                        v-for="phase in project.phases"
                        :key="phase.id"
                        class="shrink-0 inline-flex items-center rounded-full border transition-all"
                        :class="selectedPhaseFilter === phase.id ? 'ring-2 ring-offset-1 dark:ring-offset-slate-900 border-transparent shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                        :style="selectedPhaseFilter === phase.id ? { backgroundColor: `${phase.color}20`, borderColor: phase.color } : {}"
                    >
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold flex items-center gap-2 cursor-pointer"
                            @click="selectedPhaseFilter = phase.id"
                        >
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: phase.color }"></span>
                            <span class="text-content-primary">{{ phase.name }}</span>
                            <span class="text-[10px] opacity-75">({{ (allProjectMissions.filter(m => m.project_phase_id === phase.id)).length }})</span>
                        </button>
                        <button
                            type="button"
                            @click.stop="deletePhase(phase)"
                            class="pr-2 pl-0.5 py-1 text-content-muted hover:text-danger transition cursor-pointer"
                            title="Eliminar esta fase"
                        >
                            <AppIcon name="x" :size="13" />
                        </button>
                    </div>
                </div>

                <div class="shrink-0 flex items-center gap-2">
                    <button
                        type="button"
                        @click="showAddPhaseModal = true"
                        class="px-3 py-1.5 rounded-xl border border-dashed border-primary-strong/50 bg-primary-strong/5 hover:bg-primary-strong/15 text-primary-strong text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <AppIcon name="plus" :size="14" />
                        + Nueva Fase
                    </button>
                    <button
                        type="button"
                        @click="showManagePhasesModal = true"
                        class="p-1.5 rounded-xl border border-border-interactive bg-surface hover:bg-surface-raised text-content-secondary hover:text-content-primary text-xs transition cursor-pointer"
                        title="Administrar Fases"
                    >
                        <AppIcon name="settings" :size="16" />
                    </button>
                </div>
            </div>

            <!-- KANBAN UNIFICADO -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
                
                <!-- Columna 1: Por Hacer -->
                <div class="rounded-2xl bg-surface-raised/90 border border-border-interactive/90 p-3.5 flex flex-col min-h-[440px]">
                    <div class="flex items-center justify-between pb-2.5 border-b border-border-interactive mb-3">
                        <h2 class="font-bold text-xs text-content-primary flex items-center gap-1.5">
                            📋 Por Hacer
                        </h2>
                        <span class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-bold border border-border-interactive">{{ kanbanBacklog.length }}</span>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        <div v-if="kanbanBacklog.length === 0" class="py-12 text-center text-xs text-content-muted">
                            Sin misiones por hacer.
                        </div>
                        <div
                            v-for="m in kanbanBacklog"
                            :key="m.id"
                            class="relative rounded-xl bg-surface border border-border-interactive p-3.5 shadow-xs transition hover:shadow-md cursor-pointer group"
                            @click="goToMission(m.id)"
                        >
                            <!-- Indicador de Color de Fase -->
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-xl" :style="{ backgroundColor: m.phase_color || '#6366f1' }"></div>
                            
                            <div class="pl-2">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="text-[9px] font-bold uppercase tracking-wider block" :style="{ color: m.phase_color || '#6366f1' }">
                                        {{ m.phase_name || 'General' }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="toggleMissionComplete($event, m)"
                                        class="text-content-muted hover:text-success transition p-0.5"
                                        title="Marcar como terminada"
                                    >
                                        <AppIcon name="circle" :size="15" />
                                    </button>
                                </div>
                                <h4 class="font-bold text-xs text-content-primary line-clamp-2 leading-tight">{{ m.title }}</h4>
                                <div class="mt-2.5 text-[10px] font-semibold opacity-75 flex items-center justify-between text-content-secondary">
                                    <span>Pasos: {{ m.subtask_done || 0 }}/{{ m.subtask_count || 0 }}</span>
                                    <span v-if="m.due_date" class="flex items-center gap-1">📅 {{ m.due_date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: En Proceso -->
                <div class="rounded-2xl bg-primary-strong/10 border border-primary-strong/30 p-3.5 flex flex-col min-h-[440px]">
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
                        <div
                            v-for="m in kanbanInProgress"
                            :key="m.id"
                            class="relative rounded-xl bg-surface border border-border-interactive p-3.5 shadow-xs transition hover:shadow-md cursor-pointer group"
                            @click="goToMission(m.id)"
                        >
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-xl" :style="{ backgroundColor: m.phase_color || '#6366f1' }"></div>
                            
                            <div class="pl-2">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="text-[9px] font-bold uppercase tracking-wider block" :style="{ color: m.phase_color || '#6366f1' }">
                                        {{ m.phase_name || 'General' }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="toggleMissionComplete($event, m)"
                                        class="text-content-muted hover:text-success transition p-0.5"
                                        title="Marcar como terminada"
                                    >
                                        <AppIcon name="circle" :size="15" />
                                    </button>
                                </div>
                                <h4 class="font-bold text-xs text-content-primary line-clamp-2 leading-tight">{{ m.title }}</h4>
                                
                                <div class="mt-2.5">
                                    <div class="h-1.5 w-full rounded-full bg-current/15 overflow-hidden">
                                        <div
                                            class="h-full bg-primary-strong transition-all"
                                            :style="{ width: m.subtask_count > 0 ? ((m.subtask_done || 0) / m.subtask_count) * 100 + '%' : '0%' }"
                                        ></div>
                                    </div>
                                    <div class="text-[9px] font-semibold text-right mt-1 text-content-secondary">
                                        {{ m.subtask_done || 0 }}/{{ m.subtask_count || 0 }} completados
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: En Revisión -->
                <div class="rounded-2xl bg-amber-500/10 border border-amber-500/30 p-3.5 flex flex-col min-h-[440px]">
                    <div class="flex items-center justify-between pb-2.5 border-b border-amber-500/20 mb-3">
                        <h2 class="font-bold text-xs text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                            🔍 En Revisión
                        </h2>
                        <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:text-amber-300">{{ kanbanInReview.length }}</span>
                    </div>
                    
                    <div class="space-y-3 flex-1">
                        <div v-if="kanbanInReview.length === 0" class="py-12 text-center text-xs text-content-muted">
                            Misiones listas para cerrar.
                        </div>
                        <div
                            v-for="m in kanbanInReview"
                            :key="m.id"
                            class="relative rounded-xl bg-surface border border-border-interactive p-3.5 shadow-xs transition hover:shadow-md cursor-pointer group"
                            @click="goToMission(m.id)"
                        >
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-xl" :style="{ backgroundColor: m.phase_color || '#6366f1' }"></div>
                            
                            <div class="pl-2">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="text-[9px] font-bold uppercase tracking-wider block" :style="{ color: m.phase_color || '#6366f1' }">
                                        {{ m.phase_name || 'General' }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="toggleMissionComplete($event, m)"
                                        class="text-content-muted hover:text-success transition p-0.5"
                                        title="Marcar como terminada"
                                    >
                                        <AppIcon name="circle" :size="15" />
                                    </button>
                                </div>
                                <h4 class="font-bold text-xs text-content-primary line-clamp-2 leading-tight">{{ m.title }}</h4>
                                <div class="mt-2.5 text-xs font-bold text-success flex items-center gap-1">
                                    <AppIcon name="check-circle" :size="13" /> Pasos 100% listos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 4: Terminado -->
                <div class="rounded-2xl bg-success/10 border border-success/30 p-3.5 flex flex-col min-h-[440px]">
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
                        <div
                            v-for="m in completedMissions"
                            :key="m.id"
                            class="relative rounded-xl bg-surface border border-border-interactive p-3.5 shadow-xs transition hover:shadow-md cursor-pointer opacity-85 group"
                            @click="goToMission(m.id)"
                        >
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-xl" :style="{ backgroundColor: m.phase_color || '#6366f1' }"></div>
                            
                            <div class="pl-2">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="text-[9px] font-bold uppercase tracking-wider block" :style="{ color: m.phase_color || '#6366f1' }">
                                        {{ m.phase_name || 'General' }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="toggleMissionComplete($event, m)"
                                        class="text-success hover:text-danger transition p-0.5"
                                        title="Desmarcar completada"
                                    >
                                        <AppIcon name="check-circle" :size="15" />
                                    </button>
                                </div>
                                <h4 class="font-bold text-xs text-content-secondary line-through line-clamp-2 leading-tight">{{ m.title }}</h4>
                                <div class="mt-2.5 text-[10px] text-content-muted flex items-center gap-1">
                                    <span>Completada</span>
                                    <span v-if="m.completed_at">📅 {{ m.completed_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL: AÑADIR NUEVA FASE -->
        <BaseModal
            :show="showAddPhaseModal"
            @close="showAddPhaseModal = false"
            title="✨ Añadir Nueva Fase al Proyecto"
            size="md"
        >
            <form @submit.prevent="submitNewPhase" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-content-secondary mb-1">
                        Nombre de la Fase *
                    </label>
                    <input
                        v-model="newPhaseForm.name"
                        type="text"
                        required
                        placeholder="Ej. Fase 3: Pruebas y Validación"
                        class="w-full rounded-xl border border-border-interactive bg-surface px-3 py-2 text-sm text-content-primary outline-none focus:border-primary-strong"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-content-secondary mb-1">
                        Color Distintivo
                    </label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            v-model="newPhaseForm.color"
                            class="w-10 h-10 rounded-lg cursor-pointer border border-border-interactive p-0.5 bg-surface"
                        />
                        <span class="text-xs text-content-secondary font-mono">{{ newPhaseForm.color }}</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-border-interactive">
                    <BaseButton variant="ghost" type="button" @click="showAddPhaseModal = false">
                        Cancelar
                    </BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="newPhaseForm.processing">
                        Guardar Fase
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: GESTIONAR FASES EXISTENTES -->
        <BaseModal
            :show="showManagePhasesModal"
            @close="showManagePhasesModal = false"
            title="⚙️ Administrar Fases del Proyecto"
            size="md"
        >
            <div class="space-y-4">
                <p class="text-xs text-content-secondary">
                    Puedes renombrar, cambiar el color o eliminar fases. Al eliminar una fase, las misiones asignadas no se eliminarán.
                </p>

                <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                    <div
                        v-for="p in (project?.phases || [])"
                        :key="p.id"
                        class="flex items-center justify-between gap-3 p-2.5 rounded-xl border border-border-interactive bg-surface-raised"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0" :style="{ backgroundColor: p.color }"></span>
                            <span class="font-bold text-xs text-content-primary truncate">{{ p.name }}</span>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[11px] text-content-muted">
                                {{ (allProjectMissions.filter(m => m.project_phase_id === p.id)).length }} misiones
                            </span>
                            <button
                                type="button"
                                @click="deletePhase(p)"
                                class="p-1.5 rounded-lg text-content-muted hover:text-danger hover:bg-danger/10 transition cursor-pointer"
                                title="Eliminar fase"
                            >
                                <AppIcon name="trash-2" :size="15" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-3 border-t border-border-interactive">
                    <BaseButton
                        variant="primary"
                        size="sm"
                        @click="showManagePhasesModal = false; showAddPhaseModal = true;"
                    >
                        + Añadir Otra Fase
                    </BaseButton>
                    <BaseButton variant="ghost" size="sm" @click="showManagePhasesModal = false">
                        Cerrar
                    </BaseButton>
                </div>
            </div>
        </BaseModal>
    </div>
</template>
