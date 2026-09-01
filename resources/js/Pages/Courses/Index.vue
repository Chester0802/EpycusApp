<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Plus, Trash2, Pencil } from '@lucide/vue';

const props = defineProps({
    courses: {
        type: Array,
        default: () => [],
    },
});

const showCourseModal = ref(false);
const isEditing = ref(false);
const editingCourseId = ref(null);

const courseForm = useForm({
    name: '',
    professor: '',
    credits: '',
    target_grade: '',
    min_pass_grade: '',
    color: 'primary',
    starts_at: '',
    ends_at: '',
    sessions: [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }],
});

const colorOptions = [
    { value: 'primary', label: 'Indigo / Azul' },
    { value: 'accent', label: 'Púrpura' },
    { value: 'success', label: 'Verde' },
    { value: 'warning', label: 'Naranja' },
    { value: 'secondary', label: 'Gris' },
];

function openCreateModal() {
    isEditing.value = false;
    editingCourseId.value = null;
    courseForm.reset();
    courseForm.clearErrors();
    courseForm.sessions = [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }];
    showCourseModal.value = true;
}

function openEditModal(course) {
    isEditing.value = true;
    editingCourseId.value = course.id;
    courseForm.clearErrors();
    courseForm.name = course.name;
    courseForm.professor = course.professor || '';
    courseForm.credits = course.credits ?? '';
    courseForm.target_grade = course.target_grade ?? '';
    courseForm.min_pass_grade = course.min_pass_grade ?? '';
    courseForm.color = course.color || 'primary';
    courseForm.starts_at = course.starts_at ? course.starts_at.substring(0, 10) : '';
    courseForm.ends_at = course.ends_at ? course.ends_at.substring(0, 10) : '';

    if (course.sessions && course.sessions.length > 0) {
        courseForm.sessions = course.sessions.map((s) => ({
            day_of_week: s.day_of_week,
            start_time: s.start_time ? s.start_time.substring(0, 5) : '08:00',
            end_time: s.end_time ? s.end_time.substring(0, 5) : '10:00',
            classroom: s.classroom || '',
        }));
    } else {
        courseForm.sessions = [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }];
    }

    showCourseModal.value = true;
}

function addSession() {
    courseForm.sessions.push({ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' });
}

function removeSession(index) {
    if (courseForm.sessions.length > 1) {
        courseForm.sessions.splice(index, 1);
    }
}

function setSessionDay(idx, val) {
    courseForm.sessions[idx].day_of_week = Number(val);
}

function submitCourse() {
    if (isEditing.value && editingCourseId.value) {
        courseForm.put(route('calendar.courses.update', editingCourseId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showCourseModal.value = false;
                courseForm.reset();
            },
        });
    } else {
        courseForm.post(route('calendar.courses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showCourseModal.value = false;
                courseForm.reset();
            },
        });
    }
}

const viewMode = ref('active'); // 'active' | 'archived'

const filteredCourses = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.courses.filter(c => {
        if (viewMode.value === 'active') {
            return !c.ends_at || c.ends_at >= today;
        } else {
            return c.ends_at && c.ends_at < today;
        }
    });
});
</script>

<template>
    <Head title="Cursos y Hub Académico" />

    <AppLayout>
        <template #header>
            <h2 class="font-bold text-xl text-content-primary leading-tight">
                Cursos
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <header class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-content-primary">Hub Académico</h1>
                        <p class="mt-1 text-sm text-content-secondary">
                            Gestiona tus apuntes, notas, proyectos y flashcards por asignatura.
                        </p>
                    </div>
                    <BaseButton @click="openCreateModal">
                        <Plus :size="18" class="mr-1.5" />
                        Nuevo Curso
                    </BaseButton>
                </header>

                <div class="flex items-center gap-4 mb-6 border-b border-border pb-px">
                    <button
                        type="button"
                        class="pb-3 text-sm font-bold transition-all relative cursor-pointer"
                        :class="viewMode === 'active' ? 'text-primary-strong' : 'text-content-secondary hover:text-content-primary'"
                        @click="viewMode = 'active'"
                    >
                        Cursos Activos
                        <div v-if="viewMode === 'active'" class="absolute bottom-0 left-0 w-full h-0.5 bg-primary-strong rounded-t-full"></div>
                    </button>
                    <button
                        type="button"
                        class="pb-3 text-sm font-bold transition-all relative cursor-pointer"
                        :class="viewMode === 'archived' ? 'text-primary-strong' : 'text-content-secondary hover:text-content-primary'"
                        @click="viewMode = 'archived'"
                    >
                        Archivados
                        <div v-if="viewMode === 'archived'" class="absolute bottom-0 left-0 w-full h-0.5 bg-primary-strong rounded-t-full"></div>
                    </button>
                </div>

                <div v-if="filteredCourses.length === 0" class="text-center py-20">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-surface-raised mb-4">
                        <AppIcon name="book" :size="32" class="text-content-muted" />
                    </div>
                    <h3 class="text-lg font-bold text-content-primary">
                        {{ viewMode === 'active' ? 'Sin Cursos Activos' : 'No hay Cursos Archivados' }}
                    </h3>
                    <p class="text-content-secondary mt-2">
                        {{ viewMode === 'active' ? 'No has agregado ningún curso o todos han finalizado.' : 'Los cursos pasados aparecerán aquí.' }}
                    </p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="course in filteredCourses"
                        :key="course.id"
                        :href="route('courses.show', course.id)"
                        class="block group"
                    >
                        <BaseCard class="h-full p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 relative overflow-hidden flex flex-col justify-between">
                            <!-- Fondo sutil de color del curso -->
                            <div 
                                class="absolute top-0 left-0 w-full h-1 opacity-50"
                                :style="{ backgroundColor: course.color || '#3b82f6' }"
                            ></div>
                            
                            <div>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-lg font-bold text-content-primary group-hover:text-primary-strong transition-colors truncate">
                                            {{ course.name }}
                                        </h3>
                                        <p class="text-xs text-content-secondary mt-1 font-medium truncate">
                                            {{ course.professor ? `👨‍🏫 ${course.professor}` : 'Profesor no asignado' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button
                                            type="button"
                                            class="p-1.5 rounded-lg text-content-muted hover:text-primary-strong hover:bg-surface-raised transition cursor-pointer"
                                            title="Editar Curso"
                                            @click.prevent.stop="openEditModal(course)"
                                        >
                                            <Pencil :size="15" />
                                        </button>
                                        <div 
                                            class="w-9 h-9 rounded-xl flex items-center justify-center bg-surface text-white font-bold text-xs"
                                            :style="{ backgroundColor: course.color || '#3b82f6' }"
                                        >
                                            {{ course.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-border-interactive grid grid-cols-2 gap-4">
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-content-muted">Meta</span>
                                    <span class="block text-sm font-semibold text-content-primary">
                                        {{ course.target_grade ? course.target_grade : '--' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-content-muted">Créditos</span>
                                    <span class="block text-sm font-semibold text-primary-strong">
                                        {{ course.credits ? `${course.credits} cr.` : '--' }}
                                    </span>
                                </div>
                            </div>
                        </BaseCard>
                    </Link>
                </div>
            </div>
        </div>

        <BaseModal
            :show="showCourseModal"
            @close="showCourseModal = false"
            :title="isEditing ? '✏️ Editar Curso' : '➕ Añadir Nuevo Curso'"
            size="lg"
        >
            <form @submit.prevent="submitCourse" class="space-y-6 pb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1 md:col-span-2">
                        <BaseInput
                            id="course-name"
                            v-model="courseForm.name"
                            label="Nombre de la Asignatura *"
                            placeholder="Ej. Arquitectura de Computadoras"
                            :error="courseForm.errors.name"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="course-professor"
                            v-model="courseForm.professor"
                            label="Profesor / Docente (Opcional)"
                            placeholder="Ej. Juan Pérez"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="course-credits"
                            v-model="courseForm.credits"
                            label="Créditos Académicos (ej. 3, 4, 5)"
                            type="number"
                            min="0"
                            max="50"
                            placeholder="4"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseSelect
                            id="course-color"
                            v-model="courseForm.color"
                            label="Color Distintivo"
                            :options="colorOptions"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="course-target-grade"
                            v-model="courseForm.target_grade"
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
                            id="course-starts-at"
                            v-model="courseForm.starts_at"
                            label="Fecha de Inicio"
                            type="date"
                            :error="courseForm.errors.starts_at"
                        />
                    </div>
                    <div class="space-y-1">
                        <BaseInput
                            id="course-ends-at"
                            v-model="courseForm.ends_at"
                            label="Fecha de Fin (Opcional)"
                            type="date"
                            :error="courseForm.errors.ends_at"
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
                            v-for="(session, index) in courseForm.sessions"
                            :key="index"
                            class="grid grid-cols-1 sm:grid-cols-[1fr_100px_100px_1fr_auto] gap-2 items-center bg-surface p-3 rounded-xl border border-border"
                        >
                            <BaseSelect
                                :id="'session-day-' + index"
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
                                :id="'session-start-' + index"
                                v-model="session.start_time"
                                type="time"
                                aria-label="Hora Inicio"
                                required
                            />
                            <BaseInput
                                :id="'session-end-' + index"
                                v-model="session.end_time"
                                type="time"
                                aria-label="Hora Fin"
                                required
                            />
                            <BaseInput
                                :id="'session-classroom-' + index"
                                v-model="session.classroom"
                                placeholder="Aula (Opc.)"
                                aria-label="Aula"
                            />
                            <button
                                type="button"
                                @click="removeSession(index)"
                                class="p-2 text-content-muted hover:text-danger hover:bg-danger/10 rounded-lg transition-colors disabled:opacity-50 cursor-pointer"
                                :disabled="courseForm.sessions.length <= 1"
                                title="Eliminar sesión"
                            >
                                <Trash2 :size="16" />
                            </button>
                        </div>
                    </div>
                    <div v-if="courseForm.errors.sessions" class="text-danger text-xs">
                        {{ courseForm.errors.sessions }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border mt-6">
                    <BaseButton variant="ghost" type="button" @click="showCourseModal = false">
                        Cancelar
                    </BaseButton>
                    <BaseButton type="submit" :loading="courseForm.processing">
                        {{ isEditing ? 'Guardar Cambios' : 'Registrar Curso' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
