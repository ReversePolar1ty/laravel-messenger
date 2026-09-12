<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-start text-sm font-semibold leading-5 text-[var(--ink-soft)] transition duration-150 ease-in-out hover:bg-[var(--ember-10)] hover:text-[var(--ember-soft)] focus:bg-[var(--ember-10)] focus:text-[var(--ember-soft)] focus:outline-none"
    >
        <slot />
    </Link>
</template>
