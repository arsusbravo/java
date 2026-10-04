<script setup lang="ts">
import { computed } from 'vue';

// An on/off switch, e.g. Featured in the admin lists
const props = withDefaults(
    defineProps<{
        checked: boolean;
        // What is switched, for screen readers, e.g. "Featured: Borobudur Temple"
        label: string;
        // Short name of the setting for the hover hint, e.g. "Featured"
        name?: string;
        disabled?: boolean;
    }>(),
    { name: undefined, disabled: false },
);

defineEmits<{ toggle: [] }>();

const hint = computed(() => {
    const name = props.name ?? props.label;
    return props.checked
        ? `${name}, click to turn off`
        : `Click to turn on ${name.toLowerCase()}`;
});
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="checked"
        :aria-label="label"
        :title="hint"
        :disabled="disabled"
        :class="[
            'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:opacity-60',
            checked ? 'bg-green-600' : 'bg-muted-foreground/30',
        ]"
        @click="$emit('toggle')"
    >
        <span
            :class="[
                'inline-block size-4 rounded-full bg-white shadow transition-transform',
                checked ? 'translate-x-4.5' : 'translate-x-0.5',
            ]"
        />
    </button>
</template>
