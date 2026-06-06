<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full border-l-4 border-[#ff4f2f] bg-[#ff4f2f]/10 py-3 pe-4 ps-3 text-start text-sm font-bold uppercase tracking-[0.16em] text-[#f4f1ec] transition duration-150 ease-in-out focus:outline-none'
        : 'block w-full border-l-4 border-transparent py-3 pe-4 ps-3 text-start text-sm font-bold uppercase tracking-[0.16em] text-[#a9a39c] transition duration-150 ease-in-out hover:border-[#ff4f2f]/60 hover:bg-[#ff4f2f]/5 hover:text-[#f4f1ec] focus:outline-none',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
