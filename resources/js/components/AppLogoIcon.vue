<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <path
            fill="currentColor"
            d="M10 9.5A7.5 7.5 0 0 1 17.5 2h13A7.5 7.5 0 0 1 38 9.5v15A7.5 7.5 0 0 1 30.5 32H25l-9.7 7.3A2 2 0 0 1 12 37.7V32h-1.5A7.5 7.5 0 0 1 3 24.5v-15Z"
        />
        <path
            fill="currentColor"
            d="M34.5 25.5a2 2 0 0 1 2-2H43a2 2 0 0 1 0 4h-6.5a2 2 0 0 1-2-2Zm-4.9 10.9a2 2 0 0 1 2.8-.3l5.2 4.1a2 2 0 1 1-2.5 3.1L30 39.2a2 2 0 0 1-.3-2.8Zm.1-21.8a2 2 0 0 1 .3-2.8l5.2-4.1a2 2 0 1 1 2.5 3.1l-5.2 4.1a2 2 0 0 1-2.8-.3Z"
            opacity="0.7"
        />
        <path
            fill="white"
            d="M14 14.5a2.5 2.5 0 1 1 5 0 2.5 2.5 0 0 1-5 0Zm8 0a2.5 2.5 0 1 1 5 0 2.5 2.5 0 0 1-5 0Zm8 0a2.5 2.5 0 1 1 5 0 2.5 2.5 0 0 1-5 0Z"
            opacity="0.9"
        />
    </svg>
</template>
