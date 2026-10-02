<script setup lang="ts">
import { useChoiceSearch } from '@/components/admin/useChoiceSearch';
import { type Choice } from '@/types/admin';
import { X } from 'lucide-vue-next';
import { computed, onMounted, ref, toRef } from 'vue';

const props = defineProps<{
    id: string;
    choices: Choice[];
    // Set for large relations: choices then only holds the current selection
    optionsUrl?: string;
}>();

const model = defineModel<(string | number)[]>({ default: () => [] });
const search = ref('');

// Only this many local options are drawn at once; search narrows the rest
const RENDER_LIMIT = 100;

const remote = computed(() => !!props.optionsUrl);
const {
    results,
    loading,
    search: runSearch,
} = useChoiceSearch(toRef(props, 'optionsUrl'), search);

// Labels for everything seen so far, so selected chips keep their names
const known = ref(new Map<string | number, Choice>());
const remember = (choices: Choice[]) =>
    choices.forEach((choice) => known.value.set(choice.value, choice));
remember(props.choices);

const selected = computed(() =>
    model.value.map(
        (value) => known.value.get(value) ?? { value, label: `#${value}` },
    ),
);

const matches = computed(() => {
    if (remote.value) return results.value;
    const term = search.value.toLowerCase();
    return props.choices.filter((choice) =>
        choice.label.toLowerCase().includes(term),
    );
});

const visible = computed(() => matches.value.slice(0, RENDER_LIMIT));

const toggle = (choice: Choice) => {
    remember([choice]);
    model.value = model.value.includes(choice.value)
        ? model.value.filter((item) => item !== choice.value)
        : [...model.value, choice.value];
};

onMounted(() => {
    if (remote.value) runSearch();
});
</script>

<template>
    <div class="rounded-md border border-input">
        <div
            v-if="selected.length"
            class="flex flex-wrap gap-1.5 border-b border-input p-2"
        >
            <span
                v-for="choice in selected"
                :key="choice.value"
                class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
            >
                {{ choice.label }}
                <button
                    type="button"
                    class="hover:text-destructive"
                    :aria-label="`Remove ${choice.label}`"
                    @click="toggle(choice)"
                >
                    <X class="size-3" />
                </button>
            </span>
        </div>
        <input
            :id="id"
            v-model="search"
            type="search"
            :placeholder="remote ? 'Type to search…' : 'Search…'"
            class="w-full border-b border-input bg-transparent px-3 py-2 text-sm outline-none"
        />
        <div class="max-h-44 overflow-y-auto p-1" :aria-busy="loading">
            <p
                v-if="!visible.length"
                class="px-2 py-3 text-sm text-muted-foreground"
            >
                <template v-if="loading">Searching…</template>
                <template v-else-if="search">No matches.</template>
                <template v-else>Nothing to choose from yet.</template>
            </p>
            <label
                v-for="choice in visible"
                :key="choice.value"
                class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-accent"
            >
                <input
                    type="checkbox"
                    class="rounded border-input"
                    :checked="model.includes(choice.value)"
                    @change="toggle(choice)"
                />
                {{ choice.label }}
            </label>
            <p
                v-if="remote || matches.length > RENDER_LIMIT"
                class="px-2 py-1.5 text-xs text-muted-foreground"
            >
                Showing the first {{ visible.length }} matches — type to narrow
                down.
            </p>
        </div>
    </div>
</template>
