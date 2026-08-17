<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import AppIcon from '@/Components/AppIcon.vue';
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue';
import { useTelemetry } from '@/Composables/useTelemetry';

const props = defineProps({
    villain: { type: Object, default: null },
    bestiary: { type: Array, default: () => [] },
    battleLog: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_defeated: 0, total_villains: 10 }) },
});

const { track } = useTelemetry();

const activeTab = ref('battle'); // 'battle' | 'bestiary'
const showStrategyGuide = ref(false);

onMounted(() => {
    track('villains.viewed', 'villains', {
        villain_code: props.villain?.code,
        status: props.villain?.status,
        remaining_hp: props.villain?.remaining_hp,
    });
});

function setTab(tab) {
    activeTab.value = tab;
    if (tab === 'bestiary') {
        track('villains.bestiary_viewed', 'villains', {
            total_defeated: props.stats.total_defeated,
        });
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const formatted = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T');
    const d = new Date(formatted);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('es-PE', { day: 'numeric', month: 'long' });
}

const dateRangeLabel = computed(() => {
    if (!props.villain) return '';
    const start = formatDate(props.villain.assigned_at);
    const end = formatDate(props.villain.expires_at);
    return `Semana ${props.villain.week_number}: Del ${start} al ${end}`;
});

function handleAttack(destination, source) {
    track('villains.attack_clicked', 'villains', {
        villain_code: props.villain?.code,
        source_type: source,
        destination,
    });
    router.visit(route(destination));
}

// Estrategias científicas por villano
const villainStrategies = {
    procrastination: [
        { title: 'Regla de los 2 minutos', text: 'Si una tarea toma menos de dos minutos, hazla ya. Si toma más, solo comprométete a iniciar 2 minutos sin presión.' },
        { title: 'Enfoque en Cuadrante Q2', text: 'Planifica tareas importantes antes de que se vuelvan urgentes para no estudiar bajo pánico.' },
        { title: 'Técnica del Queso Suizo', text: 'Haz pequeños "agujeros" a una tarea gigante resolviendo una subtarea de 10 minutos.' },
    ],
    distraction: [
        { title: 'Caja de Fricción Digital', text: 'Coloca tu celular en otra habitación o en modo avión durante tus bloques de estudio.' },
        { title: 'Regla de las 3 Pestañas', text: 'Estudia solo con las pestañas indispensables abiertas para evitar saltar a redes o videos.' },
        { title: 'Bloques Pomodoro 25/5', text: 'El cerebro sostiene atención plena con facilidad si sabe que tendrá 5 min de descanso garantizado.' },
    ],
    anxiety: [
        { title: 'Escritura Expresiva (Brain Dump)', text: 'Escribe en tu Diario todo lo que te preocupa antes de estudiar para vaciar la memoria de trabajo.' },
        { title: 'Respiración Diafragmática 4-7-8', text: 'Inhala en 4s, retén 7s y exhala en 8s para desactivar la respuesta de estrés del sistema nervioso.' },
        { title: 'Desglose en Micro-Pasos', text: 'La ansiedad disminuye cuando el reto gigante se divide en pasos alcanzables de 15 min.' },
    ],
    disorder: [
        { title: 'Flujo Kanban Visual', text: 'Mueve tus misiones entre Por Hacer, En Proceso y Revisión para tener siempre claridad de tu estado.' },
        { title: 'Checklist de 3 Prioridades', text: 'Define cada noche las 3 únicas metas cruciales para el día siguiente.' },
        { title: 'Espacio de Trabajo Despejado', text: 'Un escritorio físico y digital limpio reduce la carga cognitiva en un 40%.' },
    ],
    fatigue: [
        { title: 'Higiene del Sueño', text: 'Duerme entre 7 y 8 horas continuas. Durante el sueño REM se consolida lo aprendido en el día.' },
        { title: 'Pausa Activa de Movimiento', text: 'Estírate o camina 5 minutos tras cada bloque de estudio para reactivar la circulación.' },
        { title: 'Hidratación Cerebral', text: 'Una deshidratación de solo el 2% reduce tu memoria a corto plazo y velocidad de procesamiento.' },
    ],
    impostor_syndrome: [
        { title: 'Registro de Evidencia Real', text: 'Revisa tu historial de misiones y logros completados: tus éxitos son fruto de tu esfuerzo, no del azar.' },
        { title: 'Mentalidad de Crecimiento', text: 'No saber algo hoy no significa incapacidad, solo significa que estás en proceso de aprendizaje.' },
        { title: 'Autocompasión Académica', text: 'Háblate a ti mismo como le hablarías a un buen amigo que está aprendiendo algo difícil.' },
    ],
    perfectionism: [
        { title: 'Lo Hecho es Mejor que lo Perfecto', text: 'Un borrador completo al 80% te permite iterar y mejorar; una hoja en blanco no te enseña nada.' },
        { title: 'Límites de Tiempo Estrictos', text: 'Usa Pomodoros para forzarte a cerrar secciones sin sobrepensar detalles cosméticos.' },
        { title: 'El Error como Dato', text: 'Equivocarse en prácticas y simulacros es la forma más rápida de generar conexiones neuronales fuertes.' },
    ],
    isolation: [
        { title: 'Estudio en Co-Working Virtual', text: 'Entra a una sala de Grupo de Estudio en Epycus: ver a otros concentrados contagia el enfoque.' },
        { title: 'Pedir Ayuda a Tiempo', text: 'Si llevas más de 20 min trabado en un ejercicio, consulta a la IA Edy o a tus compañeros.' },
        { title: 'Explicar para Aprender (Feynman)', text: 'Compartir conceptos con compañeros te ayuda a dominar el tema el doble de rápido.' },
    ],
    burnout: [
        { title: 'Día de Desconexión Semanal', text: 'Programa al menos medio día sin estudio para recargar dopamina y motivación intrínseca.' },
        { title: 'Micro-Pausas de Ojos Cerrados', text: 'Descansa la vista 2 minutos entre pantallas para relajar el músculo ciliar.' },
        { title: 'Equilibrio de Hábitos', text: 'La productividad no es estudiar 14 horas, sino estudiar 4 horas con máxima energía y claridad.' },
    ],
    all_nighter: [
        { title: 'Estudio Espaciado vs Masivo', text: 'Estudiar 1 hora durante 5 días rinde 3 veces más que estudiar 5 horas en una sola noche.' },
        { title: 'El Mito del Amanecida', text: 'Sin sueño, el cerebro pierde la capacidad de recuperar fórmulas y conceptos en el examen.' },
        { title: 'Entrega Preventiva Q2', text: 'Ponte como fecha límite interna 24 horas antes de la entrega oficial.' },
    ],
};

const currentStrategies = computed(() => {
    if (!props.villain?.code) return [];
    return villainStrategies[props.villain.code] || [];
});
</script>

<template>
    <Head title="Villano Semanal & Bestiario — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Tip dinámico del módulo -->
            <UsageTipBanner module="villains" />

            <!-- Header Card Principal -->
            <BaseCard class="p-6 border-border-interactive">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-2 shadow-sm"
                        >
                            <img
                                src="/assets/gifs/villains.gif"
                                alt="Villanos Académicos"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">
                                Villanos Semanales ⚔️
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Personifica y derrota los mayores obstáculos que frenan tu potencial académico.
                            </p>
                        </div>
                    </div>

                    <!-- Métricas de Victorias -->
                    <div class="flex items-center gap-3 bg-surface-raised/40 p-3 rounded-xl border border-border-interactive/50 shrink-0">
                        <div class="text-center px-2">
                            <p class="text-xs text-content-muted font-medium">Victorias</p>
                            <p class="text-xl font-bold text-success">{{ stats.total_defeated }}</p>
                        </div>
                        <div class="h-8 w-px bg-border-interactive"></div>
                        <div class="text-center px-2">
                            <p class="text-xs text-content-muted font-medium">Bestiario</p>
                            <p class="text-xl font-bold text-primary-strong">{{ stats.total_villains }} Bosses</p>
                        </div>
                    </div>
                </header>

                <!-- Selector de Pestañas: Combate vs Bestiario -->
                <div class="mt-6 pt-4 border-t border-border-interactive/50 flex gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-2"
                        :class="
                            activeTab === 'battle'
                                ? 'bg-primary-strong text-white shadow-sm'
                                : 'bg-surface-raised/60 text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                        "
                        @click="setTab('battle')"
                    >
                        <AppIcon name="sword" :size="14" />
                        <span>Combate Semanal</span>
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-2"
                        :class="
                            activeTab === 'bestiary'
                                ? 'bg-primary-strong text-white shadow-sm'
                                : 'bg-surface-raised/60 text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                        "
                        @click="setTab('bestiary')"
                    >
                        <AppIcon name="shield" :size="14" />
                        <span>Bestiario & Trofeos ({{ stats.total_defeated }}/{{ stats.total_villains }})</span>
                    </button>
                </div>
            </BaseCard>

            <!-- PESTAÑA 1: COMBATE SEMANAL ACTIVO -->
            <div v-if="activeTab === 'battle'" class="space-y-6">
                <!-- Sin Villano Activo -->
                <BaseCard v-if="!villain" class="p-8">
                    <EmptyState
                        title="Sin villano activo esta semana"
                        description="¡Gran trabajo! Mantén tus hábitos y misiones al día para estar preparado ante el próximo desafío."
                    >
                        <template #icon>
                            <AppIcon name="shield" :size="56" class="text-content-muted" />
                        </template>
                    </EmptyState>
                </BaseCard>

                <!-- Tarjeta Principal del Villano en Batalla -->
                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Columna Izquierda: Imagen y Estado del Boss -->
                    <BaseCard class="lg:col-span-2 p-6 flex flex-col justify-between space-y-6 relative overflow-hidden">
                        <div>
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                                <div class="relative shrink-0 group">
                                    <img
                                        :src="villain.image_url"
                                        :alt="villain.name"
                                        class="h-44 w-44 rounded-2xl object-contain bg-surface-raised/40 p-2 border border-border-interactive shadow-sm transition-transform duration-300 group-hover:scale-105"
                                        :class="{ 'grayscale opacity-60': villain.status === 'defeated' }"
                                    />
                                    <div
                                        v-if="villain.status === 'defeated'"
                                        class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-2xl backdrop-blur-xs"
                                    >
                                        <span class="rounded-full bg-success px-3 py-1 text-xs font-black text-white shadow-md">
                                            ¡VENCIDO! 🏆
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-2 text-center sm:text-left flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                        <span class="rounded-full bg-danger/15 px-2.5 py-0.5 text-[11px] font-bold text-danger border border-danger/30">
                                            Boss Semanal #{{ villain.week_number }}
                                        </span>
                                        <span
                                            v-if="villain.status === 'defeated'"
                                            class="rounded-full bg-success/15 px-2.5 py-0.5 text-[11px] font-bold text-success border border-success/30"
                                        >
                                            ✓ Derrotado
                                        </span>
                                    </div>

                                    <h2 class="font-display text-2xl font-bold text-content-primary">
                                        {{ villain.name }}
                                    </h2>

                                    <p class="text-sm text-content-secondary leading-relaxed">
                                        {{ villain.description }}
                                    </p>

                                    <!-- Debilidad Destacada -->
                                    <div class="pt-2">
                                        <div class="inline-flex items-start gap-2 rounded-xl bg-accent/10 p-3 border border-accent/20 text-xs text-content-primary text-left">
                                            <span class="text-accent text-base shrink-0">🎯</span>
                                            <div>
                                                <span class="font-bold text-accent">Debilidad Crítica:</span>
                                                <p class="text-content-secondary mt-0.5">{{ villain.weakness_description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Barra de Vida (HP) de Gran Impacto -->
                            <div class="mt-6 space-y-2">
                                <div class="flex justify-between items-center text-xs font-bold">
                                    <span class="text-content-secondary flex items-center gap-1.5">
                                        <AppIcon name="heart" :size="14" class="text-danger" /> Vida del Villano
                                    </span>
                                    <span :class="villain.status === 'defeated' ? 'text-success' : 'text-danger'">
                                        {{ villain.remaining_hp }} / {{ villain.total_hp }} HP ({{ Math.round((villain.remaining_hp / villain.total_hp) * 100) }}%)
                                    </span>
                                </div>
                                <ProgressBar
                                    :value="villain.remaining_hp"
                                    :max="villain.total_hp"
                                    :color="villain.status === 'defeated' ? 'bg-success' : 'bg-danger'"
                                    size="h-4"
                                    class="w-full shadow-inner"
                                />
                            </div>
                        </div>

                        <div class="pt-3 border-t border-border-interactive/40 flex items-center justify-between text-xs text-content-muted">
                            <span>📅 {{ dateRangeLabel }}</span>
                            <span v-if="villain.defeated_at" class="text-success font-semibold">
                                ✓ Derrotado el {{ formatDate(villain.defeated_at) }}
                            </span>
                        </div>
                    </BaseCard>

                    <!-- Columna Derecha: Botones de Ataque Directo -->
                    <BaseCard class="p-5 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                                <span>⚔️</span> Acciones de Ataque Directo
                            </h3>
                            <p class="text-xs text-content-secondary mt-1">
                                Cada acción enfocada inflige <strong class="text-danger">-10 HP</strong> al villano:
                            </p>

                            <div class="mt-4 space-y-2.5">
                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between p-2.5 rounded-xl border border-border-interactive bg-surface hover:border-primary-strong hover:bg-primary-strong/5 transition cursor-pointer text-xs group text-left"
                                    @click="handleAttack('pomodoro.index', 'pomodoro')"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-strong/15 text-primary-strong">
                                            <AppIcon name="timer" :size="16" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-content-primary group-hover:text-primary-strong">Iniciar Pomodoro</p>
                                            <p class="text-[10px] text-content-muted">Estudio profundo sin cortes</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger shrink-0">
                                        -10 HP
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between p-2.5 rounded-xl border border-border-interactive bg-surface hover:border-primary-strong hover:bg-primary-strong/5 transition cursor-pointer text-xs group text-left"
                                    @click="handleAttack('missions.index', 'mission')"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-success/15 text-success">
                                            <AppIcon name="target" :size="16" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-content-primary group-hover:text-primary-strong">Completar Misión</p>
                                            <p class="text-[10px] text-content-muted">Avanzar tareas y subtareas</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger shrink-0">
                                        -10 HP
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between p-2.5 rounded-xl border border-border-interactive bg-surface hover:border-primary-strong hover:bg-primary-strong/5 transition cursor-pointer text-xs group text-left"
                                    @click="handleAttack('habits.index', 'habit')"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent/15 text-accent">
                                            <AppIcon name="leaf" :size="16" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-content-primary group-hover:text-primary-strong">Marcar Hábito</p>
                                            <p class="text-[10px] text-content-muted">Rutinas y constancia diaria</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger shrink-0">
                                        -10 HP
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between p-2.5 rounded-xl border border-border-interactive bg-surface hover:border-primary-strong hover:bg-primary-strong/5 transition cursor-pointer text-xs group text-left"
                                    @click="handleAttack('wellbeing.index', 'journal')"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-400">
                                            <AppIcon name="brain" :size="16" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-content-primary group-hover:text-primary-strong">Escribir en Diario</p>
                                            <p class="text-[10px] text-content-muted">Descarga y bienestar mental</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger shrink-0">
                                        -10 HP
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2 text-center">
                            <span class="text-[11px] text-content-muted">
                                ¡Tus acciones en la app aplican daño automático!
                            </span>
                        </div>
                    </BaseCard>
                </div>

                <!-- Playbook Estratégico Científico Anti-Villano -->
                <BaseCard v-if="currentStrategies.length > 0" class="p-5 border-l-4 border-l-primary-strong">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <AppIcon name="lightbulb" :size="18" class="text-warning shrink-0" />
                            <h3 class="font-bold text-sm text-content-primary">
                                Playbook Estratégico: Cómo derrotar a {{ villain.name }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-semibold text-primary-strong hover:underline cursor-pointer"
                            @click="showStrategyGuide = !showStrategyGuide"
                        >
                            {{ showStrategyGuide ? 'Ocultar guía' : 'Ver 3 técnicas científicas' }}
                        </button>
                    </div>

                    <div v-if="showStrategyGuide" class="mt-4 pt-3 border-t border-border-interactive grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div
                            v-for="(strat, i) in currentStrategies"
                            :key="'strat-' + i"
                            class="p-3 rounded-xl bg-surface-raised border border-border-interactive text-xs space-y-1"
                        >
                            <p class="font-bold text-primary-strong flex items-center gap-1">
                                <span>#{{ i + 1 }}</span> {{ strat.title }}
                            </p>
                            <p class="text-content-secondary leading-relaxed text-[11px]">
                                {{ strat.text }}
                            </p>
                        </div>
                    </div>
                </BaseCard>

                <!-- Battle Log: Registro de Combate Semanal -->
                <BaseCard class="p-5">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-border-interactive">
                        <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                            <AppIcon name="scroll" :size="16" class="text-primary-strong" /> Registro de Combate (Esta Semana)
                        </h3>
                        <span class="text-xs text-content-muted">
                            {{ battleLog.length }} impactos registrados
                        </span>
                    </div>

                    <div v-if="battleLog.length === 0" class="py-6 text-center text-xs text-content-muted">
                        Aún no has registrado ataques esta semana. ¡Completa un Pomodoro o Misión para infligir tus primeros 10 HP!
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="(log, idx) in battleLog"
                            :key="'log-' + idx"
                            class="flex items-center justify-between p-2.5 rounded-xl bg-surface-raised/60 border border-border-interactive text-xs"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">
                                    <template v-if="log.source === 'pomodoro'">⏱️</template>
                                    <template v-else-if="log.source === 'mission'">🎯</template>
                                    <template v-else-if="log.source === 'habit'">🌱</template>
                                    <template v-else>📔</template>
                                </span>
                                <div>
                                    <p class="font-semibold text-content-primary">{{ log.action }}</p>
                                    <p class="text-[10px] text-content-muted">{{ log.created_at }}</p>
                                </div>
                            </div>

                            <span class="rounded-full bg-danger/15 px-2.5 py-0.5 text-xs font-black text-danger border border-danger/30">
                                -{{ log.damage }} HP
                            </span>
                        </div>
                    </div>
                </BaseCard>
            </div>

            <!-- PESTAÑA 2: BESTIARIO ACADÉMICO & SALA DE TROFEOS -->
            <div v-else-if="activeTab === 'bestiary'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-xl font-bold text-content-primary">
                            Bestiario de Obstáculos Académicos
                        </h2>
                        <p class="text-xs text-content-secondary mt-0.5">
                            Catálogo de los 10 villanos que enfrentarás a lo largo del ciclo.
                        </p>
                    </div>
                    <span class="rounded-full bg-success/15 px-3 py-1 text-xs font-bold text-success border border-success/30">
                        {{ stats.total_defeated }} Victorias Acumuladas
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <BaseCard
                        v-for="b in bestiary"
                        :key="b.id"
                        class="p-4 flex flex-col justify-between transition-all duration-200"
                        :class="[
                            b.is_unlocked
                                ? 'bg-surface-raised/90 border-border-interactive shadow-sm hover:shadow-md hover:border-primary-strong/40'
                                : 'bg-surface/50 border-border-interactive/60 opacity-60 grayscale',
                        ]"
                    >
                        <div>
                            <div class="flex items-start gap-3">
                                <img
                                    :src="b.image_url"
                                    :alt="b.name"
                                    class="h-16 w-16 rounded-xl object-contain bg-surface-raised p-1 border border-border-interactive shrink-0"
                                />
                                <div class="space-y-0.5 flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <h3 class="font-bold text-sm text-content-primary truncate">
                                            {{ b.name }}
                                        </h3>
                                        <span
                                            v-if="b.is_current"
                                            class="rounded bg-danger/20 px-1.5 py-0.2 text-[9px] font-black text-danger shrink-0"
                                        >
                                            ACTIVO
                                        </span>
                                    </div>
                                    <p class="text-xs text-content-secondary line-clamp-2 leading-relaxed">
                                        {{ b.description }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 p-2 rounded-lg bg-surface/80 border border-border-interactive/40 text-[11px] text-content-secondary">
                                <span class="font-bold text-accent">Debilidad:</span> {{ b.weakness_description }}
                            </div>
                        </div>

                        <div class="mt-3.5 pt-2.5 border-t border-border-interactive/40 flex items-center justify-between text-xs">
                            <span
                                v-if="b.times_defeated > 0"
                                class="text-success font-bold flex items-center gap-1"
                            >
                                🏆 Vencido {{ b.times_defeated }}x
                            </span>
                            <span v-else-if="b.is_current" class="text-danger font-semibold flex items-center gap-1">
                                ⚔️ En combate
                            </span>
                            <span v-else class="text-content-muted font-medium">
                                🔒 Por enfrentar
                            </span>

                            <span v-if="b.last_defeated_at" class="text-[10px] text-content-muted">
                                Último: {{ b.last_defeated_at }}
                            </span>
                        </div>
                    </BaseCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
