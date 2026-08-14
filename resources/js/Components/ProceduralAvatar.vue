<script setup>
import { computed } from 'vue';
import { createAvatar } from '@dicebear/core';
import * as openPeeps from '@dicebear/open-peeps';

/*
 * Avatar Procedural & Personalizable Epycus (Open Peeps por Pablo Stanley).
 *
 * Soporta dos modos:
 *   1. Modo Personalizado: Si se pasan `options` (o `avatarOptions`), renderiza
 *      las selecciones hechas por el usuario (color de piel, cabello, expresión,
 *      lentes, barba, ropa y fondo).
 *   2. Modo Defecto: Si no hay opciones guardadas, genera rasgos basados en género y carrera.
 */

const props = defineProps({
    options: { type: Object, default: null },
    avatarOptions: { type: Object, default: null },
    career: { type: String, default: 'base' },
    gender: { type: String, default: 'm' },
    phase: { type: Number, default: 1 },
    seed: { type: String, default: null },
    size: { type: Number, default: 256 },
});

const PHASE_BG = [
    'fff8fb', // 1
    'fbeff7',
    'fbe9f3',
    'f8e3ef',
    'f2ecff', // 5
    'eaf6f0',
    'fff4e0',
    'eaf6ff',
    'ffebed',
    '0e0a1a', // 10
];

const MALE_HAIRS = ['short1', 'short2', 'short3', 'short4', 'short5', 'pomp', 'flatTop', 'afro', 'shaved1'];
const FEMALE_HAIRS = ['long', 'longCurly', 'longBangs', 'bun', 'bun2', 'buns', 'medium1', 'mediumBangs', 'mediumStraight', 'bangs'];

const CAREER_PROFILES = {
    health: { clothingColor: ['e0f2fe', 'ffffff'], accessories: ['glasses', 'glasses3'], mask: ['medicalMask'] },
    technical: { clothingColor: ['f97316', 'f29c2e'], accessories: ['glasses2', 'glasses5'], mask: ['respirator'] },
    business: { clothingColor: ['1e293b', '475569'], accessories: ['glasses4', 'glasses3'] },
    systems: { clothingColor: ['0284c7', '0f172a'], accessories: ['glasses5', 'glasses'] },
    law: { clothingColor: ['0f172a', '18181b'], accessories: ['glasses4', 'glasses2'] },
    base: { clothingColor: ['64748b', '8fa7df'], accessories: ['glasses'] },
};

const avatarDataUri = computed(() => {
    const customOpts = props.options || props.avatarOptions;

    if (customOpts && typeof customOpts === 'object' && Object.keys(customOpts).length > 0) {
        const skinColor = customOpts.skinColor ? customOpts.skinColor.replace('#', '') : 'ffdbb4';
        const clothingColor = customOpts.clothingColor ? customOpts.clothingColor.replace('#', '') : '1e293b';
        const backgroundColor = customOpts.backgroundColor ? customOpts.backgroundColor.replace('#', '') : 'fff8fb';

        const head = customOpts.head || 'short1';
        const face = customOpts.face || 'smile';

        const hasAccessories = customOpts.accessories && customOpts.accessories !== 'none';
        const hasFacialHair = customOpts.facialHair && customOpts.facialHair !== 'none';

        const dicebearOptions = {
            seed: props.seed ?? 'custom-user-avatar',
            size: props.size,
            backgroundColor: [backgroundColor],
            skinColor: [skinColor],
            clothingColor: [clothingColor],
            head: [head],
            face: [face],
            accessoriesProbability: hasAccessories ? 100 : 0,
            ...(hasAccessories ? { accessories: [customOpts.accessories] } : {}),
            facialHairProbability: hasFacialHair ? 100 : 0,
            ...(hasFacialHair ? { facialHair: [customOpts.facialHair] } : {}),
        };

        return createAvatar(openPeeps, dicebearOptions).toDataUri();
    }

    // Fallback por defecto si aún no ha personalizado su avatar
    const careerKey = props.career && CAREER_PROFILES[props.career] ? props.career : 'base';
    const profile = CAREER_PROFILES[careerKey];
    const isMale = props.gender === 'm';
    const hairList = isMale ? MALE_HAIRS : FEMALE_HAIRS;
    const phase = Math.min(Math.max(props.phase || 1, 1), 10);

    const selectedHair = hairList[(phase - 1) % hairList.length];
    const faces = phase >= 7 ? ['driven', 'explaining', 'serious', 'smileBig'] : ['smile', 'calm', 'cute', 'eatingHappy'];
    const selectedFace = faces[(phase - 1) % faces.length];
    const wearsAccessory = phase >= 3;
    const selectedAccessory = wearsAccessory ? profile.accessories : [];
    const wearsMask = phase >= 4 && profile.mask;

    const defaultOptions = {
        seed: props.seed ?? `${careerKey}-${props.gender}-${phase}`,
        size: props.size,
        backgroundColor: [PHASE_BG[phase - 1]],
        clothingColor: profile.clothingColor,
        head: [selectedHair],
        face: [selectedFace],
        facialHairProbability: isMale && phase >= 5 ? 20 : 0,
        accessoriesProbability: wearsAccessory ? 100 : 0,
        ...(wearsAccessory ? { accessories: selectedAccessory } : {}),
        maskProbability: wearsMask ? 50 : 0,
        ...(wearsMask ? { mask: profile.mask } : {}),
    };

    return createAvatar(openPeeps, defaultOptions).toDataUri();
});
</script>

<template>
    <img :src="avatarDataUri" alt="Tu avatar" class="h-full w-full object-contain" />
</template>