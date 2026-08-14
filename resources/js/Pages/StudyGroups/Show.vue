<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
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
const currentLastId = ref(props.lastMessageId);
const newMessage = ref('');
const isSending = ref(false);
const chatContainer = ref(null);
const pollInterval = ref(null);
const tickInterval = ref(null);

const roomPhase = ref(props.session.phase || 'idle');
const roomPhaseEndsAt = ref(props.session.phase_ends_at || null);
const roomCycle = ref(props.session.current_cycle || 0);
const roomTotalCycles = ref(props.session.cycles || 4);
const roomState = ref(props.session.state || 'open');
const serverNow = ref(null);
const clockOffsetMs = ref(0);
const remainingSeconds = ref(0);

const showConfigureModal = ref(false);
const configForm = ref({
    focus_minutes: props.session.focus_minutes || 25,
    break_minutes: props.session.break_minutes || 5,
    cycles: props.session.cycles || 4,
});

const isHost = computed(() => props.session.host_id === props.userId);
const isIdle = computed(() => roomPhase.value === 'idle');
const isFocus = computed(() => roomPhase.value === 'focus');
const isBreak = computed(() => roomPhase.value === 'break');
const isCompleted = computed(() => roomPhase.value === 'completed');
const isRunning = computed(() => isFocus.value || isBreak.value);
const chatEnabled = computed(() => isIdle.value || isBreak.value || isCompleted.value);

const phaseIcon = computed(() => {
    if (isFocus.value) return '🔴';
    if (isBreak.value) return '🟢';
    if (isCompleted.value) return '✅';
    return '⏸';
});

const phaseText = computed(() => {
    if (isCompleted.value) return 'Completado';
    if (isFocus.value) return 'Enfoque';
    if (isBreak.value) return 'Descanso';
    return 'Esperando inicio';
});

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function updateRemaining() {
    if (!roomPhaseEndsAt.value) {
        remainingSeconds.value = 0;
        return;
    }
    const now = Date.now() + clockOffsetMs.value;
    const endsAt = new Date(roomPhaseEndsAt.value).getTime();
    remainingSeconds.value = Math.max(0, Math.floor((endsAt - now) / 1000));
}

function startTicker() {
    stopTicker();
    updateRemaining();
    tickInterval.value = setInterval(updateRemaining, 1000);
}

function stopTicker() {
    if (tickInterval.value) {
        clearInterval(tickInterval.value);
        tickInterval.value = null;
    }
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

function advancePhase() {
    postJson(route('study-groups.advance', props.session.id))
        .then((r) => r.json())
        .then((data) => {
            if (data.advanced) {
                track(
                    data.phase === 'focus' ? 'group_focus.started' : 'group_break.started',
                    'study_groups',
                    {
                        session_id: props.session.id,
                        cycle: data.cycle,
                    },
                );
            }
        })
        .catch(() => {});
}

function configureRoom() {
    postJson(route('study-groups.configure', props.session.id), {
        focus_minutes: configForm.value.focus_minutes,
        break_minutes: configForm.value.break_minutes,
        cycles: configForm.value.cycles,
    })
        .then((r) => r.json())
        .then(() => {
            showConfigureModal.value = false;
        })
        .catch(() => {});
}

function sendMessage() {
    if (!newMessage.value.trim() || isSending.value || !chatEnabled.value) return;
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
                chatParticipants.value = data.participants;
            }
            if (data.room) {
                const prevPhase = roomPhase.value;
                roomPhase.value = data.room.phase;
                roomPhaseEndsAt.value = data.room.phase_ends_at;
                roomCycle.value = data.room.current_cycle;
                roomTotalCycles.value = data.room.total_cycles;
                roomState.value = data.room.state;
                serverNow.value = data.room.server_now;

                if (serverNow.value) {
                    clockOffsetMs.value = new Date(data.room.server_now).getTime() - Date.now();
                }

                if (data.room.phase !== prevPhase) {
                    updateRemaining();
                    if (data.room.phase === 'focus' || data.room.phase === 'break') {
                        startTicker();
                    } else {
                        stopTicker();
                    }
                }
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

onMounted(() => {
    scrollToBottom();
    pollInterval.value = setInterval(poll, 5000);
    if (isRunning.value) startTicker();
});

onUnmounted(() => {
    if (pollInterval.value) clearInterval(pollInterval.value);
    stopTicker();
});
</script>

<template>
    <AppLayout :title="`Sesión: ${session.name}`">
        <div class="mx-auto flex h-[calc(100vh-10rem)] max-w-6xl flex-col gap-4 lg:flex-row">
            <div class="flex flex-1 flex-col rounded-xl panel-raised">
                <div
                    class="flex items-center justify-between border-b border-border-interactive px-4 py-3"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary">
                            {{ session.name }}
                        </h2>
                        <p class="text-xs text-content-muted">
                            {{ chatParticipants.length }} participante{{
                                chatParticipants.length !== 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                    <BaseButton variant="danger" size="sm" @click="leaveSession">Salir</BaseButton>
                </div>

                <div
                    class="flex flex-col items-center justify-center border-b border-border-interactive px-4 py-8"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <span>{{ phaseIcon }}</span>
                        <span
                            class="text-sm font-medium uppercase tracking-wider text-content-secondary"
                            >{{ phaseText }}</span
                        >
                        <span v-if="isRunning" class="text-xs text-content-muted"
                            >Ciclo {{ roomCycle }}/{{ roomTotalCycles }}</span
                        >
                    </div>
                    <div
                        v-if="isRunning"
                        class="text-6xl font-extrabold tabular-nums tracking-tighter text-content-primary"
                    >
                        {{ formatTime(remainingSeconds) }}
                    </div>
                    <div v-else-if="isIdle" class="flex flex-col items-center gap-3">
                        <p class="text-sm text-content-secondary">
                            {{ session.focus_minutes }} min foco · {{ session.break_minutes }} min
                            descanso · {{ session.cycles }} ciclos
                        </p>
                        <BaseButton v-if="isHost" @click="advancePhase">Iniciar estudio</BaseButton>
                    </div>
                    <div v-else class="flex flex-col items-center gap-2">
                        <p class="text-lg text-content-secondary">¡Sesión completada!</p>
                    </div>
                    <div
                        v-if="isRunning"
                        class="mt-3 h-2 w-64 overflow-hidden rounded-full bg-surface-raised"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-1000"
                            :class="isFocus ? 'bg-danger' : 'bg-success'"
                            :style="{
                                width:
                                    roomTotalCycles > 0
                                        ? `${(roomCycle / roomTotalCycles) * 100}%`
                                        : '0%',
                            }"
                        />
                    </div>
                </div>

                <div ref="chatContainer" class="flex-1 space-y-3 overflow-y-auto p-4">
                    <div v-for="msg in chatMessages" :key="msg.id" class="flex items-start gap-3">
                        <div class="flex flex-col">
                            <span class="text-xs text-content-muted">
                                {{ msg.user_id === userId ? 'Tú' : `Usuario #${msg.user_id}` }}
                            </span>
                            <div
                                class="mt-1 max-w-[80%] rounded-xl px-4 py-2 text-sm"
                                :class="
                                    msg.user_id === userId
                                        ? 'ml-auto rounded-br-sm bg-primary-strong text-on-accent'
                                        : 'rounded-bl-sm bg-surface-raised text-content-primary'
                                "
                            >
                                {{ msg.body }}
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="chatMessages.length === 0"
                        class="py-8 text-center text-sm text-content-muted"
                    >
                        {{
                            isFocus ? 'Chat desactivado durante el enfoque' : 'No hay mensajes aún'
                        }}
                    </div>
                </div>

                <form
                    v-if="chatEnabled"
                    class="flex gap-2 border-t border-border-interactive p-4"
                    @submit.prevent="sendMessage"
                >
                    <input
                        v-model="newMessage"
                        type="text"
                        maxlength="500"
                        placeholder="Escribe un mensaje..."
                        class="min-h-[44px] flex-1 rounded-xl bg-surface-raised px-4 text-sm text-content-primary placeholder-content-muted outline-none ring-1 ring-border-interactive focus:ring-2 focus:ring-primary-strong"
                        :disabled="isSending"
                    />
                    <BaseButton type="submit" :disabled="!newMessage.trim() || isSending"
                        >Enviar</BaseButton
                    >
                </form>
                <div
                    v-else
                    class="flex items-center justify-center gap-2 border-t border-border-interactive bg-surface-raised/50 p-4"
                >
                    <span class="text-sm text-content-muted"
                        >💬 Chat disponible durante el descanso</span
                    >
                </div>
            </div>

            <aside class="w-full shrink-0 space-y-4 lg:w-64">
                <BaseCard class="p-4">
                    <h3 class="mb-3 text-sm font-semibold text-content-primary">Sala de estudio</h3>
                    <div class="space-y-2 text-xs text-content-secondary">
                        <p>
                            <span class="font-medium text-content-primary">Foco:</span>
                            {{ session.focus_minutes }} min
                        </p>
                        <p>
                            <span class="font-medium text-content-primary">Descanso:</span>
                            {{ session.break_minutes }} min
                        </p>
                        <p>
                            <span class="font-medium text-content-primary">Ciclos:</span>
                            {{ session.cycles }}
                        </p>
                    </div>
                    <BaseButton
                        v-if="isHost && isIdle"
                        size="sm"
                        class="mt-3 w-full"
                        @click="showConfigureModal = true"
                    >
                        Configurar
                    </BaseButton>
                </BaseCard>

                <BaseCard class="p-4">
                    <h3 class="mb-3 text-sm font-semibold text-content-primary">Participantes</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="p in chatParticipants"
                            :key="p.id"
                            class="flex items-center gap-3"
                        >
                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full border border-border-interactive bg-surface-raised">
                                <ProceduralAvatar
                                    :career="p.avatar_style || 'base'"
                                    :gender="p.avatar_gender || 'm'"
                                    :avatar-options="p.avatar_options"
                                    :size="64"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-content-primary">
                                    {{ p.alias || 'Estudiante' }}
                                </p>
                                <p
                                    v-if="p.id === session.host_id"
                                    class="text-xs text-primary-strong"
                                >
                                    Anfitrión
                                </p>
                            </div>
                        </li>
                    </ul>
                </BaseCard>
            </aside>
        </div>
    </AppLayout>

    <BaseModal :show="showConfigureModal" @close="showConfigureModal = false">
        <template #title>Configurar sala de estudio</template>
        <form class="space-y-4" @submit.prevent="configureRoom">
            <div class="grid grid-cols-3 gap-3">
                <BaseInput
                    v-model.number="configForm.focus_minutes"
                    label="Foco (min)"
                    type="number"
                    min="5"
                    max="120"
                />
                <BaseInput
                    v-model.number="configForm.break_minutes"
                    label="Descanso (min)"
                    type="number"
                    min="1"
                    max="30"
                />
                <BaseInput
                    v-model.number="configForm.cycles"
                    label="Ciclos"
                    type="number"
                    min="1"
                    max="20"
                />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <BaseButton type="button" variant="ghost" @click="showConfigureModal = false"
                    >Cancelar</BaseButton
                >
                <BaseButton type="submit">Guardar configuración</BaseButton>
            </div>
        </form>
    </BaseModal>
</template>
