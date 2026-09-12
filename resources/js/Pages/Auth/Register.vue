<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Регистрация" />

        <div class="mb-7">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[var(--ember)]">
                join the space
            </p>
            <h1 class="mt-3 text-3xl font-black uppercase leading-none tracking-[-0.04em] text-[var(--ink)] sm:text-4xl">
                Регистрация
            </h1>
            <p class="mt-3 text-sm font-semibold leading-6 text-[var(--muted)]">
                Создайте аккаунт и начните личные диалоги в мессенджере.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label
                    for="name"
                    class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-[var(--ink-soft)]"
                >
                    Имя
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="messenger-field h-12 w-full rounded-lg px-4 text-sm font-semibold shadow-sm"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Как вас зовут"
                >
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

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
                    autocomplete="username"
                    placeholder="you@example.com"
                >
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <label
                    for="password"
                    class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-[var(--ink-soft)]"
                >
                    Пароль
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="messenger-field h-12 w-full rounded-lg px-4 text-sm font-semibold shadow-sm"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                >
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-[var(--ink-soft)]"
                >
                    Подтверждение
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="messenger-field h-12 w-full rounded-lg px-4 text-sm font-semibold shadow-sm"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                >
                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <button
                type="submit"
                class="inline-flex h-12 w-full items-center justify-center rounded-lg bg-[var(--ember)] px-5 text-sm font-black uppercase tracking-[0.1em] text-[var(--ember-contrast)] transition hover:bg-[var(--ember-soft)] disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Создание…' : 'Создать аккаунт' }}
            </button>
        </form>

        <p class="mt-7 border-t border-[var(--border)] pt-6 text-center text-sm font-semibold text-[var(--muted)]">
            Уже есть аккаунт?
            <Link
                :href="route('login')"
                class="ms-1 font-black uppercase tracking-[0.08em] text-[var(--ember)] transition hover:text-[var(--ember-soft)]"
            >
                Войти
            </Link>
        </p>
    </GuestLayout>
</template>
