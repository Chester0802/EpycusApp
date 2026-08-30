<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
});

const fileInput = ref(null);
const isUploading = ref(false);

const form = useForm({
    syllabus: null,
});

const syllabusUrl = computed(() => {
    if (props.course.syllabus_path) {
        return `/storage/${props.course.syllabus_path}`;
    }
    return null;
});

function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
        if (file.type !== 'application/pdf') {
            alert('Por favor, selecciona un archivo PDF.');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('El archivo es demasiado grande (Máximo 10MB).');
            return;
        }
        form.syllabus = file;
        uploadSyllabus();
    }
}

function triggerFileInput() {
    fileInput.value?.click();
}

function uploadSyllabus() {
    isUploading.value = true;
    form.post(route('courses.syllabus.upload', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            isUploading.value = false;
            form.reset();
        },
        onError: () => {
            isUploading.value = false;
        }
    });
}

function deleteSyllabus() {
    if (confirm('¿Estás seguro de eliminar el sílabo actual?')) {
        form.delete(route('courses.syllabus.delete', props.course.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="space-y-6 h-full flex flex-col min-h-[60vh]">
        <!-- Visor del PDF -->
        <BaseCard v-if="syllabusUrl" class="flex-1 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-border-interactive">
                <h3 class="font-bold text-content-primary flex items-center gap-2">
                    <AppIcon name="file-text" :size="20" class="text-primary-strong" />
                    Sílabo Académico
                </h3>
                <div class="flex items-center gap-2">
                    <a :href="syllabusUrl" download target="_blank" class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-surface-raised text-content-primary hover:text-primary-strong transition border border-border-interactive flex items-center gap-2">
                        <AppIcon name="download" :size="16" />
                        Descargar
                    </a>
                    <BaseButton variant="danger" size="sm" @click="deleteSyllabus">
                        <AppIcon name="trash-2" :size="16" class="mr-1" />
                        Eliminar
                    </BaseButton>
                </div>
            </div>
            <!-- Visor iframe -->
            <div class="flex-1 bg-surface-sunken min-h-[500px]">
                <iframe :src="syllabusUrl" class="w-full h-full border-none" title="Visor de Sílabo"></iframe>
            </div>
        </BaseCard>

        <!-- Estado Vacío / Subida -->
        <BaseCard v-else class="flex-1 flex flex-col items-center justify-center p-12 text-center border-dashed border-2 border-border-interactive bg-surface-sunken">
            <div class="w-20 h-20 rounded-full bg-surface-raised flex items-center justify-center text-primary-strong mb-6 shadow-sm">
                <AppIcon name="upload-cloud" :size="36" />
            </div>
            
            <h3 class="text-xl font-black text-content-primary mb-2">Sube el Sílabo de tu Curso</h3>
            <p class="text-content-secondary max-w-md mx-auto mb-8">
                Ten a la mano toda la información del curso: reglas, temas por semana, bibliografía y sistema de calificación en formato PDF (Máx. 10MB).
            </p>

            <input type="file" ref="fileInput" class="hidden" accept="application/pdf" @change="handleFileChange" />
            
            <BaseButton variant="primary" size="lg" @click="triggerFileInput" :disabled="isUploading">
                <template v-if="isUploading">
                    <span class="animate-spin mr-2">⏳</span> Subiendo...
                </template>
                <template v-else>
                    Seleccionar Archivo PDF
                </template>
            </BaseButton>
        </BaseCard>
    </div>
</template>
