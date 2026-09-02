<script setup>
import { ref, computed, watch } from 'vue';
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

const evaluations = computed(() => props.course.grade_evaluations || []);
const isAdding = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    weight: '',
    obtained_score: null,
    max_score: 20.00,
    eval_date: '',
});

// Modo Simulador de Notas
const isSimulationActive = ref(true);
const simulatedScores = ref({});

// Inicializar / mantener notas simuladas
watch(evaluations, (newEvals) => {
    newEvals.forEach(item => {
        if (item.obtained_score === null && simulatedScores.value[item.id] === undefined) {
            simulatedScores.value[item.id] = '';
        }
    });
}, { immediate: true });

function resetForm() {
    form.reset();
    form.name = '';
    form.weight = '';
    form.obtained_score = null;
    form.eval_date = '';
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
    form.obtained_score = evalItem.obtained_score !== null ? evalItem.obtained_score : '';
    form.max_score = evalItem.max_score || 20.00;
    form.eval_date = evalItem.eval_date || '';
}

function startSetGrade(evalItem) {
    startEdit(evalItem);
    // Si tenía una nota simulada, sugerirla en el formulario
    if (evalItem.obtained_score === null && simulatedScores.value[evalItem.id]) {
        form.obtained_score = simulatedScores.value[evalItem.id];
    }
}

function saveEvaluation() {
    // Normalizar si la nota viene vacía
    if (form.obtained_score === '' || form.obtained_score === null || form.obtained_score === undefined) {
        form.obtained_score = null;
    } else {
        form.obtained_score = parseFloat(form.obtained_score);
    }

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
    if (confirm('¿Estás seguro de eliminar este rubro de evaluación?')) {
        router.delete(route('course.grades.destroy', { course: props.course.id, grade: id }), {
            preserveScroll: true,
        });
    }
}

function saveSimulatedAsReal(item) {
    const simScore = simulatedScores.value[item.id];
    if (simScore === '' || simScore === undefined || isNaN(parseFloat(simScore))) {
        alert('Por favor escribe una nota válida antes de guardarla.');
        return;
    }
    
    router.put(route('course.grades.update', { course: props.course.id, grade: item.id }), {
        name: item.name,
        weight: item.weight,
        obtained_score: parseFloat(simScore),
        max_score: item.max_score || 20.00,
        eval_date: item.eval_date || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            delete simulatedScores.value[item.id];
        }
    });
}

function clearAllSimulations() {
    simulatedScores.value = {};
    evaluations.value.forEach(item => {
        if (item.obtained_score === null) {
            simulatedScores.value[item.id] = '';
        }
    });
}

// Lógica de cálculo reactivo
const totalWeight = computed(() => {
    return evaluations.value.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
});

// Aporte de lo evaluado con notas reales
const accumulatedScore = computed(() => {
    return evaluations.value.reduce((sum, item) => {
        if (item.obtained_score !== null) {
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
    return (accumulatedScore.value / (gradedWeight.value / 100)).toFixed(2);
});

// Aporte total proyectado en simulación (reales + simuladas)
const projectedScore = computed(() => {
    return evaluations.value.reduce((sum, item) => {
        if (item.obtained_score !== null) {
            const score = parseFloat(item.obtained_score) || 0;
            const weight = parseFloat(item.weight) || 0;
            return sum + (score * (weight / 100));
        } else {
            const simVal = simulatedScores.value[item.id];
            if (simVal !== '' && simVal !== undefined && !isNaN(parseFloat(simVal))) {
                const score = parseFloat(simVal);
                const weight = parseFloat(item.weight) || 0;
                return sum + (score * (weight / 100));
            }
        }
        return sum;
    }, 0);
});

const totalSimulatedWeight = computed(() => {
    return evaluations.value.reduce((sum, item) => {
        if (item.obtained_score !== null) {
            return sum + (parseFloat(item.weight) || 0);
        } else {
            const simVal = simulatedScores.value[item.id];
            if (simVal !== '' && simVal !== undefined && !isNaN(parseFloat(simVal))) {
                return sum + (parseFloat(item.weight) || 0);
            }
        }
        return sum;
    }, 0);
});

const targetGrade = computed(() => parseFloat(props.course.target_grade) || 0);
const minPassGrade = computed(() => parseFloat(props.course.min_pass_grade) || 10.5);
const remainingWeight = computed(() => Math.max(0, 100 - gradedWeight.value));

const requiredAverageForTarget = computed(() => {
    if (targetGrade.value === 0) return null;
    if (remainingWeight.value === 0) return null;
    
    const pointsNeeded = targetGrade.value - accumulatedScore.value;
    if (pointsNeeded <= 0) return 0;
    
    return (pointsNeeded / (remainingWeight.value / 100)).toFixed(2);
});

const requiredAverageToPass = computed(() => {
    if (remainingWeight.value === 0) return null;
    const pointsNeeded = minPassGrade.value - accumulatedScore.value;
    if (pointsNeeded <= 0) return 0;
    return (pointsNeeded / (remainingWeight.value / 100)).toFixed(2);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Dashboard de Calificaciones & Simulación -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Nota Real Actual -->
            <BaseCard class="p-5 flex flex-col justify-center border-l-4 border-l-primary-strong shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-content-muted uppercase tracking-wide">Nota Actual (Evaluada)</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-primary-strong/10 text-primary-strong">
                        {{ gradedWeight }}% Calificado
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-black text-content-primary">{{ currentAverage }}</span>
                    <span class="text-sm font-semibold text-content-secondary">/ 20</span>
                </div>
                <div class="mt-1.5 text-xs font-medium flex items-center gap-1.5" :class="currentAverage >= minPassGrade ? 'text-success font-bold' : 'text-danger-text font-bold'">
                    <AppIcon :name="currentAverage >= minPassGrade ? 'check-circle' : 'alert-circle'" :size="14" />
                    <span>Aporte neto: <strong>{{ accumulatedScore.toFixed(2) }} pts</strong></span>
                </div>
            </BaseCard>

            <!-- Nota Proyectada / Simulador -->
            <BaseCard class="p-5 flex flex-col justify-center border-l-4 border-l-indigo-500 shadow-xs bg-indigo-500/5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wide flex items-center gap-1">
                        <AppIcon name="cpu" :size="14" />
                        Simulador de Nota Final
                    </span>
                    <span v-if="totalSimulatedWeight > gradedWeight" class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-500/20 text-indigo-600 dark:text-indigo-300">
                        {{ totalSimulatedWeight }}% proyectado
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-black text-indigo-600 dark:text-indigo-400">{{ projectedScore.toFixed(2) }}</span>
                    <span class="text-sm font-semibold text-content-secondary">/ 20</span>
                </div>
                <div class="mt-1.5 text-xs font-semibold text-content-secondary">
                    <span v-if="projectedScore >= (targetGrade || minPassGrade)" class="text-success flex items-center gap-1 font-bold">
                        <AppIcon name="check" :size="14" /> ¡Alcanzas la meta proyectada!
                    </span>
                    <span v-else-if="projectedScore >= minPassGrade" class="text-amber-600 dark:text-amber-400 font-bold">
                        Aprobando según notas simuladas
                    </span>
                    <span v-else class="text-danger-text font-bold">
                        En riesgo según notas simuladas
                    </span>
                </div>
            </BaseCard>

            <!-- Predictor de lo que Necesitas -->
            <BaseCard class="p-5 flex flex-col justify-center border-l-4 border-l-amber-500 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-content-muted uppercase tracking-wide">
                        {{ targetGrade > 0 ? 'Para tu Meta (' + targetGrade + ')' : 'Para Aprobar (' + minPassGrade + ')' }}
                    </span>
                    <span class="text-[10px] text-content-muted">Falta {{ remainingWeight }}%</span>
                </div>
                <div v-if="remainingWeight > 0" class="mt-2">
                    <div v-if="(targetGrade > 0 ? requiredAverageForTarget : requiredAverageToPass) > 20" class="text-danger-text font-bold text-sm">
                        ¡Requiere más de 20 pts! Ajusta tu meta.
                    </div>
                    <div v-else-if="(targetGrade > 0 ? requiredAverageForTarget : requiredAverageToPass) <= 0" class="text-success font-bold text-sm flex items-center gap-1">
                        <AppIcon name="check-circle" :size="16" /> ¡Ya superaste el puntaje requerido!
                    </div>
                    <div v-else class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-content-primary">
                            {{ targetGrade > 0 ? requiredAverageForTarget : requiredAverageToPass }}
                        </span>
                        <span class="text-xs font-semibold text-content-secondary">promedio en lo pendiente</span>
                    </div>
                </div>
                <div v-else class="mt-2 text-sm text-content-secondary font-medium">
                    100% de evaluaciones rendidas.
                </div>
            </BaseCard>
        </div>

        <!-- Tabla y Simulador de Evaluaciones -->
        <BaseCard class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-content-primary flex items-center gap-2">
                        <AppIcon name="list" :size="20" class="text-primary-strong" />
                        Evaluaciones y Simulador de Notas
                    </h3>
                    <p class="text-xs text-content-secondary mt-0.5">
                        Registra tus rubros con título y porcentaje. Si no tienes nota aún, déjala pendiente y pruébala en el simulador.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="Object.values(simulatedScores).some(v => v !== '')"
                        type="button"
                        @click="clearAllSimulations"
                        class="text-xs text-content-muted hover:text-content-primary font-semibold px-2.5 py-1.5 rounded-lg border border-border-interactive transition cursor-pointer"
                    >
                        Limpiar Simuladas
                    </button>
                    <BaseButton v-if="!isAdding && !editingId" variant="primary" size="sm" @click="startAdd">
                        + Añadir Evaluación
                    </BaseButton>
                </div>
            </div>

            <!-- Advertencia si la suma de pesos no da 100% -->
            <div v-if="totalWeight !== 100 && evaluations.length > 0 && !isAdding && !editingId" class="mb-4 p-3 rounded-xl bg-amber-500/10 text-content-primary text-xs font-semibold flex items-center gap-2 border border-amber-500/30">
                <AppIcon name="alert-triangle" :size="16" class="text-amber-500 shrink-0" />
                <span>La suma total de los porcentajes es <strong>{{ totalWeight }}%</strong>. Para un cálculo exacto, configura los rubros para que sumen 100%.</span>
            </div>

            <!-- Formulario (Crear / Editar Evaluación) -->
            <div v-if="isAdding || editingId" class="mb-6 p-4 md:p-5 rounded-2xl bg-surface-raised border border-border-interactive shadow-xs">
                <div class="flex items-center justify-between mb-3 border-b border-border-interactive pb-2">
                    <h4 class="font-bold text-sm text-content-primary flex items-center gap-2">
                        <AppIcon :name="editingId ? 'edit-2' : 'plus-circle'" :size="16" class="text-primary-strong" />
                        {{ editingId ? 'Editar Evaluación' : 'Nueva Evaluación del Sílabo' }}
                    </h4>
                    <button type="button" @click="resetForm" class="text-content-muted hover:text-content-primary transition p-1">
                        <AppIcon name="x" :size="16" />
                    </button>
                </div>

                <form @submit.prevent="saveEvaluation" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <BaseInput
                                id="eval-name"
                                v-model="form.name"
                                label="Título de la Nota / Evaluación *"
                                placeholder="Ej. Examen Parcial, Práctica Calificada 1, Avance de Proyecto..."
                                :error="form.errors.name"
                                required
                            />
                        </div>
                        <div>
                            <BaseInput
                                id="eval-weight"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                v-model="form.weight"
                                label="Valor Porcentual (%) *"
                                placeholder="Ej. 20 (sin símbolo %)"
                                :error="form.errors.weight"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <BaseInput
                                id="eval-obtained_score"
                                type="number"
                                step="0.01"
                                min="0"
                                max="20"
                                v-model="form.obtained_score"
                                label="Nota Obtenida (Opcional - dejar vacío si es pendiente)"
                                placeholder="Ej. 16.5 (O dejar vacío)"
                                :error="form.errors.obtained_score"
                            />
                            <span class="text-[11px] text-content-muted mt-1 block">
                                Puedes dejar este campo vacío y poner tu nota después o probarla en el simulador.
                            </span>
                        </div>
                        <div>
                            <BaseInput
                                id="eval-date"
                                type="date"
                                v-model="form.eval_date"
                                label="Fecha Programada (Opcional)"
                                :error="form.errors.eval_date"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-border-interactive">
                        <BaseButton type="button" variant="ghost" @click="resetForm">
                            Cancelar
                        </BaseButton>
                        <BaseButton type="submit" variant="primary" :disabled="form.processing">
                            {{ editingId ? 'Guardar Cambios' : 'Registrar Evaluación' }}
                        </BaseButton>
                    </div>
                </form>
            </div>

            <!-- Tabla de Evaluaciones y Simulador -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border-interactive text-content-muted text-xs uppercase tracking-wider">
                            <th class="pb-3 font-semibold">Evaluación</th>
                            <th class="pb-3 font-semibold text-center">Fecha</th>
                            <th class="pb-3 font-semibold text-right">Peso</th>
                            <th class="pb-3 font-semibold text-center">Nota Real</th>
                            <th class="pb-3 font-semibold text-center">Simulador</th>
                            <th class="pb-3 font-semibold text-right">Aporte Neto</th>
                            <th class="pb-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-interactive/50">
                        <tr v-if="evaluations.length === 0">
                            <td colspan="7" class="py-10 text-center text-content-muted text-xs">
                                No hay evaluaciones registradas. Comienza añadiendo los rubros de tu curso (ej. Parciales, Prácticas, Trabajos).
                            </td>
                        </tr>
                        <tr
                            v-for="item in evaluations"
                            :key="item.id"
                            class="transition-colors hover:bg-surface-raised/60"
                        >
                            <!-- Nombre -->
                            <td class="py-3.5 pr-3 font-semibold text-content-primary">
                                <div class="flex items-center gap-2">
                                    <span>{{ item.name }}</span>
                                </div>
                            </td>

                            <!-- Fecha -->
                            <td class="py-3.5 px-2 text-center text-xs text-content-secondary">
                                {{ item.eval_date || '--' }}
                            </td>

                            <!-- Peso % -->
                            <td class="py-3.5 px-2 text-right font-bold text-content-secondary">
                                {{ item.weight }}%
                            </td>

                            <!-- Nota Real -->
                            <td class="py-3.5 px-3 text-center">
                                <div v-if="item.obtained_score !== null" class="inline-flex items-center gap-1.5 font-black text-content-primary text-base">
                                    <span>{{ item.obtained_score }}</span>
                                    <span class="text-[10px] text-content-muted font-normal">/20</span>
                                </div>
                                <div v-else>
                                    <button
                                        type="button"
                                        @click="startSetGrade(item)"
                                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-primary-strong/10 text-primary-strong hover:bg-primary-strong hover:text-white transition cursor-pointer"
                                        title="Ingresar la nota que obtuviste"
                                    >
                                        + Poner Nota
                                    </button>
                                </div>
                            </td>

                            <!-- Simulador de Nota -->
                            <td class="py-3.5 px-2 text-center">
                                <div v-if="item.obtained_score === null" class="inline-flex items-center gap-1.5 justify-center">
                                    <input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="20"
                                        v-model="simulatedScores[item.id]"
                                        placeholder="Simular..."
                                        class="w-20 rounded-lg border border-border-interactive bg-surface px-2 py-1 text-xs text-center font-bold text-primary-strong outline-none focus:border-primary-strong focus:ring-1 focus:ring-primary-strong"
                                    />
                                    <button
                                        v-if="simulatedScores[item.id] !== '' && simulatedScores[item.id] !== undefined"
                                        type="button"
                                        @click="saveSimulatedAsReal(item)"
                                        class="p-1 rounded bg-success/15 hover:bg-success/25 text-success transition cursor-pointer"
                                        title="Guardar nota simulada como nota real en el sistema"
                                    >
                                        <AppIcon name="check" :size="14" />
                                    </button>
                                </div>
                                <div v-else class="text-[11px] text-content-muted font-medium italic">
                                    Calificado
                                </div>
                            </td>

                            <!-- Aporte Neto -->
                            <td class="py-3.5 px-2 text-right font-semibold">
                                <div v-if="item.obtained_score !== null" class="text-content-primary">
                                    +{{ (item.obtained_score * (item.weight / 100)).toFixed(2) }} pts
                                </div>
                                <div v-else-if="simulatedScores[item.id] !== '' && simulatedScores[item.id] !== undefined" class="text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                                    ~+{{ (parseFloat(simulatedScores[item.id] || 0) * (item.weight / 100)).toFixed(2) }} pts
                                </div>
                                <div v-else class="text-content-muted">
                                    --
                                </div>
                            </td>

                            <!-- Acciones (Siempre Visibles) -->
                            <td class="py-3.5 pl-2 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        @click="startEdit(item)"
                                        class="p-1.5 rounded-lg text-content-muted hover:text-primary-strong hover:bg-surface transition cursor-pointer"
                                        title="Editar evaluación"
                                    >
                                        <AppIcon name="edit-2" :size="15" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteEvaluation(item.id)"
                                        class="p-1.5 rounded-lg text-content-muted hover:text-danger hover:bg-danger/10 transition cursor-pointer"
                                        title="Eliminar evaluación"
                                    >
                                        <AppIcon name="trash-2" :size="15" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila de Totales -->
                        <tr v-if="evaluations.length > 0" class="border-t-2 border-border-interactive font-black bg-surface-raised/40">
                            <td colspan="2" class="py-3.5 text-content-primary">TOTALES</td>
                            <td class="py-3.5 px-2 text-right" :class="totalWeight === 100 ? 'text-success' : 'text-amber-500'">
                                {{ totalWeight }}%
                            </td>
                            <td class="py-3.5 px-2 text-center text-xs text-content-secondary">
                                Promedio: <strong class="text-content-primary">{{ currentAverage }}</strong>
                            </td>
                            <td class="py-3.5 px-2 text-center text-xs text-indigo-600 dark:text-indigo-400">
                                Proyectado: <strong>{{ projectedScore.toFixed(2) }}</strong>
                            </td>
                            <td class="py-3.5 px-2 text-right text-primary-strong">
                                {{ accumulatedScore.toFixed(2) }} pts
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>
    </div>
</template>
