<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="AI Customer Support SaaS" />

    <div class="min-h-screen bg-slate-950 text-white">
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-cyan-400/20 ring-1 ring-cyan-300/40" />
                <p class="text-sm font-semibold tracking-wide text-slate-100">SupportPilot AI</p>
            </div>

            <nav class="flex items-center gap-3 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 font-medium text-slate-100 transition hover:border-cyan-400/60 hover:text-cyan-300"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-lg border border-slate-700 px-4 py-2 font-medium text-slate-200 transition hover:border-cyan-400/60 hover:text-cyan-300"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950 transition hover:bg-cyan-300"
                    >
                        Get started
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto grid w-full max-w-7xl gap-10 px-6 pb-16 pt-6 lg:grid-cols-2 lg:px-8">
            <section class="space-y-8">
                <div class="inline-flex items-center rounded-full border border-cyan-300/30 bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-200">
                    Multi-tenant AI Support Platform
                </div>
                <div class="space-y-5">
                    <h1 class="text-4xl font-semibold leading-tight text-white lg:text-5xl">
                        Resolve more support tickets with AI and human handoff.
                    </h1>
                    <p class="max-w-xl text-base leading-relaxed text-slate-300">
                        Deploy an embeddable widget, connect your knowledge base, and run a single inbox where AI handles routine queries and agents step in when needed.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-if="!$page.props.auth.user && canRegister"
                        :href="register()"
                        class="rounded-xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                    >
                        Start building
                    </Link>
                    <Link
                        :href="$page.props.auth.user ? dashboard() : login()"
                        class="rounded-xl border border-slate-700 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-cyan-300/60 hover:text-cyan-200"
                    >
                        View product dashboard
                    </Link>
                </div>
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                        <dt class="text-xs text-slate-400">Average first response</dt>
                        <dd class="mt-2 text-2xl font-semibold text-cyan-300">6.8s</dd>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                        <dt class="text-xs text-slate-400">AI deflection</dt>
                        <dd class="mt-2 text-2xl font-semibold text-emerald-300">71%</dd>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 col-span-2 sm:col-span-1">
                        <dt class="text-xs text-slate-400">Active tenants</dt>
                        <dd class="mt-2 text-2xl font-semibold text-violet-300">124</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 shadow-2xl shadow-cyan-500/10">
                <div class="space-y-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Live support ops preview</p>
                    <div class="space-y-3">
                        <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-slate-100">Refund request for order #8921</p>
                                    <p class="mt-1 text-xs text-slate-400">Intent: Billing • Confidence 0.42</p>
                                </div>
                                <span class="rounded-full bg-amber-400/20 px-2 py-1 text-xs font-medium text-amber-300">Escalated</span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-slate-100">How to reset 2FA for team members?</p>
                                    <p class="mt-1 text-xs text-slate-400">Matched KB source: Security FAQ</p>
                                </div>
                                <span class="rounded-full bg-emerald-400/20 px-2 py-1 text-xs font-medium text-emerald-300">AI handled</span>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4">
                            <p class="text-sm font-medium text-slate-100">Widget snippet</p>
                            <pre class="mt-2 overflow-x-auto rounded-lg bg-black/40 p-3 text-xs text-cyan-200">&lt;script src="https://yourapp.com/widget.js" data-key="AGENCY_KEY"&gt;&lt;/script&gt;</pre>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
