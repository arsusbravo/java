<script setup lang="ts">
import { useChoiceSearch } from '@/components/admin/useChoiceSearch';
import { type Choice } from '@/types/admin';
import { onClickOutside } from '@vueuse/core';
import { ChevronsUpDown, X } from 'lucide-vue-next';
import { computed, nextTick, ref, toRef } from 'vue';

// A searchable single choice for relations too large for a plain <select>
const props = defineProps<{
    id: string;
    choices: Choice[];
    optionsUrl: string;
    required: boolean;
}>();

const model = defineModel<string | number | null>({ default: null });

const open = ref(false);
const term = ref('');
const root = ref<HTMLElement>();
const input = ref<HTMLInputElement>();
const { results, loading, search } = useChoiceSearch(
    toRef(props, 'optionsUrl'),
    term,
);

const known = ref<Choice | null>(
    props.choices.find((choice) => choice.value === model.value) ?? null,
);
const label = computed(() =>
    model.value === null || model.value === ''
        ? null
        : (known.value?.label ?? `#${model.value}`),
);

const show = async () => {
    open.value = true;
    search();
    await nextTick();
    input.value?.focus();
};

const choose = (choice: Choice) => {
    known.value = choice;
    model.value = choice.value;
    open.value = false;
    term.value = '';
};

onClickOutside(root, () => (open.value = false));
</script>

<template>
    <div ref="root" class="relative">
        <button
            :id="id"
            type="button"
            class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 text-left text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
            :aria-expanded="open"
            aria-haspopup="listbox"
            @click="open ? (open = false) : show()"
            @keydown.escape="open = false"
        >
            <span :class="['truncate', !label && 'text-muted-foreground']">
                {{ label ?? (required ? 'Choose…' : '— None —') }}
            </span>
            <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
        </button>
        <button
            v-if="label && !required"
            type="button"
            class="absolute top-1/2 right-8 -translate-y-1/2 text-muted-foreground hover:text-destructive"
            aria-label="Clear"
            @click="model = null"
        >
            <X class="size-3.5" />
        </button>

        <div
            v-if="open"
            class="absolute z-20 mt-1 w-full rounded-md border border-input bg-popover shadow-md"
            @keydown.escape="open = false"
        >
            <input
                ref="input"
                v-model="term"
                type="search"
                placeholder="Type to search…"
                class="w-full border-b border-input bg-transparent px-3 py-2 text-sm outline-none"
            />
            <ul
                role="listbox"
                class="max-h-60 overflow-y-auto p-1"
                :aria-busy="loading"
            >
                <li
                    v-if="!results.length"
                    class="px-2 py-3 text-sm text-muted-foreground"
                >
                    {{ loading ? 'Searching…' : 'No matches.' }}
                </li>
                <li
                    v-for="choice in results"
                    :key="choice.value"
                    role="option"
                    :aria-selected="choice.value === model"
                >
                    <button
                        type="button"
                        :class="[
                            'flex w-full items-baseline justify-between gap-3 rounded px-2 py-1.5 text-left text-sm hover:bg-accent',
                            choice.value === model && 'bg-accent font-medium',
                        ]"
                        @click="choose(choice)"
                    >
                        <span class="truncate">{{ choice.label }}</span>
                        <span
                            v-if="choice.group"
                            class="shrink-0 text-xs text-muted-foreground"
                            >{{ choice.group }}</span
                        >
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
