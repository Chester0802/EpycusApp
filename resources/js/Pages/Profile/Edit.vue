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
    avatarImage: {
        type: String,
        default: null,
    },
    participantCode: {
        type: String,
        default: null,
    },
    profileData: {
        type: Object,
        default: () => ({}),
    },
});

const labelMap = {
    alias: 'Alias',
    career: 'Carrera',
    cycle: 'Ciclo',
    avatarGender: 'Género del avatar',
    institutionType: 'Tipo de institución',
};

const genderMap = {
    m: 'Masculino',
    f: 'Femenino',
};

const instMap = {
    universidad: 'Universidad',
    instituto: 'Instituto',
};
</script>

<template>
    <Head title="Perfil" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Perfil</h1>

        <div class="space-y-6">
            <BaseCard>
                <div class="flex flex-col items-center gap-6 lg:flex-row">
                    <div
                        v-if="avatarImage"
                        class="flex h-40 w-60 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-4 lg:h-[360px] lg:w-[360px]"
                    >
                        <img :src="avatarImage" alt="Tu avatar" class="h-full w-full object-contain" />
                    </div>

                    <div class="w-full flex-1 space-y-4">
                        <h2 class="font-display text-xl text-content-primary">Datos del perfil</h2>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div v-for="(value, key) in profileData" :key="key">
                                <p class="text-xs uppercase tracking-wide text-content-muted">
                                    {{ labelMap[key] || key }}
                                </p>
                                <p class="text-base text-content-primary">
                                    {{ key === 'avatarGender' ? (genderMap[value] || value) : key === 'institutionType' ? (instMap[value] || value) : value || '—' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="participantCode">
                            <p class="text-xs uppercase tracking-wide text-content-muted">
                                Código de participante
                            </p>
                            <p class="font-mono text-sm text-content-secondary">
                                {{ participantCode }}
                            </p>
                        </div>
                    </div>
                </div>
            </BaseCard>

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
