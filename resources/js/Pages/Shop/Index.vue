<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import { triggerConfetti, triggerHapticVibration } from '@/utils/celebration';
import {
    Gift,
    Coins,
    Plus,
    Pencil,
    Trash2,
    Sparkles,
    CheckCircle,
    ShoppingBag,
} from '@lucide/vue';

const props = defineProps({
    coins:       { type: Number, default: 0 },
    rewards:     { type: Array,  default: () => [] },
    redemptions: { type: Array,  default: () => [] },
    templates:   { type: Array,  default: () => [] },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const isProcessing = ref(false);

const rewardForm = ref({
    id: null,
    title: '',
    cost_coins: 150,
    icon: '🎁',
    category: 'ocio',
});

const iconOptions = ['☕', '🎬', '🎮', '🍕', '🌳', '🎧', '🛍️', '🍣', '😴', '🍦', '📚', '🚴', '🍔', '🎁', '✨'];

const categories = [
    { value: 'ocio', label: '🎮 Ocio y Entretenimiento' },
    { value: 'comida', label: '🍕 Comida y Snacks' },
    { value: 'descanso', label: '🌳 Descanso y Autocuidado' },
    { value: 'social', label: '👥 Social y Salidas' },
    { value: 'otro', label: '✨ Otro' },
];

function openCreateModal() {
    rewardForm.value = {
        id: null,
        title: '',
        cost_coins: 150,
        icon: '🎁',
        category: 'ocio',
    };
    showCreateModal.value = true;
}

function openEditModal(reward) {
    rewardForm.value = {
        id: reward.id,
        title: reward.title,
        cost_coins: reward.cost_coins,
        icon: reward.icon || '🎁',
        category: reward.category || 'ocio',
    };
    showEditModal.value = true;
}

function addFromTemplate(template) {
    rewardForm.value = {
        id: null,
        title: template.title,
        cost_coins: template.cost_coins,
        icon: template.icon || '🎁',
        category: template.category || 'ocio',
    };
    showCreateModal.value = true;
}

function submitReward() {
    isProcessing.value = true;
    if (rewardForm.value.id) {
        router.put(
            route('shop.rewards.update', { id: rewardForm.value.id }),
            rewardForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isProcessing.value = false;
                    showEditModal.value = false;
                },
                onError: () => {
                    isProcessing.value = false;
                },
            }
        );
    } else {
        router.post(
            route('shop.rewards.store'),
            rewardForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isProcessing.value = false;
                    showCreateModal.value = false;
                },
                onError: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
}

function deleteReward(rewardId) {
    if (!confirm('¿Deseas eliminar esta recompensa de tu catálogo?')) return;
    router.delete(route('shop.rewards.destroy', { id: rewardId }), {
        preserveScroll: true,
    });
}

function redeemReward(reward) {
    if (props.coins < reward.cost_coins) {
        alert('No tienes suficientes monedas para este canje.');
        return;
    }

    if (!confirm(`¿Canjear "${reward.title}" por ${reward.cost_coins} monedas?`)) return;

    isProcessing.value = true;
    router.post(
        route('shop.redeem', { id: reward.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                triggerConfetti();
                triggerHapticVibration([50, 40, 90]);
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function markAsUsed(redemptionId) {
    router.patch(
        route('shop.redemptions.used', { id: redemptionId }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                triggerConfetti();
            },
        }
    );
}
</script>

<template>
    <Head title="Tienda de Recompensas — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header con Saldo de Monedas -->
            <div class="p-6 rounded-3xl bg-surface-raised border border-border shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-2xl bg-warning/15 text-warning text-2xl font-bold">🪙</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="font-display text-2xl sm:text-3xl font-bold text-content-primary">Tienda de Recompensas</h1>
                            </div>
                            <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
                                Canjea tus monedas ganadas con hábitos, misiones y rutinas por premios reales de autocuidado.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Saldo Gigante -->
                <div class="flex items-center gap-4 bg-surface p-4 rounded-2xl border border-border shrink-0">
                    <div class="text-right">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-content-muted block">Tu Saldo Disponible</span>
                        <div class="flex items-center gap-1.5 justify-end">
                            <span class="font-display text-3xl font-black text-warning">{{ coins }}</span>
                            <span class="text-sm font-bold text-content-secondary">Monedas</span>
                        </div>
                    </div>
                    <BaseButton variant="primary" size="sm" @click="openCreateModal">
                        <Plus :size="15" />
                        Crear Recompensa
                    </BaseButton>
                </div>
            </div>

            <!-- Catálogo de Recompensas Activas -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-lg font-bold text-content-primary flex items-center gap-2">
                            <span>🎁</span> Mis Recompensas de Autocuidado
                        </h2>
                        <p class="text-xs text-content-secondary">Premios de la vida real que desbloqueas con tu disciplina diaria</p>
                    </div>
                </div>

                <div v-if="rewards.length === 0" class="p-8 text-center bg-surface rounded-2xl border border-dashed border-border space-y-3">
                    <p class="text-sm text-content-muted">No tienes recompensas configuradas.</p>
                    <BaseButton variant="primary" size="sm" @click="openCreateModal">
                        Crear Mi Primera Recompensa
                    </BaseButton>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="reward in rewards"
                        :key="reward.id"
                        class="p-5 rounded-3xl bg-surface border transition-all duration-300 flex flex-col justify-between gap-4 group hover:shadow-md"
                        :class="reward.can_afford ? 'border-border hover:border-primary/50' : 'border-border/60 opacity-80'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl p-3 rounded-2xl bg-surface-raised border border-border/80 group-hover:scale-110 transition-transform">
                                    {{ reward.icon }}
                                </span>
                                <div>
                                    <h3 class="font-display font-bold text-sm text-content-primary leading-tight">
                                        {{ reward.title }}
                                    </h3>
                                    <span class="text-[11px] text-content-muted capitalize block mt-0.5">
                                        {{ reward.category }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    type="button"
                                    class="p-1 text-content-muted hover:text-primary-strong"
                                    title="Editar"
                                    @click="openEditModal(reward)"
                                >
                                    <Pencil :size="13" />
                                </button>
                                <button
                                    type="button"
                                    class="p-1 text-content-muted hover:text-danger-text"
                                    title="Eliminar"
                                    @click="deleteReward(reward.id)"
                                >
                                    <Trash2 :size="13" />
                                </button>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-border/70 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-1 text-warning font-bold text-sm">
                                <span>🪙</span>
                                <span>{{ reward.cost_coins }}</span>
                            </div>

                            <button
                                type="button"
                                :disabled="!reward.can_afford || isProcessing"
                                :class="[
                                    'px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5',
                                    reward.can_afford
                                        ? 'bg-primary-strong text-on-primary-strong hover:opacity-90 active:scale-95 shadow-primary-strong/20'
                                        : 'bg-surface-sunken text-content-muted cursor-not-allowed border border-border'
                                ]"
                                @click="redeemReward(reward)"
                            >
                                <Sparkles :size="13" />
                                {{ reward.can_afford ? 'Canjear' : 'Te faltan monedas' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sugerencias / Plantillas Rápidas -->
            <BaseCard class="p-5 space-y-3">
                <h3 class="font-display font-bold text-sm text-content-primary flex items-center gap-2">
                    <span>💡</span> Ideas y Plantillas para Estudiantes
                </h3>
                <p class="text-xs text-content-secondary">
                    Haz clic en una idea para añadirla rápidamente a tus recompensas:
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-1">
                    <button
                        v-for="(tpl, idx) in templates"
                        :key="idx"
                        type="button"
                        class="p-3.5 rounded-2xl bg-surface border border-border/80 hover:border-primary/50 hover:bg-surface-raised text-left transition-all group flex flex-col justify-between gap-2"
                        @click="addFromTemplate(tpl)"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ tpl.icon }}</span>
                            <span class="font-bold text-xs text-content-primary group-hover:text-primary-strong leading-tight">
                                {{ tpl.title }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-content-muted pt-1">
                            <span class="text-warning font-bold">🪙 {{ tpl.cost_coins }}</span>
                            <span class="text-primary-strong font-semibold">+ Añadir</span>
                        </div>
                    </button>
                </div>
            </BaseCard>

            <!-- Historial de Canjes Realizados -->
            <BaseCard class="p-5 space-y-4">
                <div class="border-b border-border/70 pb-3">
                    <h3 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                        <span>📜</span> Historial de Canjes
                    </h3>
                    <p class="text-xs text-content-secondary">Premios canjeados recientemente y su estado de uso</p>
                </div>

                <div v-if="redemptions.length === 0" class="p-6 text-center text-xs text-content-muted">
                    Aún no has canjeado ninguna recompensa. ¡Sigue acumulando monedas y date un gusto!
                </div>

                <div v-else class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                    <div
                        v-for="red in redemptions"
                        :key="red.id"
                        class="flex items-center justify-between p-3.5 rounded-2xl bg-surface border border-border/80 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ red.icon }}</span>
                            <div>
                                <h4 class="font-bold text-sm text-content-primary">{{ red.title }}</h4>
                                <span class="text-[11px] text-content-muted block">
                                    Canjeado el {{ red.redeemed_at }} • Costo: 🪙 {{ red.cost_coins }} monedas
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                v-if="red.status === 'used'"
                                class="px-2.5 py-1 rounded-full bg-success/15 text-success font-bold text-[11px] flex items-center gap-1"
                            >
                                <CheckCircle :size="12" /> Disfrutado
                            </span>
                            <button
                                v-else
                                type="button"
                                class="px-3 py-1.5 rounded-xl bg-success text-on-accent font-bold text-xs shadow-sm hover:opacity-90 transition-all active:scale-95 flex items-center gap-1"
                                @click="markAsUsed(red.id)"
                            >
                                🥳 Marcar Disfrutado
                            </button>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Modal: Crear / Editar Recompensa -->
        <BaseModal
            :show="showCreateModal || showEditModal"
            :title="rewardForm.id ? '✏️ Editar Recompensa' : '➕ Nueva Recompensa Personal'"
            @close="showCreateModal = false; showEditModal = false"
        >
            <form class="space-y-4" @submit.prevent="submitReward">
                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Nombre de la recompensa</label>
                    <BaseInput v-model="rewardForm.title" placeholder="Ej. 1 hora de videojuegos o postre favorito" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Costo en Monedas</label>
                        <BaseInput v-model="rewardForm.cost_coins" type="number" min="10" max="10000" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Categoría</label>
                        <BaseSelect v-model="rewardForm.category" :options="categories" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1.5">Icono representativo</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="icon in iconOptions"
                            :key="icon"
                            type="button"
                            :class="[
                                'w-10 h-10 rounded-xl text-xl flex items-center justify-center border transition-all',
                                rewardForm.icon === icon ? 'bg-primary/20 border-primary-strong scale-110' : 'bg-surface border-border hover:bg-surface-raised'
                            ]"
                            @click="rewardForm.icon = icon"
                        >
                            {{ icon }}
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showCreateModal = false; showEditModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">
                        {{ rewardForm.id ? 'Guardar Cambios' : 'Crear Recompensa' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
