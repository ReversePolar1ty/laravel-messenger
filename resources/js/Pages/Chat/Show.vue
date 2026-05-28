<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    chat: {
        type: Object,
        required: true,
    },
    messages: {
        type: Array,
        default: () => [],
    },
    readStates: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const messageList = ref([...props.messages]);
const readStateList = ref([...props.readStates]);
const newMessage = ref('');
const sending = ref(false);
const error = ref('');
const messagesContainer = ref(null);
const readRequestMessageId = ref(null);
const companionStatus = ref(props.chat.other_user_status || {
    status: 'offline',
    last_seen: null,
});

const chatTitle = computed(() => props.chat.display_title || props.chat.title || 'Чат');
const chatInitial = computed(() => chatTitle.value.trim().charAt(0).toUpperCase() || 'C');

const companion = computed(() =>
    (props.chat.participants || []).find(
        (participant) => Number(participant.id) !== Number(currentUser.value?.id),
    ),
);
const isDirectChat = computed(() => props.chat.type === 'direct' && companion.value);
const companionStatusText = computed(() => {
    if (!isDirectChat.value) {
        return props.chat.type === 'group' ? 'Групповой чат' : 'Личный чат';
    }

    if (companionStatus.value?.status === 'online') {
        return 'В сети';
    }

    return `Был(а) в сети ${companionStatus.value?.last_seen || 'давно'}`;
});
const companionIsOnline = computed(() => isDirectChat.value && companionStatus.value?.status === 'online');

const sortedMessages = computed(() =>
    [...messageList.value].sort((a, b) => {
        const first = new Date(a.created_at || 0).getTime();
        const second = new Date(b.created_at || 0).getTime();

        return first - second;
    }),
);

const scrollToBottom = async () => {
    await nextTick();

    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const messageKey = (message, index) => message.id || message._id || `${message.created_at}-${index}`;

const messageId = (message) => message?.id || message?._id;

const isPageVisible = () => document.visibilityState === 'visible' && document.hasFocus();

const isMine = (message) => {
    const senderId = message.sender_id || message.user_id;

    return Number(senderId) === Number(currentUser.value?.id);
};

const formatTime = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
};

const shouldShowDate = (message, index) => {
    if (index === 0) {
        return true;
    }

    const current = new Date(message.created_at || 0).toDateString();
    const previous = new Date(sortedMessages.value[index - 1]?.created_at || 0).toDateString();

    return current !== previous;
};

const pushMessage = (message) => {
    const id = messageId(message);

    if (id && messageList.value.some((item) => messageId(item) === id)) {
        return;
    }

    messageList.value.push(message);
    scrollToBottom();

    if (!isMine(message)) {
        markLatestMessageAsRead();
    }
};

const latestMessageId = computed(() => messageId(sortedMessages.value[sortedMessages.value.length - 1]));

const messagePositionById = computed(() => {
    const positions = new Map();

    sortedMessages.value.forEach((message, index) => {
        const id = messageId(message);

        if (id) {
            positions.set(id, index);
        }
    });

    return positions;
});

const otherParticipantIds = computed(() =>
    (props.chat.participants || [])
        .map((participant) => Number(participant.id))
        .filter((id) => id !== Number(currentUser.value?.id)),
);

const readStateByUserId = computed(() => {
    const states = new Map();

    readStateList.value.forEach((state) => {
        states.set(Number(state.user_id), state);
    });

    return states;
});

const upsertReadState = (state) => {
    if (!state?.user_id || !state?.last_read_message_id) {
        return;
    }

    const index = readStateList.value.findIndex((item) => Number(item.user_id) === Number(state.user_id));

    if (index === -1) {
        readStateList.value.push(state);
        return;
    }

    readStateList.value[index] = {
        ...readStateList.value[index],
        ...state,
    };
};

const isReadAtLeast = (readMessageId, targetMessageId) => {
    if (!readMessageId || !targetMessageId) {
        return false;
    }

    const readPosition = messagePositionById.value.get(readMessageId);
    const targetPosition = messagePositionById.value.get(targetMessageId);

    if (readPosition !== undefined && targetPosition !== undefined) {
        return readPosition >= targetPosition;
    }

    return String(readMessageId) >= String(targetMessageId);
};

const isMessageReadByOthers = (message) => {
    const id = messageId(message);

    if (!id || !isMine(message) || otherParticipantIds.value.length === 0) {
        return false;
    }

    return otherParticipantIds.value.every((participantId) => {
        const readState = readStateByUserId.value.get(participantId);

        return isReadAtLeast(readState?.last_read_message_id, id);
    });
};

const markLatestMessageAsRead = async () => {
    const id = latestMessageId.value;

    if (!id || !isPageVisible() || readRequestMessageId.value === id) {
        return;
    }

    readRequestMessageId.value = id;

    try {
        const response = await window.axios.post(route('chats.read.store', props.chat.id), {
            last_read_message_id: id,
        }, {
            skipGlobalLoader: true,
        });

        if (response.data?.data) {
            upsertReadState(response.data.data);
        }
    } catch {
        // Reading state is best-effort; the next open or incoming message will retry.
    } finally {
        if (readRequestMessageId.value === id) {
            readRequestMessageId.value = null;
        }

        if (isPageVisible() && latestMessageId.value && latestMessageId.value !== id) {
            markLatestMessageAsRead();
        }
    }
};

const markReadWhenVisible = () => {
    if (isPageVisible()) {
        markLatestMessageAsRead();
    }
};

const refreshCompanionStatus = async () => {
    if (!isDirectChat.value) {
        return;
    }

    try {
        const response = await window.axios.get(route('users.status.show', companion.value.id), {
            skipGlobalLoader: true,
        });

        companionStatus.value = response.data;
    } catch {
        // Keep the last known status if the lightweight status request fails.
    }
};

const setCompanionOnline = (user) => {
    if (Number(user?.id) === Number(companion.value?.id)) {
        companionStatus.value = {
            status: 'online',
            last_seen: null,
        };
    }
};

const setCompanionOffline = (user) => {
    if (Number(user?.id) === Number(companion.value?.id)) {
        companionStatus.value = {
            status: 'offline',
            last_seen: 'только что',
        };
        refreshCompanionStatus();
    }
};

const handleOnlineUsersHere = (event) => {
    const users = event.detail || [];

    if (users.some((user) => Number(user.id) === Number(companion.value?.id))) {
        setCompanionOnline(companion.value);
    }
};

const handleOnlineUserJoining = (event) => {
    setCompanionOnline(event.detail);
};

const handleOnlineUserLeaving = (event) => {
    setCompanionOffline(event.detail);
};

const sendMessage = async () => {
    const text = newMessage.value.trim();

    if (!text || sending.value) {
        return;
    }

    sending.value = true;
    error.value = '';

    try {
        const response = await window.axios.post(route('chats.messages.store', props.chat.id), {
            chat_id: props.chat.id,
            type: 'text',
            text,
        });

        pushMessage(response.data.data);
        upsertReadState({
            chat_id: props.chat.id,
            user_id: currentUser.value?.id,
            last_read_message_id: messageId(response.data.data),
            last_read_at: new Date().toISOString(),
        });
        newMessage.value = '';
    } catch (exception) {
        error.value = exception.response?.data?.message || 'Не удалось отправить сообщение.';
    } finally {
        sending.value = false;
    }
};

let echoChannel = null;
let statusTimer = null;

onMounted(() => {
    scrollToBottom();
    markReadWhenVisible();
    refreshCompanionStatus();
    statusTimer = window.setInterval(refreshCompanionStatus, 15000);
    document.addEventListener('visibilitychange', markReadWhenVisible);
    window.addEventListener('focus', markReadWhenVisible);
    window.addEventListener('online-users:here', handleOnlineUsersHere);
    window.addEventListener('online-users:joining', handleOnlineUserJoining);
    window.addEventListener('online-users:leaving', handleOnlineUserLeaving);

    if (window.Echo) {
        echoChannel = window.Echo.private(`chat.${props.chat.id}`)
            .listen('MessageSent', (event) => {
                const message = event.message || event.messageData || event.data || event;

                if (message) {
                    pushMessage(message);
                }
            })
            .listen('MessageRead', (event) => {
                upsertReadState(event);
            });
    }
});

onBeforeUnmount(() => {
    window.clearInterval(statusTimer);
    document.removeEventListener('visibilitychange', markReadWhenVisible);
    window.removeEventListener('focus', markReadWhenVisible);
    window.removeEventListener('online-users:here', handleOnlineUsersHere);
    window.removeEventListener('online-users:joining', handleOnlineUserJoining);
    window.removeEventListener('online-users:leaving', handleOnlineUserLeaving);

    if (window.Echo && echoChannel) {
        window.Echo.leave(`chat.${props.chat.id}`);
    }
});
</script>

<template>
    <Head :title="chatTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <Link
                        :href="route('chats.index')"
                        class="text-sm font-medium text-gray-500 transition hover:text-gray-800"
                    >
                        Назад
                    </Link>
                    <h2 class="mt-1 truncate text-xl font-semibold leading-tight text-gray-800">
                        {{ chatTitle }}
                    </h2>
                </div>
                <div class="text-sm text-gray-500">
                    {{ chat.type === 'group' ? 'Групповой чат' : 'Личный чат' }}
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex h-[calc(100vh-13rem)] min-h-[34rem] flex-col">
                        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-4 sm:px-6">
                            <img
                                v-if="chat.avatar_url"
                                :src="chat.avatar_url"
                                :alt="chatTitle"
                                class="h-11 w-11 rounded-full object-cover"
                            >
                            <div
                                v-else
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-900 text-sm font-semibold text-white"
                            >
                                {{ chatInitial }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-gray-900">
                                    {{ chatTitle }}
                                </div>
                                <div
                                    class="inline-flex items-center gap-1.5 text-sm"
                                    :class="companionIsOnline ? 'text-emerald-600' : 'text-gray-500'"
                                >
                                    <span
                                        v-if="isDirectChat"
                                        class="h-2 w-2 rounded-full"
                                        :class="companionIsOnline ? 'bg-emerald-500' : 'bg-gray-300'"
                                    />
                                    {{ companionStatusText }}
                                </div>
                            </div>
                        </div>

                        <div
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto bg-gray-50 px-4 py-5 sm:px-6"
                        >
                            <div
                                v-if="sortedMessages.length === 0"
                                class="flex h-full items-center justify-center text-center text-sm text-gray-500"
                            >
                                Сообщений пока нет.
                            </div>

                            <div v-else class="space-y-4">
                                <template
                                    v-for="(message, index) in sortedMessages"
                                    :key="messageKey(message, index)"
                                >
                                    <div
                                        v-if="shouldShowDate(message, index)"
                                        class="flex justify-center"
                                    >
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-500 shadow-sm">
                                            {{ formatDate(message.created_at) }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex"
                                        :class="isMine(message) ? 'justify-end' : 'justify-start'"
                                    >
                                        <div
                                            class="max-w-[82%] rounded-lg px-4 py-2 shadow-sm sm:max-w-[68%]"
                                            :class="isMine(message)
                                                ? 'bg-gray-900 text-white'
                                                : 'bg-white text-gray-900'"
                                        >
                                            <p class="whitespace-pre-wrap break-words text-sm leading-6">
                                                {{ message.text }}
                                            </p>
                                            <div
                                                class="mt-1 flex items-center justify-end gap-2 text-xs"
                                                :class="isMine(message) ? 'text-gray-300' : 'text-gray-400'"
                                            >
                                                <span>{{ formatTime(message.created_at) }}</span>
                                                <span
                                                    v-if="isMine(message)"
                                                    class="inline-flex items-center"
                                                    :aria-label="isMessageReadByOthers(message) ? 'Прочитано' : 'Отправлено'"
                                                    :title="isMessageReadByOthers(message) ? 'Прочитано' : 'Отправлено'"
                                                >
                                                    <svg
                                                        v-if="isMessageReadByOthers(message)"
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="none"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M2.5 10.5 6 14l7.5-8"
                                                            stroke="currentColor"
                                                            stroke-width="1.8"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        />
                                                        <path
                                                            d="M8 13.5 9.5 15 17.5 6"
                                                            stroke="currentColor"
                                                            stroke-width="1.8"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        />
                                                    </svg>
                                                    <svg
                                                        v-else
                                                        class="h-4 w-4"
                                                        viewBox="0 0 20 20"
                                                        fill="none"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M3.5 10.5 7.5 14.5 16.5 5.5"
                                                            stroke="currentColor"
                                                            stroke-width="1.8"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        />
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <form
                            class="border-t border-gray-100 bg-white p-4 sm:p-6"
                            @submit.prevent="sendMessage"
                        >
                            <p
                                v-if="error"
                                class="mb-3 text-sm font-medium text-red-600"
                            >
                                {{ error }}
                            </p>

                            <div class="flex items-end gap-3">
                                <textarea
                                    v-model="newMessage"
                                    rows="1"
                                    class="max-h-36 min-h-11 flex-1 resize-none rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Напишите сообщение"
                                    @keydown.enter.exact.prevent="sendMessage"
                                />
                                <button
                                    type="submit"
                                    class="inline-flex h-11 shrink-0 items-center rounded-md bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="sending || newMessage.trim().length === 0"
                                >
                                    {{ sending ? 'Отправка' : 'Отправить' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
