<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';
import { useTelemetry } from '@/Composables/useTelemetry';
import { usePomodoroState } from '@/Composables/usePomodoroState';

const { track } = useTelemetry();

function csrfHeaderLocal() {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const props = defineProps({
    session:       { type: Object,  required: true },
    messages:      { type: Array,   default: () => [] },
    participants:  { type: Array,   default: () => [] },
    lastMessageId: { type: Number,  default: 0 },
    userId:        { type: Number,  required: true },
    missions:      { type: Array,   default: () => [] },
});

// ── Misiones ────────────────────────────────────────────────────────────────
const selectedMissionId = ref(null);
const expandedMissionId = ref(null);
const subtaskBusy = ref({});
const showMissions = ref(false);

function selectMission(id) {
    selectedMissionId.value = selectedMissionId.value === id ? null : id;
}

function toggleExpandMission(id) {
    expandedMissionId.value = expandedMissionId.value === id ? null : id;
}

async function toggleSubtask(missionId, subtask) {
    subtaskBusy.value[subtask.id] = true;
    try {
        const res = await fetch(
            route('missions.subtasks.toggle', { id: missionId, subtaskId: subtask.id }),
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfHeaderLocal()
                }
            }
        );
        if (res.ok) {
            subtask.is_completed = !subtask.is_completed;
        }
    } finally {
        subtaskBusy.value[subtask.id] = false;
    }
}

// ── Chat & Polling ──────────────────────────────────────────────────────────
const chatMessages     = ref(props.messages);
const chatParticipants = ref(props.participants);
const activePomodoros  = ref([]);
const currentLastId    = ref(props.lastMessageId);
const newMessage       = ref('');
const isSending        = ref(false);
const chatContainer    = ref(null);
const pollInterval     = ref(null);
const draggingId       = ref(null);
const sceneRef         = ref(null);

const backgrounds = [
    { id: 1, name: 'Cafetería',  path: '/assets/images/rooms/cafeteria.webp' },
    { id: 2, name: 'Biblioteca', path: '/assets/images/rooms/biblioteca.webp' },
];
const currentBg = computed(() => backgrounds.find(b => b.id === props.session.id) || backgrounds[0]);

function postJson(url, body = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const xsrf = (() => {
        const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    })();
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-XSRF-TOKEN': xsrf
        },
        body: JSON.stringify(body)
    });
}

function sendMessage() {
    if (!newMessage.value.trim() || isSending.value) return;
    isSending.value = true;
    postJson(route('study-groups.messages', props.session.id), { body: newMessage.value.trim() })
        .then(res => {
            if (res.ok) return res.json();
            throw new Error('Error al enviar');
        })
        .then(msg => {
            chatMessages.value.push(msg);
            if (msg.id > currentLastId.value) currentLastId.value = msg.id;
            newMessage.value = '';
            scrollToBottom();
            track('group_chat.message_sent', 'study_groups', {
                session_id: props.session.id,
                message_length: msg.body?.length ?? 0
            });
        })
        .catch(() => {})
        .finally(() => {
            isSending.value = false;
        });
}

function poll() {
    if (document.visibilityState === 'hidden') return;
    fetch(route('study-groups.poll', props.session.id) + '?since=' + currentLastId.value, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(res => res.json())
        .then(data => {
            if (data.messages?.length) {
                for (const msg of data.messages) {
                    chatMessages.value.push(msg);
                    if (msg.id > currentLastId.value) currentLastId.value = msg.id;
                }
                scrollToBottom();
            }
            if (data.participants) {
                for (const up of data.participants) {
                    const idx = chatParticipants.value.findIndex(p => p.id === up.id);
                    if (idx !== -1) {
                        if (up.id !== props.userId || draggingId.value !== props.userId) {
                            chatParticipants.value[idx] = up;
                        }
                    } else {
                        chatParticipants.value.push(up);
                    }
                }
                chatParticipants.value = chatParticipants.value.filter(p =>
                    data.participants.some(up => up.id === p.id)
                );
            }
            if (data.active_pomodoros) {
                activePomodoros.value = data.active_pomodoros;
            }
        })
        .catch(() => {});
}

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}

function leaveSession(isAsync = false) {
    track('group_session.left', 'study_groups', { session_id: props.session.id });
    if (isAsync) {
        postJson(route('study-groups.leave', props.session.id));
    } else {
        router.post(route('study-groups.leave', props.session.id));
    }
}

// ── Arrastre de Avatar de Usuario ──────────────────────────────────────────
function handleAvatarPointerDown(e, pid) {
    if (pid !== props.userId) return;
    draggingId.value = pid;
    try {
        e.target.setPointerCapture(e.pointerId);
    } catch (_) {}
}

function handleAvatarPointerMove(e) {
    if (draggingId.value !== props.userId || !sceneRef.value) return;
    const rect = sceneRef.value.getBoundingClientRect();
    const p = chatParticipants.value.find(p => p.id === props.userId);
    if (p) {
        p.pos_x = (Math.max(0, Math.min(rect.width,  e.clientX - rect.left)) / rect.width)  * 100;
        p.pos_y = (Math.max(0, Math.min(rect.height, e.clientY - rect.top))  / rect.height) * 100;
    }
}

function handleAvatarPointerUp() {
    if (draggingId.value !== props.userId) return;
    draggingId.value = null;
    const p = chatParticipants.value.find(p => p.id === props.userId);
    if (p?.pos_x != null && p?.pos_y != null) {
        postJson(route('study-groups.move', props.session.id), {
            pos_x: p.pos_x,
            pos_y: p.pos_y
        });
    }
}

function getAvatarStyle(p, index) {
    if (p.pos_x != null && p.pos_y != null) {
        return { left: p.pos_x + '%', top: p.pos_y + '%' };
    }
    const defaults = [
        { left: '25%', top: '60%' },
        { left: '45%', top: '60%' },
        { left: '65%', top: '60%' },
        { left: '15%', top: '75%' },
        { left: '75%', top: '75%' },
    ];
    return defaults[index % defaults.length];
}

function getParticipantPomodoro(uid) {
    return activePomodoros.value.find(p => p.user_id === uid) ?? null;
}

// ── Pomodoro Global Integrado ────────────────────────────────────────────────
const { mode: pomoStatus, formattedTime: formattedLocalTime, isWidgetHidden, startFocus, abandon } = usePomodoroState();
const pomoFocusMinutes  = ref(25);
const pomoBreakMinutes  = ref(5);
const pomodoroCollapsed = ref(false);
const chatCollapsed     = ref(false);

function toggleLocalPomodoro() {
    if (pomoStatus.value === 'idle') {
        const m = props.missions.find(m => m.id === selectedMissionId.value);
        startFocus(pomoFocusMinutes.value, selectedMissionId.value, m?.title ?? null, props.session.id);
    } else {
        abandon();
    }
}

// ── Sistema de Arrastre Universal (Mouse + Touch) ────────────────────────────
function makeDraggable(posRef, elementRef, containerRef) {
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    function onPointerDown(e) {
        if (e.target.closest('button, input, textarea, select, label, form')) return;
        if (!elementRef.value || !containerRef.value) return;

        isDragging = true;

        const containerRect = containerRef.value.getBoundingClientRect();
        const elementRect = elementRef.value.getBoundingClientRect();

        const currentLeft = elementRect.left - containerRect.left;
        const currentTop = elementRect.top - containerRect.top;

        offsetX = e.clientX - elementRect.left;
        offsetY = e.clientY - elementRect.top;

        posRef.value = { x: currentLeft, y: currentTop };

        try {
            e.currentTarget.setPointerCapture(e.pointerId);
        } catch (_) {}

        window.addEventListener('pointermove', onPointerMove, { passive: false });
        window.addEventListener('pointerup', onPointerUp);
        window.addEventListener('pointercancel', onPointerUp);
    }

    function onPointerMove(e) {
        if (!isDragging || !elementRef.value || !containerRef.value) return;

        if (e.cancelable) e.preventDefault();

        const containerRect = containerRef.value.getBoundingClientRect();
        const elementRect = elementRef.value.getBoundingClientRect();

        let newX = e.clientX - containerRect.left - offsetX;
        let newY = e.clientY - containerRect.top - offsetY;

        const maxX = Math.max(0, containerRect.width - elementRect.width);
        const maxY = Math.max(0, containerRect.height - elementRect.height);

        posRef.value = {
            x: Math.max(0, Math.min(maxX, newX)),
            y: Math.max(0, Math.min(maxY, newY)),
        };
    }

    function onPointerUp(e) {
        if (isDragging) {
            isDragging = false;
            try {
                if (e?.currentTarget?.releasePointerCapture && e?.pointerId) {
                    e.currentTarget.releasePointerCapture(e.pointerId);
                }
            } catch (_) {}
            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('pointerup', onPointerUp);
            window.removeEventListener('pointercancel', onPointerUp);
        }
    }

    return { onPointerDown };
}

const chatPos       = ref({ x: null, y: null });
const chatWidgetRef = ref(null);
const { onPointerDown: handleChatPointerDown } = makeDraggable(chatPos, chatWidgetRef, sceneRef);

const widgetPos = ref({ x: null, y: null });
const widgetRef = ref(null);
const { onPointerDown: handleWidgetPointerDown } = makeDraggable(widgetPos, widgetRef, sceneRef);

const isMobile = ref(false);
const resizeHandler = () => {
    isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value) {
        pomodoroCollapsed.value = true;
        chatCollapsed.value = false;
    }
    isWidgetHidden.value = true;
    scrollToBottom();
    pollInterval.value = setInterval(poll, 5000);
    window.addEventListener('resize', resizeHandler);
});

onUnmounted(() => {
    isWidgetHidden.value = false;
    if (pollInterval.value) clearInterval(pollInterval.value);
    window.removeEventListener('resize', resizeHandler);
    leaveSession(true);
});
</script>

<template>
    <AppLayout :title="`Sala: ${session.name}`">
        <div class="mx-auto flex flex-col min-h-[calc(100vh-4.5rem)] max-w-7xl px-1 sm:px-2 lg:px-4 gap-2 sm:gap-3">
            
            <!-- Barra Superior de la Sala -->
            <div class="flex items-center justify-between bg-surface/60 backdrop-blur-md border border-border-interactive px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl sm:rounded-2xl shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-base sm:text-lg">🏛️</span>
                    <div>
                        <h2 class="text-sm sm:text-lg font-black text-content-primary leading-tight">{{ session.name }}</h2>
                        <p class="text-[10px] sm:text-xs text-content-muted font-medium">
                            {{ chatParticipants.length }} / {{ session.max_seats }} participantes en sala
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <BaseButton variant="danger" size="sm" @click="leaveSession()">Salir</BaseButton>
                </div>
            </div>

            <!-- Contenedor que centra la Escena con mayor altura (530px) -->
            <div class="flex-1 flex items-center justify-center w-full min-h-0">
                <div
                    ref="sceneRef"
                    class="relative w-full max-w-full aspect-[4/3] sm:aspect-[16/10] lg:aspect-[2816/1536] min-h-[530px] sm:min-h-[580px] max-h-[calc(100vh-6.5rem)] rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl border border-border-interactive select-none touch-none bg-surface mx-auto"
                    @pointermove="handleAvatarPointerMove"
                    @pointerup="handleAvatarPointerUp"
                    @pointerleave="handleAvatarPointerUp"
                >
                    <!-- Imagen de fondo balanceada: más espaciosa y nítida -->
                    <img
                        :src="currentBg.path"
                        :alt="currentBg.name"
                        class="absolute inset-0 w-full h-full object-cover object-center transition-all duration-1000 dark:brightness-70 dark:saturate-85 pointer-events-none select-none"
                        draggable="false"
                    />

                    <!-- Avatares Posicionados en la Escena -->
                    <div
                        v-for="(p, index) in chatParticipants.slice(0, 5)"
                        :key="p.id"
                        class="absolute transform -translate-x-1/2 -translate-y-1/2 ease-out z-10"
                        :class="[
                            draggingId === p.id ? 'duration-0 scale-110 z-20 cursor-grabbing' : 'duration-700 hover:scale-105',
                            p.id === userId ? 'cursor-grab touch-none' : 'cursor-default'
                        ]"
                        :style="getAvatarStyle(p, index)"
                        @pointerdown="handleAvatarPointerDown($event, p.id)"
                    >
                        <div class="flex flex-col items-center gap-0.5 sm:gap-1 group/avatar">
                            
                            <!-- Estado Pomodoro: Compañeros -->
                            <template v-if="p.id !== userId">
                                <div
                                    v-if="getParticipantPomodoro(p.id)"
                                    class="px-1.5 py-0.5 backdrop-blur-md rounded-full text-[8px] sm:text-[11px] font-bold shadow-lg flex items-center gap-1"
                                    :class="getParticipantPomodoro(p.id).status === 'running' ? 'bg-red-500/90 text-white animate-pulse' : 'bg-green-500/90 text-white'"
                                >
                                    <span>{{ getParticipantPomodoro(p.id).status === 'running' ? '🔴' : '🟢' }}</span>
                                    <span class="hidden xs:inline sm:inline">{{ getParticipantPomodoro(p.id).status === 'running' ? 'Enfocado' : 'Descanso' }}</span>
                                </div>
                                <div v-else class="px-1.5 py-0.5 bg-black/40 backdrop-blur-md rounded-full text-[7px] sm:text-[10px] font-medium text-white/70">
                                    ⚪ Libre
                                </div>
                            </template>

                            <!-- Estado Pomodoro: Usuario Actual -->
                            <div
                                v-else-if="pomoStatus !== 'idle'"
                                class="px-1.5 py-0.5 backdrop-blur-md rounded-full text-[8px] sm:text-[11px] font-bold flex items-center gap-1 shadow-lg"
                                :class="pomoStatus === 'running' ? 'bg-red-500/90 text-white animate-pulse' : 'bg-green-500/90 text-white'"
                            >
                                <span>{{ pomoStatus === 'running' ? '🔴' : '🟢' }}</span>
                                <span>{{ formattedLocalTime }}</span>
                            </div>
                            <div v-else class="px-1.5 py-0.5 bg-black/40 backdrop-blur-md rounded-full text-[7px] sm:text-[10px] font-medium text-white/70">
                                ⚪ Libre
                            </div>

                            <!-- Nombre / Alias del usuario -->
                            <div class="px-1.5 py-0.5 bg-black/60 backdrop-blur-md rounded-md sm:rounded-lg text-[7px] sm:text-[10px] font-bold text-white opacity-80 group-hover/avatar:opacity-100 transition-opacity whitespace-nowrap">
                                {{ p.id === userId ? 'Tú' : (p.alias || 'Estudiante') }}
                            </div>

                            <!-- Avatar Procedural Escala Responsiva -->
                            <div
                                class="w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 shrink-0 overflow-hidden rounded-full border-2 sm:border-4 shadow-2xl transition-all"
                                :class="[
                                    (p.id === userId && pomoStatus === 'running') || getParticipantPomodoro(p.id)?.status === 'running'
                                        ? 'border-red-400 shadow-red-400/40 ring-2 ring-red-400/50'
                                        : (p.id === userId && pomoStatus === 'break') || getParticipantPomodoro(p.id)?.status === 'paused'
                                            ? 'border-green-400 shadow-green-400/40 ring-2 ring-green-400/50'
                                            : 'border-white/40'
                                ]"
                            >
                                <ProceduralAvatar
                                    :career="p.avatar_style || 'base'"
                                    :gender="p.avatar_gender || 'm'"
                                    :avatar-options="p.avatar_options"
                                    :size="isMobile ? 65 : 100"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════════════ -->
                    <!-- CHAT WIDGET: Arrastrable en móvil y desktop + colapsable   -->
                    <!-- ═══════════════════════════════════════════════════════════ -->
                    <div
                        ref="chatWidgetRef"
                        class="absolute pointer-events-auto z-30 flex flex-col touch-none"
                        :class="chatCollapsed ? 'w-32 sm:w-44' : 'w-52 sm:w-72'"
                        :style="chatPos.x !== null 
                            ? { left: chatPos.x + 'px', top: chatPos.y + 'px' } 
                            : { right: '0.75rem', top: '0.75rem' }"
                    >
                        <div class="flex flex-col bg-black/65 backdrop-blur-xl border border-white/20 rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl">
                            
                            <!-- Header Chat: Zona de Arrastre -->
                            <div
                                class="bg-black/40 px-2.5 py-1.5 sm:px-3 sm:py-2 border-b border-white/10 flex items-center justify-between select-none cursor-grab active:cursor-grabbing shrink-0 touch-none"
                                @pointerdown="handleChatPointerDown"
                            >
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-xs">💬</span>
                                    <h3 class="text-xs sm:text-sm font-bold text-white truncate">Chat</h3>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="text-[9px] sm:text-[10px] px-1.5 py-0.5 rounded-md bg-white/10 hover:bg-white/20 text-white/80 transition-colors"
                                        @click.stop="chatCollapsed = !chatCollapsed"
                                    >
                                        {{ chatCollapsed ? '▲' : '▼' }}
                                    </button>
                                    <span class="text-white/40 text-[10px] hidden sm:inline">⠿</span>
                                </div>
                            </div>

                            <!-- Contenido del Chat (Solo cuando no está colapsado) -->
                            <template v-if="!chatCollapsed">
                                <!-- Lista de Mensajes -->
                                <div
                                    ref="chatContainer"
                                    class="flex-1 overflow-y-auto p-2 sm:p-3 space-y-2 custom-scrollbar"
                                    :style="isMobile ? 'max-height: 120px; min-height: 60px;' : 'max-height: 260px; min-height: 120px;'"
                                >
                                    <div
                                        v-for="msg in chatMessages"
                                        :key="msg.id"
                                        class="flex flex-col"
                                        :class="msg.user_id === userId ? 'items-end' : 'items-start'"
                                    >
                                        <span class="text-[8px] sm:text-[9px] text-white/60 mb-0.5 font-medium px-1">
                                            {{ msg.user_id === userId ? 'Tú' : (chatParticipants.find(p => p.id === msg.user_id)?.alias || 'Compañero') }}
                                        </span>
                                        <div
                                            class="max-w-[90%] rounded-xl sm:rounded-2xl px-2 sm:px-2.5 py-1 text-xs shadow-md break-words"
                                            :class="msg.user_id === userId
                                                ? 'bg-primary-strong text-white rounded-br-sm'
                                                : 'bg-white/20 text-white border border-white/10 rounded-bl-sm'"
                                        >
                                            {{ msg.body }}
                                        </div>
                                    </div>
                                    <div v-if="chatMessages.length === 0" class="py-2.5 text-center text-[10px] font-medium text-white/40">
                                        Comienza la conversación.
                                    </div>
                                </div>

                                <!-- Input para Enviar Mensajes -->
                                <form class="p-1.5 sm:p-2 bg-black/30 border-t border-white/10 shrink-0" @submit.prevent="sendMessage">
                                    <div class="flex gap-1 sm:gap-1.5">
                                        <input
                                            v-model="newMessage"
                                            type="text"
                                            maxlength="500"
                                            placeholder="Escribe..."
                                            class="w-full rounded-lg sm:rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/40 px-2 py-1 text-xs focus:ring-2 focus:ring-primary-strong outline-none transition-all"
                                            :disabled="isSending"
                                            @keydown.stop
                                        />
                                        <button
                                            type="submit"
                                            :disabled="!newMessage.trim() || isSending"
                                            class="shrink-0 px-2 sm:px-2.5 py-1 bg-primary-strong text-white rounded-lg sm:rounded-xl disabled:opacity-50 hover:bg-primary transition-colors text-xs font-bold"
                                        >
                                            ➤
                                        </button>
                                    </div>
                                </form>
                            </template>

                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════════════ -->
                    <!-- POMODORO WIDGET: Arrastrable en móvil y desktop + colapsable -->
                    <!-- ═══════════════════════════════════════════════════════════ -->
                    <div
                        ref="widgetRef"
                        class="absolute pointer-events-auto z-30 touch-none"
                        :class="pomodoroCollapsed ? 'w-32 sm:w-44' : 'w-52 sm:w-72'"
                        :style="widgetPos.x !== null 
                            ? { left: widgetPos.x + 'px', top: widgetPos.y + 'px' } 
                            : { left: '0.75rem', bottom: '0.75rem' }"
                    >
                        <div class="bg-white/80 dark:bg-black/70 backdrop-blur-xl border border-black/10 dark:border-white/15 rounded-2xl sm:rounded-3xl shadow-2xl flex flex-col items-center w-full overflow-hidden">
                            
                            <!-- Header Pomodoro: Zona de Arrastre -->
                            <div
                                class="w-full flex items-center justify-between px-2.5 py-1.5 sm:px-4 sm:py-2 select-none cursor-grab active:cursor-grabbing bg-black/5 dark:bg-black/30 border-b border-black/5 dark:border-white/10 touch-none"
                                @pointerdown="handleWidgetPointerDown"
                            >
                                <div class="flex items-center gap-1 text-[10px] sm:text-xs font-bold text-content-primary uppercase tracking-wider truncate">
                                    <span>{{ pomoStatus === 'running' ? '🔴' : (pomoStatus === 'break' ? '🟢' : '⏸') }}</span>
                                    <span class="truncate">{{ pomoStatus === 'running' ? 'Enfoque' : (pomoStatus === 'break' ? 'Descanso' : 'Listo') }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="text-[9px] sm:text-[10px] px-1.5 py-0.5 rounded-md bg-black/10 dark:bg-white/10 text-content-muted hover:text-content-primary transition-colors select-none"
                                        @click.stop="pomodoroCollapsed = !pomodoroCollapsed"
                                    >
                                        {{ pomodoroCollapsed ? '▲' : '▼' }}
                                    </button>
                                    <span class="text-content-muted text-[10px] hidden sm:inline">⠿</span>
                                </div>
                            </div>

                            <!-- Temporizador Siempre Visible -->
                            <div
                                class="tabular-nums font-black tracking-tighter text-content-primary drop-shadow-sm select-none"
                                :class="pomodoroCollapsed ? 'text-xl sm:text-3xl py-0.5 sm:py-1' : 'text-2xl sm:text-5xl py-1 sm:py-2'"
                            >
                                {{ formattedLocalTime }}
                            </div>

                            <!-- Contenido Expandido del Pomodoro -->
                            <template v-if="!pomodoroCollapsed">
                                <!-- Inputs de Minutos de Estudio/Descanso (Solo en Idle) -->
                                <div v-if="pomoStatus === 'idle'" class="flex gap-1.5 px-2.5 sm:px-3 pb-2 w-full">
                                    <div class="w-1/2 text-center">
                                        <span class="text-[8px] sm:text-[9px] font-bold text-content-muted block mb-0.5">Enfoque</span>
                                        <BaseInput id="focus-minutes" type="number" v-model.number="pomoFocusMinutes" min="15" max="50" class="text-center text-xs py-0.5 sm:py-1 bg-white/50 dark:bg-black/50" />
                                    </div>
                                    <div class="w-1/2 text-center">
                                        <span class="text-[8px] sm:text-[9px] font-bold text-content-muted block mb-0.5">Descanso</span>
                                        <BaseInput id="break-minutes" type="number" v-model.number="pomoBreakMinutes" min="1" max="15" class="text-center text-xs py-0.5 sm:py-1 bg-white/50 dark:bg-black/50" />
                                    </div>
                                </div>

                                <!-- Misiones Colapsables -->
                                <div class="w-full px-2.5 sm:px-3 pb-2">
                                    <button
                                        type="button"
                                        class="w-full flex items-center justify-between text-[9px] sm:text-xs font-bold uppercase tracking-widest text-content-muted py-0.5 sm:py-1 px-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 transition-colors"
                                        @click.stop="showMissions = !showMissions"
                                    >
                                        <span class="truncate">📋 Misiones ({{ missions.length }})</span>
                                        <span>{{ showMissions ? '▲' : '▼' }}</span>
                                    </button>

                                    <div v-if="showMissions" class="mt-1 space-y-1 max-h-32 sm:max-h-44 overflow-y-auto custom-scrollbar pr-1">
                                        <p v-if="missions.length === 0" class="text-[9px] sm:text-[10px] text-content-muted text-center py-1.5">No hay misiones activas.</p>
                                        <div
                                            v-for="m in missions"
                                            :key="m.id"
                                            class="rounded-lg border transition-all text-left"
                                            :class="selectedMissionId === m.id
                                                ? 'border-primary-strong bg-primary/10'
                                                : 'border-black/10 dark:border-white/10 hover:border-primary/40'"
                                        >
                                            <div class="flex items-center justify-between p-1 sm:p-1.5 gap-1">
                                                <button
                                                    type="button"
                                                    class="min-w-0 flex-1 text-left text-[10px] sm:text-[11px] font-semibold text-content-primary truncate"
                                                    @click.stop="selectMission(m.id)"
                                                >
                                                    {{ selectedMissionId === m.id ? '✓ ' : '' }}{{ m.title }}
                                                </button>
                                                <button
                                                    v-if="m.subtask_count > 0"
                                                    type="button"
                                                    class="text-[8px] sm:text-[9px] text-content-muted shrink-0 hover:text-content-primary"
                                                    @click.stop="toggleExpandMission(m.id)"
                                                >
                                                    {{ m.subtask_done }}/{{ m.subtask_count }} {{ expandedMissionId === m.id ? '▲' : '▼' }}
                                                </button>
                                            </div>
                                            <div
                                                v-if="expandedMissionId === m.id && m.subtasks.length > 0"
                                                class="border-t border-black/10 dark:border-white/10 bg-black/5 dark:bg-white/5 px-2 py-1"
                                            >
                                                <ul class="space-y-1">
                                                    <li v-for="sub in m.subtasks" :key="sub.id" class="flex items-center gap-1 text-[9px] sm:text-[10px]">
                                                        <input
                                                            type="checkbox"
                                                            :checked="sub.is_completed"
                                                            :disabled="subtaskBusy[sub.id]"
                                                            class="h-3 w-3 rounded border-border text-primary-strong"
                                                            @change.stop="toggleSubtask(m.id, sub)"
                                                        />
                                                        <span :class="sub.is_completed ? 'line-through text-content-muted' : 'text-content-primary'" class="truncate">
                                                            {{ sub.title }}
                                                        </span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón de Iniciar / Cancelar -->
                                <div class="px-2.5 sm:px-3 pb-2.5 sm:pb-3 w-full">
                                    <button
                                        @click.stop="toggleLocalPomodoro"
                                        class="w-full py-1 sm:py-2 font-black rounded-xl transition-all shadow-lg text-[11px] sm:text-sm"
                                        :class="pomoStatus === 'idle'
                                            ? 'bg-primary text-white hover:bg-primary-strong'
                                            : 'bg-red-500/90 text-white hover:bg-red-600'"
                                    >
                                        {{ pomoStatus === 'idle' ? '▶ Iniciar' : '⏹ Parar' }}
                                    </button>
                                </div>
                            </template>

                            <!-- Botón Compacto cuando está Minimizado -->
                            <template v-else>
                                <div class="px-2 pb-1.5 sm:pb-2 w-full">
                                    <button
                                        @click.stop="toggleLocalPomodoro"
                                        class="w-full py-0.5 sm:py-1 text-[10px] sm:text-[11px] font-bold rounded-lg transition-all"
                                        :class="pomoStatus === 'idle'
                                            ? 'bg-primary/20 text-primary hover:bg-primary/30'
                                            : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'"
                                    >
                                        {{ pomoStatus === 'idle' ? '▶ Iniciar' : '⏹ Parar' }}
                                    </button>
                                </div>
                            </template>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>
