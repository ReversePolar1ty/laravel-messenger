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
        ? 'inline-flex items-center border-b-2 border-[var(--ember)] px-1 pt-1 text-sm font-bold uppercase tracking-[0.18em] text-[var(--ink)] transition duration-150 ease-in-out focus:outline-none focus:border-[var(--ember-soft)]'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-bold uppercase tracking-[0.18em] text-[var(--muted)] transition duration-150 ease-in-out hover:border-[var(--ember-50)] hover:text-[var(--ink)] focus:outline-none focus:border-[var(--ember-60)] focus:text-[var(--ink)]',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
