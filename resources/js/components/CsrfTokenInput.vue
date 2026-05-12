<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const csrfToken = usePage().props.csrf_token as string;
</script>

<template>
    <input type="hidden" name="_token" :value="csrfToken" />
</template>
