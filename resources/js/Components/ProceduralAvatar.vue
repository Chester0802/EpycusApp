<script setup>
import { computed } from 'vue';
import { createAvatar } from '@dicebear/core';
import * as avataaars from '@dicebear/avataaars';

/*
 * Prototipo — Opción 4 (avatar procedural v2).
 *
 * Usa el estilo `avataaars` de DiceBear, mapeando rasgos a los 3 ejes del
 * usuario para que el avatar se parezca a su carrera, género y fase:
 *
 *   carrera  -> ropa (clothing) + color + anteojos  (CAREER_PROFILE)
 *   género   -> peinado (HAIR_BY_GENDER)
 *   fase     -> credibilidad creciente: a fase 3+ se ven los anteojos de la
 *                carrera; a fase 7+ la ropa se vuelve formal; y el color de
 *                fondo (PHASE_BG) cambia por fase.
 *
 * `clothingGraphic` queda SIEMPRE en un valor neutro (hola) y no se usa la
 * prenda `graphicShirt`, para que en el avatar nunca aparezca el cráneo ni
 * logos raros del estilo por defecto. (avataaars no trae batas ni cascos;
 * la identidad de carrera es por color + prenda + anteojos.)
 */

const props = defineProps({
    career: { type: String, default: 'base' },
    gender: { type: String, default: 'm' },
    phase: { type: Number, default: 1 },
    seed: { type: String, default: null },
    size: { type: Number, default: 256 },
});

const PHASE_BG = [
    '#FFF8FB', // 1
    '#FBEFF7',
    '#FBE9F3',
    '#F8E3EF',
    '#F2ECFF', // 5
    '#EAF6F0',
    '#FFF4E0',
    '#EAF6FF',
    '#FFEBED',
    '#0E0A1A', // 10
];

const HAIR_COLOR = '#2B2018'; // café oscuro (next a tonos "de otro país")
const MALE_HAIR = ['shortCurly', 'shortWaved', 'theCaesar', 'shortRound', 'shortFlat'];
const FEMALE_HAIR = ['longButNotTooLong', 'curly', 'straight01', 'bob', 'shaggy'];

// Identidad por carrera: prenda + color + anteojos (se lucen desde fase 3).
const CAREER_PROFILE = {
    'retiro': { clothing: 'blazerAndSweater', clothesColor: '#D9E2EC', glasses: 'prescription01' }, // salud (med/bata)
    technical: { clothing: 'overall',          clothesColor: '#F29C2E', glasses: 'round' },          // ing. civil/minas/arquit.
    business:  { clothing: 'blazerAndShirt',   clothesColor: '#5C6472', glasses: 'prescription02' }, // administración/contab
    systems:   { clothing: 'hoodie',           clothesColor: '#2F6E8A', glasses: 'round' },          // sistemas (tech)
    law:       { clothing: 'blazerAndShirt',   clothesColor: '#3B3A4A', glasses: 'prescription01' }, // derecho (formal)
    base:      { clothing: 'shirtCrewNeck',    clothesColor: '#8A9BAE', glasses: 'berp' },
};

const avatarDataUri = computed(() => {
    const p = CAREER_PROFILE[props.career] ?? CAREER_PROFILE.base;
    const isMale = props.gender === 'm';
    const hairList = isMale ? MALE_HAIR : FEMALE_HAIR;
    const phase = Math.min(Math.max(props.phase, 1), 10);
    // El peinado varía con la fase para dar cambio sin romper la identidad de género.
    const hair = hairList[(phase - 1) % hairList.length];
    const wears = phase >= 3 ? p.glasses : null;

    // Credibilidad creciente: a fase 7+ la ropa informal se vuelve traje.
    let clothing = p.clothing;
    if (phase >= 7 && ['overall', 'hoodie', 'shirtCrewNeck'].includes(clothing)) {
        clothing = 'blazerAndShirt';
    }

    const options = {
        seed: props.seed ?? `${props.career}-${props.gender}-${props.phase}`,
        size: props.size,
        backgroundColor: [PHASE_BG[phase - 1]],
        skinColor: ['#D29A6B'],
        hairColor: [HAIR_COLOR],
        eyes: ['default'],
        eyebrows: ['default'],
        mouth: ['smile'],
        top: [hair],
        facialHairProbability: 0,
        clothing: [clothing],
        clothesColor: [p.clothesColor],
        clothingGraphic: ['hola'],
        ...(wears ? { accessories: [wears] } : {}),
    };

    return createAvatar(avataaars, options).toDataUri();
});
</script>

<template>
    <img :src="avatarDataUri" alt="Tu avatar" class="h-full w-full object-contain" />
</template>