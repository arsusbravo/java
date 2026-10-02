<script setup lang="ts">
import { type Component } from 'vue';

defineProps<{
    icon: Component;
    label: string;
    active?: boolean;
    disabled?: boolean;
}>();

defineEmits<{ click: [] }>();
</script>

<template>
    <button
        type="button"
        :title="label"
        :aria-label="label"
        :aria-pressed="active"
        :disabled="disabled"
        :class="[
            'inline-flex size-8 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-35',
            active
                ? 'bg-primary text-primary-foreground'
                : 'text-foreground/80 hover:bg-accent hover:text-foreground',
        ]"
        @mousedown.prevent
        @click="$emit('click')"
    >
        <component :is="icon" class="size-4" />
    </button>
</template>
