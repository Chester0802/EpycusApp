<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import BaseButton from '@/Components/ui/BaseButton.vue'
import BaseBadge from '@/Components/ui/BaseBadge.vue'
import {
    LayoutDashboard,
    Users,
    AlertTriangle,
    BarChart2,
    Download,
    Activity,
    Flame,
    CheckCircle2,
    Timer,
    ShieldAlert,
    TrendingUp,
    CalendarDays,
    User,
    Search,
    ArrowUpDown,
} from '@lucide/vue'

const props = defineProps({
    metrics:      { type: Object, required: true },
    participants: { type: Array,  default: () => [] },
    dropout:      { type: Array,  default: () => [] },
    telemetry:    { type: Object, required: true },
    health:       { type: Object, required: true },
})

const activeTab = ref('dashboard')

const tabs = [
    { id: 'dashboard',    label: 'Dashboard',          icon: LayoutDashboard },
    { id: 'participants', label: 'Participantes',       icon: Users },
    { id: 'dropout',      label: 'Deserción (3+ días)', icon: AlertTriangle },
    { id: 'telemetry',    label: 'Telemetría',          icon: BarChart2 },
    { id: 'export',       label: 'Exportar Datasets',   icon: Download },
    { id: 'health',       label: 'Salud del Sistema',   icon: Activity },
]

// ─── Participantes: búsqueda y orden ──────────────────────────────────────────
const participantSearch = ref('')
const participantSort   = ref({ key: 'total_xp', dir: 'desc' })

const filteredParticipants = computed(() => {
    const q = participantSearch.value.toLowerCase()
    let list = props.participants.filter(p =>
        !q ||
        p.alias?.toLowerCase().includes(q) ||
        p.participant_code?.toLowerCase().includes(q) ||
        p.career?.toLowerCase().includes(q)
    )
    list = [...list].sort((a, b) => {
        const va = a[participantSort.value.key] ?? ''
        const vb = b[participantSort.value.key] ?? ''
        return participantSort.value.dir === 'asc'
            ? (va > vb ? 1 : -1)
            : (va < vb ? 1 : -1)
    })
    return list
})

function toggleSort(key) {
    if (participantSort.value.key === key) {
        participantSort.value.dir = participantSort.value.dir === 'asc' ? 'desc' : 'asc'
    } else {
        participantSort.value = { key, dir: 'desc' }
    }
}

// ─── Telemetría: búsqueda de log ─────────────────────────────────────────────
const telemetrySearch = ref('')
const telemetryView   = ref('log') // 'log' | 'category' | 'users'

const filteredEvents = computed(() => {
    const q = telemetrySearch.value.toLowerCase()
    if (!q) return props.telemetry.recent_events ?? []
    return (props.telemetry.recent_events ?? []).filter(e =>
        e.alias?.toLowerCase().includes(q) ||
        e.participant_code?.toLowerCase().includes(q) ||
        e.event_name?.toLowerCase().includes(q) ||
        e.event_category?.toLowerCase().includes(q)
    )
})

// ─── Exportar: descarga directa ───────────────────────────────────────────────
function downloadCsv(type) {
    window.open(route('admin.export', type), '_blank')
}

const exportDatasets = [
    {
        type:        'participants',
        title:       '1. Dataset Participantes',
        description: 'Alias, carrera, ciclo, institución, niveles, XP y rachas.',
        icon:        Users,
        color:       'text-blue-400',
    },
    {
        type:        'habits_pomodoro',
        title:       '2. Hábitos y Pomodoro',
        description: 'Sesiones de foco, minutos completados e intenciones por usuario.',
        icon:        Timer,
        color:       'text-amber-400',
    },
    {
        type:        'telemetry',
        title:       '3. Dataset Telemetría',
        description: 'Log completo de eventos de comportamiento con usuario identificado.',
        icon:        BarChart2,
        color:       'text-emerald-400',
    },
    {
        type:        'epa_responses',
        title:       '4. Diagnóstico EPA',
        description: 'Respuestas del cuestionario inicial y postest (pre/post intervención).',
        icon:        CheckCircle2,
        color:       'text-purple-400',
    },
    {
        type:        'dropout',
        title:       '5. Riesgo de Deserción',
        description: 'Usuarios con 3+ días de inactividad con datos de perfil y ciclo.',
        icon:        AlertTriangle,
        color:       'text-rose-400',
    },
]
</script>

<template>
    <Head title="Panel de Administración — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-7xl space-y-5 pb-10">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <BaseCard class="p-5 border-l-4 border-l-primary-strong">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-display text-2xl font-bold tracking-tight text-content-primary">
                                Panel de Investigación y Administración
                            </h1>
                            <BaseBadge variant="primary" size="sm">Solo Admin</BaseBadge>
                        </div>
                        <p class="mt-1 text-xs text-content-secondary">
                            Monitoreo científico en tiempo real · Protección de datos Ley N.° 29733
                        </p>
                    </div>
                    <!-- Stats rápidos en header -->
                    <div class="flex gap-4 text-center">
                        <div>
                            <p class="font-display text-xl font-bold text-content-primary">{{ metrics.total_participants }}</p>
                            <p class="text-[10px] text-content-muted">Participantes</p>
                        </div>
                        <div>
                            <p class="font-display text-xl font-bold text-emerald-400">{{ metrics.active_users_today }}</p>
                            <p class="text-[10px] text-content-muted">Activos hoy</p>
                        </div>
                        <div>
                            <p class="font-display text-xl font-bold text-rose-400">{{ metrics.dropout_risk_count }}</p>
                            <p class="text-[10px] text-content-muted">En riesgo</p>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <!-- ── Tabs ────────────────────────────────────────────────────── -->
            <div class="flex gap-1.5 overflow-x-auto no-scrollbar">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="flex items-center gap-1.5 whitespace-nowrap px-4 py-2 text-xs font-semibold rounded-xl transition-all"
                    :class="[
                        activeTab === tab.id
                            ? 'bg-primary-strong text-white shadow-sm'
                            : 'bg-surface-raised/60 text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    ]"
                    @click="activeTab = tab.id"
                >
                    <component :is="tab.icon" :size="13" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- Tab 1: Dashboard General                                       -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'dashboard'" class="space-y-5">
                <!-- KPI Cards -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <BaseCard class="p-4 text-center space-y-1.5">
                        <Users class="mx-auto text-primary-strong" :size="20" />
                        <p class="font-display text-2xl font-bold text-content-primary">{{ metrics.total_participants }}</p>
                        <p class="text-xs text-content-secondary">Participantes Totales</p>
                    </BaseCard>

                    <BaseCard class="p-4 text-center space-y-1.5">
                        <Activity class="mx-auto text-emerald-400" :size="20" />
                        <p class="font-display text-2xl font-bold text-emerald-400">{{ metrics.active_users_today }}</p>
                        <p class="text-xs text-content-secondary">Activos Hoy</p>
                    </BaseCard>

                    <BaseCard class="p-4 text-center space-y-1.5">
                        <Timer class="mx-auto text-primary-strong" :size="20" />
                        <p class="font-display text-2xl font-bold text-primary-strong">{{ metrics.total_pomodoros }}</p>
                        <p class="text-xs text-content-secondary">Pomodoros Completados</p>
                    </BaseCard>

                    <BaseCard class="p-4 text-center space-y-1.5">
                        <ShieldAlert class="mx-auto text-rose-400" :size="20" />
                        <p class="font-display text-2xl font-bold text-rose-400">{{ metrics.dropout_risk_count }}</p>
                        <p class="text-xs text-content-secondary">Riesgo Deserción (3+ días)</p>
                    </BaseCard>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <BaseCard class="p-5 space-y-2">
                        <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                            <Flame class="text-orange-400" :size="15" />
                            Adherencia Media de Rachas
                        </h3>
                        <p class="text-3xl font-display font-bold text-primary-strong">
                            {{ metrics.avg_streak_days }}
                            <span class="text-sm font-normal text-content-secondary">días seguidos</span>
                        </p>
                        <p class="text-xs text-content-muted">Promedio global de constancia diaria en hábitos de estudio.</p>
                    </BaseCard>

                    <BaseCard class="p-5 space-y-2">
                        <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                            <CheckCircle2 class="text-emerald-400" :size="15" />
                            Hábitos Marcados en Total
                        </h3>
                        <p class="text-3xl font-display font-bold text-emerald-400">
                            {{ metrics.total_habits_done }}
                        </p>
                        <p class="text-xs text-content-muted">Total de ejecuciones de hábitos registradas en la intervención.</p>
                    </BaseCard>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- Tab 2: Participantes                                           -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'participants'" class="space-y-4">
                <!-- Búsqueda -->
                <BaseCard class="p-3">
                    <div class="flex items-center gap-2">
                        <Search :size="14" class="text-content-muted shrink-0" />
                        <input
                            v-model="participantSearch"
                            type="text"
                            placeholder="Buscar por alias, código o carrera…"
                            class="w-full bg-transparent text-xs text-content-primary placeholder:text-content-muted outline-none"
                        />
                        <span class="text-xs text-content-muted whitespace-nowrap">{{ filteredParticipants.length }} / {{ participants.length }}</span>
                    </div>
                </BaseCard>

                <BaseCard class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-raised text-content-secondary uppercase font-semibold border-b border-border-interactive/40 text-[10px]">
                                <tr>
                                    <th class="p-3">Código</th>
                                    <th class="p-3">Alias Público</th>
                                    <th class="p-3">Carrera</th>
                                    <th class="p-3">Ciclo</th>
                                    <th class="p-3">Institución</th>
                                    <th class="p-3 cursor-pointer hover:text-content-primary" @click="toggleSort('current_level')">
                                        <span class="flex items-center gap-1">
                                            Nivel
                                            <ArrowUpDown :size="10" />
                                        </span>
                                    </th>
                                    <th class="p-3">Fase</th>
                                    <th class="p-3 cursor-pointer hover:text-content-primary" @click="toggleSort('total_xp')">
                                        <span class="flex items-center gap-1">
                                            Total XP
                                            <ArrowUpDown :size="10" />
                                        </span>
                                    </th>
                                    <th class="p-3 cursor-pointer hover:text-content-primary" @click="toggleSort('current_streak')">
                                        <span class="flex items-center gap-1">
                                            Racha
                                            <ArrowUpDown :size="10" />
                                        </span>
                                    </th>
                                    <th class="p-3">Último Acceso</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-interactive/20">
                                <tr
                                    v-for="p in filteredParticipants"
                                    :key="p.participant_code"
                                    class="hover:bg-surface-raised/40"
                                >
                                    <td class="p-3 font-mono font-bold text-primary-strong">{{ p.participant_code }}</td>
                                    <td class="p-3 font-medium text-content-primary">{{ p.alias }}</td>
                                    <td class="p-3 text-content-secondary">{{ p.career }}</td>
                                    <td class="p-3 text-content-secondary">{{ p.cycle }}</td>
                                    <td class="p-3 text-content-muted">{{ p.institution_type }}</td>
                                    <td class="p-3">
                                        <span class="font-semibold text-primary-strong">Nv. {{ p.current_level }}</span>
                                    </td>
                                    <td class="p-3 text-content-muted">Fase {{ p.current_phase }}</td>
                                    <td class="p-3 font-semibold text-amber-400">{{ p.total_xp }} XP</td>
                                    <td class="p-3">
                                        <span class="flex items-center gap-1">
                                            <Flame :size="11" class="text-orange-400" />
                                            {{ p.current_streak }}d
                                        </span>
                                    </td>
                                    <td class="p-3 text-content-muted">{{ p.last_active_at }}</td>
                                </tr>
                                <tr v-if="filteredParticipants.length === 0">
                                    <td colspan="10" class="p-6 text-center text-xs text-content-muted">
                                        No se encontraron participantes con ese criterio.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>
            </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- Tab 3: Deserción                                               -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'dropout'" class="space-y-4">
                <BaseCard class="p-4 border-l-4 border-l-rose-500 bg-rose-500/5">
                    <h3 class="font-bold text-sm text-rose-400 flex items-center gap-2 mb-1">
                        <AlertTriangle :size="14" />
                        Monitoreo de Riesgo de Deserción
                    </h3>
                    <p class="text-xs text-content-secondary">
                        Estudiantes sin actividad registrada por 3 o más días consecutivos.
                        Indicador clave de la intervención de 66 días.
                    </p>
                </BaseCard>

                <BaseCard class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-raised text-content-secondary uppercase font-semibold border-b border-border-interactive/40 text-[10px]">
                                <tr>
                                    <th class="p-3">Código</th>
                                    <th class="p-3">Alias</th>
                                    <th class="p-3">Carrera</th>
                                    <th class="p-3">Ciclo</th>
                                    <th class="p-3">Días Inactivo</th>
                                    <th class="p-3">Nivel</th>
                                    <th class="p-3">Racha</th>
                                    <th class="p-3">Último Registro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-interactive/20">
                                <tr v-for="d in dropout" :key="d.participant_code" class="hover:bg-rose-500/5">
                                    <td class="p-3 font-mono font-bold text-rose-400">{{ d.participant_code }}</td>
                                    <td class="p-3 font-medium text-content-primary">{{ d.alias }}</td>
                                    <td class="p-3 text-content-secondary">{{ d.career }}</td>
                                    <td class="p-3 text-content-muted">{{ d.cycle }}</td>
                                    <td class="p-3">
                                        <span class="font-bold text-rose-400">{{ d.days_inactive }} días</span>
                                    </td>
                                    <td class="p-3 text-content-secondary">Nv. {{ d.current_level }}</td>
                                    <td class="p-3">
                                        <span class="flex items-center gap-1">
                                            <Flame :size="11" class="text-orange-400" />
                                            {{ d.current_streak }}d
                                        </span>
                                    </td>
                                    <td class="p-3 text-content-muted">{{ d.last_active_at }}</td>
                                </tr>
                                <tr v-if="dropout.length === 0">
                                    <td colspan="8" class="p-6 text-center text-xs text-content-muted">
                                        <CheckCircle2 class="mx-auto mb-1 text-emerald-400" :size="20" />
                                        ¡Excelente! Ningún participante supera los 3 días de inactividad actualmente.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>
            </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- Tab 4: Telemetría                                              -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'telemetry'" class="space-y-4">
                <!-- Sub-tabs -->
                <div class="flex gap-2">
                    <button
                        v-for="v in [{id:'log', label:'Log de Eventos'}, {id:'category', label:'Por Categoría'}, {id:'users', label:'Top Usuarios'}]"
                        :key="v.id"
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                        :class="telemetryView === v.id ? 'bg-primary-strong/20 text-primary-strong border border-primary-strong/40' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="telemetryView = v.id"
                    >{{ v.label }}</button>
                    <span class="ml-auto text-xs font-mono font-bold text-primary-strong self-center">
                        Total: {{ telemetry.total_events?.toLocaleString() }} eventos
                    </span>
                </div>

                <!-- Log de Eventos -->
                <BaseCard v-if="telemetryView === 'log'" class="p-0 overflow-hidden">
                    <div class="p-3 border-b border-border-interactive/30 flex items-center gap-2">
                        <Search :size="13" class="text-content-muted shrink-0" />
                        <input
                            v-model="telemetrySearch"
                            type="text"
                            placeholder="Buscar por alias, código, evento o categoría…"
                            class="w-full bg-transparent text-xs text-content-primary placeholder:text-content-muted outline-none"
                        />
                        <span class="text-[10px] text-content-muted whitespace-nowrap">{{ filteredEvents.length }} / {{ telemetry.recent_events?.length ?? 0 }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-raised text-content-secondary uppercase font-semibold border-b border-border-interactive/40 text-[10px]">
                                <tr>
                                    <th class="p-3">Código</th>
                                    <th class="p-3">Alias</th>
                                    <th class="p-3">Evento</th>
                                    <th class="p-3">Categoría</th>
                                    <th class="p-3">Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-interactive/20">
                                <tr v-for="(e, i) in filteredEvents" :key="i" class="hover:bg-surface-raised/40">
                                    <td class="p-3 font-mono text-primary-strong">{{ e.participant_code }}</td>
                                    <td class="p-3 font-medium text-content-primary">{{ e.alias }}</td>
                                    <td class="p-3 font-mono text-content-primary">{{ e.event_name }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-surface-raised text-content-secondary">
                                            {{ e.event_category }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-content-muted">{{ e.occurred_at }}</td>
                                </tr>
                                <tr v-if="filteredEvents.length === 0">
                                    <td colspan="5" class="p-6 text-center text-xs text-content-muted">Sin resultados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>

                <!-- Por Categoría + Por Día -->
                <div v-else-if="telemetryView === 'category'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <BaseCard class="p-4 space-y-2">
                        <h4 class="text-xs font-semibold text-content-secondary uppercase flex items-center gap-1.5">
                            <TrendingUp :size="12" /> Por Categoría
                        </h4>
                        <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
                            <div
                                v-for="cat in telemetry.by_category"
                                :key="cat.category"
                                class="flex items-center justify-between p-2 rounded-lg bg-surface-raised/40 text-xs"
                            >
                                <span class="font-mono text-content-primary">{{ cat.category }}</span>
                                <span class="font-bold text-primary-strong">{{ cat.count }}</span>
                            </div>
                        </div>
                    </BaseCard>

                    <BaseCard class="p-4 space-y-2">
                        <h4 class="text-xs font-semibold text-content-secondary uppercase flex items-center gap-1.5">
                            <CalendarDays :size="12" /> Por Fecha (Últimos 14 días)
                        </h4>
                        <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
                            <div
                                v-for="day in telemetry.by_day"
                                :key="day.date"
                                class="flex items-center justify-between p-2 rounded-lg bg-surface-raised/40 text-xs"
                            >
                                <span class="font-mono text-content-primary">{{ day.date }}</span>
                                <span class="font-bold text-emerald-400">{{ day.count }} eventos</span>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Top Usuarios -->
                <BaseCard v-else-if="telemetryView === 'users'" class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-border-interactive/30">
                        <h4 class="text-xs font-semibold text-content-primary flex items-center gap-1.5">
                            <User :size="13" /> Top 20 Usuarios por Volumen de Eventos
                        </h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-raised text-content-secondary uppercase font-semibold border-b border-border-interactive/40 text-[10px]">
                                <tr>
                                    <th class="p-3">#</th>
                                    <th class="p-3">Código</th>
                                    <th class="p-3">Alias</th>
                                    <th class="p-3">Total Eventos</th>
                                    <th class="p-3">Barra</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-interactive/20">
                                <tr v-for="(u, i) in telemetry.top_users" :key="u.participant_code" class="hover:bg-surface-raised/40">
                                    <td class="p-3 font-bold text-content-muted">{{ i + 1 }}</td>
                                    <td class="p-3 font-mono text-primary-strong">{{ u.participant_code }}</td>
                                    <td class="p-3 font-medium text-content-primary">{{ u.alias }}</td>
                                    <td class="p-3 font-bold text-amber-400">{{ u.total_events }}</td>
                                    <td class="p-3 w-40">
                                        <div class="h-1.5 bg-surface-raised rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary-strong rounded-full"
                                                :style="{width: `${Math.round((u.total_events / (telemetry.top_users[0]?.total_events || 1)) * 100)}%`}"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>
            </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- Tab 5: Exportar Datasets                                       -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'export'" class="space-y-4">
                <BaseCard class="p-5 space-y-1">
                    <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                        <Download :size="14" class="text-primary-strong" />
                        Exportar Datasets Científicos (CSV)
                    </h3>
                    <p class="text-xs text-content-secondary">
                        Datasets listos para análisis estadístico en R / SPSS / Python. Incluyen BOM UTF-8 para compatibilidad con Excel.
                    </p>
                </BaseCard>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button
                        v-for="ds in exportDatasets"
                        :key="ds.type"
                        type="button"
                        class="p-5 rounded-xl border border-border-interactive bg-surface-raised hover:bg-surface-raised/60 hover:border-primary-strong/50 transition-all text-left space-y-3 group"
                        @click="downloadCsv(ds.type)"
                    >
                        <component :is="ds.icon" :size="22" :class="ds.color" class="group-hover:scale-110 transition-transform" />
                        <div>
                            <strong class="text-xs block text-content-primary font-bold">{{ ds.title }}</strong>
                            <p class="text-[11px] text-content-muted mt-0.5">{{ ds.description }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-primary-strong group-hover:underline">
                            <Download :size="11" />
                            Descargar CSV
                        </div>
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════ -->
            <!-- Tab 6: Salud del Sistema                                       -->
            <!-- ══════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'health'" class="space-y-4">
                <BaseCard class="p-5 space-y-4">
                    <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                        <Activity :size="14" class="text-emerald-400" />
                        Estado Técnico del Sistema
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3 rounded-xl bg-surface-raised space-y-1">
                            <span class="text-content-muted block">Versión PHP / Laravel:</span>
                            <strong class="font-mono text-content-primary">PHP {{ health.php_version }} / {{ health.laravel_version }}</strong>
                        </div>

                        <div class="p-3 rounded-xl bg-surface-raised space-y-1">
                            <span class="text-content-muted block">Base de Datos MariaDB:</span>
                            <strong class="font-mono text-emerald-400">{{ health.db_status }} ({{ health.db_version }})</strong>
                        </div>

                        <div class="p-3 rounded-xl bg-surface-raised space-y-1">
                            <span class="text-content-muted block">Capacidad Hosting:</span>
                            <strong class="font-mono text-primary-strong">{{ health.hostinger_workers_capacity }}</strong>
                        </div>

                        <div class="p-3 rounded-xl bg-surface-raised space-y-1">
                            <span class="text-content-muted block">Consultas IA Usadas Hoy:</span>
                            <strong class="font-mono text-content-primary">{{ health.ai_consultations_today }} consultas</strong>
                        </div>
                    </div>
                </BaseCard>
            </div>

        </div>
    </AppLayout>
</template>
