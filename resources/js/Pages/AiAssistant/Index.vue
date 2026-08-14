<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import { useTelemetry } from '@/Composables/useTelemetry';

const props = defineProps({
    initialConversationId: { type: [Number, String], default: null },
    initialMessages: { type: Array, default: () => [] },
    conversations: { type: Array, default: () => [] },
    quota: { type: Object, required: true },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
});

const { track } = useTelemetry();

const conversationId = ref(props.initialConversationId);
const messages = ref([...props.initialMessages]);
const conversationList = ref([...props.conversations]);
const currentQuota = ref({ ...props.quota });

const userInput = ref('');
const isLoading = ref(false);
const errorMessage = ref(null);
const chatContainer = ref(null);
const showHistoryMobile = ref(false);

const suggestions = [
    '¿Cómo divido un proyecto grande en misiones?',
    '¿Cómo mantengo mi racha de hábitos en días ocupados?',
    'Me siento abrumado por un examen, ¿qué me recomiendas?',
    '¿Cómo puedo combinar mi temporizador Pomodoro con mis misiones?',
];

function formatMarkdown(text) {
    if (!text) return '';

    // Escapar caracteres HTML básicos para prevenir XSS
    let html = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Resaltar **negrita** y __negrita__
    html = html.replace(
        /\*\*(.*?)\*\*/g,
        '<strong class="font-bold text-content-primary">$1</strong>',
    );
    html = html.replace(/__(.*?)__/g, '<strong class="font-bold text-content-primary">$1</strong>');

    // Cursiva *texto*
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Viñetas de lista (- o *)
    html = html.replace(/^[\-\*]\s+(.*)$/gm, '<span class="inline-block pl-2">• $1</span>');

    // Preservar saltos de línea
    html = html.replace(/\n/g, '<br>');

    return html;
}

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}

onMounted(() => {
    scrollToBottom();
    track('ai.opened', 'ai_assistant', {
        messages_count: messages.value.length,
        quota_remaining: currentQuota.value.remaining,
    });
});

function startNewConversation() {
    conversationId.value = null;
    messages.value = [];
    errorMessage.value = null;
    showHistoryMobile.value = false;
}

async function selectConversation(conv) {
    if (conversationId.value === conv.id) return;

    isLoading.value = true;
    errorMessage.value = null;
    showHistoryMobile.value = false;

    try {
        const response = await axios.get(route('ai-assistant.conversation', conv.id));
        if (response.data.success) {
            conversationId.value = conv.id;
            messages.value = response.data.data.messages;
            scrollToBottom();
        }
    } catch (err) {
        errorMessage.value = 'No se pudo cargar la conversación seleccionada.';
    } finally {
        isLoading.value = false;
    }
}

function sendSuggestion(text) {
    userInput.value = text;
    sendMessage();
}

async function sendMessage() {
    const text = userInput.value.trim();
    if (!text || isLoading.value || currentQuota.value.is_exhausted) return;

    errorMessage.value = null;
    const t0 = Date.now();
    isLoading.value = true;

    // Añadir mensaje del usuario inmediatamente al chat local
    messages.value.push({
        id: Date.now(),
        role: 'user',
        content: text,
        created_at: new Date().toISOString(),
    });
    userInput.value = '';
    scrollToBottom();

    track('ai.consulted', 'ai_assistant', {
        input_length: text.length,
        quota_used: currentQuota.value.used_count,
    });

    try {
        const response = await axios.post(route('ai-assistant.consult'), {
            message: text,
            conversation_id: conversationId.value,
        });

        if (response.data.success) {
            const data = response.data.data;
            const isNewConv = !conversationId.value;
            conversationId.value = data.conversation_id;
            currentQuota.value = data.quota;

            messages.value.push({
                id: data.message_id,
                role: 'assistant',
                content: data.response,
                category: data.is_crisis ? 'crisis' : 'general',
                rating: null,
                created_at: new Date().toISOString(),
            });

            // Actualizar lista de historial local si era una nueva conversación
            if (isNewConv) {
                const nowStr = new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                });
                conversationList.value.unshift({
                    id: data.conversation_id,
                    title: 'Conversación ' + nowStr,
                    last_message: text.length > 35 ? text.substring(0, 35) + '...' : text,
                    updated_at: 'Ahora',
                });
            }

            track('ai.response_received', 'ai_assistant', {
                is_crisis: data.is_crisis,
                response_length: data.response.length,
            });
        }
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message ||
            'Ocurrió un error al procesar tu consulta. Intenta de nuevo.';
    } finally {
        // Tiempo mínimo de carga para que la animación del gato se
        // alcance a pintar aunque la respuesta llegue rápido.
        const elapsed = Date.now() - t0;
        if (elapsed < 2400) {
            await new Promise((r) => setTimeout(r, 2400 - elapsed));
        }
        isLoading.value = false;
        scrollToBottom();
    }
}

async function rateMessage(msg, stars) {
    if (msg.rating === stars) return;

    try {
        await axios.post(route('ai-assistant.rate'), {
            message_id: msg.id,
            rating: stars,
        });
        msg.rating = stars;
        track('advice.rated', 'ai_assistant', {
            message_id: msg.id,
            rating: stars,
        });
    } catch (err) {
        console.error('Error guardando calificación:', err);
    }
}
</script>

<template>
    <Head title="Asistente Edy — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header Card -->
            <BaseCard class="p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-1 shadow-sm"
                        >
                            <img
                                src="/assets/gifs/benny-typing-v2.gif"
                                alt="Edy Asistente IA"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <h1
                                class="font-display text-2xl font-bold tracking-tight text-content-primary"
                            >
                                Edy — Asistente de Apoyo
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Orientación empática para organizar tu estudio, descomponer misiones
                                y mantener tu constancia.
                            </p>
                        </div>
                    </div>

                    <!-- Quota Pill Counter & Mobile History Toggle -->
                    <div class="flex items-center gap-3 self-end sm:self-center">
                        <BaseButton
                            class="sm:hidden text-xs"
                            variant="ghost"
                            size="sm"
                            @click="showHistoryMobile = !showHistoryMobile"
                        >
                            📜 Historial ({{ conversationList.length }})
                        </BaseButton>

                        <div class="flex flex-col items-end shrink-0">
                            <div
                                class="flex items-center gap-2 text-xs font-semibold text-content-primary"
                            >
                                <span>⚡ Consultas hoy:</span>
                                <span
                                    class="rounded-full px-3 py-1 font-bold"
                                    :class="
                                        currentQuota.is_exhausted
                                            ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                                            : 'bg-surface-raised border border-border-interactive text-primary-strong'
                                    "
                                >
                                    {{ currentQuota.used_count }} / {{ currentQuota.max_quota }}
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] text-content-muted">Reinicia a las 00:00</p>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <!-- Layout Principal: Historial Sidebar + Chat Container -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-start">
                <!-- Panel Lateral de Historial (Escritorio / Móvil desplegable) -->
                <BaseCard
                    class="sm:col-span-4 p-4 space-y-3"
                    :class="[showHistoryMobile ? 'block' : 'hidden sm:block']"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-sm text-content-primary flex items-center gap-2">
                            <span>📜 Historial de Chats</span>
                        </h2>
                        <BaseButton
                            variant="primary"
                            size="sm"
                            class="text-xs"
                            @click="startNewConversation"
                        >
                            + Nuevo Chat
                        </BaseButton>
                    </div>

                    <div
                        v-if="conversationList.length === 0"
                        class="text-center py-6 text-xs text-content-muted"
                    >
                        Aún no tienes conversaciones guardadas.
                    </div>

                    <div v-else class="space-y-1.5 max-h-[440px] overflow-y-auto pr-1">
                        <button
                            v-for="conv in conversationList"
                            :key="conv.id"
                            type="button"
                            class="w-full text-left p-2.5 rounded-xl border transition-all text-xs space-y-1"
                            :class="[
                                conversationId === conv.id
                                    ? 'bg-primary-strong/10 border-primary-strong/40 text-content-primary font-semibold'
                                    : 'bg-surface-raised/40 border-border-interactive text-content-secondary hover:bg-surface-raised',
                            ]"
                            @click="selectConversation(conv)"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="font-medium text-content-primary truncate max-w-[140px]"
                                    >{{ conv.title }}</span
                                >
                                <span class="text-[10px] text-content-muted shrink-0">{{
                                    conv.updated_at
                                }}</span>
                            </div>
                            <p class="text-[11px] text-content-muted truncate">
                                {{ conv.last_message }}
                            </p>
                        </button>
                    </div>
                </BaseCard>

                <!-- Chat Interface Box -->
                <BaseCard class="sm:col-span-8 flex flex-col h-[520px] p-0 overflow-hidden">
                    <!-- Messages Scroll Area -->
                    <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                        <!-- Welcome Initial Prompt if no messages -->
                        <div
                            v-if="messages.length === 0"
                            class="text-center my-8 space-y-4 max-w-md mx-auto"
                        >
                            <div
                                class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-surface-raised p-2 border border-border-interactive shadow-sm"
                            >
                                <img
                                    src="/assets/Edy.png"
                                    alt="Edy"
                                    class="h-full w-full object-contain"
                                />
                            </div>
                            <h2 class="font-bold text-lg text-content-primary">¡Hola! Soy Edy</h2>
                            <p class="text-sm text-content-secondary">
                                Puedo ayudarte a organizar tus actividades académicas, sugerirte
                                estrategias contra la postergación o darte ideas para tus hábitos.
                            </p>
                        </div>

                        <!-- Chat Messages List -->
                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            class="flex flex-col"
                            :class="msg.role === 'user' ? 'items-end' : 'items-start'"
                        >
                            <!-- Crisis Alert Banner -->
                            <div
                                v-if="msg.category === 'crisis'"
                                class="mb-2 w-full rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300 flex items-start gap-2"
                            >
                                <span class="text-base">🚨</span>
                                <div>
                                    <strong class="font-bold"
                                        >Protocolo de Atención de Salud Mental (Línea 113
                                        MINSA):</strong
                                    >
                                    <p class="mt-0.5">
                                        Si requieres orientación inmediata o contención emocional
                                        gratuita en Perú, marca el **113 opción 5**.
                                    </p>
                                </div>
                            </div>

                            <!-- Message Bubble with Rendered Markdown -->
                            <div
                                class="max-w-[88%] sm:max-w-[80%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm transition-all"
                                :class="[
                                    msg.role === 'user'
                                        ? 'bg-primary-strong text-white rounded-br-none'
                                        : msg.category === 'crisis'
                                          ? 'bg-surface-raised border border-rose-500/40 text-content-primary rounded-bl-none'
                                          : 'bg-surface-raised border border-border-interactive text-content-primary rounded-bl-none',
                                ]"
                            >
                                <!-- Se renderiza Markdown formato negrita sin mostrar asteriscos -->
                                <div
                                    class="font-sans leading-relaxed text-sm"
                                    v-html="formatMarkdown(msg.content)"
                                ></div>

                                <!-- Rating stars for assistant responses -->
                                <div
                                    v-if="msg.role === 'assistant'"
                                    class="mt-3 pt-2 border-t border-border-interactive/30 flex items-center justify-between text-xs text-content-muted"
                                >
                                    <span
                                        class="font-medium text-[11px] transition-colors"
                                        :class="
                                            msg.rating ? 'text-amber-400' : 'text-content-muted'
                                        "
                                    >
                                        {{
                                            msg.rating
                                                ? `¡Gracias por valorar! (${msg.rating}/5 ★)`
                                                : '¿Te fue útil este consejo?'
                                        }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button
                                            v-for="star in 5"
                                            :key="star"
                                            type="button"
                                            class="hover:scale-125 transition-transform text-base leading-none p-0.5 focus:outline-none"
                                            :class="[
                                                (
                                                    msg.hoverRating
                                                        ? msg.hoverRating >= star
                                                        : msg.rating >= star
                                                )
                                                    ? 'text-amber-400 font-bold drop-shadow-[0_0_6px_rgba(251,191,36,0.6)]'
                                                    : 'text-content-muted/40 hover:text-amber-400/80',
                                            ]"
                                            @mouseenter="msg.hoverRating = star"
                                            @mouseleave="msg.hoverRating = null"
                                            @click="rateMessage(msg, star)"
                                        >
                                            ★
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State Indicator -->
                        <div
                            v-if="isLoading"
                            class="flex flex-col items-center gap-3 px-4 py-6 text-sm text-content-secondary italic"
                        >
                            <img
                                src="/assets/gifs/benny-typing-v2.gif"
                                alt="Edy escribiendo"
                                class="h-24 w-24 object-contain"
                            />
                            <div class="flex items-center gap-1.5">
                                <span>Edy está analizando tus métricas y redactando respuesta</span>
                                <span class="inline-flex items-end gap-1">
                                    <span
                                        v-for="n in 3"
                                        :key="n"
                                        class="h-1.5 w-1.5 rounded-full bg-content-secondary animate-bounce"
                                        :style="{ animationDelay: n * 0.15 + 's' }"
                                    />
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Error Alert Banner -->
                    <div
                        v-if="errorMessage"
                        class="bg-rose-500/10 border-t border-rose-500/20 px-4 py-2 text-xs text-rose-300"
                    >
                        ⚠️ {{ errorMessage }}
                    </div>

                    <!-- Suggestion Chips (when quota available) -->
                    <div
                        v-if="!currentQuota.is_exhausted && !isLoading"
                        class="px-4 py-2 border-t border-border-interactive/50 bg-surface-raised/30 flex gap-2 overflow-x-auto text-xs no-scrollbar"
                    >
                        <button
                            v-for="(sug, idx) in suggestions"
                            :key="idx"
                            type="button"
                            class="whitespace-nowrap rounded-full bg-surface-raised px-3 py-1 text-content-secondary border border-border-interactive hover:border-primary-strong hover:text-primary-strong transition-colors shrink-0"
                            @click="sendSuggestion(sug)"
                        >
                            💡 {{ sug }}
                        </button>
                    </div>

                    <!-- Input Controls Box -->
                    <div class="p-3 border-t border-border-interactive bg-surface">
                        <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                            <input
                                v-model="userInput"
                                type="text"
                                placeholder="Escribe tu consulta a Edy sobre misiones, pomodoro o hábitos..."
                                class="flex-1 rounded-xl border border-border bg-surface-raised px-4 py-2.5 text-sm text-content-primary placeholder-content-muted focus:border-primary-strong focus:outline-none"
                                :disabled="isLoading || currentQuota.is_exhausted"
                            />
                            <BaseButton
                                type="submit"
                                variant="primary"
                                :disabled="
                                    isLoading || !userInput.trim() || currentQuota.is_exhausted
                                "
                            >
                                Enviar 🚀
                            </BaseButton>
                        </form>
                        <p
                            v-if="currentQuota.is_exhausted"
                            class="mt-2 text-center text-xs text-rose-400 font-medium"
                        >
                            Has completado tus 5 consultas de hoy. Tu cuota se renovará
                            automáticamente a las 00:00.
                        </p>
                    </div>
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>
