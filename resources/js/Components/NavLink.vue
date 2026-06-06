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
        ? 'inline-flex items-center border-b-2 border-[#ff4f2f] px-1 pt-1 text-sm font-bold uppercase tracking-[0.18em] text-[#f4f1ec] transition duration-150 ease-in-out focus:outline-none focus:border-[#ff6a3d]'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-bold uppercase tracking-[0.18em] text-[#a9a39c] transition duration-150 ease-in-out hover:border-[#ff4f2f]/50 hover:text-[#f4f1ec] focus:outline-none focus:border-[#ff4f2f]/70 focus:text-[#f4f1ec]',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
