<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import CourseProject from './Partials/CourseProject.vue';
import GradeSimulator from './Partials/GradeSimulator.vue';
import SyllabusViewer from './Partials/SyllabusViewer.vue';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('proyecto');

const tabs = [
    { id: 'apuntes', label: 'Apuntes', icon: 'file-text' },
    { id: 'notas', label: 'Notas', icon: 'bar-chart' },
    { id: 'silabo', label: 'Sílabo', icon: 'file' },
    { id: 'proyecto', label: 'Proyecto ABP', icon: 'trello' },
    { id: 'zona-aprendizaje', label: 'Zona de Aprendizaje', icon: 'brain' },
];
</script>

<template>
    <Head :title="course.name" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('courses.index')" class="text-content-secondary hover:text-content-primary transition">
                    <AppIcon name="arrow-left" :size="20" />
                </Link>
                <div class="h-6 w-px bg-border-interactive"></div>
                <h2 class="font-bold text-xl text-content-primary leading-tight flex items-center gap-2">
                    <div 
                        class="w-6 h-6 rounded flex items-center justify-center flex-shrink-0 text-white font-bold text-xs"
                        :style="{ backgroundColor: course.color || '#3b82f6' }"
                    >
                        {{ course.name.substring(0, 1).toUpperCase() }}
                    </div>
                    {{ course.name }}
                </h2>
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

                <div v-show="activeTab !== 'proyecto' && activeTab !== 'notas' && activeTab !== 'silabo'">
                    <BaseCard class="p-12 text-center flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-full bg-surface-raised flex items-center justify-center text-content-muted mb-4">
                            <AppIcon :name="tabs.find(t => t.id === activeTab)?.icon" :size="32" />
                        </div>
                        <h3 class="text-xl font-bold text-content-primary">Próximamente en Fase {{ activeTab === 'zona-aprendizaje' ? '6' : '3' }}</h3>
                        <p class="text-content-secondary mt-2 max-w-md mx-auto">
                            El módulo de {{ tabs.find(t => t.id === activeTab)?.label }} se implementará en las siguientes fases del plan maestro de Epycus 2.0.
                        </p>
                    </BaseCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
