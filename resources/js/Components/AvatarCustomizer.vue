<script setup>
import { ref, reactive, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';

const props = defineProps({
    initialOptions: {
        type: Object,
        default: () => ({}),
    },
    gender: {
        type: String,
        default: 'm',
    },
});

const emit = defineEmits(['saved']);

// Opciones de personalización
const SKIN_TONES = [
    { id: 'ffdbb4', label: 'Claro', color: '#ffdbb4' },
    { id: 'edb98a', label: 'Cálido', color: '#edb98a' },
    { id: 'd08b5b', label: 'Canela', color: '#d08b5b' },
    { id: 'ae5d29', label: 'Moreno', color: '#ae5d29' },
    { id: '694d3d', label: 'Oscuro', color: '#694d3d' },
];

const HAIR_OPTIONS = [
    { id: 'short1', label: 'Corto 1', category: 'short' },
    { id: 'short2', label: 'Corto 2', category: 'short' },
    { id: 'short3', label: 'Corto 3', category: 'short' },
    { id: 'short4', label: 'Ondulado', category: 'short' },
    { id: 'pomp', label: 'Tupé / Copete', category: 'short' },
    { id: 'flatTop', label: 'Plano', category: 'short' },
    { id: 'afro', label: 'Afro', category: 'short' },
    { id: 'shaved1', label: 'Rapado', category: 'short' },
    { id: 'long', label: 'Largo Recto', category: 'long' },
    { id: 'longCurly', label: 'Largo Rizado', category: 'long' },
    { id: 'longBangs', label: 'Largo c/ Flequillo', category: 'long' },
    { id: 'bun', label: 'Moño Alto', category: 'long' },
    { id: 'buns', label: 'Doble Moño', category: 'long' },
    { id: 'medium1', label: 'Medio', category: 'medium' },
    { id: 'bangs', label: 'Flequillo', category: 'medium' },
];

const FACE_OPTIONS = [
    { id: 'smile', label: 'Sonriente 😃' },
    { id: 'smileBig', label: 'Alegre 😁' },
    { id: 'cute', label: 'Tierno 😊' },
    { id: 'calm', label: 'Tranquilo 😌' },
    { id: 'driven', label: 'Enfocado 🤓' },
    { id: 'explaining', label: 'Explicando 🗣️' },
    { id: 'serious', label: 'Serio 😐' },
    { id: 'solemn', label: 'Solemne 🤔' },
];

const ACCESSORIES_OPTIONS = [
    { id: 'none', label: 'Sin lentes' },
    { id: 'glasses', label: 'Cuadrados 👓' },
    { id: 'glasses2', label: 'Retro 🕶️' },
    { id: 'glasses3', label: 'Finos 👓' },
    { id: 'glasses4', label: 'Ejecutivos 🤓' },
    { id: 'glasses5', label: 'Redondos 👓' },
    { id: 'sunglasses', label: 'Sol Clásicas 🕶️' },
    { id: 'sunglasses2', label: 'Sol Modernas 🕶️' },
];

const FACIAL_HAIR_OPTIONS = [
    { id: 'none', label: 'Sin barba' },
    { id: 'moustache1', label: 'Bigote clásico 👨' },
    { id: 'chin', label: 'Perilla corta 🧔' },
    { id: 'goatee1', label: 'Candado 🧔' },
    { id: 'full', label: 'Barba completa 🧔' },
];

const CLOTHING_COLORS = [
    { id: '1e293b', label: 'Azul Noche', color: '#1e293b' },
    { id: '0284c7', label: 'Cyan Tech', color: '#0284c7' },
    { id: 'f97316', label: 'Naranja Vivo', color: '#f97316' },
    { id: '10b981', label: 'Verde Esmeralda', color: '#10b981' },
    { id: 'e11d48', label: 'Rojo Carmesí', color: '#e11d48' },
    { id: '7c3aed', label: 'Violeta Real', color: '#7c3aed' },
    { id: 'ffffff', label: 'Blanco Guardapolvo', color: '#ffffff' },
    { id: '18181b', label: 'Carbón Formal', color: '#18181b' },
];

const BG_COLORS = [
    { id: 'fff8fb', label: 'Rosa Suave', color: '#fff8fb' },
    { id: 'f2ecff', label: 'Lavanda', color: '#f2ecff' },
    { id: 'eaf6f0', label: 'Menta', color: '#eaf6f0' },
    { id: 'fff4e0', label: 'Melocotón', color: '#fff4e0' },
    { id: 'eaf6ff', label: 'Celeste', color: '#eaf6ff' },
    { id: '0e0a1a', label: 'Noche Estelar', color: '#0e0a1a' },
];

const form = useForm({
    skinColor: props.initialOptions?.skinColor || 'ffdbb4',
    head: props.initialOptions?.head || (props.gender === 'f' ? 'long' : 'short1'),
    face: props.initialOptions?.face || 'smile',
    accessories: props.initialOptions?.accessories || 'none',
    facialHair: props.initialOptions?.facialHair || 'none',
    clothingColor: props.initialOptions?.clothingColor || '1e293b',
    backgroundColor: props.initialOptions?.backgroundColor || 'fff8fb',
});

const activeTab = ref('skin'); // 'skin' | 'hair' | 'face' | 'accessories' | 'facialHair' | 'clothing' | 'bg'
const showSavedMessage = ref(false);

function submit() {
    form.patch(route('profile.avatar.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSavedMessage.value = true;
            setTimeout(() => {
                showSavedMessage.value = false;
            }, 3000);
            emit('saved');
        },
    });
}
</script>

<template>
    <BaseCard class="p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <!-- Columna Izquierda: Vista previa del avatar personalizable -->
            <div class="flex flex-col items-center gap-4 lg:w-72 lg:shrink-0">
                <div class="relative flex h-52 w-52 items-center justify-center rounded-3xl border-2 border-border-interactive bg-surface-raised/40 p-4 shadow-lg">
                    <ProceduralAvatar
                        :options="{
                            skinColor: form.skinColor,
                            head: form.head,
                            face: form.face,
                            accessories: form.accessories,
                            facialHair: form.facialHair,
                            clothingColor: form.clothingColor,
                            backgroundColor: form.backgroundColor,
                        }"
                        :size="256"
                    />
                </div>
                <p class="text-xs text-content-muted font-medium text-center">
                    Vista previa de tu identidad en Epycus
                </p>

                <BaseButton
                    variant="primary"
                    class="w-full"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Guardando...' : 'Guardar Avatar' }}
                </BaseButton>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div v-if="showSavedMessage" class="rounded-xl bg-success/20 px-3 py-1.5 text-xs font-bold text-success text-center">
                        ✓ Avatar guardado correctamente
                    </div>
                </Transition>
            </div>

            <!-- Columna Derecha: Tabs y selectores de personalización -->
            <div class="flex-1 space-y-5">
                <div>
                    <h2 class="font-display text-xl font-bold text-content-primary">
                        Diseña tu Avatar
                    </h2>
                    <p class="text-xs text-content-secondary mt-0.5">
                        Personaliza tu personaje. Este avatar aparecerá en tu Dashboard, Ranking, Grupos de Estudio y Perfil.
                    </p>
                </div>

                <!-- Tabs de Navegación -->
                <div class="flex flex-wrap gap-1.5 border-b border-border-interactive pb-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'skin' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'skin'"
                    >
                        🎨 Piel
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'hair' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'hair'"
                    >
                        💇 Cabello
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'face' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'face'"
                    >
                        😃 Expresión
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'accessories' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'accessories'"
                    >
                        👓 Lentes
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'facialHair' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'facialHair'"
                    >
                        🧔 Barba
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'clothing' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'clothing'"
                    >
                        👕 Ropa
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="activeTab === 'bg' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                        @click="activeTab = 'bg'"
                    >
                        🖼️ Fondo
                    </button>
                </div>

                <!-- Tab 1: Piel -->
                <div v-if="activeTab === 'skin'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige tu tono de piel:</span>
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-for="tone in SKIN_TONES"
                            :key="tone.id"
                            type="button"
                            class="flex flex-col items-center gap-1.5 rounded-xl border p-2 transition-all"
                            :class="form.skinColor === tone.id ? 'border-primary-strong ring-2 ring-primary-strong/30 bg-primary-strong/5' : 'border-border-interactive hover:bg-surface-raised'"
                            @click="form.skinColor = tone.id"
                        >
                            <span class="h-8 w-8 rounded-full shadow-inner border border-black/10" :style="{ backgroundColor: tone.color }" />
                            <span class="text-[11px] font-medium text-content-primary">{{ tone.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 2: Cabello -->
                <div v-if="activeTab === 'hair'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige tu estilo de cabello:</span>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <button
                            v-for="hair in HAIR_OPTIONS"
                            :key="hair.id"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-left text-xs font-medium transition-all"
                            :class="form.head === hair.id ? 'border-primary-strong bg-primary-strong text-on-primary-strong shadow-sm' : 'border-border-interactive text-content-secondary hover:bg-surface-raised hover:text-content-primary'"
                            @click="form.head = hair.id"
                        >
                            {{ hair.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab 3: Expresión -->
                <div v-if="activeTab === 'face'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige tu expresión facial:</span>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <button
                            v-for="face in FACE_OPTIONS"
                            :key="face.id"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-center text-xs font-medium transition-all"
                            :class="form.face === face.id ? 'border-primary-strong bg-primary-strong text-on-primary-strong shadow-sm' : 'border-border-interactive text-content-secondary hover:bg-surface-raised hover:text-content-primary'"
                            @click="form.face = face.id"
                        >
                            {{ face.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab 4: Lentes -->
                <div v-if="activeTab === 'accessories'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige tus lentes o anteojos:</span>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <button
                            v-for="acc in ACCESSORIES_OPTIONS"
                            :key="acc.id"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-center text-xs font-medium transition-all"
                            :class="form.accessories === acc.id ? 'border-primary-strong bg-primary-strong text-on-primary-strong shadow-sm' : 'border-border-interactive text-content-secondary hover:bg-surface-raised hover:text-content-primary'"
                            @click="form.accessories = acc.id"
                        >
                            {{ acc.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab 5: Barba -->
                <div v-if="activeTab === 'facialHair'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige tu estilo de vello facial:</span>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <button
                            v-for="fh in FACIAL_HAIR_OPTIONS"
                            :key="fh.id"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-center text-xs font-medium transition-all"
                            :class="form.facialHair === fh.id ? 'border-primary-strong bg-primary-strong text-on-primary-strong shadow-sm' : 'border-border-interactive text-content-secondary hover:bg-surface-raised hover:text-content-primary'"
                            @click="form.facialHair = fh.id"
                        >
                            {{ fh.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab 6: Ropa -->
                <div v-if="activeTab === 'clothing'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige el color de tu vestimenta:</span>
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-for="c in CLOTHING_COLORS"
                            :key="c.id"
                            type="button"
                            class="flex flex-col items-center gap-1.5 rounded-xl border p-2 transition-all"
                            :class="form.clothingColor === c.id ? 'border-primary-strong ring-2 ring-primary-strong/30 bg-primary-strong/5' : 'border-border-interactive hover:bg-surface-raised'"
                            @click="form.clothingColor = c.id"
                        >
                            <span class="h-8 w-8 rounded-full shadow-inner border border-black/10" :style="{ backgroundColor: c.color }" />
                            <span class="text-[11px] font-medium text-content-primary">{{ c.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 7: Fondo -->
                <div v-if="activeTab === 'bg'" class="space-y-3">
                    <span class="text-xs font-semibold text-content-secondary">Elige el color de fondo de tu avatar:</span>
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-for="bg in BG_COLORS"
                            :key="bg.id"
                            type="button"
                            class="flex flex-col items-center gap-1.5 rounded-xl border p-2 transition-all"
                            :class="form.backgroundColor === bg.id ? 'border-primary-strong ring-2 ring-primary-strong/30 bg-primary-strong/5' : 'border-border-interactive hover:bg-surface-raised'"
                            @click="form.backgroundColor = bg.id"
                        >
                            <span class="h-8 w-8 rounded-full shadow-inner border border-black/10" :style="{ backgroundColor: bg.color }" />
                            <span class="text-[11px] font-medium text-content-primary">{{ bg.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BaseCard>
</template>
