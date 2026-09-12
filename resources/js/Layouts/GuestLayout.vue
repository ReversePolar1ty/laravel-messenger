<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen flex-col justify-center px-4 py-10 sm:px-6">
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-[var(--ember-10)] blur-3xl" />
            <div class="absolute -right-16 bottom-10 h-80 w-80 rounded-full bg-[var(--glow-soft)] blur-3xl" />
        </div>

        <div class="relative mx-auto w-full max-w-md">
            <div class="mb-8 flex flex-col items-center text-center">
                <Link
                    href="/"
                    class="group flex flex-col items-center gap-4 transition"
                >
                    <span
                        class="flex h-16 w-16 items-center justify-center rounded-2xl border border-[var(--ember-40)] bg-[var(--ember-10)] shadow-[0_0_40px_var(--ember-glow-soft)] transition group-hover:border-[var(--ember-60)] group-hover:bg-[var(--ember-15)]"
                    >
                        <ApplicationLogo class="h-9 w-auto fill-current text-[var(--ember)]" />
                    </span>
                    <span class="text-sm font-black uppercase tracking-[0.2em] text-[var(--ember)]">
                        Laravel.Messenger
                    </span>
                </Link>
            </div>

            <div class="messenger-panel overflow-hidden rounded-2xl px-6 py-7 sm:px-8 sm:py-8">
                <slot />
            </div>
        </div>
    </div>
</template>
