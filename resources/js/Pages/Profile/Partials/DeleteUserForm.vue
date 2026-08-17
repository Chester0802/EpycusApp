<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const page = usePage();
const isGoogleUser = computed(() => Boolean(page.props.auth?.user?.google_id));

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    if (!isGoogleUser.value) {
        nextTick(() => passwordInput.value?.focus());
    }
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => {
            if (!isGoogleUser.value) {
                passwordInput.value?.focus();
            }
        },
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-xl font-display text-content-primary">
                Eliminar cuenta
            </h2>
            <p class="mt-1 text-sm text-content-secondary">
                Una vez eliminada tu cuenta, todos sus datos se perderán permanentemente.
                Antes de eliminar, asegúrate de haber respaldado cualquier información que
                desees conservar.
            </p>
        </header>

        <BaseButton variant="danger" @click="confirmUserDeletion">
            Eliminar cuenta
        </BaseButton>

        <BaseModal
            :show="confirmingUserDeletion"
            title="¿Eliminar cuenta permanentemente?"
            @close="closeModal"
        >
            <p v-if="!isGoogleUser" class="text-sm text-content-secondary">
                Esta acción es irreversible. Ingresa tu contraseña para confirmar que
                deseas eliminar tu cuenta permanentemente.
            </p>
            <p v-else class="text-sm text-content-secondary">
                Tu cuenta está vinculada con <strong>Google</strong>. Esta acción es <strong>irreversible</strong> y eliminará permanentemente tu perfil, avance de gamificación, hábitos, misiones y estadísticas asociadas.
            </p>

            <div v-if="!isGoogleUser" class="mt-4">
                <BaseInput
                    id="delete-password"
                    ref="passwordInput"
                    label="Contraseña"
                    type="password"
                    :model-value="form.password"
                    :error="form.errors.password"
                    @update:model-value="form.password = $event"
                    @keyup.enter="deleteUser"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <BaseButton variant="secondary" @click="closeModal">
                    Cancelar
                </BaseButton>

                <BaseButton
                    variant="danger"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteUser"
                >
                    Eliminar cuenta
                </BaseButton>
            </div>
        </BaseModal>
    </section>
</template>
