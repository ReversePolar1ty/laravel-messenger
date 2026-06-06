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
        class="block w-full px-4 py-2.5 text-start text-sm font-semibold leading-5 text-[#d7d2cb] transition duration-150 ease-in-out hover:bg-[#ff4f2f]/10 hover:text-[#ff6a3d] focus:bg-[#ff4f2f]/10 focus:text-[#ff6a3d] focus:outline-none"
    >
        <slot />
    </Link>
</template>
