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
    readings: { type: Array, default: () => [] },
    activeReadings: { type: Array, default: () => [] },
    wantToRead: { type: Array, default: () => [] },
    finishedReadings: { type: Array, default: () => [] },
    pausedReadings: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ total_readings: 0, active_count: 0, finished_this_year: 0, total_pages_read: 0 }),
    },
});

const activeTab = ref('all'); // 'all' | 'reading' | 'want_to_read' | 'finished' | 'paused'
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showProgressModal = ref(false);
const selectedReading = ref(null);
const xpToast = ref(null);

const typeOptions = [
    { value: 'book_nonfiction', label: '📘 Libro No Ficción / Ensayo' },
    { value: 'book_fiction', label: '📕 Novela / Ficción' },
    { value: 'academic_article', label: '📄 Paper / Artículo Académico' },
    { value: 'thesis', label: '🎓 Tesis / Trabajo de Grado' },
    { value: 'manual', label: '⚙️ Manual / Guía Técnica' },
    { value: 'other', label: '✨ Otro' },
];

const typeLabels = {
    book_nonfiction: 'No Ficción',
    book_fiction: 'Ficción',
    academic_article: 'Paper',
    thesis: 'Tesis',
    manual: 'Manual',
    other: 'Lectura',
};

const statusOptions = [
    { value: 'want_to_read', label: '📌 Por Leer (Pendiente)' },
    { value: 'reading', label: '📖 Leyendo Actualmente' },
    { value: 'finished', label: '🏆 Finalizado' },
    { value: 'paused', label: '⏸️ En Pausa' },
    { value: 'dropped', label: '🛑 Abandonado' },
];

const statusBadges = {
    reading: { label: 'Leyendo', class: 'bg-primary/15 text-primary-strong border border-primary/30' },
    want_to_read: { label: 'Por Leer', class: 'bg-surface-raised text-content-secondary border border-border-interactive' },
    finished: { label: 'Completado', class: 'bg-success/15 text-success border border-success/30' },
    paused: { label: 'En Pausa', class: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 font-bold border border-amber-500/30' },
    dropped: { label: 'Abandonado', class: 'bg-danger/15 text-danger-text border border-danger/30' },
};

const filteredReadings = computed(() => {
    if (activeTab.value === 'all') return props.readings;
    if (activeTab.value === 'reading') return props.activeReadings;
    if (activeTab.value === 'want_to_read') return props.wantToRead;
    if (activeTab.value === 'finished') return props.finishedReadings;
    if (activeTab.value === 'paused') return props.pausedReadings;
    return props.readings;
});

const createForm = useForm({
    title: '',
    author: '',
    year: '',
    type: 'book_nonfiction',
    total_pages: '',
    isbn: '',
    cover_url: '',
    status: 'want_to_read',
    current_page: 0,
    rating: null,
    tagsInput: '',
});

const editForm = useForm({
    title: '',
    author: '',
    year: '',
    type: 'book_nonfiction',
    total_pages: '',
    isbn: '',
    cover_url: '',
    status: 'want_to_read',
    current_page: 0,
    rating: null,
    tagsInput: '',
});

const progressForm = ref({
    current_page: 0,
});

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function submitCreate() {
    const tags = createForm.tagsInput
        ? createForm.tagsInput.split(',').map((t) => t.trim()).filter(Boolean)
        : [];

    createForm.transform((data) => ({
        ...data,
        tags,
    })).post(route('readings.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(reading) {
    selectedReading.value = reading;
    editForm.title = reading.title;
    editForm.author = reading.author || '';
    editForm.year = reading.year || '';
    editForm.type = reading.type || 'book_nonfiction';
    editForm.total_pages = reading.total_pages || '';
    editForm.isbn = reading.isbn || '';
    editForm.cover_url = reading.cover_url || '';
    editForm.status = reading.status || 'want_to_read';
    editForm.current_page = reading.current_page || 0;
    editForm.rating = reading.rating;
    editForm.tagsInput = (reading.tags || []).join(', ');
    showEditModal.value = true;
}

function submitEdit() {
    if (!selectedReading.value) return;
    const tags = editForm.tagsInput
        ? editForm.tagsInput.split(',').map((t) => t.trim()).filter(Boolean)
        : [];

    editForm.transform((data) => ({
        ...data,
        tags,
    })).put(route('readings.update', { id: selectedReading.value.id }), {
        onSuccess: () => {
            showEditModal.value = false;
            selectedReading.value = null;
        },
    });
}

function openProgressModal(reading) {
    selectedReading.value = reading;
    progressForm.value.current_page = reading.current_page;
    showProgressModal.value = true;
}

async function quickAdvancePages(reading, pages) {
    const newPage = Math.min(reading.total_pages || 99999, reading.current_page + pages);
    await updatePageProgress(reading, newPage);
}

async function submitProgress() {
    if (!selectedReading.value) return;
    await updatePageProgress(selectedReading.value, progressForm.value.current_page);
    showProgressModal.value = false;
}

async function updatePageProgress(reading, pageNum) {
    try {
        const response = await axios.post(route('readings.progress', { id: reading.id }), {
            current_page: pageNum,
        });

        reading.current_page = pageNum;
        if (reading.total_pages && reading.total_pages > 0) {
            reading.progress_percent = Math.min(100, Math.round((pageNum / reading.total_pages) * 100));
        }

        if (response.data.is_finished) {
            reading.status = 'finished';
            triggerConfetti();
            playSuccessChime();
        }

        const xp = response.data.xp_awarded;
        if (xp > 0) {
            triggerHapticVibration([50, 40, 60]);
            xpToast.value = response.data.message;
            setTimeout(() => {
                xpToast.value = null;
            }, 3500);
        }
    } catch (e) {
        alert('Error al actualizar páginas: ' + (e.response?.data?.message || e.message));
    }
}

function deleteReading(readingId) {
    if (confirm('¿Eliminar esta lectura de tu biblioteca?')) {
        router.delete(route('readings.destroy', { id: readingId }), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Biblioteca & Lecturas - Epycus" />

    <AppLayout>
        <!-- Toast de XP -->
        <div
            v-if="xpToast"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-2xl bg-surface-raised border border-success/40 px-4 py-3 text-sm font-bold text-success shadow-xl animate-bounce"
        >
            <span>🎉</span>
            <span>{{ xpToast }}</span>
        </div>

        <div class="space-y-6 pb-12">
            <!-- Header -->
            <BaseCard class="p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📚</span>
                            <h1 class="text-xl sm:text-2xl font-black text-content-primary tracking-tight">
                                Biblioteca & Lecturas
                            </h1>
                        </div>
                        <p class="mt-1 text-xs sm:text-sm text-content-secondary max-w-2xl">
                            Gestiona tus libros, artículos de investigación y tesis. Registra tu avance página a página y convierte la lectura en una fuente de experiencia activa.
                        </p>
                    </div>

                    <BaseButton variant="primary" @click="openCreateModal">
                        <AppIcon name="plus" :size="16" /> Nueva Lectura
                    </BaseButton>
                </div>
            </BaseCard>

            <!-- Métricas Clave -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-primary-strong">{{ stats.total_readings }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">En Biblioteca</p>
                </BaseCard>
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-primary">{{ stats.active_count }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Leyendo Ahora</p>
                </BaseCard>
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-success">{{ stats.finished_this_year }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Leídos en {{ new Date().getFullYear() }}</p>
                </BaseCard>
                <BaseCard class="p-3 sm:p-4 text-center">
                    <p class="text-2xl font-black text-content-primary">{{ stats.total_pages_read }}</p>
                    <p class="text-xs text-content-muted font-medium mt-0.5">Páginas Leídas</p>
                </BaseCard>
            </div>

            <!-- Selector de Pestañas / Filtros -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeTab === 'all' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeTab = 'all'"
                >
                    ⚡ Todos ({{ readings.length }})
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeTab === 'reading' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeTab = 'reading'"
                >
                    📖 Leyendo ({{ activeReadings.length }})
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeTab === 'want_to_read' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeTab = 'want_to_read'"
                >
                    📌 Por Leer ({{ wantToRead.length }})
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeTab === 'finished' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeTab = 'finished'"
                >
                    🏆 Leídos ({{ finishedReadings.length }})
                </button>
                <button
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border shrink-0"
                    :class="activeTab === 'paused' ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-xs' : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'"
                    @click="activeTab = 'paused'"
                >
                    ⏸️ Pausa / Dejados ({{ pausedReadings.length }})
                </button>
            </div>

            <!-- Grid de Lecturas -->
            <div v-if="filteredReadings.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <BaseCard
                    v-for="item in filteredReadings"
                    :key="item.id"
                    class="p-4 sm:p-5 flex flex-col justify-between transition hover:border-primary/40 group"
                >
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap mb-1">
                                    <span
                                        class="rounded px-1.5 py-0.2 text-[10px] font-bold"
                                        :class="statusBadges[item.status]?.class"
                                    >
                                        {{ statusBadges[item.status]?.label }}
                                    </span>
                                    <span class="rounded bg-surface-raised px-1.5 py-0.2 text-[10px] font-semibold text-content-muted border border-border">
                                        {{ typeLabels[item.type] || item.type }}
                                    </span>
                                    <span v-if="item.year" class="text-[10px] text-content-muted">
                                        ({{ item.year }})
                                    </span>
                                </div>

                                <h3 class="font-bold text-base text-content-primary group-hover:text-primary-strong transition truncate">
                                    {{ item.title }}
                                </h3>
                                <p v-if="item.author" class="text-xs text-content-secondary font-medium mt-0.5 truncate">
                                    ✍️ {{ item.author }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-content-muted hover:text-content-primary hover:bg-surface-raised transition cursor-pointer"
                                    title="Editar Lectura"
                                    @click="openEditModal(item)"
                                >
                                    <AppIcon name="pencil" :size="14" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg text-content-muted hover:text-danger hover:bg-danger/10 transition cursor-pointer"
                                    title="Eliminar"
                                    @click="deleteReading(item.id)"
                                >
                                    <AppIcon name="trash" :size="14" />
                                </button>
                            </div>
                        </div>

                        <!-- Barra de Progreso de Lectura -->
                        <div v-if="item.total_pages && item.total_pages > 0" class="mt-4 space-y-1.5">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-content-secondary">
                                    Pág. {{ item.current_page }} de {{ item.total_pages }}
                                </span>
                                <span class="text-primary-strong font-bold">
                                    {{ item.progress_percent }}%
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-surface-sunken overflow-hidden border border-border-interactive">
                                <div
                                    class="h-full bg-gradient-to-r from-primary to-primary-strong transition-all duration-300 rounded-full"
                                    :style="{ width: `${item.progress_percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Calificación con estrellas si está terminado -->
                        <div v-if="item.status === 'finished' && item.rating" class="mt-3 flex items-center gap-1 text-amber-400 text-xs">
                            <span v-for="star in 5" :key="star">
                                {{ star <= item.rating ? '★' : '☆' }}
                            </span>
                            <span class="text-content-muted text-[11px] ml-1">({{ item.rating }}/5)</span>
                        </div>

                        <!-- Tags -->
                        <div v-if="item.tags && item.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
                            <span
                                v-for="tag in item.tags"
                                :key="tag"
                                class="text-[10px] font-medium bg-surface-raised px-2 py-0.5 rounded-md text-content-secondary border border-border"
                            >
                                #{{ tag }}
                            </span>
                        </div>
                    </div>

                    <!-- Botones de Acción Rápida de Avance de Páginas -->
                    <div v-if="item.status === 'reading'" class="mt-4 pt-3 border-t border-border flex items-center justify-between gap-2">
                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                class="px-2 py-1 rounded-lg bg-surface-raised text-[11px] font-bold text-content-primary border border-border hover:border-primary transition cursor-pointer"
                                @click="quickAdvancePages(item, 5)"
                            >
                                +5 págs
                            </button>
                            <button
                                type="button"
                                class="px-2 py-1 rounded-lg bg-surface-raised text-[11px] font-bold text-content-primary border border-border hover:border-primary transition cursor-pointer"
                                @click="quickAdvancePages(item, 10)"
                            >
                                +10 págs
                            </button>
                        </div>

                        <BaseButton size="sm" variant="secondary" @click="openProgressModal(item)">
                            Registrar Pág. 📖
                        </BaseButton>
                    </div>
                </BaseCard>
            </div>

            <!-- Estado Vacío -->
            <BaseCard v-else class="p-12 text-center flex flex-col items-center">
                <span class="text-5xl mb-3">📚</span>
                <h3 class="text-base font-bold text-content-primary">No hay lecturas en esta sección</h3>
                <p class="text-xs text-content-secondary max-w-sm mt-1">
                    Comienza a armar tu lista de lecturas académicas o personales para dar seguimiento a tu aprendizaje.
                </p>
                <BaseButton class="mt-4" variant="primary" @click="openCreateModal">
                    + Añadir mi primera lectura
                </BaseButton>
            </BaseCard>
        </div>

        <!-- MODAL: NUEVA LECTURA -->
        <BaseModal :show="showCreateModal" title="Nueva Lectura o Libro" @close="showCreateModal = false">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <BaseInput
                    id="reading-title"
                    v-model="createForm.title"
                    label="Título de la obra / artículo"
                    placeholder="Ej. Hábitos Atómicos / Clean Architecture / Paper de Redes Neuronales"
                    required
                />

                <div class="grid grid-cols-2 gap-3">
                    <BaseInput
                        id="reading-author"
                        v-model="createForm.author"
                        label="Autor(es)"
                        placeholder="Ej. James Clear"
                    />
                    <BaseInput
                        id="reading-year"
                        v-model="createForm.year"
                        label="Año (Opcional)"
                        type="number"
                        placeholder="2024"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <BaseSelect
                        id="reading-type"
                        v-model="createForm.type"
                        label="Tipo de Lectura"
                        :options="typeOptions"
                        required
                    />
                    <BaseSelect
                        id="reading-status"
                        v-model="createForm.status"
                        label="Estado Inicial"
                        :options="statusOptions"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <BaseInput
                        id="reading-total-pages"
                        v-model="createForm.total_pages"
                        label="Total de Páginas"
                        type="number"
                        placeholder="Ej. 320"
                    />
                    <BaseInput
                        id="reading-current-page"
                        v-model="createForm.current_page"
                        label="Página Actual"
                        type="number"
                        placeholder="0"
                    />
                </div>

                <div>
                    <BaseInput
                        id="reading-tags"
                        v-model="createForm.tagsInput"
                        label="Etiquetas / Categorías (separadas por coma)"
                        placeholder="Ej. psicología, universidad, desarrollo personal, tesis"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showCreateModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Guardar Lectura</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: EDITAR LECTURA -->
        <BaseModal :show="showEditModal" :title="`Editar Lectura: ${selectedReading?.title || ''}`" @close="showEditModal = false">
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput
                    id="edit-reading-title"
                    v-model="editForm.title"
                    label="Título"
                    required
                />

                <div class="grid grid-cols-2 gap-3">
                    <BaseInput
                        id="edit-reading-author"
                        v-model="editForm.author"
                        label="Autor"
                    />
                    <BaseInput
                        id="edit-reading-year"
                        v-model="editForm.year"
                        label="Año"
                        type="number"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <BaseSelect
                        id="edit-reading-type"
                        v-model="editForm.type"
                        label="Tipo"
                        :options="typeOptions"
                        required
                    />
                    <BaseSelect
                        id="edit-reading-status"
                        v-model="editForm.status"
                        label="Estado"
                        :options="statusOptions"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <BaseInput
                        id="edit-reading-total-pages"
                        v-model="editForm.total_pages"
                        label="Total Páginas"
                        type="number"
                    />
                    <BaseInput
                        id="edit-reading-current-page"
                        v-model="editForm.current_page"
                        label="Página Actual"
                        type="number"
                    />
                </div>

                <div v-if="editForm.status === 'finished'">
                    <label class="block text-xs font-bold text-content-primary mb-1">Calificación (1 a 5 estrellas)</label>
                    <div class="flex gap-2">
                        <button
                            v-for="s in 5"
                            :key="s"
                            type="button"
                            class="text-xl transition cursor-pointer"
                            :class="(editForm.rating || 0) >= s ? 'text-amber-400' : 'text-content-muted'"
                            @click="editForm.rating = s"
                        >
                            ★
                        </button>
                    </div>
                </div>

                <div>
                    <BaseInput
                        id="edit-reading-tags"
                        v-model="editForm.tagsInput"
                        label="Etiquetas (separadas por coma)"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showEditModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Actualizar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: REGISTRAR PROGRESO / PÁGINAS -->
        <BaseModal :show="showProgressModal" :title="`Actualizar Progreso: ${selectedReading?.title || ''}`" @close="showProgressModal = false">
            <form class="space-y-4" @submit.prevent="submitProgress">
                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">
                        ¿En qué página te encuentras actualmente?
                    </label>
                    <BaseInput
                        v-model="progressForm.current_page"
                        type="number"
                        min="0"
                        :max="selectedReading?.total_pages || 99999"
                        required
                    />
                    <p class="text-[11px] text-content-muted mt-1">
                        Total de la obra: {{ selectedReading?.total_pages || 'Desconocido' }} páginas.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showProgressModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Guardar Avance 📖</BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
