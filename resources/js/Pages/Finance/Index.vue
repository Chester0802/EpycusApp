<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import { triggerConfetti, triggerHapticVibration } from '@/utils/celebration';
import {
    DollarSign,
    TrendingUp,
    TrendingDown,
    PiggyBank,
    Plus,
    Trash2,
    Sliders,
    ChevronLeft,
    ChevronRight,
    Target,
} from '@lucide/vue';

const props = defineProps({
    overview: {
        type: Object,
        required: true,
    },
});

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const showTransactionModal = ref(false);
const showBudgetModal = ref(false);
const showSavingsGoalModal = ref(false);
const showDepositModal = ref(false);
const isProcessing = ref(false);
const selectedGoal = ref(null);

const transactionForm = ref({
    type: 'expense',
    amount: '',
    category: 'transporte',
    date: new Date().toISOString().split('T')[0],
    notes: '',
});

const budgetForm = ref({
    category: 'alimentacion',
    monthly_limit: '',
});

const savingsGoalForm = ref({
    title: '',
    target_amount: '',
    current_amount: 0,
    target_date: '',
    reward_xp: 100,
});

const depositAmount = ref('');

const expenseCategories = [
    { value: 'transporte', label: '🚌 Transporte / Pasajes' },
    { value: 'alimentacion', label: '🍱 Alimentación / Almuerzo' },
    { value: 'materiales', label: '📚 Fotocopias y Materiales' },
    { value: 'ocio', label: '🎮 Salidas y Ocio' },
    { value: 'suscripciones', label: '📱 Suscripciones y Apps' },
    { value: 'servicios', label: '💡 Servicios y Celular' },
    { value: 'otro', label: '📌 Otros Gastos' },
];

const incomeCategories = [
    { value: 'ingreso_mesada', label: '💵 Mesada / Apoyo Familiar' },
    { value: 'ingreso_trabajo', label: '💼 Empleo / Prácticas / Freelance' },
    { value: 'ingreso_otro', label: '✨ Otro Ingreso' },
];

const availableCategories = computed(() => {
    return transactionForm.value.type === 'expense' ? expenseCategories : incomeCategories;
});

function changeMonth(delta) {
    let m = props.overview.month + delta;
    let y = props.overview.year;
    if (m > 12) {
        m = 1;
        y++;
    } else if (m < 1) {
        m = 12;
        y--;
    }
    router.get(route('finance.index', { month: m, year: y }), {}, { preserveScroll: true });
}

function openTransactionModal(type = 'expense') {
    transactionForm.value = {
        type,
        amount: '',
        category: type === 'expense' ? 'transporte' : 'ingreso_mesada',
        date: new Date().toISOString().split('T')[0],
        notes: '',
    };
    showTransactionModal.value = true;
}

function submitTransaction() {
    isProcessing.value = true;
    router.post(
        route('finance.transactions.store'),
        transactionForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showTransactionModal.value = false;
                if (transactionForm.value.type === 'income') {
                    triggerConfetti();
                }
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function deleteTransaction(id) {
    if (!confirm('¿Eliminar este movimiento?')) return;
    router.delete(route('finance.transactions.destroy', { id }), { preserveScroll: true });
}

function openBudgetModal(category = 'alimentacion') {
    const existing = props.overview.budgets.find(b => b.category === category);
    budgetForm.value = {
        category,
        monthly_limit: existing ? existing.limit : '',
    };
    showBudgetModal.value = true;
}

function submitBudget() {
    isProcessing.value = true;
    router.post(
        route('finance.budgets.save'),
        {
            ...budgetForm.value,
            month: props.overview.month,
            year: props.overview.year,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showBudgetModal.value = false;
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function openSavingsGoalModal() {
    savingsGoalForm.value = {
        title: '',
        target_amount: '',
        current_amount: 0,
        target_date: '',
        reward_xp: 100,
    };
    showSavingsGoalModal.value = true;
}

function submitSavingsGoal() {
    isProcessing.value = true;
    router.post(
        route('finance.savings.store'),
        savingsGoalForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showSavingsGoalModal.value = false;
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function openDepositModal(goal) {
    selectedGoal.value = goal;
    depositAmount.value = '';
    showDepositModal.value = true;
}

function submitDeposit() {
    if (!selectedGoal.value || !depositAmount.value) return;
    isProcessing.value = true;
    router.post(
        route('finance.savings.deposit', { id: selectedGoal.value.id }),
        { amount: depositAmount.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showDepositModal.value = false;
                triggerConfetti();
                triggerHapticVibration([40, 40, 80]);
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function deleteSavingsGoal(id) {
    if (!confirm('¿Eliminar esta meta de ahorro?')) return;
    router.delete(route('finance.savings.destroy', { id }), { preserveScroll: true });
}
</script>

<template>
    <Head title="Finanzas Estudiantiles — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Principal & Navegación de Mes -->
            <div class="p-6 rounded-3xl bg-surface-raised border border-border shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-2xl bg-success/15 text-success text-2xl font-bold">💰</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="font-display text-2xl sm:text-3xl font-bold text-content-primary">Finanzas Estudiantiles</h1>
                            </div>
                            <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
                                Control presupuestario universitario, seguimiento de gastos diarios y metas de ahorro con recompensas XP.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Selector de Mes & Botones de Acción Rápida -->
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex items-center bg-surface p-1 rounded-2xl border border-border">
                        <button type="button" class="p-2 rounded-xl text-content-secondary hover:bg-surface-raised" @click="changeMonth(-1)">
                            <ChevronLeft :size="16" />
                        </button>
                        <span class="px-3 font-display font-bold text-sm text-content-primary">
                            {{ monthNames[overview.month - 1] }} {{ overview.year }}
                        </span>
                        <button type="button" class="p-2 rounded-xl text-content-secondary hover:bg-surface-raised" @click="changeMonth(1)">
                            <ChevronRight :size="16" />
                        </button>
                    </div>

                    <BaseButton variant="primary" size="sm" @click="openTransactionModal('expense')">
                        <Plus :size="14" />
                        Registrar Gasto
                    </BaseButton>
                    <BaseButton variant="secondary" size="sm" @click="openTransactionModal('income')">
                        <Plus :size="14" />
                        Ingreso
                    </BaseButton>
                </div>
            </div>

            <!-- Métricas Financieras del Mes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <BaseCard class="p-5 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-content-muted">Ingresos Totales</span>
                        <TrendingUp :size="18" class="text-success" />
                    </div>
                    <div class="font-display text-2xl font-black text-success mt-2">
                        S/ {{ overview.summary.total_income.toFixed(2) }}
                    </div>
                    <span class="text-[11px] text-content-muted mt-1">mesadas, sueldo, otros</span>
                </BaseCard>

                <BaseCard class="p-5 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-content-muted">Gastos Totales</span>
                        <TrendingDown :size="18" class="text-danger-text" />
                    </div>
                    <div class="font-display text-2xl font-black text-danger-text mt-2">
                        S/ {{ overview.summary.total_expenses.toFixed(2) }}
                    </div>
                    <span class="text-[11px] text-content-muted mt-1">transporte, comida, ocio</span>
                </BaseCard>

                <div
                    :class="[
                        'p-5 rounded-2xl border flex flex-col justify-between transition-all',
                        overview.summary.net_balance >= 0 ? 'bg-success/10 border-success/30' : 'bg-danger/10 border-danger/30'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold" :class="overview.summary.net_balance >= 0 ? 'text-success' : 'text-danger-text'">
                            Balance Neto
                        </span>
                        <DollarSign :size="18" :class="overview.summary.net_balance >= 0 ? 'text-success' : 'text-danger-text'" />
                    </div>
                    <div class="font-display text-2xl font-black mt-2" :class="overview.summary.net_balance >= 0 ? 'text-success' : 'text-danger-text'">
                        S/ {{ overview.summary.net_balance.toFixed(2) }}
                    </div>
                    <span class="text-[11px] opacity-80 mt-1">diferencia del mes</span>
                </div>

                <BaseCard class="p-5 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-content-muted">Tasa de Ahorro</span>
                        <PiggyBank :size="18" class="text-primary-strong" />
                    </div>
                    <div class="font-display text-2xl font-black text-primary-strong mt-2">
                        {{ overview.summary.savings_rate }}%
                    </div>
                    <div class="w-full bg-surface-sunken rounded-full h-1.5 mt-1 overflow-hidden border border-border">
                        <div class="bg-primary-strong h-1.5 rounded-full" :style="{ width: `${Math.min(100, Math.max(0, overview.summary.savings_rate))}%` }"></div>
                    </div>
                </BaseCard>
            </div>

            <!-- Presupuestos Mensuales & Metas de Ahorro (2 Columnas) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Columna Izquierda: Presupuestos por Categoría (Semáforo) -->
                <BaseCard class="lg:col-span-6 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-border/70 pb-3">
                        <div>
                            <h3 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                                <span>🚦</span> Presupuestos del Mes
                            </h3>
                            <p class="text-xs text-content-secondary mt-0.5">Control con límites y alertas visuales</p>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                            @click="openBudgetModal('alimentacion')"
                        >
                            <Sliders :size="13" /> Ajustar Límite
                        </button>
                    </div>

                    <div v-if="overview.budgets.length === 0" class="p-6 text-center text-xs text-content-muted border border-dashed border-border rounded-2xl space-y-2">
                        <p>No tienes presupuestos configurados para este mes.</p>
                        <BaseButton variant="secondary" size="sm" @click="openBudgetModal('alimentacion')">
                            Establecer Primer Presupuesto
                        </BaseButton>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="b in overview.budgets"
                            :key="b.category"
                            class="p-3.5 rounded-2xl bg-surface border border-border/80 space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ b.icon }}</span>
                                    <div>
                                        <span class="font-bold text-content-primary">{{ b.label }}</span>
                                        <span class="text-[11px] text-content-muted block">
                                            Gastado: S/ {{ b.spent.toFixed(2) }} de S/ {{ b.limit.toFixed(2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-black',
                                            b.status === 'red' ? 'bg-danger/20 text-danger-text' :
                                            b.status === 'yellow' ? 'bg-warning/20 text-warning' :
                                            'bg-success/20 text-success'
                                        ]"
                                    >
                                        {{ b.raw_percentage }}%
                                    </span>
                                    <span class="text-[10px] text-content-muted block mt-0.5">
                                        Restante: S/ {{ b.remaining.toFixed(2) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Barra de Semáforo -->
                            <div class="w-full bg-surface-sunken rounded-full h-2 overflow-hidden border border-border">
                                <div
                                    class="h-2 rounded-full transition-all duration-500"
                                    :class="[
                                        b.status === 'red' ? 'bg-danger' :
                                        b.status === 'yellow' ? 'bg-warning' :
                                        'bg-success'
                                    ]"
                                    :style="{ width: `${b.percentage}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </BaseCard>

                <!-- Columna Derecha: Metas de Ahorro Gamificadas con XP -->
                <BaseCard class="lg:col-span-6 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-border/70 pb-3">
                        <div>
                            <h3 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                                <span>🎯</span> Metas de Ahorro Estudiantiles
                            </h3>
                            <p class="text-xs text-content-secondary mt-0.5">Ahorra para tus objetivos y gana XP</p>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                            @click="openSavingsGoalModal"
                        >
                            <Plus :size="13" /> Nueva Meta
                        </button>
                    </div>

                    <div v-if="overview.savings_goals.length === 0" class="p-6 text-center text-xs text-content-muted border border-dashed border-border rounded-2xl space-y-2">
                        <p>No tienes metas de ahorro activas.</p>
                        <BaseButton variant="primary" size="sm" @click="openSavingsGoalModal">
                            Crear Meta de Ahorro
                        </BaseButton>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="goal in overview.savings_goals"
                            :key="goal.id"
                            :class="[
                                'p-4 rounded-2xl border transition-all space-y-2.5',
                                goal.is_completed ? 'bg-success/10 border-success/30' : 'bg-surface border-border/80'
                            ]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-sm text-content-primary">{{ goal.title }}</h4>
                                        <span v-if="goal.is_completed" class="px-2 py-0.5 rounded-full bg-success text-on-accent text-[10px] font-bold">
                                            ¡COMPLETADA! 🎉
                                        </span>
                                    </div>
                                    <span class="text-xs text-content-muted block mt-0.5">
                                        S/ {{ goal.current_amount.toFixed(2) }} de S/ {{ goal.target_amount.toFixed(2) }} • Meta: {{ goal.progress_percentage }}%
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        v-if="!goal.is_completed"
                                        type="button"
                                        class="px-2.5 py-1 rounded-xl bg-primary-strong text-on-primary-strong font-bold text-xs shadow-sm hover:opacity-90 transition-all flex items-center gap-1"
                                        @click="openDepositModal(goal)"
                                    >
                                        + Aportar
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1 text-content-muted hover:text-danger-text"
                                        @click="deleteSavingsGoal(goal.id)"
                                    >
                                        <Trash2 :size="13" />
                                    </button>
                                </div>
                            </div>

                            <!-- Barra de Progreso -->
                            <div class="w-full bg-surface-sunken rounded-full h-2 overflow-hidden border border-border">
                                <div
                                    class="bg-primary-strong h-2 rounded-full transition-all duration-500"
                                    :style="{ width: `${goal.progress_percentage}%` }"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-content-muted">
                                <span>Recompensa: ⚡ +{{ goal.reward_xp }} XP</span>
                                <span v-if="goal.target_date">Límite: {{ goal.target_date }}</span>
                            </div>
                        </div>
                    </div>
                </BaseCard>
            </div>

            <!-- Historial de Transacciones del Mes -->
            <BaseCard class="p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border/70 pb-3">
                    <div>
                        <h3 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                            <span>📜</span> Movimientos del Mes
                        </h3>
                        <p class="text-xs text-content-secondary mt-0.5">Listado cronológico de ingresos y gastos</p>
                    </div>
                </div>

                <div v-if="overview.transactions.length === 0" class="p-6 text-center text-xs text-content-muted">
                    No hay movimientos registrados en {{ monthNames[overview.month - 1] }} {{ overview.year }}.
                </div>

                <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    <div
                        v-for="t in overview.transactions"
                        :key="t.id"
                        class="flex items-center justify-between p-3.5 rounded-2xl bg-surface border border-border/80 text-xs hover:border-primary/40 transition-all"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ t.icon }}</span>
                            <div>
                                <h4 class="font-bold text-sm text-content-primary">{{ t.label }}</h4>
                                <span class="text-[11px] text-content-muted block">
                                    {{ t.date }} <span v-if="t.notes">• {{ t.notes }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                class="font-display font-bold text-sm"
                                :class="t.type === 'income' ? 'text-success' : 'text-danger-text'"
                            >
                                {{ t.type === 'income' ? '+' : '-' }} S/ {{ t.amount.toFixed(2) }}
                            </span>
                            <button
                                type="button"
                                class="p-1 text-content-muted hover:text-danger-text"
                                @click="deleteTransaction(t.id)"
                            >
                                <Trash2 :size="13" />
                            </button>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Modal: Registrar Movimiento -->
        <BaseModal
            :show="showTransactionModal"
            :title="transactionForm.type === 'expense' ? '💸 Registrar Gasto' : '💵 Registrar Ingreso'"
            @close="showTransactionModal = false"
        >
            <form class="space-y-4" @submit.prevent="submitTransaction">
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="p-2.5 rounded-xl border text-xs font-bold transition-all"
                        :class="transactionForm.type === 'expense' ? 'bg-danger/20 border-danger text-danger-text' : 'bg-surface border-border text-content-muted'"
                        @click="transactionForm.type = 'expense'; transactionForm.category = 'transporte'"
                    >
                        💸 Gasto
                    </button>
                    <button
                        type="button"
                        class="p-2.5 rounded-xl border text-xs font-bold transition-all"
                        :class="transactionForm.type === 'income' ? 'bg-success/20 border-success text-success' : 'bg-surface border-border text-content-muted'"
                        @click="transactionForm.type = 'income'; transactionForm.category = 'ingreso_mesada'"
                    >
                        💵 Ingreso
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Monto en Soles (S/)</label>
                        <BaseInput v-model="transactionForm.amount" type="number" step="0.1" min="0.1" placeholder="Ej. 15.50" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Fecha</label>
                        <BaseInput v-model="transactionForm.date" type="date" required />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Categoría</label>
                    <BaseSelect v-model="transactionForm.category" :options="availableCategories" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Notas / Descripción (opcional)</label>
                    <BaseInput v-model="transactionForm.notes" placeholder="Ej. Almuerzo menú universitario" />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showTransactionModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">Guardar Movimiento</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- Modal: Configurar Presupuesto -->
        <BaseModal :show="showBudgetModal" title="⚙️ Ajustar Límite de Presupuesto" @close="showBudgetModal = false">
            <form class="space-y-4" @submit.prevent="submitBudget">
                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Categoría de Gasto</label>
                    <BaseSelect v-model="budgetForm.category" :options="expenseCategories" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Límite Mensual en Soles (S/)</label>
                    <BaseInput v-model="budgetForm.monthly_limit" type="number" step="1" min="1" placeholder="Ej. 250.00" required />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showBudgetModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">Guardar Límite</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- Modal: Crear Meta de Ahorro -->
        <BaseModal :show="showSavingsGoalModal" title="🎯 Nueva Meta de Ahorro" @close="showSavingsGoalModal = false">
            <form class="space-y-4" @submit.prevent="submitSavingsGoal">
                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Nombre del objetivo</label>
                    <BaseInput v-model="savingsGoalForm.title" placeholder="Ej. Comprar calculadora científica o viaje" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Monto Objetivo (S/)</label>
                        <BaseInput v-model="savingsGoalForm.target_amount" type="number" step="1" min="1" placeholder="300.00" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Ahorro Inicial (S/)</label>
                        <BaseInput v-model="savingsGoalForm.current_amount" type="number" step="1" min="0" placeholder="0" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Fecha Límite Estimada (opcional)</label>
                    <BaseInput v-model="savingsGoalForm.target_date" type="date" />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showSavingsGoalModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">Crear Meta</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- Modal: Aporte al Ahorro -->
        <BaseModal :show="showDepositModal" title="💰 Aportar al Ahorro" @close="showDepositModal = false">
            <form class="space-y-4" @submit.prevent="submitDeposit">
                <div v-if="selectedGoal">
                    <p class="text-xs text-content-secondary">
                        Sumar fondos a la meta: <strong class="text-content-primary">{{ selectedGoal.title }}</strong>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Monto a depositar en Soles (S/)</label>
                    <BaseInput v-model="depositAmount" type="number" step="0.5" min="0.5" placeholder="Ej. 20.00" required />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showDepositModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">Depositar</BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
