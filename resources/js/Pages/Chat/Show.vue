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
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const messageList = ref([...props.messages]);
const newMessage = ref('');
const sending = ref(false);
const error = ref('');
const messagesContainer = ref(null);

const chatTitle = computed(() => props.chat.display_title || props.chat.title || 'Чат');
const chatInitial = computed(() => chatTitle.value.trim().charAt(0).toUpperCase() || 'C');

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
    const messageId = message.id || message._id;

    if (messageId && messageList.value.some((item) => (item.id || item._id) === messageId)) {
        return;
    }

    messageList.value.push(message);
    scrollToBottom();
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
        newMessage.value = '';
    } catch (exception) {
        error.value = exception.response?.data?.message || 'Не удалось отправить сообщение.';
    } finally {
        sending.value = false;
    }
};

let echoChannel = null;

onMounted(() => {
    scrollToBottom();

    if (window.Echo) {
        echoChannel = window.Echo.private(`chat.${props.chat.id}`)
            .listen('MessageSentEvent', (event) => {
                const message = event.message || event.data || event;

                if (message) {
                    pushMessage(message);
                }
            });
    }
});

onBeforeUnmount(() => {
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
                                <div class="text-sm text-gray-500">
                                    {{ sortedMessages.length }} сообщений
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
                                                class="mt-1 text-right text-xs"
                                                :class="isMine(message) ? 'text-gray-300' : 'text-gray-400'"
                                            >
                                                {{ formatTime(message.created_at) }}
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
