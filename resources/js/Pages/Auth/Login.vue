<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход" />

        <div class="mb-7">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[var(--ember)]">
                welcome back
            </p>
            <h1 class="mt-3 text-3xl font-black uppercase leading-none tracking-[-0.04em] text-[var(--ink)] sm:text-4xl">
                Вход
            </h1>
            <p class="mt-3 text-sm font-semibold leading-6 text-[var(--muted)]">
                Войдите, чтобы продолжить переписку в реальном времени.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-5 rounded-lg border border-[var(--ember-30)] bg-[var(--ember-10)] px-4 py-3 text-sm font-semibold text-[var(--ember-soft)]"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label
                    for="email"
                    class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-[var(--ink-soft)]"
                >
                    Email
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="messenger-field h-12 w-full rounded-lg px-4 text-sm font-semibold shadow-sm"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                >
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label
                        for="password"
                        class="block text-xs font-black uppercase tracking-[0.14em] text-[var(--ink-soft)]"
                    >
                        Пароль
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold uppercase tracking-[0.08em] text-[var(--muted)] transition hover:text-[var(--ember-soft)]"
                    >
                        Забыли?
                    </Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="messenger-field h-12 w-full rounded-lg px-4 text-sm font-semibold shadow-sm"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                >
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <label class="flex cursor-pointer items-center gap-3">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-white/20 bg-[var(--surface-deep)] text-[var(--ember)] focus:ring-[var(--ember)] focus:ring-offset-0"
                >
                <span class="text-sm font-semibold text-[var(--muted)]">
                    Запомнить меня
                </span>
            </label>

            <button
                type="submit"
                class="inline-flex h-12 w-full items-center justify-center rounded-lg bg-[var(--ember)] px-5 text-sm font-black uppercase tracking-[0.1em] text-[var(--ember-contrast)] transition hover:bg-[var(--ember-soft)] disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Вход…' : 'Войти' }}
            </button>
        </form>

        <p class="mt-7 border-t border-[var(--border)] pt-6 text-center text-sm font-semibold text-[var(--muted)]">
            Нет аккаунта?
            <Link
                :href="route('register')"
                class="ms-1 font-black uppercase tracking-[0.08em] text-[var(--ember)] transition hover:text-[var(--ember-soft)]"
            >
                Регистрация
            </Link>
        </p>
    </GuestLayout>
</template>
