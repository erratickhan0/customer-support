<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Copy } from 'lucide-vue-next';
import { ref } from 'vue';
import WidgetSettingsController from '@/actions/App/Http/Controllers/Settings/WidgetSettingsController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit } from '@/routes/widget-settings';

type Props = {
    widget: {
        has_api_key: boolean;
        api_key: string | null;
        key_name: string | null;
        last_used_at: string | null;
        script_url: string;
        embed_code: string | null;
    };
};

const props = defineProps<Props>();
const copied = ref(false);
const copyError = ref<string | null>(null);
const snippetInput = ref<HTMLTextAreaElement | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Widget settings',
                href: edit(),
            },
        ],
    },
});

async function copyEmbedCode(): Promise<void> {
    if (!props.widget.embed_code || typeof document === 'undefined') {
        return;
    }

    copyError.value = null;

    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(props.widget.embed_code);
        } else {
            copyWithTextareaFallback(props.widget.embed_code);
        }

        copied.value = true;

        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        try {
            copyWithTextareaFallback(props.widget.embed_code);
            copied.value = true;

            window.setTimeout(() => {
                copied.value = false;
            }, 2000);
        } catch {
            copyError.value = 'Copy failed. Select the snippet below and copy it manually.';
        }
    }
}

function copyWithTextareaFallback(value: string): void {
    const textarea = snippetInput.value ?? document.createElement('textarea');
    const appended = !snippetInput.value;

    if (appended) {
        textarea.value = value;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
    }

    textarea.focus();
    textarea.select();

    const wasCopied = document.execCommand('copy');

    if (appended) {
        document.body.removeChild(textarea);
    }

    if (!wasCopied) {
        throw new Error('Clipboard fallback failed.');
    }
}

function selectSnippet(event: FocusEvent): void {
    if (event.target instanceof HTMLTextAreaElement) {
        event.target.select();
    }
}
</script>

<template>
    <Head title="Widget settings" />

    <h1 class="sr-only">Widget settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Website widget"
            description="Generate an embed key and install the chat widget on a customer-facing site."
        />

        <Card>
            <CardHeader>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <CardTitle>Embed key</CardTitle>
                        <CardDescription>
                            Active widget keys identify this workspace when visitor messages are sent.
                        </CardDescription>
                    </div>
                    <Badge :variant="widget.has_api_key ? 'default' : 'secondary'">
                        {{ widget.has_api_key ? 'Active' : 'Not generated' }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="rounded-lg border bg-muted/40 p-4 text-sm">
                    <p class="font-medium">
                        {{ widget.key_name ?? 'No widget key yet' }}
                    </p>
                    <p class="mt-1 text-muted-foreground">
                        {{
                            widget.last_used_at
                                ? `Last used ${widget.last_used_at}`
                                : 'Generate a key before installing the widget.'
                        }}
                    </p>
                </div>

                <div
                    v-if="widget.api_key"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100"
                >
                    Copy this key now. For security, it will not be shown again after you leave this page.
                </div>

                <div v-if="widget.embed_code" class="space-y-3">
                    <p class="text-sm font-medium">Embed snippet</p>
                    <textarea
                        ref="snippetInput"
                        class="min-h-24 w-full resize-none overflow-x-auto rounded-lg border bg-slate-950 p-4 font-mono text-xs text-slate-50"
                        readonly
                        :value="widget.embed_code"
                        @focus="selectSnippet"
                    />
                    <Button type="button" variant="secondary" @click="copyEmbedCode">
                        <Check v-if="copied" class="mr-2 size-4" />
                        <Copy v-else class="mr-2 size-4" />
                        {{ copied ? 'Copied' : 'Copy snippet' }}
                    </Button>
                    <p v-if="copyError" class="text-xs text-destructive">
                        {{ copyError }}
                    </p>
                </div>

                <Form
                    v-bind="WidgetSettingsController.store.form()"
                    v-slot="{ processing }"
                >
                    <Button :disabled="processing">
                        {{ widget.has_api_key ? 'Rotate widget key' : 'Generate widget key' }}
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
