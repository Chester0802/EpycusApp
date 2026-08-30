<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
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

const evaluations = computed(() => props.course.grade_evaluations || []);
const isAdding = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    weight: 0,
    obtained_score: null,
    max_score: 20.00,
    eval_date: '',
});

function resetForm() {
    form.reset();
    isAdding.value = false;
    editingId.value = null;
    form.clearErrors();
}

function startAdd() {
    resetForm();
    isAdding.value = true;
}

function startEdit(evalItem) {
    resetForm();
    editingId.value = evalItem.id;
    form.name = evalItem.name;
    form.weight = evalItem.weight;
    form.obtained_score = evalItem.obtained_score;
    form.max_score = evalItem.max_score;
    form.eval_date = evalItem.eval_date;
}

function saveEvaluation() {
    if (editingId.value) {
        form.put(route('course.grades.update', { course: props.course.id, grade: editingId.value }), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('course.grades.store', props.course.id), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
}

function deleteEvaluation(id) {
    if (confirm('¿Estás seguro de eliminar esta evaluación?')) {
        form.delete(route('course.grades.destroy', { course: props.course.id, grade: id }), {
            preserveScroll: true,
        });
    }
}

// Lógica de cálculo reactivo
const totalWeight = computed(() => {
    return evaluations.value.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
});

const accumulatedScore = computed(() => {
    return evaluations.value.reduce((sum, item) => {
        if (item.obtained_score !== null) {
            // Normalizar la nota a escala de 20 para el promedio interno (o usar el max_score de cada una)
            // Asumimos promedio ponderado simple: (Nota * Peso) / 100
            const score = parseFloat(item.obtained_score) || 0;
            const weight = parseFloat(item.weight) || 0;
            return sum + (score * (weight / 100));
        }
        return sum;
    }, 0);
});

const gradedWeight = computed(() => {
    return evaluations.value.reduce((sum, item) => {
        if (item.obtained_score !== null) {
            return sum + (parseFloat(item.weight) || 0);
        }
        return sum;
    }, 0);
});

const currentAverage = computed(() => {
    if (gradedWeight.value === 0) return 0;
    // Promedio de lo que ya se evaluó
    return (accumulatedScore.value / (gradedWeight.value / 100)).toFixed(2);
});

const targetGrade = computed(() => parseFloat(props.course.target_grade) || 0);
const remainingWeight = computed(() => 100 - gradedWeight.value);

const requiredAverageForTarget = computed(() => {
    if (targetGrade.value === 0) return null;
    if (remainingWeight.value === 0) return null;
    
    // Lo que falta alcanzar en puntos netos
    const pointsNeeded = targetGrade.value - accumulatedScore.value;
    
    // Si ya alcanzó la nota solo con lo acumulado
    if (pointsNeeded <= 0) return 0;
    
    // Promedio requerido en el porcentaje restante
    const req = (pointsNeeded / (remainingWeight.value / 100)).toFixed(2);
    return req;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Dashboard / Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Nota Acumulada -->
            <BaseCard class="p-5 flex flex-col justify-center border-l-4 border-l-primary-strong">
                <span class="text-xs font-bold text-content-muted uppercase tracking-wide">Nota Actual (de lo evaluado)</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-black text-content-primary">{{ currentAverage }}</span>
                    <span class="text-sm font-semibold text-content-secondary">/ 20</span>
                </div>
                <div class="mt-1 text-xs font-medium" :class="currentAverage >= 14 ? 'text-success' : (currentAverage >= 10.5 ? 'text-warning' : 'text-danger')">
                    Evaluado: {{ gradedWeight }}% del curso
                </div>
            </BaseCard>

            <!-- Meta del Curso -->
            <BaseCard class="p-5 flex flex-col justify-center border-l-4 border-l-indigo-500">
                <span class="text-xs font-bold text-content-muted uppercase tracking-wide">Meta del Curso</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-black text-indigo-500">{{ targetGrade || '--' }}</span>
                </div>
                <div class="mt-1 text-xs font-medium text-content-secondary">
                    Configurada en Detalles del Curso
                </div>
            </BaseCard>

            <!-- Predictor -->
            <BaseCard class="p-5 flex flex-col justify-center border-l-4 border-l-warning">
                <span class="text-xs font-bold text-content-muted uppercase tracking-wide">Necesitas Sacar (Promedio)</span>
                <div v-if="targetGrade > 0 && remainingWeight > 0" class="mt-2">
                    <div v-if="requiredAverageForTarget > 20" class="text-danger font-bold text-sm">
                        ¡Matemáticamente imposible! Necesitas {{ requiredAverageForTarget }}.
                    </div>
                    <div v-else-if="requiredAverageForTarget <= 0" class="text-success font-bold text-sm flex items-center gap-1">
                        <AppIcon name="check-circle" :size="16" /> ¡Meta alcanzada!
                    </div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-warning">{{ requiredAverageForTarget }}</span>
                        <span class="text-xs font-semibold text-content-secondary">en el {{ remainingWeight }}% restante</span>
                    </div>
                </div>
                <div v-else-if="remainingWeight === 0" class="mt-2 text-sm text-content-secondary">
                    100% del curso evaluado.
                </div>
                <div v-else class="mt-2 text-sm text-content-secondary">
                    Configura tu Meta del Curso para ver la predicción.
                </div>
            </BaseCard>
        </div>

        <!-- Lista de Evaluaciones -->
        <BaseCard class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-content-primary flex items-center gap-2">
                    <AppIcon name="list" :size="20" class="text-primary-strong" />
                    Evaluaciones del Sílabo
                </h3>
                <BaseButton v-if="!isAdding && !editingId" variant="primary" size="sm" @click="startAdd">
                    + Añadir Evaluación
                </BaseButton>
            </div>

            <!-- Total Weight Warning -->
            <div v-if="totalWeight !== 100 && evaluations.length > 0 && !isAdding && !editingId" class="mb-4 p-3 rounded-lg bg-warning/10 text-warning text-sm font-semibold flex items-center gap-2 border border-warning/20">
                <AppIcon name="alert-triangle" :size="16" />
                La suma de los pesos es {{ totalWeight }}%. Debería ser 100%.
            </div>

            <!-- Formulario (Crear/Editar) -->
            <div v-if="isAdding || editingId" class="mb-6 p-4 rounded-xl bg-surface-raised border border-border-interactive">
                <h4 class="font-bold text-content-primary mb-4">{{ editingId ? 'Editar Evaluación' : 'Nueva Evaluación' }}</h4>
                <form @submit.prevent="saveEvaluation" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <BaseInput id="name" v-model="form.name" label="Nombre (ej. Parcial)" required />
                    </div>
                    <div>
                        <BaseInput id="weight" type="number" step="0.01" v-model="form.weight" label="Peso (%)" required />
                    </div>
                    <div>
                        <BaseInput id="obtained_score" type="number" step="0.01" v-model="form.obtained_score" label="Nota Obtenida (Opcional)" />
                    </div>
                    <!-- Max score oculto por simplicidad, o se puede mostrar si usan otra base -->
                    <div class="flex gap-2">
                        <BaseButton type="submit" variant="primary" class="flex-1" :disabled="form.processing">Guardar</BaseButton>
                        <BaseButton type="button" variant="ghost" class="px-3" @click="resetForm">
                            <AppIcon name="x" :size="16" />
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- Tabla / Lista -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border-interactive text-content-muted">
                            <th class="pb-2 font-semibold">Evaluación</th>
                            <th class="pb-2 font-semibold">Fecha</th>
                            <th class="pb-2 font-semibold text-right">Peso</th>
                            <th class="pb-2 font-semibold text-right">Nota (sobre 20)</th>
                            <th class="pb-2 font-semibold text-right">Aporte Neto</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-interactive/50">
                        <tr v-if="evaluations.length === 0">
                            <td colspan="6" class="py-8 text-center text-content-muted">
                                No hay evaluaciones registradas. Comienza añadiendo los rubros de tu sílabo.
                            </td>
                        </tr>
                        <tr v-for="item in evaluations" :key="item.id" class="group">
                            <td class="py-3 font-semibold text-content-primary">{{ item.name }}</td>
                            <td class="py-3 text-content-secondary">{{ item.eval_date || '--' }}</td>
                            <td class="py-3 text-right font-medium text-content-secondary">{{ item.weight }}%</td>
                            <td class="py-3 text-right">
                                <span v-if="item.obtained_score !== null" class="font-bold text-content-primary">
                                    {{ item.obtained_score }}
                                </span>
                                <span v-else class="text-content-muted italic">Pendiente</span>
                            </td>
                            <td class="py-3 text-right font-medium text-content-primary">
                                <span v-if="item.obtained_score !== null">
                                    +{{ (item.obtained_score * (item.weight / 100)).toFixed(2) }}
                                </span>
                                <span v-else>--</span>
                            </td>
                            <td class="py-3 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" @click="startEdit(item)" class="p-1.5 text-content-secondary hover:text-primary-strong transition">
                                    <AppIcon name="edit-2" :size="16" />
                                </button>
                                <button type="button" @click="deleteEvaluation(item.id)" class="p-1.5 text-content-secondary hover:text-danger transition ml-1">
                                    <AppIcon name="trash-2" :size="16" />
                                </button>
                            </td>
                        </tr>
                        <!-- Fila de Totales -->
                        <tr v-if="evaluations.length > 0" class="border-t-2 border-border-interactive font-bold">
                            <td colspan="2" class="py-3 text-content-primary">TOTALES</td>
                            <td class="py-3 text-right" :class="totalWeight === 100 ? 'text-success' : 'text-warning'">{{ totalWeight }}%</td>
                            <td class="py-3 text-right text-content-secondary">Promedio: {{ currentAverage }}</td>
                            <td class="py-3 text-right text-primary-strong">{{ accumulatedScore.toFixed(2) }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>
    </div>
</template>
