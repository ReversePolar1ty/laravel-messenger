<script setup>
import Show from '@/Pages/Chat/Show.vue';

defineProps({
    chat: {
        type: Object,
        required: true,
    },
    messages: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Show v-bind="$props" />
</template>
