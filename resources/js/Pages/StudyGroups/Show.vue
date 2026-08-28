<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';

import { useTelemetry } from '@/Composables/useTelemetry';
const { track } = useTelemetry();

const props = defineProps({
    session: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
    participants: { type: Array, default: () => [] },
    lastMessageId: { type: Number, default: 0 },
    userId: { type: Number, required: true },
});

const chatMessages = ref(props.messages);
const chatParticipants = ref(props.participants);
const activePomodoros = ref([]);
const currentLastId = ref(props.lastMessageId);
const newMessage = ref('');
const isSending = ref(false);
const chatContainer = ref(null);
const pollInterval = ref(null);

const backgrounds = [
    { id: 1, name: 'Cafetería', path: '/assets/images/rooms/cafeteria.webp' },
    { id: 2, name: 'Biblioteca', path: '/assets/images/rooms/biblioteca.webp' },
];
const currentBg = computed(() => backgrounds.find(b => b.id === props.session.id) || backgrounds[0]);

const draggingId = ref(null);
const sceneRef = ref(null);

function handlePointerDown(e, participantId) {
    if (participantId !== props.userId) return;
    draggingId.value = participantId;
    e.target.setPointerCapture(e.pointerId);
}

function handlePointerMove(e) {
    if (draggingId.value !== props.userId || !sceneRef.value) return;
    
    const rect = sceneRef.value.getBoundingClientRect();
    const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
    const y = Math.max(0, Math.min(rect.height, e.clientY - rect.top));
    
    const posX = (x / rect.width) * 100;
    const posY = (y / rect.height) * 100;
    
    const p = chatParticipants.value.find(p => p.id === props.userId);
    if (p) {
        p.pos_x = posX;
        p.pos_y = posY;
    }
}

function handlePointerUp(e) {
    if (draggingId.value !== props.userId) return;
    draggingId.value = null;
    
    const p = chatParticipants.value.find(p => p.id === props.userId);
    if (p && p.pos_x !== null && p.pos_y !== null) {
        postJson(route('study-groups.move', props.session.id), {
            pos_x: p.pos_x,
            pos_y: p.pos_y
        });
    }
}

function getAvatarStyle(p, index) {
    if (p.pos_x !== null && p.pos_y !== null) {
        return { left: `${p.pos_x}%`, top: `${p.pos_y}%` };
    }
    // Fallback default positions
    const defaults = [
        { left: '25%', top: '60%' },
        { left: '45%', top: '60%' },
        { left: '65%', top: '60%' },
        { left: '15%', top: '75%' },
        { left: '75%', top: '75%' },
    ];
    return defaults[index % defaults.length];
}

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
            'X-XSRF-TOKEN': xsrf,
        },
        body: JSON.stringify(body),
    });
}

function sendMessage() {
    if (!newMessage.value.trim() || isSending.value) return;
    isSending.value = true;

    postJson(route('study-groups.messages', props.session.id), {
        body: newMessage.value.trim(),
    })
        .then((res) => {
            if (res.ok) return res.json();
            throw new Error('Error al enviar mensaje');
        })
        .then((msg) => {
            chatMessages.value.push(msg);
            if (msg.id > currentLastId.value) currentLastId.value = msg.id;
            newMessage.value = '';
            scrollToBottom();
            track('group_chat.message_sent', 'study_groups', {
                session_id: props.session.id,
                message_length: msg.body.length,
            });
        })
        .catch(() => {})
        .finally(() => {
            isSending.value = false;
        });
}

function poll() {
    if (document.visibilityState === 'hidden') return;

    fetch(`${route('study-groups.poll', props.session.id)}?since=${currentLastId.value}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.messages && data.messages.length > 0) {
                for (const msg of data.messages) {
                    chatMessages.value.push(msg);
                    if (msg.id > currentLastId.value) currentLastId.value = msg.id;
                }
                scrollToBottom();
            }
            if (data.participants) {
                // Actualizar coords solo si no estoy arrastrando mi propio avatar
                for (const updatedP of data.participants) {
                    const idx = chatParticipants.value.findIndex(p => p.id === updatedP.id);
                    if (idx !== -1) {
                        if (updatedP.id !== props.userId || draggingId.value !== props.userId) {
                            chatParticipants.value[idx] = updatedP;
                        }
                    } else {
                        chatParticipants.value.push(updatedP);
                    }
                }
                // Remover los que ya no están
                chatParticipants.value = chatParticipants.value.filter(p => data.participants.some(up => up.id === p.id));
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

function leaveSession() {
    track('group_session.left', 'study_groups', { session_id: props.session.id });
    router.post(route('study-groups.leave', props.session.id));
}

// POMODORO LOCAL LOGIC
const pomoStatus = ref('idle'); // idle, running, break
const pomoTimeRemaining = ref(25 * 60);
const pomoTimer = ref(null);
const pomoFocusMinutes = ref(25);
const pomoBreakMinutes = ref(5);

const formattedLocalTime = computed(() => {
    const m = Math.floor(pomoTimeRemaining.value / 60);
    const s = pomoTimeRemaining.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

function toggleLocalPomodoro() {
    if (pomoStatus.value === 'running') {
        // Stop it completely or just pause? Let's treat it as complete/cancel for simplicity
        pomoStatus.value = 'idle';
        clearInterval(pomoTimer.value);
        pomoTimeRemaining.value = pomoFocusMinutes.value * 60;
        return;
    }

    if (pomoStatus.value === 'idle' || pomoStatus.value === 'break') {
        pomoStatus.value = 'running';
        pomoTimeRemaining.value = pomoFocusMinutes.value * 60;
        
        postJson(route('study-groups.pomodoro.start', props.session.id), {
            planned_minutes: pomoFocusMinutes.value,
        });

        pomoTimer.value = setInterval(() => {
            if (pomoTimeRemaining.value > 0) {
                pomoTimeRemaining.value--;
            } else {
                clearInterval(pomoTimer.value);
                pomoStatus.value = 'break';
                pomoTimeRemaining.value = pomoBreakMinutes.value * 60;
                // Auto start break countdown
                pomoTimer.value = setInterval(() => {
                    if (pomoTimeRemaining.value > 0) {
                        pomoTimeRemaining.value--;
                    } else {
                        clearInterval(pomoTimer.value);
                        pomoStatus.value = 'idle';
                        pomoTimeRemaining.value = pomoFocusMinutes.value * 60;
                    }
                }, 1000);
            }
        }, 1000);
    }
}

onMounted(() => {
    scrollToBottom();
    pollInterval.value = setInterval(poll, 5000);
});

onUnmounted(() => {
    if (pollInterval.value) clearInterval(pollInterval.value);
    if (pomoTimer.value) clearInterval(pomoTimer.value);
});

function getParticipantPomodoro(userId) {
    return activePomodoros.value.find(p => p.user_id === userId);
}
</script>

<template>
    <AppLayout :title="`Sala: ${session.name}`">
        <div class="mx-auto flex flex-col h-[calc(100vh-6rem)] max-w-7xl px-2 lg:px-4 gap-4">
            
            <div class="flex items-center justify-between bg-surface/50 backdrop-blur-md border border-border-interactive px-4 py-3 rounded-2xl">
                <div>
                    <h2 class="text-xl font-black text-content-primary flex items-center gap-2">
                        {{ session.name }}
                    </h2>
                    <p class="text-xs text-content-muted font-medium">
                        {{ chatParticipants.length }} / {{ session.max_seats }} participantes
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <BaseButton variant="danger" size="sm" @click="leaveSession">Salir de la sala</BaseButton>
                </div>
            </div>

            <div 
                ref="sceneRef"
                class="relative w-full flex-1 rounded-3xl overflow-hidden shadow-2xl border border-border-interactive group select-none touch-none"
                @pointermove="handlePointerMove"
                @pointerup="handlePointerUp"
                @pointerleave="handlePointerUp"
            >
                <img 
                    :src="currentBg.path" 
                    :alt="currentBg.name" 
                    class="absolute inset-0 w-full h-full object-cover transition-all duration-1000 dark:brightness-50 dark:saturate-75"
                    draggable="false"
                />

                <!-- Avatares Posicionados -->
                <div 
                    v-for="(p, index) in chatParticipants.slice(0, 5)" 
                    :key="p.id"
                    class="absolute transform -translate-x-1/2 -translate-y-1/2 transition-all ease-out z-10"
                    :class="[
                        draggingId === p.id ? 'duration-0 scale-110 z-20 cursor-grabbing' : 'duration-700 hover:scale-105',
                        p.id === userId ? 'cursor-grab' : 'cursor-default'
                    ]"
                    :style="getAvatarStyle(p, index)"
                    @pointerdown="handlePointerDown($event, p.id)"
                >
                    <div class="flex flex-col items-center gap-2 group/avatar">
                        
                        <!-- Etiqueta Estado Pomodoro Compañero -->
                        <div v-if="getParticipantPomodoro(p.id) && p.id !== userId" class="px-2 py-1 bg-white/90 backdrop-blur-md rounded-full text-[11px] font-bold text-black shadow-lg shadow-black/20 animate-bounce">
                            {{ getParticipantPomodoro(p.id).status === 'running' ? '🔴 Enfocado' : '🟢 Descanso' }}
                        </div>
                        <div v-else-if="p.id === userId && pomoStatus !== 'idle'" class="px-2 py-1 bg-white/90 backdrop-blur-md rounded-full text-[11px] font-bold text-black shadow-lg shadow-black/20">
                            {{ pomoStatus === 'running' ? '🔴' : '🟢' }} {{ formattedLocalTime }}
                        </div>

                        <!-- Etiqueta del usuario -->
                        <div class="px-2 py-1 bg-black/60 backdrop-blur-md rounded-lg text-[10px] font-bold text-white shadow-lg opacity-80 group-hover/avatar:opacity-100 transition-opacity whitespace-nowrap">
                            {{ p.id === userId ? 'Tú' : (p.alias || 'Estudiante') }}
                        </div>
                        
                        <div class="w-16 h-16 sm:w-20 sm:h-20 shrink-0 overflow-hidden rounded-full border-4 shadow-2xl transition-all border-white/30 hover:border-primary-strong/80"
                            :class="[
                                p.id === userId && pomoStatus === 'running' ? 'border-primary-strong/80 shadow-primary-strong/30' : '',
                                getParticipantPomodoro(p.id)?.status === 'running' ? 'border-primary-strong/80 shadow-primary-strong/30' : ''
                            ]"
                        >
                            <ProceduralAvatar
                                :career="p.avatar_style || 'base'"
                                :gender="p.avatar_gender || 'm'"
                                :avatar-options="p.avatar_options"
                                :size="120"
                            />
                        </div>
                    </div>
                </div>

                <div class="absolute inset-0 pointer-events-none flex flex-col lg:flex-row justify-between p-4 lg:p-6 gap-4">
                    
                    <!-- Pomodoro Widget Local -->
                    <div class="pointer-events-auto flex flex-col items-center lg:items-start self-start lg:self-center mt-4 lg:mt-0 w-full lg:w-auto">
                        <div class="bg-white/70 dark:bg-black/60 backdrop-blur-xl border border-black/10 dark:border-white/10 rounded-3xl p-6 shadow-2xl flex flex-col items-center w-72 transition-all transform hover:scale-105">
                            <div class="mb-3 flex items-center gap-2 bg-black/5 dark:bg-black/40 px-3 py-1 rounded-full backdrop-blur-md border border-black/5 dark:border-white/10">
                                <span>{{ pomoStatus === 'running' ? '🔴' : (pomoStatus === 'break' ? '🟢' : '⏸') }}</span>
                                <span class="text-xs font-bold uppercase tracking-widest text-content-primary">{{ pomoStatus === 'running' ? 'Enfoque' : (pomoStatus === 'break' ? 'Descanso' : 'Listo') }}</span>
                            </div>
                            
                            <div class="text-6xl font-black tabular-nums tracking-tighter text-content-primary drop-shadow-sm">
                                {{ formattedLocalTime }}
                            </div>
                            
                            <div v-if="pomoStatus === 'idle'" class="flex gap-2 mt-4 w-full">
                                <BaseInput type="number" v-model.number="pomoFocusMinutes" min="15" max="50" class="w-1/2 text-center bg-white/50 dark:bg-black/50" />
                                <BaseInput type="number" v-model.number="pomoBreakMinutes" min="1" max="15" class="w-1/2 text-center bg-white/50 dark:bg-black/50" />
                            </div>

                            <div class="flex flex-col items-center gap-4 w-full mt-4">
                                <button @click="toggleLocalPomodoro" class="w-full py-3 bg-primary text-white font-black rounded-xl hover:bg-primary-strong transition-all shadow-xl">
                                    {{ pomoStatus === 'idle' ? 'Iniciar Estudio' : 'Cancelar / Parar' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Widget -->
                    <div class="pointer-events-auto flex flex-col self-end lg:self-stretch w-full lg:w-80 h-64 lg:h-full max-h-[500px] bg-white/10 dark:bg-black/40 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-3xl overflow-hidden shadow-2xl transition-all">
                        <div class="bg-black/20 px-4 py-3 border-b border-white/10 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>💬 Chat de Estudio</span>
                            </h3>
                        </div>
                        
                        <div ref="chatContainer" class="flex-1 space-y-4 overflow-y-auto p-4 custom-scrollbar">
                            <div v-for="msg in chatMessages" :key="msg.id" class="flex flex-col" :class="msg.user_id === userId ? 'items-end' : 'items-start'">
                                <span class="text-[10px] text-white/60 mb-1 font-medium px-1">
                                    {{ msg.user_id === userId ? 'Tú' : (msg.alias || (chatParticipants.find(p => p.id === msg.user_id)?.alias || 'Compañero')) }}
                                </span>
                                <div class="max-w-[85%] rounded-2xl px-4 py-2 text-sm shadow-md"
                                     :class="msg.user_id === userId ? 'bg-primary-strong text-white rounded-br-sm' : 'bg-white/20 backdrop-blur-md text-white border border-white/10 rounded-bl-sm'">
                                    {{ msg.body }}
                                </div>
                            </div>
                            <div v-if="chatMessages.length === 0" class="py-10 text-center text-xs font-medium text-white/50">
                                Comienza la conversación.
                            </div>
                        </div>

                        <form class="p-3 bg-black/20 border-t border-white/10" @submit.prevent="sendMessage">
                            <div class="flex gap-2 relative">
                                <input
                                    v-model="newMessage"
                                    type="text"
                                    maxlength="500"
                                    placeholder="Escribe algo..."
                                    class="w-full rounded-xl bg-white/10 border-white/20 text-white placeholder-white/40 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-strong focus:border-transparent outline-none backdrop-blur-md transition-all"
                                    :disabled="isSending"
                                />
                                <button type="submit" :disabled="!newMessage.trim() || isSending"
                                        class="absolute right-1.5 top-1.5 p-1.5 bg-primary-strong text-white rounded-lg disabled:opacity-50 hover:bg-primary transition-colors">
                                    ➤
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
