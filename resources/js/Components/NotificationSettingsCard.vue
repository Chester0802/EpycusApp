<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import { useNotificationEngine } from '@/composables/useNotificationEngine';
import { usePwaInstall } from '@/composables/usePwaInstall';
import {
    Bell,
    BellOff,
    Clock,
    Calendar,
    Flame,
    Target,
    Droplets,
    Volume2,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    Send,
    Smartphone,
    Download,
    ChevronDown,
    Laptop,
    HelpCircle
} from '@lucide/vue';

const page = usePage();
const { isInstallable, isInstalled, promptInstall } = usePwaInstall();
const {
    isSupported,
    isGranted,
    isDenied,
    isPrompt,
    permissionStatus,
    requestPermission,
    testNotification
} = useNotificationEngine();

const isOpenNotifications = ref(false);
const isOpenPwa = ref(false);

const masterEnabled = ref(Boolean(page.props.preferences?.notificationsEnabled));
const rawSettings = page.props.preferences?.notificationSettings || {};

const channels = ref({
    pomodoro: rawSettings.pomodoro ?? true,
    calendar: rawSettings.calendar ?? true,
    habits_streak: rawSettings.habits_streak ?? true,
    daily_plan: rawSettings.daily_plan ?? true,
    hydration: rawSettings.hydration ?? false,
    sound_enabled: rawSettings.sound_enabled ?? true,
});

const isSaving = ref(false);
const saveFeedback = ref(false);
const isTesting = ref(false);

const activeChannelsCount = computed(() => {
    if (!masterEnabled.value) return 0;
    return Object.entries(channels.value).filter(([k, v]) => k !== 'sound_enabled' && Boolean(v)).length;
});

function savePreferences() {
    isSaving.value = true;
    router.patch(route('preferences.update'), {
        notifications_enabled: masterEnabled.value,
        notification_settings: channels.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isSaving.value = false;
            saveFeedback.value = true;
            setTimeout(() => {
                saveFeedback.value = false;
            }, 2500);
        },
        onError: () => {
            isSaving.value = false;
        },
    });
}

function toggleMaster() {
    masterEnabled.value = !masterEnabled.value;
    if (masterEnabled.value && isPrompt.value) {
        requestPermission();
    }
    savePreferences();
}

function toggleChannel(key) {
    if (!masterEnabled.value) return;
    channels.value[key] = !channels.value[key];
    savePreferences();
}

async function handleTest() {
    isTesting.value = true;
    try {
        await testNotification();
    } finally {
        setTimeout(() => {
            isTesting.value = false;
        }, 1000);
    }
}

async function handleEnablePermission() {
    await requestPermission();
}

const channelList = [
    {
        key: 'pomodoro',
        title: 'Pomodoro y Descansos',
        desc: 'Avisos al culminar bloques de estudio de 25 min y tiempos de descanso.',
        icon: Clock,
        badge: 'Enfoque',
    },
    {
        key: 'calendar',
        title: 'Calendario y Horario de Clases',
        desc: 'Notificación 15 minutos antes de que empiece tu clase o examen.',
        icon: Calendar,
        badge: 'Puntualidad',
    },
    {
        key: 'habits_streak',
        title: 'Protección de Racha de Hábitos',
        desc: 'Alerta nocturna si tienes hábitos pendientes para no romper tu racha.',
        icon: Flame,
        badge: 'Racha',
    },
    {
        key: 'daily_plan',
        title: 'Plan Diario y Misiones',
        desc: 'Resumen matutino de tus misiones y cambios de bloque prioritarios.',
        icon: Target,
        badge: 'Metas',
    },
    {
        key: 'hydration',
        title: 'Pausas Activas e Hidratación',
        desc: 'Recordatorios suaves para tomar agua y relajar la vista al estudiar.',
        icon: Droplets,
        badge: 'Salud',
    },
    {
        key: 'sound_enabled',
        title: 'Efectos de Sonido',
        desc: 'Tono musical suave de dos acordes al recibir cada notificación.',
        icon: Volume2,
        badge: 'Audio',
    },
];
</script>

<template>
    <div class="space-y-4">
        <!-- ── 1. ACORDEÓN: NOTIFICACIONES Y ALERTAS ──────────────────────────────── -->
        <BaseCard class="overflow-hidden transition-all duration-300">
            <!-- Encabezado Plegable -->
            <div
                @click="isOpenNotifications = !isOpenNotifications"
                class="p-5 flex items-center justify-between gap-4 cursor-pointer select-none hover:bg-surface-raised/60 transition-colors"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        :class="[
                            masterEnabled ? 'bg-primary/10 text-primary-strong' : 'bg-surface-sunken text-content-muted',
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors'
                        ]"
                    >
                        <Bell v-if="masterEnabled" class="h-5 w-5" />
                        <BellOff v-else class="h-5 w-5" />
                    </div>

                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-bold text-content-primary">
                                Notificaciones y Alertas
                            </h2>
                            <BaseBadge v-if="masterEnabled" variant="primary" size="sm">
                                {{ activeChannelsCount }} activas
                            </BaseBadge>
                            <BaseBadge v-else variant="neutral" size="sm">
                                Desactivadas
                            </BaseBadge>
                        </div>
                        <p class="text-xs text-content-secondary line-clamp-1">
                            {{ isOpenNotifications ? 'Toca para ocultar opciones' : 'Horarios, Pomodoro, rachas y avisos configurables' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <!-- Switch Maestro (detiene propagación para no plegar/desplegar al tocar el switch) -->
                    <button
                        type="button"
                        @click.stop="toggleMaster"
                        :class="[
                            masterEnabled ? 'bg-primary-strong' : 'bg-surface-sunken border border-border',
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong'
                        ]"
                        role="switch"
                        :aria-checked="masterEnabled"
                        title="Activar o desactivar todas las notificaciones"
                    >
                        <span
                            :class="[
                                masterEnabled ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-content-muted',
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full shadow-md transition duration-200 ease-in-out my-auto'
                            ]"
                        />
                    </button>

                    <!-- Icono Flecha Desplegable -->
                    <div
                        :class="[
                            isOpenNotifications ? 'rotate-180 text-primary-strong' : 'text-content-muted',
                            'transition-transform duration-300 p-1'
                        ]"
                    >
                        <ChevronDown class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <!-- Contenido Desplegable de Notificaciones -->
            <div
                v-if="isOpenNotifications"
                class="px-5 pb-5 pt-2 border-t border-border/70 space-y-4 animate-in fade-in duration-200"
            >
                <!-- Estado de Permisos del Navegador -->
                <div class="p-4 rounded-2xl bg-surface-raised border border-border/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div v-if="isGranted" class="text-success flex items-center gap-1.5 text-xs font-semibold">
                            <CheckCircle2 class="h-4 w-4 shrink-0" />
                            <span>Permiso del navegador concedido</span>
                        </div>
                        <div v-else-if="isDenied" class="text-danger flex items-center gap-1.5 text-xs font-semibold">
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            <span>Notificaciones bloqueadas en el navegador</span>
                        </div>
                        <div v-else class="text-warning flex items-center gap-1.5 text-xs font-semibold">
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            <span>Permiso pendiente de confirmación</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <BaseButton
                            v-if="!isGranted && isSupported"
                            size="sm"
                            variant="primary"
                            @click="handleEnablePermission"
                        >
                            <Sparkles class="h-3.5 w-3.5 mr-1" />
                            Conceder Permiso
                        </BaseButton>

                        <BaseButton
                            size="sm"
                            variant="secondary"
                            :disabled="!masterEnabled || isTesting"
                            @click="handleTest"
                        >
                            <Send class="h-3.5 w-3.5 mr-1" />
                            {{ isTesting ? 'Enviando...' : 'Probar Notificación' }}
                        </BaseButton>
                    </div>
                </div>

                <!-- Canales Granulares -->
                <div class="space-y-3 pt-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-content-muted px-1">
                        Canales de Notificación
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div
                            v-for="item in channelList"
                            :key="item.key"
                            @click="toggleChannel(item.key)"
                            :class="[
                                masterEnabled ? 'cursor-pointer hover:border-primary-strong/50 hover:bg-surface-sunken/40' : 'opacity-50 cursor-not-allowed',
                                channels[item.key] && masterEnabled ? 'border-primary-strong/40 bg-primary/5' : 'border-border/60 bg-surface-raised',
                                'p-4 rounded-2xl border transition-all duration-200 flex items-start justify-between gap-3 select-none'
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    :class="[
                                        channels[item.key] && masterEnabled ? 'bg-primary-strong text-white' : 'bg-surface-sunken text-content-muted',
                                        'h-9 w-9 rounded-xl flex items-center justify-center shrink-0 transition-colors'
                                    ]"
                                >
                                    <component :is="item.icon" class="h-4 w-4" />
                                </div>
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-content-primary">
                                            {{ item.title }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-content-secondary leading-relaxed">
                                        {{ item.desc }}
                                    </p>
                                </div>
                            </div>

                            <!-- Mini Toggle Check -->
                            <div class="pt-0.5 shrink-0">
                                <div
                                    :class="[
                                        channels[item.key] && masterEnabled ? 'bg-primary-strong border-primary-strong' : 'bg-surface-sunken border-border',
                                        'h-5 w-5 rounded-lg border flex items-center justify-center transition-colors'
                                    ]"
                                >
                                    <CheckCircle2
                                        v-if="channels[item.key] && masterEnabled"
                                        class="h-3.5 w-3.5 text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Indicador de guardado -->
                <div class="flex items-center justify-end h-5">
                    <span
                        v-if="saveFeedback"
                        class="text-xs font-semibold text-success flex items-center gap-1 transition-opacity"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        Preferencias guardadas
                    </span>
                </div>
            </div>
        </BaseCard>


        <!-- ── 2. ACORDEÓN: INSTALAR APP PWA ──────────────────────────────────────── -->
        <BaseCard class="overflow-hidden transition-all duration-300">
            <!-- Encabezado Plegable -->
            <div
                @click="isOpenPwa = !isOpenPwa"
                class="p-5 flex items-center justify-between gap-4 cursor-pointer select-none hover:bg-surface-raised/60 transition-colors"
            >
                <div class="flex items-center gap-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-strong text-white shadow-sm">
                        <Smartphone class="h-5 w-5" />
                    </div>

                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-bold text-content-primary">
                                Aplicación para Celular y PC
                            </h2>
                            <BaseBadge v-if="isInstalled" variant="success" size="sm">
                                Instalada
                            </BaseBadge>
                            <BaseBadge v-else-if="isInstallable" variant="primary" size="sm">
                                Lista para instalar
                            </BaseBadge>
                        </div>
                        <p class="text-xs text-content-secondary line-clamp-1">
                            {{ isOpenPwa ? 'Toca para ocultar detalles' : 'Pantalla completa, modo sin conexión y acceso directo' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <BaseButton
                        v-if="isInstallable"
                        size="sm"
                        variant="primary"
                        @click.stop="promptInstall"
                    >
                        <Download class="h-3.5 w-3.5 mr-1" />
                        Instalar
                    </BaseButton>

                    <div
                        :class="[
                            isOpenPwa ? 'rotate-180 text-primary-strong' : 'text-content-muted',
                            'transition-transform duration-300 p-1'
                        ]"
                    >
                        <ChevronDown class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <!-- Contenido Desplegable de Instalación PWA -->
            <div
                v-if="isOpenPwa"
                class="px-5 pb-5 pt-2 border-t border-border/70 space-y-4 animate-in fade-in duration-200"
            >
                <p class="text-xs text-content-secondary leading-relaxed">
                    Epycus es una Progressive Web App (PWA). Puedes instalarla directamente en tu dispositivo sin ocupar espacio de almacenamiento y sin pasar por tiendas de aplicaciones.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                    <!-- Android -->
                    <div class="p-3.5 rounded-2xl bg-surface-raised border border-border/60 space-y-1.5">
                        <div class="flex items-center gap-2 font-bold text-xs text-content-primary">
                            <Smartphone class="h-4 w-4 text-primary-strong" />
                            <span>Android (Chrome)</span>
                        </div>
                        <p class="text-[11px] text-content-secondary leading-relaxed">
                            Toca el menú de 3 puntos (⋮) arriba a la derecha y selecciona <strong>"Instalar aplicación"</strong> o <strong>"Añadir a pantalla de inicio"</strong>.
                        </p>
                    </div>

                    <!-- iOS Safari -->
                    <div class="p-3.5 rounded-2xl bg-surface-raised border border-border/60 space-y-1.5">
                        <div class="flex items-center gap-2 font-bold text-xs text-content-primary">
                            <Smartphone class="h-4 w-4 text-primary-strong" />
                            <span>iPhone / iPad (Safari)</span>
                        </div>
                        <p class="text-[11px] text-content-secondary leading-relaxed">
                            Toca el botón <strong>Compartir (⎋)</strong> abajo en Safari y selecciona <strong>"Añadir a pantalla de inicio"</strong>.
                        </p>
                    </div>

                    <!-- Laptop / PC -->
                    <div class="p-3.5 rounded-2xl bg-surface-raised border border-border/60 space-y-1.5">
                        <div class="flex items-center gap-2 font-bold text-xs text-content-primary">
                            <Laptop class="h-4 w-4 text-primary-strong" />
                            <span>Laptop (Chrome / Edge)</span>
                        </div>
                        <p class="text-[11px] text-content-secondary leading-relaxed">
                            Haz clic en el icono de <strong>pantalla con flecha</strong> en la barra de direcciones o menú (⋮) &gt; <strong>"Instalar Epycus"</strong>.
                        </p>
                    </div>
                </div>

                <div v-if="isInstallable" class="pt-2 flex justify-end">
                    <BaseButton
                        size="sm"
                        variant="primary"
                        @click="promptInstall"
                    >
                        <Download class="h-4 w-4 mr-1.5" />
                        Instalar en este Dispositivo
                    </BaseButton>
                </div>
            </div>
        </BaseCard>
    </div>
</template>
