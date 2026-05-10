<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AiSettingsController from '@/actions/App/Http/Controllers/Settings/AiSettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/ai-settings';

type Props = {
    settings: {
        ai_provider: 'openai' | 'rule_based';
        ai_confidence_threshold: number;
        ai_auto_handoff: boolean;
    };
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'AI settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="AI settings" />

    <h1 class="sr-only">AI settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Tenant AI behavior"
            description="Configure provider choice and when conversations are escalated to humans."
        />

        <Form
            v-bind="AiSettingsController.update.form()"
            class="space-y-6"
            :transform="(data) => ({ ...data, ai_auto_handoff: data.ai_auto_handoff ? 1 : 0 })"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="ai_provider">AI provider</Label>
                <select
                    id="ai_provider"
                    name="ai_provider"
                    class="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    :defaultValue="settings.ai_provider"
                    required
                >
                    <option value="openai">OpenAI + knowledge retrieval</option>
                    <option value="rule_based">Rule based fallback only</option>
                </select>
                <InputError :message="errors.ai_provider" />
            </div>

            <div class="grid gap-2">
                <Label for="ai_confidence_threshold">Confidence threshold (0.00 - 1.00)</Label>
                <Input
                    id="ai_confidence_threshold"
                    name="ai_confidence_threshold"
                    type="number"
                    min="0"
                    max="1"
                    step="0.01"
                    :default-value="String(settings.ai_confidence_threshold)"
                    required
                />
                <p class="text-xs text-muted-foreground">
                    If AI confidence is below this threshold, auto-handoff can mark the conversation as human required.
                </p>
                <InputError :message="errors.ai_confidence_threshold" />
            </div>

            <div class="flex items-center gap-3">
                <input
                    id="ai_auto_handoff"
                    name="ai_auto_handoff"
                    type="checkbox"
                    class="h-4 w-4 rounded border-input"
                    :checked="settings.ai_auto_handoff"
                />
                <Label for="ai_auto_handoff">Automatically hand off low-confidence responses to humans</Label>
            </div>
            <InputError :message="errors.ai_auto_handoff" />

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    Save AI settings
                </Button>
            </div>
        </Form>
    </div>
</template>
