<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import CourseNotesTab from './Partials/CourseNotesTab.vue';
import CourseProject from './Partials/CourseProject.vue';
import GradeSimulator from './Partials/GradeSimulator.vue';
import SyllabusViewer from './Partials/SyllabusViewer.vue';
import CourseLearningTab from './Partials/CourseLearningTab.vue';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    graphData: {
        type: Object,
        required: false,
    },
    learningStats: {
        type: Object,
        required: false,
    }
});

const tabs = [
    { id: 'apuntes', label: 'Apuntes', icon: 'file-text' },
    { id: 'zona-aprendizaje', label: 'Zona de Aprendizaje', icon: 'brain' },
    { id: 'notas', label: 'Notas', icon: 'bar-chart' },
    { id: 'silabo', label: 'Sílabo', icon: 'file' },
    { id: 'proyecto', label: 'Proyecto ABP', icon: 'folder-kanban' },
];

function getInitialTab() {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab');
        if (tabFromUrl && tabs.some(t => t.id === tabFromUrl)) {
            return tabFromUrl;
        }
        if (tabFromUrl === 'flashcards') {
            return 'zona-aprendizaje';
        }
        const savedTab = localStorage.getItem(`course_${props.course.id}_tab`);
        if (savedTab && tabs.some(t => t.id === savedTab)) {
            return savedTab;
        }
    }
    return 'apuntes';
}

const activeTab = ref(getInitialTab());
const showEditModal = ref(false);

watch(activeTab, (newTab) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem(`course_${props.course.id}_tab`, newTab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url.toString());
    }
});

const colorOptions = [
    { value: 'primary', label: 'Indigo / Azul' },
    { value: 'accent', label: 'Púrpura' },
    { value: 'success', label: 'Verde' },
    { value: 'warning', label: 'Naranja' },
    { value: 'secondary', label: 'Gris' },
];

const editCourseForm = useForm({
    name: props.course.name,
    professor: props.course.professor || '',
    credits: props.course.credits ?? '',
    target_grade: props.course.target_grade ?? '',
    min_pass_grade: props.course.min_pass_grade ?? '',
    color: props.course.color || 'primary',
    starts_at: props.course.starts_at ? props.course.starts_at.substring(0, 10) : '',
    ends_at: props.course.ends_at ? props.course.ends_at.substring(0, 10) : '',
    sessions: props.course.sessions && props.course.sessions.length > 0
        ? props.course.sessions.map((s) => ({
            day_of_week: s.day_of_week,
            start_time: s.start_time ? s.start_time.substring(0, 5) : '08:00',
            end_time: s.end_time ? s.end_time.substring(0, 5) : '10:00',
            classroom: s.classroom || '',
        }))
        : [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }],
});

function openEditModal() {
    editCourseForm.clearErrors();
    editCourseForm.name = props.course.name;
    editCourseForm.professor = props.course.professor || '';
    editCourseForm.credits = props.course.credits ?? '';
    editCourseForm.target_grade = props.course.target_grade ?? '';
    editCourseForm.min_pass_grade = props.course.min_pass_grade ?? '';
    editCourseForm.color = props.course.color || 'primary';
    editCourseForm.starts_at = props.course.starts_at ? props.course.starts_at.substring(0, 10) : '';
    editCourseForm.ends_at = props.course.ends_at ? props.course.ends_at.substring(0, 10) : '';

    if (props.course.sessions && props.course.sessions.length > 0) {
        editCourseForm.sessions = props.course.sessions.map((s) => ({
            day_of_week: s.day_of_week,
            start_time: s.start_time ? s.start_time.substring(0, 5) : '08:00',
            end_time: s.end_time ? s.end_time.substring(0, 5) : '10:00',
            classroom: s.classroom || '',
        }));
    } else {
        editCourseForm.sessions = [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }];
    }

    showEditModal.value = true;
}

function addSession() {
    editCourseForm.sessions.push({ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' });
}

function removeSession(index) {
    if (editCourseForm.sessions.length > 1) {
        editCourseForm.sessions.splice(index, 1);
    }
}

function setSessionDay(idx, val) {
    editCourseForm.sessions[idx].day_of_week = Number(val);
}

function submitEditCourse() {
    editCourseForm.put(route('calendar.courses.update', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
}
</script>

<template>
    <Head :title="course.name" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3 w-full flex-wrap">
                <div class="flex items-center gap-3">
                    <Link :href="route('courses.index')" class="text-content-secondary hover:text-content-primary transition">
                        <AppIcon name="arrow-left" :size="20" />
                    </Link>
                    <div class="h-6 w-px bg-border-interactive"></div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <div 
                            class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-white font-bold text-xs shadow-sm"
                            :style="{ backgroundColor: course.color || '#3b82f6' }"
                        >
                            {{ course.name.substring(0, 1).toUpperCase() }}
                        </div>
                        <h2 class="font-bold text-xl text-content-primary leading-tight">
                            {{ course.name }}
                        </h2>

                        <div class="flex items-center gap-1.5 ml-1 flex-wrap">
                            <span v-if="course.credits" class="px-2 py-0.5 rounded-full text-xs font-bold bg-primary/15 text-primary-strong border border-primary/30">
                                🎓 {{ course.credits }} Créditos
                            </span>
                            <span v-if="course.professor" class="px-2 py-0.5 rounded-full text-xs font-medium bg-surface-raised text-content-secondary border border-border">
                                👨‍🏫 {{ course.professor }}
                            </span>
                            <span v-if="course.target_grade" class="px-2 py-0.5 rounded-full text-xs font-bold bg-success/15 text-success border border-success/30">
                                🎯 Meta: {{ course.target_grade }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <BaseButton variant="secondary" size="sm" @click="openEditModal">
                        <Pencil :size="14" class="mr-1" /> Editar Curso
                    </BaseButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Navigation Tabs -->
                <div class="border-b border-border-interactive mb-6 overflow-x-auto scrollbar-hide">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-primary-strong text-primary-strong font-bold'
                                    : 'border-transparent text-content-secondary hover:text-content-primary hover:border-border-interactive font-medium',
                                'group inline-flex items-center gap-2 py-4 px-1 border-b-2 text-sm transition-colors whitespace-nowrap cursor-pointer'
                            ]"
                        >
                            <AppIcon :name="tab.icon" :size="16" />
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Contenido de las pestañas -->
                <div v-show="activeTab === 'proyecto'">
                    <CourseProject :course="course" />
                </div>
                
                <div v-show="activeTab === 'notas'">
                    <GradeSimulator :course="course" />
                </div>
                
                <div v-show="activeTab === 'silabo'">
                    <SyllabusViewer :course="course" />
                </div>

                <div v-show="activeTab === 'zona-aprendizaje'">
                    <CourseLearningTab 
                        :course="course" 
                        :graphData="graphData"
                        :learningStats="learningStats"
                    />
                </div>

                <div v-show="activeTab === 'apuntes'">
                    <CourseNotesTab :course="course" />
                </div>

                <div v-show="activeTab !== 'proyecto' && activeTab !== 'notas' && activeTab !== 'silabo' && activeTab !== 'zona-aprendizaje' && activeTab !== 'apuntes'">
                    <BaseCard class="p-12 text-center flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-full bg-surface-raised flex items-center justify-center text-content-muted mb-4">
                            <AppIcon :name="tabs.find(t => t.id === activeTab)?.icon" :size="32" />
                        </div>
                        <h3 class="text-xl font-bold text-content-primary">Próximamente</h3>
                        <p class="text-content-secondary mt-2 max-w-md mx-auto">
                            El módulo de {{ tabs.find(t => t.id === activeTab)?.label }} se implementará en las siguientes fases del plan maestro de Epycus 2.0.
                        </p>
                    </BaseCard>
                </div>
            </div>
        </div>

        <!-- MODAL: EDITAR CURSO -->
        <BaseModal
            :show="showEditModal"
            @close="showEditModal = false"
            title="✏️ Editar Curso & Asignatura"
            size="lg"
        >
            <form @submit.prevent="submitEditCourse" class="space-y-6 pb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1 md:col-span-2">
                        <BaseInput
                            id="edit-course-name"
                            v-model="editCourseForm.name"
                            label="Nombre de la Asignatura *"
                            :error="editCourseForm.errors.name"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="edit-course-professor"
                            v-model="editCourseForm.professor"
                            label="Profesor / Docente"
                            placeholder="Ej. Juan Pérez"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="edit-course-credits"
                            v-model="editCourseForm.credits"
                            label="Créditos Académicos (ej. 3, 4, 5)"
                            type="number"
                            min="0"
                            max="50"
                            placeholder="4"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseSelect
                            id="edit-course-color"
                            v-model="editCourseForm.color"
                            label="Color Distintivo"
                            :options="colorOptions"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="edit-course-target-grade"
                            v-model="editCourseForm.target_grade"
                            label="Meta de Nota (ej. 16, 18, 20)"
                            type="number"
                            step="0.1"
                            min="0"
                            max="20"
                            placeholder="16"
                        />
                    </div>
                    
                    <div class="space-y-1">
                        <BaseInput
                            id="edit-course-starts-at"
                            v-model="editCourseForm.starts_at"
                            label="Fecha de Inicio"
                            type="date"
                            :error="editCourseForm.errors.starts_at"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="edit-course-ends-at"
                            v-model="editCourseForm.ends_at"
                            label="Fecha de Fin (Opcional)"
                            type="date"
                            :error="editCourseForm.errors.ends_at"
                        />
                    </div>
                </div>

                <div class="bg-surface-raised p-4 rounded-2xl border border-border space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-content-primary text-sm">Horarios y Aulas</h4>
                            <p class="text-xs text-content-secondary">Configura las sesiones semanales del curso.</p>
                        </div>
                        <BaseButton variant="ghost" size="sm" type="button" @click="addSession">
                            <Plus :size="14" />
                            Añadir Sesión
                        </BaseButton>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(session, index) in editCourseForm.sessions"
                            :key="index"
                            class="grid grid-cols-1 sm:grid-cols-[1fr_100px_100px_1fr_auto] gap-2 items-center bg-surface p-3 rounded-xl border border-border"
                        >
                            <BaseSelect
                                :id="'edit-session-day-' + index"
                                :model-value="String(session.day_of_week)"
                                @update:model-value="(val) => setSessionDay(index, val)"
                                :options="[
                                    { value: '1', label: 'Lunes' },
                                    { value: '2', label: 'Martes' },
                                    { value: '3', label: 'Miércoles' },
                                    { value: '4', label: 'Jueves' },
                                    { value: '5', label: 'Viernes' },
                                    { value: '6', label: 'Sábado' },
                                    { value: '7', label: 'Domingo' },
                                ]"
                                aria-label="Día"
                            />
                            <BaseInput
                                :id="'edit-session-start-' + index"
                                v-model="session.start_time"
                                type="time"
                                aria-label="Hora Inicio"
                                required
                            />
                            <BaseInput
                                :id="'edit-session-end-' + index"
                                v-model="session.end_time"
                                type="time"
                                aria-label="Hora Fin"
                                required
                            />
                            <BaseInput
                                :id="'edit-session-classroom-' + index"
                                v-model="session.classroom"
                                placeholder="Aula (Opc.)"
                                aria-label="Aula"
                            />
                            <button
                                type="button"
                                @click="removeSession(index)"
                                class="p-2 text-content-muted hover:text-danger hover:bg-danger/10 rounded-lg transition-colors disabled:opacity-50 cursor-pointer"
                                :disabled="editCourseForm.sessions.length <= 1"
                                title="Eliminar sesión"
                            >
                                <Trash2 :size="16" />
                            </button>
                        </div>
                    </div>
                    <div v-if="editCourseForm.errors.sessions" class="text-danger text-xs">
                        {{ editCourseForm.errors.sessions }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border mt-6">
                    <BaseButton variant="ghost" type="button" @click="showEditModal = false">
                        Cancelar
                    </BaseButton>
                    <BaseButton type="submit" :loading="editCourseForm.processing">
                        Guardar Cambios
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
