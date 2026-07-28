<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);

function close() {
    emit('close');
}

function handleKeydown(event) {
    if (event.key === 'Escape' && props.show) close();
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
                @click.self="close"
            >
                <Transition
                    enter-active-class="transition-transform duration-200 ease-out"
                    enter-from-class="translate-y-full lg:translate-y-4 lg:opacity-0"
                    leave-active-class="transition-transform duration-200 ease-out"
                    leave-to-class="translate-y-full lg:translate-y-4 lg:opacity-0"
                >
                    <div
                        v-if="show"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                        class="panel-raised max-h-[90vh] w-full overflow-y-auto rounded-t-xl p-6 lg:max-w-lg lg:rounded-xl"
                        tabindex="-1"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h2 v-if="title" class="text-xl text-content-primary">{{ title }}</h2>
                            <button
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
