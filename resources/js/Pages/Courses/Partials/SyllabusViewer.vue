<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Maximize2, Minimize2, ExternalLink, Download, Trash2, FileText, UploadCloud, X } from '@lucide/vue';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
});

const fileInput = ref(null);
const isUploading = ref(false);
const isFullscreen = ref(false);

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
    <div class="space-y-4 h-full flex flex-col min-h-[75vh]">
        <!-- Visor del PDF Normal / Expandido -->
        <div
            v-if="syllabusUrl"
            :class="[
                isFullscreen
                    ? 'fixed inset-0 z-50 bg-surface flex flex-col p-4'
                    : 'flex-1 flex flex-col overflow-hidden bg-surface rounded-2xl border border-border shadow-sm'
            ]"
        >
            <div class="flex items-center justify-between p-3 sm:p-4 border-b border-border bg-surface-raised flex-wrap gap-2">
                <h3 class="font-bold text-content-primary flex items-center gap-2 text-sm sm:text-base">
                    <FileText :size="18" class="text-primary-strong" />
                    <span>Sílabo: <strong>{{ course.name }}</strong></span>
                </h3>
                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                    <button
                        type="button"
                        class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-surface hover:bg-surface-sunken text-content-primary transition border border-border flex items-center gap-1.5 cursor-pointer"
                        :title="isFullscreen ? 'Salir de pantalla completa' : 'Ver a pantalla completa'"
                        @click="isFullscreen = !isFullscreen"
                    >
                        <Minimize2 v-if="isFullscreen" :size="14" />
                        <Maximize2 v-else :size="14" />
                        <span>{{ isFullscreen ? 'Salir' : 'Pantalla Completa' }}</span>
                    </button>

                    <a
                        :href="syllabusUrl"
                        target="_blank"
                        class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-surface hover:bg-surface-sunken text-content-primary transition border border-border flex items-center gap-1.5"
                        title="Abrir en visor nativo de nueva pestaña para zoom libre"
                    >
                        <ExternalLink :size="14" />
                        <span class="hidden sm:inline">Nueva pestaña</span>
                    </a>

                    <a
                        :href="syllabusUrl"
                        download
                        class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-surface hover:bg-surface-sunken text-content-primary transition border border-border flex items-center gap-1.5"
                    >
                        <Download :size="14" />
                        <span>Descargar</span>
                    </a>

                    <BaseButton variant="danger" size="sm" @click="deleteSyllabus">
                        <Trash2 :size="14" class="mr-1" />
                        <span>Eliminar</span>
                    </BaseButton>
                </div>
            </div>

            <!-- Visor iframe Maximizado -->
            <div class="flex-1 bg-surface-sunken relative min-h-[500px]">
                <iframe
                    :src="syllabusUrl"
                    class="w-full h-full min-h-[75vh] sm:min-h-[82vh] border-none rounded-b-2xl"
                    title="Visor de Sílabo"
                ></iframe>
            </div>
        </div>

        <!-- Estado Vacío / Subida -->
        <BaseCard v-else class="flex-1 flex flex-col items-center justify-center p-8 sm:p-14 text-center border-dashed border-2 border-border-interactive bg-surface-sunken">
            <div class="w-20 h-20 rounded-2xl bg-surface-raised flex items-center justify-center text-primary-strong mb-5 shadow-sm border border-border">
                <UploadCloud :size="36" />
            </div>
            
            <h3 class="text-xl font-black text-content-primary mb-2">Sube el Sílabo de tu Curso</h3>
            <p class="text-content-secondary max-w-md mx-auto mb-6 text-xs sm:text-sm">
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
