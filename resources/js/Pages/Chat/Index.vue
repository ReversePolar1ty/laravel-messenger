<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    chats: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const chatList = ref([...props.chats]);
const search = ref(props.filters.search || '');
const searchUsers = ref([...props.users]);
const isSearching = ref(false);
const isRefreshingChats = ref(false);
const startingUserId = ref(null);
let searchTimeout = null;
let searchController = null;
let chatRefreshController = null;
let echoChannel = null;

watch(() => props.chats, (chats) => {
    chatList.value = [...chats];
});

watch(search, (value) => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        fetchSearchResults(value);
    }, 400);
});

onBeforeUnmount(() => {
    clearTimeout(searchTimeout);
    searchController?.abort();
    chatRefreshController?.abort();

    if (window.Echo && echoChannel && currentUser.value?.id) {
        window.Echo.leave(`user.${currentUser.value.id}`);
    }
});

onMounted(() => {
    if (window.Echo && currentUser.value?.id) {
        echoChannel = window.Echo.private(`user.${currentUser.value.id}`)
            .listen('ChatListUpdated', refreshChats);
    }
});

const fetchSearchResults = async (value) => {
    const query = value.trim();

    searchController?.abort();

    if (!query) {
        searchUsers.value = [];
        isSearching.value = false;
        searchController = null;
        updateSearchUrl('');
        return;
    }

    const controller = new AbortController();
    searchController = controller;
    isSearching.value = true;

    try {
        const response = await window.axios.get(route('chats.search'), {
            params: {
                search: query,
            },
            signal: controller.signal,
            skipGlobalLoader: true,
        });

        searchUsers.value = response.data.users || [];
        updateSearchUrl(query);
    } catch (error) {
        if (error.code !== 'ERR_CANCELED') {
            searchUsers.value = [];
        }
    } finally {
        if (searchController === controller) {
            isSearching.value = false;
            searchController = null;
        }
    }
};

const updateSearchUrl = (query) => {
    const url = new URL(window.location.href);

    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }

    window.history.replaceState(window.history.state, '', url);
};

const refreshChats = async () => {
    chatRefreshController?.abort();

    const controller = new AbortController();
    chatRefreshController = controller;
    isRefreshingChats.value = true;

    try {
        const response = await window.axios.get(route('chats.items'), {
            signal: controller.signal,
            skipGlobalLoader: true,
        });

        chatList.value = response.data.chats || [];
    } catch (error) {
        if (error.code !== 'ERR_CANCELED') {
            console.error('Failed to refresh chat list.', error);
        }
    } finally {
        if (chatRefreshController === controller) {
            isRefreshingChats.value = false;
            chatRefreshController = null;
        }
    }
};

const filteredChats = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return chatList.value;
    }

    return chatList.value.filter((chat) => {
        const title = chatTitle(chat);
        const lastMessage = chat.last_message_text || '';

        return `${title} ${lastMessage}`.toLowerCase().includes(query);
    });
});

const chatTitle = (chat) => chat.display_title || chat.title || 'Чат';

const chatInitial = (chat) => chatTitle(chat).trim().charAt(0).toUpperCase() || 'C';

const userInitial = (user) => user.name.trim().charAt(0).toUpperCase() || 'U';

const formatLastMessageTime = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const today = new Date();
    const isToday = date.toDateString() === today.toDateString();

    return new Intl.DateTimeFormat('ru-RU', isToday
        ? { hour: '2-digit', minute: '2-digit' }
        : { day: '2-digit', month: 'short' }
    ).format(date);
};

const startDirectChat = (user) => {
    if (user.chat_id) {
        router.visit(route('chats.show', user.chat_id));
        return;
    }

    startingUserId.value = user.id;

    router.post(route('chats.direct.store'), {
        user_id: user.id,
    }, {
        preserveScroll: true,
        onFinish: () => {
            startingUserId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Чаты" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Чаты
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ chatList.length }} активных диалогов
                    </p>
                </div>
                <div class="relative w-full sm:w-80">
                    <input
                        v-model="search"
                        type="search"
                        class="w-full rounded-md border-gray-300 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Поиск чата или человека"
                    >
                    <span
                        v-if="isSearching"
                        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin rounded-full border-2 border-gray-300 border-t-gray-700"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-[22rem_1fr] lg:px-8">
                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-4 py-3 sm:px-5">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Люди
                        </h3>
                    </div>

                    <div
                        v-if="search.trim() === ''"
                        class="px-5 py-8 text-sm text-gray-500"
                    >
                        Введите имя, чтобы найти пользователя.
                    </div>

                    <div
                        v-else-if="!isSearching && searchUsers.length === 0"
                        class="px-5 py-8 text-sm text-gray-500"
                    >
                        Пользователи не найдены.
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="user in searchUsers"
                            :key="user.id"
                            class="flex items-center gap-3 px-4 py-4 sm:px-5"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-900 text-sm font-semibold text-white">
                                {{ userInitial(user) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-gray-900">
                                    {{ user.name }}
                                </div>
                                <div class="truncate text-xs text-gray-500">
                                    {{ user.email }}
                                </div>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-9 shrink-0 items-center rounded-md bg-gray-900 px-3 text-xs font-semibold text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="startingUserId === user.id"
                                @click="startDirectChat(user)"
                            >
                                {{ user.chat_id ? 'Открыть' : (startingUserId === user.id ? 'Создание' : 'Чат') }}
                            </button>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-100 px-4 py-3 sm:px-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-900">
                                Диалоги
                            </h3>
                            <span
                                v-if="isRefreshingChats"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-gray-700"
                                aria-label="Обновление чатов"
                            />
                        </div>
                    </div>

                    <div
                        v-if="filteredChats.length === 0"
                        class="px-6 py-16 text-center"
                    >
                        <h3 class="text-base font-semibold text-gray-900">
                            Чатов не найдено
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Найдите человека слева и начните личный чат.
                        </p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <Link
                            v-for="chat in filteredChats"
                            :key="chat.id"
                            :href="route('chats.show', chat.id)"
                            class="flex items-center gap-4 px-4 py-4 transition hover:bg-gray-50 sm:px-6"
                            :class="chat.has_unread ? 'bg-gray-50' : ''"
                        >
                            <img
                                v-if="chat.avatar_url"
                                :src="chat.avatar_url"
                                :alt="chatTitle(chat)"
                                class="h-12 w-12 rounded-full object-cover"
                            >
                            <div
                                v-else
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-900 text-sm font-semibold text-white"
                            >
                                {{ chatInitial(chat) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="truncate text-sm font-semibold text-gray-900">
                                        {{ chatTitle(chat) }}
                                    </h3>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span
                                            v-if="chat.has_unread"
                                            class="h-2 w-2 rounded-full bg-gray-900"
                                            aria-label="Unread messages"
                                        />
                                        <time class="text-xs text-gray-500">
                                            {{ formatLastMessageTime(chat.last_message_at) }}
                                        </time>
                                    </div>
                                </div>
                                <p
                                    class="mt-1 truncate text-sm"
                                    :class="chat.has_unread ? 'font-semibold text-gray-900' : 'text-gray-500'"
                                >
                                    {{ chat.last_message_text || 'Сообщений пока нет' }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
