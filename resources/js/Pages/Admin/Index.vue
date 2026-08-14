<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import BaseButton from '@/Components/ui/BaseButton.vue'
import BaseBadge from '@/Components/ui/BaseBadge.vue'

const props = defineProps({
    metrics: { type: Object, required: true },
    participants: { type: Array, default: () => [] },
    dropout: { type: Array, default: () => [] },
    telemetry: { type: Object, required: true },
    health: { type: Object, required: true },
})

const activeTab = ref('dashboard')

const tabs = [
    { id: 'dashboard', label: '📊 Dashboard' },
    { id: 'participants', label: '🎓 Participantes' },
    { id: 'dropout', label: '⚠️ Deserción (3+ días)' },
    { id: 'telemetry', label: '📈 Telemetría' },
    { id: 'export', label: '📥 Exportar Datasets' },
    { id: 'health', label: '⚡ Salud del Sistema' },
]
</script>

<template>
    <Head title="Panel de Administración de Investigación — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Banner -->
            <BaseCard class="p-6 border-l-4 border-l-primary-strong">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-display text-2xl font-bold tracking-tight text-content-primary">
                                Panel de Investigación y Administración
                            </h1>
                            <BaseBadge variant="primary" size="sm">Solo Lectura</BaseBadge>
                        </div>
                        <p class="mt-1 text-sm text-content-secondary">
                            Monitoreo científico en tiempo real de la intervención de 66 días. Protección de datos Ley N.° 29733 (Sin PII).
                        </p>
                    </div>

                    <Link :href="route('dashboard')">
                        <BaseButton variant="ghost" size="sm">
                            ← Volver al Dashboard Estudiante
                        </BaseButton>
                    </Link>
                </div>
            </BaseCard>

            <!-- Navigation Tabs -->
            <div class="flex gap-2 overflow-x-auto border-b border-border-interactive/50 pb-2 no-scrollbar">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="whitespace-nowrap px-4 py-2 text-xs font-semibold rounded-xl transition-all"
                    :class="[
                        activeTab === tab.id
                            ? 'bg-primary-strong text-white shadow-sm'
                            : 'bg-surface-raised/60 text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    ]"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab 1: Dashboard General -->
            <div v-if="activeTab === 'dashboard'" class="space-y-6">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <BaseCard class="p-4 text-center space-y-1">
                        <span class="text-2xl">👥</span>
                        <p class="font-display text-2xl font-bold text-content-primary">{{ metrics.total_participants }}</p>
                        <p class="text-xs text-content-secondary">Participantes Totales</p>
                    </BaseCard>

                    <BaseCard class="p-4 text-center space-y-1">
                        <span class="text-2xl">⚡</span>
                        <p class="font-display text-2xl font-bold text-emerald-400">{{ metrics.active_users_today }}</p>
                        <p class="text-xs text-content-secondary">Activos Hoy</p>
                    </BaseCard>

                    <BaseCard class="p-4 text-center space-y-1">
                        <span class="text-2xl">⏱️</span>
                        <p class="font-display text-2xl font-bold text-primary-strong">{{ metrics.total_pomodoros }}</p>
                        <p class="text-xs text-content-secondary">Pomodoros Completados</p>
                    </BaseCard>

                    <BaseCard class="p-4 text-center space-y-1">
                        <span class="text-2xl">🚨</span>
                        <p class="font-display text-2xl font-bold text-rose-400">{{ metrics.dropout_risk_count }}</p>
                        <p class="text-xs text-content-secondary">Riesgo Deserción (3+ días)</p>
                    </BaseCard>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <BaseCard class="p-5 space-y-3">
                        <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                            <span>🔥 Adherencia Media de Rachas</span>
                        </h3>
                        <p class="text-3xl font-display font-bold text-primary-strong">
                            {{ metrics.avg_streak_days }} <span class="text-sm font-normal text-content-secondary">días seguidos</span>
                        </p>
                        <p class="text-xs text-content-muted">
                            Promedio global de constancia diaria en hábitos de estudio.
                        </p>
                    </BaseCard>

                    <BaseCard class="p-5 space-y-3">
                        <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                            <span>✅ Hábitos Marcados</span>
                        </h3>
                        <p class="text-3xl font-display font-bold text-emerald-400">
                            {{ metrics.total_habits_done }}
                        </p>
                        <p class="text-xs text-content-muted">
                            Total de ejecuciones de hábitos registradas en la intervención.
                        </p>
                    </BaseCard>
                </div>
            </div>

            <!-- Tab 2: Participantes (Sin PII) -->
            <div v-else-if="activeTab === 'participants'" class="space-y-4">
                <BaseCard class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-border-interactive/40 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-content-primary">Dataset de Participantes (Anónimo)</h3>
                        <span class="text-xs text-content-muted">Total: {{ participants.length }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-raised text-content-secondary uppercase font-semibold border-b border-border-interactive/40">
                                <tr>
                                    <th class="p-3">Código Seudónimo</th>
                                    <th class="p-3">Nivel</th>
                                    <th class="p-3">Fase Avatar</th>
                                    <th class="p-3">Total XP</th>
                                    <th class="p-3">Racha</th>
                                    <th class="p-3">Último Acceso</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-interactive/20">
                                <tr v-for="p in participants" :key="p.participant_code" class="hover:bg-surface-raised/40">
                                    <td class="p-3 font-mono font-bold text-primary-strong">{{ p.participant_code }}</td>
                                    <td class="p-3">Nivel {{ p.current_level }}</td>
                                    <td class="p-3">Fase {{ p.current_phase }}</td>
                                    <td class="p-3 font-semibold">{{ p.total_xp }} XP</td>
                                    <td class="p-3">🔥 {{ p.current_streak }} días</td>
                                    <td class="p-3 text-content-muted">{{ p.last_active_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>
            </div>

            <!-- Tab 3: Deserción (3+ días sin actividad) -->
            <div v-else-if="activeTab === 'dropout'" class="space-y-4">
                <BaseCard class="p-5 border-l-4 border-l-rose-500">
                    <h3 class="font-bold text-sm text-rose-300 flex items-center gap-2 mb-1">
                        <span>⚠️ Monitoreo de Riesgo de Deserción</span>
                    </h3>
                    <p class="text-xs text-content-secondary">
                        Indicador clave de la intervención: estudiantes sin actividad registrada por 3 o más días consecutivos.
                    </p>
                </BaseCard>

                <BaseCard class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-raised text-content-secondary uppercase font-semibold border-b border-border-interactive/40">
                                <tr>
                                    <th class="p-3">Código Seudónimo</th>
                                    <th class="p-3">Días Inactivo</th>
                                    <th class="p-3">Nivel Alcanzado</th>
                                    <th class="p-3">Última Racha</th>
                                    <th class="p-3">Último Registro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-interactive/20">
                                <tr v-for="d in dropout" :key="d.participant_code" class="hover:bg-rose-500/5">
                                    <td class="p-3 font-mono font-bold text-rose-400">{{ d.participant_code }}</td>
                                    <td class="p-3 font-bold text-rose-400">{{ d.days_inactive }} días sin ingresar</td>
                                    <td class="p-3">Nivel {{ d.current_level }}</td>
                                    <td class="p-3">🔥 {{ d.current_streak }} días</td>
                                    <td class="p-3 text-content-muted">{{ d.last_active_at }}</td>
                                </tr>
                                <tr v-if="dropout.length === 0">
                                    <td colspan="5" class="p-6 text-center text-xs text-content-muted">
                                        🎉 ¡Excelente! Ningún participante supera los 3 días de inactividad actualmente.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>
            </div>

            <!-- Tab 4: Telemetría -->
            <div v-else-if="activeTab === 'telemetry'" class="space-y-6">
                <BaseCard class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-sm text-content-primary">Volumen de Eventos Registrados</h3>
                        <span class="text-xs font-mono font-bold text-primary-strong">Total: {{ telemetry.total_events }} eventos</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <h4 class="text-xs font-semibold text-content-secondary uppercase">Por Categoría</h4>
                            <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                                <div v-for="cat in telemetry.by_category" :key="cat.category" class="flex items-center justify-between p-2 rounded-lg bg-surface-raised/40 text-xs">
                                    <span class="font-mono text-content-primary">{{ cat.category }}</span>
                                    <span class="font-bold text-primary-strong">{{ cat.count }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-xs font-semibold text-content-secondary uppercase">Por Fecha (Últimos 14 días)</h4>
                            <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                                <div v-for="day in telemetry.by_day" :key="day.date" class="flex items-center justify-between p-2 rounded-lg bg-surface-raised/40 text-xs">
                                    <span class="font-mono text-content-primary">{{ day.date }}</span>
                                    <span class="font-bold text-emerald-400">{{ day.count }} ev</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </BaseCard>
            </div>

            <!-- Tab 5: Exportación de CSV -->
            <div v-else-if="activeTab === 'export'" class="space-y-4">
                <BaseCard class="p-6 space-y-4">
                    <div>
                        <h3 class="font-bold text-sm text-content-primary">Exportar Dataset Científico (Archivos CSV)</h3>
                        <p class="mt-1 text-xs text-content-secondary">
                            Descarga los datasets listos para el análisis estadístico del artículo en R / SPSS / Python.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a :href="route('admin.export', 'participants')" target="_blank" class="p-4 rounded-xl border border-border-interactive bg-surface-raised hover:bg-surface transition-all text-center space-y-2 group">
                            <span class="text-3xl block group-hover:scale-110 transition-transform">📄</span>
                            <strong class="text-xs block text-content-primary font-bold">1. Dataset Participantes</strong>
                            <p class="text-[11px] text-content-muted">Códigos, niveles, XP y rachas.</p>
                            <BaseButton variant="primary" size="sm" class="w-full text-xs">Descargar CSV</BaseButton>
                        </a>

                        <a :href="route('admin.export', 'habits_pomodoro')" target="_blank" class="p-4 rounded-xl border border-border-interactive bg-surface-raised hover:bg-surface transition-all text-center space-y-2 group">
                            <span class="text-3xl block group-hover:scale-110 transition-transform">⏱️</span>
                            <strong class="text-xs block text-content-primary font-bold">2. Hábitos y Pomodoro</strong>
                            <p class="text-[11px] text-content-muted">Sesiones de foco e intenciones.</p>
                            <BaseButton variant="primary" size="sm" class="w-full text-xs">Descargar CSV</BaseButton>
                        </a>

                        <a :href="route('admin.export', 'telemetry')" target="_blank" class="p-4 rounded-xl border border-border-interactive bg-surface-raised hover:bg-surface transition-all text-center space-y-2 group">
                            <span class="text-3xl block group-hover:scale-110 transition-transform">📈</span>
                            <strong class="text-xs block text-content-primary font-bold">3. Dataset Telemetría</strong>
                            <p class="text-[11px] text-content-muted">Eventos de comportamiento completo.</p>
                            <BaseButton variant="primary" size="sm" class="w-full text-xs">Descargar CSV</BaseButton>
                        </a>

                        <a :href="route('admin.export', 'epa_responses')" target="_blank" class="p-4 rounded-xl border border-border-interactive bg-surface-raised hover:bg-surface transition-all text-center space-y-2 group">
                            <span class="text-3xl block group-hover:scale-110 transition-transform">📝</span>
                            <strong class="text-xs block text-content-primary font-bold">4. Diagnóstico EPA</strong>
                            <p class="text-[11px] text-content-muted">Respuestas de evaluación inicial.</p>
                            <BaseButton variant="primary" size="sm" class="w-full text-xs">Descargar CSV</BaseButton>
                        </a>
                    </div>
                </BaseCard>
            </div>

            <!-- Tab 6: Salud del Sistema -->
            <div v-else-if="activeTab === 'health'" class="space-y-4">
                <BaseCard class="p-6 space-y-4">
                    <h3 class="font-bold text-sm text-content-primary">Estado Técnico del Sistema</h3>

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
                            <span class="text-content-muted block">Capacidad Hosting Compartido:</span>
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
