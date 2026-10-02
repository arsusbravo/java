<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, X, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash);
const dismissed = ref(false);

// Show again whenever a new message arrives
watch(flash, () => (dismissed.value = false));
</script>

<template>
    <div
        v-if="!dismissed && (flash.success || flash.error)"
        role="status"
        :class="[
            'flex items-center gap-3 rounded-lg border px-4 py-3 text-sm',
            flash.error
                ? 'border-destructive/30 bg-destructive/10 text-destructive'
                : 'border-green-600/30 bg-green-600/10 text-green-700 dark:text-green-400',
        ]"
    >
        <XCircle v-if="flash.error" class="size-4 shrink-0" />
        <CheckCircle2 v-else class="size-4 shrink-0" />
        <span class="flex-1">{{ flash.error || flash.success }}</span>
        <button
            type="button"
            class="opacity-70 hover:opacity-100"
            aria-label="Dismiss"
            @click="dismissed = true"
        >
            <X class="size-4" />
        </button>
    </div>
</template>
