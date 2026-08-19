<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    closeable: { type: Boolean, default: true },
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
        document.body.style.overflow = show ? 'hidden' : '';
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200 ease-out"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-end justify-center bg-backdrop lg:items-center"
                @keydown="handleKeydown"
                @click.self="closeable ? close() : null"
            >
                <Transition
                    enter-active-class="transition-transform duration-200 ease-out"
                    enter-from-class="translate-y-full lg:translate-y-4 lg:opacity-0"
                    leave-active-class="transition-transform duration-200 ease-out"
                    leave-to-class="translate-y-full lg:translate-y-4 lg:opacity-0"
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                        class="panel-raised bg-surface-raised text-content-primary border border-border/80 shadow-2xl max-h-[90vh] w-full overflow-y-auto rounded-t-xl p-6 lg:max-w-lg lg:rounded-xl"
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
                                class="ml-auto flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-content-secondary hover:text-content-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
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
