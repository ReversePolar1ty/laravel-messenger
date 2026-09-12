<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { useTheme } from '@/composables/useTheme';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const isChatsSection = computed(() => route().current('chats.*'));
const { isDark, saving: themeSaving, toggleTheme } = useTheme();

let onlineChannel = null;
let heartbeatTimer = null;
let isLoggingOut = false;

const heartbeat = async () => {
    if (isLoggingOut || !isChatsSection.value) {
        return;
    }

    try {
        await window.axios.post(route('users.status.heartbeat'), {}, {
            skipGlobalLoader: true,
        });
    } catch {
        // Online status is best-effort and will expire by Redis TTL.
    }
};

const emitOnlineEvent = (name, detail) => {
    window.dispatchEvent(new CustomEvent(name, { detail }));
};

const stopOnlinePresence = () => {
    window.clearInterval(heartbeatTimer);
    heartbeatTimer = null;

    if (window.Echo && onlineChannel) {
        window.Echo.leave('online');
        onlineChannel = null;
    }
};

const beginLogout = () => {
    isLoggingOut = true;
    stopOnlinePresence();
};

onMounted(() => {
    if (!page.props.auth?.user || !isChatsSection.value) {
        return;
    }

    heartbeat();
    heartbeatTimer = window.setInterval(heartbeat, 30000);

    if (window.Echo) {
        onlineChannel = window.Echo.join('online')
            .here((users) => emitOnlineEvent('online-users:here', users))
            .joining((user) => emitOnlineEvent('online-users:joining', user))
            .leaving((user) => emitOnlineEvent('online-users:leaving', user));
    }
});

onBeforeUnmount(() => {
    stopOnlinePresence();
});
</script>

<template>
    <div>
        <div class="theme-page min-h-screen">
            <nav class="theme-nav border-b backdrop-blur">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-[5.4rem] justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('chats.index')"
                                    class="flex items-center gap-3"
                                >
                                    <ApplicationLogo
                                        class="block h-8 w-auto fill-current text-[var(--ember)]"
                                    />
                                    <span class="text-sm font-black uppercase tracking-[0.16em] text-[var(--ember)]">
                                        Laravel.Messenger
                                    </span>
                                </Link>
                            </div>

                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('chats.index')"
                                    :active="route().current('chats.*')"
                                >
                                    Чаты
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden items-center gap-3 sm:ms-6 sm:flex">
                            <button
                                type="button"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] text-[var(--ember)] transition hover:border-[var(--ember-60)] hover:bg-[var(--ember-10)] hover:text-[var(--ember-soft)] focus:outline-none disabled:opacity-60"
                                :disabled="themeSaving"
                                :aria-label="isDark ? 'Включить светлую тему' : 'Включить тёмную тему'"
                                :title="isDark ? 'Светлая тема' : 'Тёмная тема'"
                                @click="toggleTheme"
                            >
                                <!-- Солнце: сейчас тёмная → переключить на светлую -->
                                <svg
                                    v-if="isDark"
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="4" />
                                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                                </svg>
                                <!-- Луна: сейчас светлая → переключить на тёмную -->
                                <svg
                                    v-else
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z" />
                                </svg>
                            </button>

                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-[var(--border)] bg-[var(--surface)] px-4 py-3 text-xs font-black uppercase leading-4 tracking-[0.14em] text-[var(--ember)] transition duration-150 ease-in-out hover:bg-[var(--ember-10)] hover:text-[var(--ember-soft)] focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            @click="beginLogout"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center gap-2 sm:hidden">
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] text-[var(--ember)] transition hover:border-[var(--ember-60)] hover:text-[var(--ember-soft)] focus:outline-none disabled:opacity-60"
                                :disabled="themeSaving"
                                :aria-label="isDark ? 'Включить светлую тему' : 'Включить тёмную тему'"
                                @click="toggleTheme"
                            >
                                <svg
                                    v-if="isDark"
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="4" />
                                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z" />
                                </svg>
                            </button>

                            <button
                                class="inline-flex items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] p-2 text-[var(--ink)] transition duration-150 ease-in-out hover:border-[var(--ember-60)] hover:text-[var(--ember)] focus:outline-none"
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('chats.index')"
                            :active="route().current('chats.*')"
                        >
                            Чаты
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-[var(--border-strong)] pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-bold text-[var(--ink)]">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-[var(--muted)]">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                @click="beginLogout"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header
                v-if="$slots.header"
                class="theme-header border-b"
            >
                <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
