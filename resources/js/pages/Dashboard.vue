<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import api from '@/routes/api';
import { dashboard } from '@/routes';
import { computed, ref } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

type MessageItem = {
    id: number;
    sender_type: 'user' | 'ai' | 'agent';
    content: string;
    confidence: number | null;
    created_at: string | null;
};

type ConversationItem = {
    id: number;
    session_id: string;
    status: 'ai_handled' | 'human_required' | 'human_active' | 'closed';
    last_message_at: string | null;
    latest_message?: MessageItem | null;
    messages?: MessageItem[];
};

type PaginatedConversations = {
    data: ConversationItem[];
    meta?: {
        total?: number;
    };
};

const props = defineProps<{
    filters: { status: string | null; q: string | null };
    conversations: PaginatedConversations;
    selectedConversation: ConversationItem | null;
}>();

const isSubmitting = ref(false);
const reply = ref('');
const selectedStatus = ref(props.filters.status ?? '');
const search = ref(props.filters.q ?? '');
const replyError = ref<string | null>(null);

const statusLabelMap: Record<ConversationItem['status'], string> = {
    ai_handled: 'AI handled',
    human_required: 'Human required',
    human_active: 'Human active',
    closed: 'Closed',
};

const statusClassMap: Record<ConversationItem['status'], string> = {
    ai_handled: 'bg-emerald-500/15 text-emerald-500',
    human_required: 'bg-amber-500/15 text-amber-500',
    human_active: 'bg-cyan-500/15 text-cyan-500',
    closed: 'bg-slate-500/15 text-slate-500',
};

const openCount = computed(() => props.conversations.data.filter((item) => item.status !== 'closed').length);
const escalationCount = computed(() => props.conversations.data.filter((item) => item.status === 'human_required').length);

usePoll(
    7000,
    {
        only: ['conversations', 'selectedConversation'],
        preserveState: true,
        preserveScroll: true,
    },
    {
        keepAlive: true,
    },
);

const applyFilters = (): void => {
    router.get(
        dashboard.url({
            query: {
                status: selectedStatus.value || undefined,
                q: search.value || undefined,
                conversation: props.selectedConversation?.id,
            },
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['filters', 'conversations', 'selectedConversation'],
        },
    );
};

const selectConversation = (conversationId: number): void => {
    router.get(
        dashboard.url({
            query: {
                status: selectedStatus.value || undefined,
                q: search.value || undefined,
                conversation: conversationId,
            },
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['filters', 'conversations', 'selectedConversation'],
        },
    );
};

const sendReply = async (): Promise<void> => {
    replyError.value = null;

    if (! props.selectedConversation || ! reply.value.trim()) {
        return;
    }

    isSubmitting.value = true;

    try {
        const endpoint = api.inbox.conversations.messages.store({
            conversation: props.selectedConversation.id,
        });

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch(endpoint.url, {
            method: endpoint.method.toUpperCase(),
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                content: reply.value,
                status: 'human_active',
            }),
        });

        if (! response.ok) {
            throw new Error('Unable to send message.');
        }

        reply.value = '';

        router.reload({
            only: ['conversations', 'selectedConversation'],
            preserveScroll: true,
        });
    } catch (error) {
        replyError.value = error instanceof Error ? error.message : 'Unable to send message.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Open conversations</p>
                <p class="mt-2 text-2xl font-semibold">{{ openCount }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Live from inbox feed</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Human required</p>
                <p class="mt-2 text-2xl font-semibold text-amber-500">{{ escalationCount }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Escalation queue pressure</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Total tracked conversations</p>
                <p class="mt-2 text-2xl font-semibold">{{ props.conversations.meta?.total ?? props.conversations.data.length }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Tenant scoped</p>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-5">
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-5 xl:col-span-2 dark:border-sidebar-border">
                <div class="mb-4 space-y-3">
                    <h2 class="text-sm font-semibold">Live inbox queue</h2>
                    <div class="grid gap-2 md:grid-cols-2">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by session or message"
                            class="rounded-md border border-sidebar-border bg-background px-3 py-2 text-sm outline-none ring-0 focus:border-primary dark:border-sidebar-border"
                        />
                        <select
                            v-model="selectedStatus"
                            class="rounded-md border border-sidebar-border bg-background px-3 py-2 text-sm outline-none ring-0 focus:border-primary dark:border-sidebar-border"
                        >
                            <option value="">All statuses</option>
                            <option value="ai_handled">AI handled</option>
                            <option value="human_required">Human required</option>
                            <option value="human_active">Human active</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border border-sidebar-border px-3 py-2 text-xs text-muted-foreground transition hover:text-foreground dark:border-sidebar-border"
                        @click="applyFilters"
                    >
                        Apply filters
                    </button>
                </div>

                <div class="space-y-3">
                    <article
                        v-for="conversation in props.conversations.data"
                        :key="conversation.id"
                        class="cursor-pointer rounded-lg border border-sidebar-border/70 p-4 transition hover:border-primary/40 dark:border-sidebar-border"
                        @click="selectConversation(conversation.id)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium">Session {{ conversation.session_id }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ conversation.latest_message?.content ?? 'No messages yet' }}
                                </p>
                            </div>
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="statusClassMap[conversation.status]"
                            >
                                {{ statusLabelMap[conversation.status] }}
                            </span>
                        </div>
                    </article>
                    <p v-if="props.conversations.data.length === 0" class="rounded-lg border border-dashed border-sidebar-border/70 p-4 text-xs text-muted-foreground dark:border-sidebar-border">
                        No conversations found for current filters.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-background p-5 xl:col-span-3 dark:border-sidebar-border">
                <div v-if="props.selectedConversation" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold">Conversation #{{ props.selectedConversation.id }}</h2>
                            <p class="text-xs text-muted-foreground">Session {{ props.selectedConversation.session_id }}</p>
                        </div>
                        <span
                            class="rounded-full px-2 py-1 text-xs font-medium"
                            :class="statusClassMap[props.selectedConversation.status]"
                        >
                            {{ statusLabelMap[props.selectedConversation.status] }}
                        </span>
                    </div>

                    <div class="max-h-[420px] space-y-3 overflow-y-auto rounded-lg border border-sidebar-border/70 bg-muted/20 p-4 dark:border-sidebar-border">
                        <article
                            v-for="message in props.selectedConversation.messages ?? []"
                            :key="message.id"
                            class="rounded-md border border-sidebar-border/70 bg-background p-3 dark:border-sidebar-border"
                        >
                            <div class="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                                <span class="font-medium uppercase tracking-wide">{{ message.sender_type }}</span>
                                <span>{{ message.created_at }}</span>
                            </div>
                            <p class="text-sm leading-relaxed">{{ message.content }}</p>
                        </article>
                    </div>

                    <form class="space-y-2" @submit.prevent="sendReply">
                        <textarea
                            v-model="reply"
                            rows="3"
                            placeholder="Type your reply to continue this conversation..."
                            class="w-full rounded-md border border-sidebar-border bg-background px-3 py-2 text-sm outline-none ring-0 focus:border-primary dark:border-sidebar-border"
                        />
                        <p v-if="replyError" class="text-xs text-destructive">{{ replyError }}</p>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="isSubmitting || reply.trim().length === 0"
                            >
                                {{ isSubmitting ? 'Sending...' : 'Send reply' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div v-else class="flex h-full min-h-[280px] items-center justify-center rounded-lg border border-dashed border-sidebar-border/70 text-sm text-muted-foreground dark:border-sidebar-border">
                    Select a conversation from the inbox to open the thread.
                </div>
            </div>
        </section>
    </div>
</template>
