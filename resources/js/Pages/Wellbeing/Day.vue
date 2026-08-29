<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    date: { type: String, required: true },
    entries: { type: Array, default: () => [] },
    avgScore: { type: Number, default: 0 },
    moodScale: { type: Array, default: () => [] },
    entryTags: { type: Array, default: () => [] },
    physicalActivityTypes: { type: Array, default: () => [] },
    isHoliday: { type: Boolean, default: false },
    holidayName: { type: String, default: null },
    isExam: { type: Boolean, default: false },
});

// Íconos Lucide para energía (1=muy baja..5=muy alta) y estrés
// Se muestran como <AppIcon> en el template en lugar de emojis
const ENERGY_ICON = ['', 'bed', 'meh', 'smile', 'dumbbell', 'zap'];
const STRESS_ICON = ['', 'smile', 'meh', 'annoyed', 'frown', 'x-circle'];
const ENERGY_LABEL = ['', 'Muy baja', 'Baja', 'Normal', 'Alta', 'Muy alta'];
const STRESS_LABEL = ['', 'Muy bajo', 'Bajo', 'Normal', 'Alto', 'Muy alto'];

const showForm = ref(false);
const editingId = ref(null);
const formMood = ref(3);
const formEnergy = ref(null);
const formStress = ref(null);
const formSleepHours = ref(null);
const formSleepMinutes = ref(null);
const formActivity = ref(null);
const formActivityDuration = ref(null);
const formContent = ref('');
const formTags = ref([]);
const busy = ref(false);
const toast = ref(null);

function csrfHeader() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const dateObj = new Date(props.date + 'T12:00:00');
const displayDate = dateObj.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const avgMoodInfo = computed(() => props.moodScale.find(m => m.value === props.avgScore));

const availableActivityTypes = computed(() => props.physicalActivityTypes.length > 0
    ? props.physicalActivityTypes
    : ['Caminata', 'Running', 'Gimnasio', 'Yoga', 'Estiramientos', 'Otro'],
);

function openNewEntry() {
    editingId.value = null;
    formMood.value = 3;
    formEnergy.value = null;
    formStress.value = null;
    formSleepHours.value = null;
    formSleepMinutes.value = null;
    formActivity.value = null;
    formActivityDuration.value = null;
    formContent.value = '';
    formTags.value = [];
    showForm.value = true;
}

function openEdit(entry) {
    editingId.value = entry.id;
    formMood.value = entry.mood_score;
    formEnergy.value = entry.energy ?? null;
    formStress.value = entry.stress ?? null;
    if (entry.sleep_hours !== null && entry.sleep_hours !== undefined) {
        formSleepHours.value = Math.floor(entry.sleep_hours);
        formSleepMinutes.value = Math.round((entry.sleep_hours - Math.floor(entry.sleep_hours)) * 60);
    } else {
        formSleepHours.value = null;
        formSleepMinutes.value = null;
    }
    formActivity.value = entry.physical_activity?.type ?? null;
    formActivityDuration.value = entry.physical_activity?.duration ?? null;
    formContent.value = entry.content ?? '';
    formTags.value = entry.tags ?? [];
    showForm.value = true;
}

function cancelForm() {
    showForm.value = false;
    editingId.value = null;
}

function buildSleepHours() {
    if (formSleepHours.value === null || formSleepHours.value === '') return null;
    const h = Number(formSleepHours.value);
    const m = formSleepMinutes.value !== null && formSleepMinutes.value !== '' ? Number(formSleepMinutes.value) : 0;
    return h + (m / 60);
}

async function submitEntry() {
    busy.value = true;
    toast.value = null;
    try {
        const method = editingId.value ? 'PATCH' : 'POST';
        const url = editingId.value
            ? route('wellbeing.update', { id: editingId.value })
            : route('wellbeing.store');
        const body = {
            date: props.date,
            mood_score: formMood.value,
            energy: formEnergy.value,
            stress: formStress.value,
            sleep_hours: buildSleepHours(),
            physical_activity: formActivity.value && formActivityDuration.value
                ? { type: formActivity.value, duration: Number(formActivityDuration.value) }
                : null,
            content: formContent.value,
            tags: formTags.value,
        };
        if (editingId.value) delete body.date;

        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            toast.value = err.message || 'Error al guardar.';
            return;
        }
        showForm.value = false;
        editingId.value = null;
        router.reload({ only: ['entries', 'avgScore'] });
    } finally {
        busy.value = false;
    }
}

function toggleTag(tag) {
    if (formTags.value.includes(tag)) {
        formTags.value = formTags.value.filter(t => t !== tag);
    } else {
        formTags.value = [...formTags.value, tag];
    }
}

function goBack() {
    router.get(route('wellbeing.index', { month: dateObj.getMonth() + 1, year: dateObj.getFullYear() }));
}
</script>

<template>
    <Head :title="`${displayDate} — Bienestar — Epycus`" />

    <AppLayout>
        <div class="mx-auto max-w-3xl">
            <button type="button" class="mb-4 inline-block text-sm text-content-secondary hover:text-content-primary" @click="goBack">← Volver al calendario</button>

            <BaseCard class="mb-6 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="font-display text-2xl text-content-primary">{{ displayDate }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span v-if="isHoliday" class="rounded bg-danger/10 px-2 py-0.5 text-danger">🏖 {{ holidayName }}</span>
                            <span v-if="isExam" class="rounded bg-warning/20 px-2 py-0.5 text-warning">📝 Semana de exámenes</span>
                            <span v-if="avgMoodInfo" class="text-content-muted">Ánimo promedio: {{ avgMoodInfo.emoji }} {{ avgMoodInfo.label }} ({{ avgScore }}/5)</span>
                        </div>
                    </div>
                    <BaseButton v-if="!showForm" variant="primary" @click="openNewEntry">+ Nueva entrada</BaseButton>
                </div>
            </BaseCard>

            <div v-if="toast" class="mb-6 rounded-lg bg-accent px-4 py-3 text-sm font-semibold text-on-accent" role="status">
                {{ toast }}
            </div>

            <BaseCard v-if="showForm" class="mb-6 p-6">
                <h2 class="mb-4 font-display text-lg text-content-primary">{{ editingId ? 'Editar entrada' : 'Nueva entrada' }}</h2>
                <div class="space-y-5">
                    <!-- Ánimo -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-content-primary">¿Cómo te sentís?</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="m in moodScale" :key="m.value"
                                type="button"
                                class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-sm transition min-w-[60px]"
                                :class="formMood === m.value ? 'bg-primary-strong text-on-accent shadow-sm' : 'bg-surface text-content-secondary hover:bg-surface-raised'"
                                @click="formMood = m.value"
                            >
                                <span class="text-xl">{{ m.emoji }}</span>
                                <span class="text-[10px]">{{ m.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Energía -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-content-primary">Energía <span class="text-content-muted font-normal">(opcional)</span></label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="e in 5" :key="e"
                                type="button"
                                class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-sm transition min-w-[52px]"
                                :class="formEnergy === e ? 'bg-accent text-on-accent shadow-sm' : 'bg-surface text-content-secondary hover:bg-surface-raised'"
                                @click="formEnergy = formEnergy === e ? null : e"
                            >
                                <AppIcon :name="ENERGY_ICON[e]" :size="20" />
                                <span class="text-[10px]">{{ ENERGY_LABEL[e] }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Estrés -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-content-primary">Estrés <span class="text-content-muted font-normal">(opcional)</span></label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="s in 5" :key="s"
                                type="button"
                                class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-sm transition min-w-[52px]"
                                :class="formStress === s ? 'bg-danger text-on-accent shadow-sm' : 'bg-surface text-content-secondary hover:bg-surface-raised'"
                                @click="formStress = formStress === s ? null : s"
                            >
                                <AppIcon :name="STRESS_ICON[s]" :size="20" />
                                <span class="text-[10px]">{{ STRESS_LABEL[s] }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Horas de sueño -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-content-primary">Horas de sueño <span class="text-content-muted font-normal">(opcional)</span></label>
                        <div class="flex items-center gap-2">
                            <select v-model.number="formSleepHours" class="w-24 rounded-lg border-border bg-surface px-3 py-2 text-sm outline-none">
                                <option :value="null">—</option>
                                <option v-for="h in 16" :key="h" :value="h">{{ h }}</option>
                            </select>
                            <span class="text-sm text-content-secondary">h</span>
                            <select v-model.number="formSleepMinutes" class="w-24 rounded-lg border-border bg-surface px-3 py-2 text-sm outline-none">
                                <option :value="null">—</option>
                                <option v-for="m in [0, 15, 30, 45]" :key="m" :value="m">{{ String(m).padStart(2, '0') }}</option>
                            </select>
                            <span class="text-sm text-content-secondary">min</span>
                        </div>
                    </div>

                    <!-- Actividad física -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-content-primary">Actividad física <span class="text-content-muted font-normal">(opcional)</span></label>
                        <div class="flex flex-wrap items-center gap-2">
                            <select v-model="formActivity" class="rounded-lg border-border bg-surface px-3 py-2 text-sm outline-none">
                                <option :value="null">Ninguna</option>
                                <option v-for="a in availableActivityTypes" :key="a" :value="a">{{ a }}</option>
                            </select>
                            <template v-if="formActivity">
                                <input
                                    v-model.number="formActivityDuration"
                                    type="number"
                                    min="1"
                                    max="600"
                                    class="w-20 rounded-lg border-border bg-surface px-3 py-2 text-sm outline-none"
                                    placeholder="min"
                                />
                                <span class="text-sm text-content-secondary">minutos</span>
                            </template>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div>
                        <label for="entry_content" class="mb-1 block text-sm font-medium text-content-primary">¿Querés escribir algo?</label>
                        <p class="mb-2 text-xs text-content-muted">Opcional. Solo lo ves vos — se guarda cifrado.</p>
                        <textarea
                            id="entry_content"
                            v-model="formContent"
                            class="w-full rounded-lg border-border bg-surface px-3 py-2 text-sm text-content-primary outline-none resize-none"
                            rows="4"
                            placeholder="Contame cómo estuvo tu día..."
                            maxlength="5000"
                        ></textarea>
                    </div>

                    <!-- Etiquetas -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-content-primary">Etiquetas <span class="text-content-muted font-normal">(opcional)</span></label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in entryTags" :key="tag"
                                type="button"
                                class="rounded-full px-3 py-1 text-xs font-medium transition"
                                :class="formTags.includes(tag) ? 'bg-primary-strong text-on-accent' : 'bg-surface-sunken text-content-secondary hover:bg-surface-raised'"
                                @click="toggleTag(tag)"
                            >{{ tag }}</button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <BaseButton variant="ghost" :disabled="busy" @click="cancelForm">Cancelar</BaseButton>
                        <BaseButton :disabled="busy" @click="submitEntry">{{ editingId ? 'Guardar cambios' : 'Guardar entrada' }}</BaseButton>
                    </div>
                </div>
            </BaseCard>

            <BaseCard v-if="entries.length === 0 && !showForm" class="flex flex-col items-center p-8 text-center">
                <AppIcon name="notebook-text" :size="40" class="mb-3 text-content-muted" />
                <h2 class="text-sm font-semibold text-content-primary">Sin entradas este día</h2>
                <p class="mt-1 text-sm text-content-secondary">No registraste tu ánimo hoy. Hacé clic en "Nueva entrada" para empezar.</p>
            </BaseCard>

            <div v-else class="space-y-4">
                <div v-for="entry in entries" :key="entry.id" class="rounded-lg border border-border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ moodScale.find(m => m.value === entry.mood_score)?.emoji }}</span>
                            <div>
                                <span class="font-medium text-content-primary">{{ moodScale.find(m => m.value === entry.mood_score)?.label }}</span>
                                <span class="ml-2 text-xs text-content-muted">{{ new Date(entry.created_at).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }) }}</span>
                            </div>
                        </div>
                        <button type="button" class="text-xs text-content-muted hover:text-content-primary" @click="openEdit(entry)"><AppIcon name="pencil" :size="12" /></button>
                    </div>
                    <div v-if="entry.energy || entry.stress || entry.sleep_hours || entry.physical_activity" class="mt-2 flex flex-wrap gap-2">
                        <span v-if="entry.energy" class="rounded-full bg-accent/10 px-2 py-0.5 text-xs text-accent">Energía: {{ entry.energy }}/5</span>
                        <span v-if="entry.stress" class="rounded-full bg-danger/10 px-2 py-0.5 text-xs text-danger">Estrés: {{ entry.stress }}/5</span>
                        <span v-if="entry.sleep_hours" class="rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-content-muted inline-flex items-center gap-1"><AppIcon name="bed" :size="10" /> {{ entry.sleep_hours }} h</span>
                        <span v-if="entry.physical_activity" class="rounded-full bg-success/10 px-2 py-0.5 text-xs text-success inline-flex items-center gap-1"><AppIcon name="activity" :size="10" /> {{ entry.physical_activity.type }} {{ entry.physical_activity.duration }} min</span>
                    </div>
                    <p v-if="entry.content" class="mt-2 whitespace-pre-wrap text-sm text-content-secondary">{{ entry.content }}</p>
                    <div v-if="entry.tags && entry.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                        <span v-for="tag in entry.tags" :key="tag" class="rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-content-muted">{{ tag }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
