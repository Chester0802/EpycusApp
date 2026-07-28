<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Perfil</h1>

        <div class="space-y-6">
            <BaseCard>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </BaseCard>

            <BaseCard>
                <UpdatePasswordForm class="max-w-xl" />
            </BaseCard>

            <BaseCard>
                <DeleteUserForm class="max-w-xl" />
            </BaseCard>
        </div>
    </AppLayout>
</template>
