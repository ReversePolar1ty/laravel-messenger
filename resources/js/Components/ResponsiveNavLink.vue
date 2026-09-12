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
        ? 'block w-full border-l-4 border-[var(--ember)] bg-[var(--ember-10)] py-3 pe-4 ps-3 text-start text-sm font-bold uppercase tracking-[0.16em] text-[var(--ink)] transition duration-150 ease-in-out focus:outline-none'
        : 'block w-full border-l-4 border-transparent py-3 pe-4 ps-3 text-start text-sm font-bold uppercase tracking-[0.16em] text-[var(--muted)] transition duration-150 ease-in-out hover:border-[var(--ember-60)] hover:bg-[var(--ember-10)] hover:text-[var(--ink)] focus:outline-none',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
