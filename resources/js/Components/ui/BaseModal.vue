<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    closeable: { type: Boolean, default: true },
    maxWidth: { type: String, default: 'lg' },
});

const emit = defineEmits(['close']);

function close() {
    if (!props.closeable) return;
    emit('close');
}

function handleKeydown(event) {
    if (event.key === 'Escape' && props.show && props.closeable) close();
}

watch(
    () => props.show,
    (show) => {
        if (typeof document !== 'undefined') {
            document.body.style.overflow = show ? 'hidden' : '';
        }
    },
);

const maxWidthClass = computed(() => {
    return {
        sm: 'lg:max-w-sm',
        md: 'lg:max-w-md',
        lg: 'lg:max-w-lg',
        xl: 'lg:max-w-xl',
        '2xl': 'lg:max-w-2xl',
        '3xl': 'lg:max-w-3xl',
    }[props.maxWidth] || 'lg:max-w-lg';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
                @keydown="handleKeydown"
                @click.self="closeable ? close() : null"
            >
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="show"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                        class="panel-raised bg-surface-raised text-content-primary border border-border/80 shadow-2xl max-h-[90vh] w-full overflow-y-auto rounded-2xl p-5 sm:p-6 transition-all"
                        :class="maxWidthClass"
                        tabindex="-1"
                    >
                        <div
                            v-if="title || closeable"
                            class="mb-4 flex items-center justify-between"
                        >
                            <h2 v-if="title" class="text-xl font-bold text-content-primary">
                                {{ title }}
                            </h2>
                            <button
                                v-if="closeable"
                                type="button"
                                class="ml-auto flex min-h-[36px] min-w-[36px] items-center justify-center rounded-xl text-content-secondary hover:text-content-primary hover:bg-surface-sunken transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                                aria-label="Cerrar"
                                @click="close"
                            >
                                ✕
                            </button>
                        </div>
                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
