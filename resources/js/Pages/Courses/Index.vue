<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import AppIcon from '@/Components/AppIcon.vue';

defineProps({
    courses: {
        type: Array,
        default: () => [],
    },
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
                </header>

                <div v-if="courses.length === 0" class="text-center py-20">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-surface-raised mb-4">
                        <AppIcon name="book" :size="32" class="text-content-muted" />
                    </div>
                    <h3 class="text-lg font-bold text-content-primary">Sin Cursos</h3>
                    <p class="text-content-secondary mt-2">No has agregado ningún curso en el Calendario.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="course in courses"
                        :key="course.id"
                        :href="route('courses.show', course.id)"
                        class="block group"
                    >
                        <BaseCard class="h-full p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 relative overflow-hidden">
                            <!-- Fondo sutil de color del curso -->
                            <div 
                                class="absolute top-0 left-0 w-full h-1 opacity-50"
                                :style="{ backgroundColor: course.color || '#3b82f6' }"
                            ></div>
                            
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-content-primary group-hover:text-primary-strong transition-colors">
                                        {{ course.name }}
                                    </h3>
                                    <p class="text-sm text-content-secondary mt-1 font-medium">
                                        {{ course.professor || 'Profesor no asignado' }}
                                    </p>
                                </div>
                                <div 
                                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-surface text-white font-bold"
                                    :style="{ backgroundColor: course.color || '#3b82f6' }"
                                >
                                    {{ course.name.substring(0, 2).toUpperCase() }}
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
                                    <span class="block text-sm font-semibold text-content-primary">
                                        {{ course.credits ? course.credits : '--' }}
                                    </span>
                                </div>
                            </div>
                        </BaseCard>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
