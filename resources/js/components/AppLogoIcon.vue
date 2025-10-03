<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/img/elsembrador.jpeg"
        alt="El Sembrador Logo"
        :class="className"
        v-bind="$attrs"
        style="height: 120px; width: auto; object-fit: contain;"
    />
</template>
