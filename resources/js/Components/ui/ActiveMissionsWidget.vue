<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    missions: {
        type: Array,
        required: true,
    }
});

function formatDueDate(dateString) {
    if (!dateString) return 'Sin fecha límite';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-PE', { day: 'numeric', month: 'short' });
}

function isOverdue(dateString) {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
}
</script>

<template>
    <BaseCard class="p-6 relative overflow-hidden border-border/50 bg-gradient-to-br from-surface to-surface-raised">
        <!-- Glow accent -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-accent/10 rounded-full blur-3xl opacity-50 pointer-events-none -mt-10 -mr-10"></div>
        
        <div class="relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="font-display text-xl font-bold text-content-primary flex items-center gap-2">
                    <AppIcon name="target" :size="24" class="text-accent" />
                    Misiones Activas
                </h2>
                <p class="text-sm text-content-secondary mt-1">Tus objetivos más urgentes para ganar XP.</p>
            </div>
            
            <Link :href="route('dashboard')">
                <BaseButton variant="secondary" size="sm" class="shrink-0 bg-surface shadow-sm">
                    Ver todas <AppIcon name="arrow-right" :size="14" class="ml-1" />
                </BaseButton>
            </Link>
        </div>

        <div v-if="missions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 relative z-10">
            <div 
                v-for="mission in missions" 
                :key="mission.id"
                class="group flex flex-col bg-surface border border-border rounded-2xl p-4 transition-all duration-300 hover:shadow-md hover:border-accent/40 hover:-translate-y-1 relative overflow-hidden"
            >
                <!-- Categoría Tag -->
                <div class="absolute top-0 right-0 bg-accent text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg">
                    {{ mission.type === 'study' ? 'Estudio' : mission.type === 'routine' ? 'Rutina' : 'General' }}
                </div>
                
                <h3 class="font-bold text-content-primary pr-12 line-clamp-2 min-h-[40px] mt-2 group-hover:text-accent transition-colors">
                    {{ mission.title }}
                </h3>
                
                <div class="mt-auto pt-4 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs font-semibold" :class="isOverdue(mission.due_date) ? 'text-danger' : 'text-content-secondary'">
                        <AppIcon name="clock" :size="14" />
                        {{ formatDueDate(mission.due_date) }}
                    </div>
                    
                    <Link :href="route('dashboard')">
                        <button class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center transition-transform hover:scale-110 hover:bg-accent hover:text-white">
                            <AppIcon name="arrow-right" :size="14" class="ml-0.5" />
                        </button>
                    </Link>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-10 relative z-10 bg-surface-sunken/50 rounded-2xl border border-dashed border-border-interactive mt-4">
            <div class="w-16 h-16 rounded-full bg-surface shadow-inner flex items-center justify-center mx-auto mb-4 text-success">
                <AppIcon name="check-circle" :size="32" />
            </div>
            <h3 class="font-bold text-lg text-content-primary">¡Zona Despejada!</h3>
            <p class="text-sm text-content-secondary mt-1 max-w-sm mx-auto">Has completado todas tus misiones pendientes. Descansa o crea una nueva para seguir sumando XP.</p>
        </div>
    </BaseCard>
</template>
