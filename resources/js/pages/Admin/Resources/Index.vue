<script setup lang="ts">
import FlashMessages from '@/components/admin/FlashMessages.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import {
    type Column,
    type Filter,
    type Paginated,
    type RecordRow,
    type ResourceMeta,
} from '@/types/admin';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Check,
    ExternalLink,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';

const props = defineProps<{
    resource: ResourceMeta;
    columns: Column[];
    filters: Filter[];
    actions: {
        key: string;
        label: string;
        attributes: Record<string, unknown>;
    }[];
    records: Paginated<RecordRow>;
    query: Record<string, string | null>;
}>();

const baseUrl = computed(() => `/admin/${props.resource.key}`);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.resource.label, href: baseUrl.value },
]);

const state = reactive<Record<string, string | null>>({
    search: props.query.search ?? '',
    ...Object.fromEntries(
        props.filters.map((filter) => [
            filter.name,
            props.query[filter.name] ?? '',
        ]),
    ),
});

const visit = (extra: Record<string, string | null> = {}) => {
    const params = {
        ...state,
        sort: props.query.sort,
        direction: props.query.direction,
        ...extra,
    };
    // Drop empty values to keep URLs clean
    const clean = Object.fromEntries(
        Object.entries(params).filter(([, value]) => value),
    );
    router.get(baseUrl.value, clean, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => state.search,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => visit(), 300);
    },
);

const sortBy = (column: Column) => {
    if (!column.sortable) return;
    const direction =
        props.query.sort === column.name && props.query.direction === 'asc'
            ? 'desc'
            : 'asc';
    visit({ sort: column.name, direction });
};

const hasFilters = computed(() => Object.values(state).some((value) => value));

const clearFilters = () => {
    Object.keys(state).forEach((key) => (state[key] = ''));
    clearTimeout(searchTimer);
    visit();
};

// Hide actions whose result the row already has, e.g. "Approve" on an approved review
const availableActions = (row: RecordRow) =>
    props.actions.filter((action) =>
        Object.entries(action.attributes).some(
            ([key, value]) => row.cells[key] !== value,
        ),
    );

const runAction = (row: RecordRow, action: string) => {
    router.post(
        `${baseUrl.value}/${row.id}/actions/${action}`,
        {},
        { preserveScroll: true },
    );
};

const destroy = (row: RecordRow) => {
    if (confirm(`Delete "${row.title}"? This can't be undone.`)) {
        router.delete(`${baseUrl.value}/${row.id}`, { preserveScroll: true });
    }
};

const badgeClass = (value: unknown) => {
    const colors: Record<string, string> = {
        published: 'bg-green-500/15 text-green-700 dark:text-green-400',
        approved: 'bg-green-500/15 text-green-700 dark:text-green-400',
        draft: 'bg-muted text-muted-foreground',
        pending: 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
        archived: 'bg-red-500/15 text-red-700 dark:text-red-400',
        rejected: 'bg-red-500/15 text-red-700 dark:text-red-400',
    };
    return colors[String(value)] ?? 'bg-primary/10 text-primary';
};

const label = (value: unknown) =>
    String(value ?? '')
        .replace(/_/g, ' ')
        .replace(/^\w/, (c) => c.toUpperCase());
</script>

<template>
    <Head :title="resource.label" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-5 p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl">
                        {{ resource.label }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ records.total }}
                        {{ records.total === 1 ? 'record' : 'records' }}
                    </p>
                </div>
                <Link
                    v-if="resource.creatable"
                    :href="`${baseUrl}/create`"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    <Plus class="size-4" />
                    New {{ resource.singular }}
                </Link>
            </div>

            <FlashMessages />

            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-52 flex-1 sm:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="state.search"
                        type="search"
                        :placeholder="`Search ${resource.label.toLowerCase()}…`"
                        :aria-label="`Search ${resource.label.toLowerCase()}`"
                        class="h-9 w-full rounded-md border border-input bg-transparent pr-3 pl-9 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    />
                </div>
                <select
                    v-for="filter in filters"
                    :key="filter.name"
                    v-model="state[filter.name]"
                    :aria-label="filter.label"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm dark:bg-input/30"
                    @change="visit()"
                >
                    <option value="">
                        All {{ filter.label.toLowerCase() }}
                    </option>
                    <option
                        v-for="option in filter.options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <button
                    v-if="hasFilters"
                    type="button"
                    class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>

            <!-- Table -->
            <div
                class="overflow-x-auto rounded-lg border border-sidebar-border bg-card"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-sidebar-border bg-muted/40 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column.name"
                                :class="[
                                    'px-4 py-3 whitespace-nowrap',
                                    column.type === 'number' && 'text-right',
                                ]"
                                :aria-sort="
                                    query.sort === column.name
                                        ? query.direction === 'asc'
                                            ? 'ascending'
                                            : 'descending'
                                        : undefined
                                "
                            >
                                <button
                                    v-if="column.sortable"
                                    type="button"
                                    class="inline-flex items-center gap-1 uppercase hover:text-foreground"
                                    @click="sortBy(column)"
                                >
                                    {{ column.label }}
                                    <ArrowUp
                                        v-if="
                                            query.sort === column.name &&
                                            query.direction === 'asc'
                                        "
                                        class="size-3"
                                    />
                                    <ArrowDown
                                        v-else-if="query.sort === column.name"
                                        class="size-3"
                                    />
                                </button>
                                <template v-else>{{ column.label }}</template>
                            </th>
                            <th class="px-4 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="!records.data.length">
                            <td
                                :colspan="columns.length + 1"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <template v-if="hasFilters">
                                    Nothing matches these filters.
                                </template>
                                <template v-else>
                                    No {{ resource.label.toLowerCase() }} yet.
                                </template>
                            </td>
                        </tr>
                        <tr
                            v-for="row in records.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/40"
                        >
                            <td
                                v-for="(column, index) in columns"
                                :key="column.name"
                                :class="[
                                    'px-4 py-2.5 align-middle',
                                    column.type === 'number' &&
                                        'text-right tabular-nums',
                                    column.type === 'date' &&
                                        'whitespace-nowrap text-muted-foreground',
                                ]"
                            >
                                <img
                                    v-if="column.type === 'image'"
                                    :src="row.cells[column.name] as string"
                                    alt=""
                                    loading="lazy"
                                    class="h-10 w-14 rounded object-cover"
                                />
                                <span
                                    v-else-if="
                                        column.type === 'badge' &&
                                        row.cells[column.name]
                                    "
                                    :class="[
                                        'inline-block rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                                        badgeClass(row.cells[column.name]),
                                    ]"
                                >
                                    {{ label(row.cells[column.name]) }}
                                </span>
                                <template v-else-if="column.type === 'boolean'">
                                    <Check
                                        v-if="row.cells[column.name]"
                                        class="size-4 text-green-600"
                                        aria-label="Yes"
                                    />
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </template>
                                <Link
                                    v-else-if="
                                        resource.editable &&
                                        index ===
                                            columns.findIndex(
                                                (c) => c.type !== 'image',
                                            )
                                    "
                                    :href="`${baseUrl}/${row.id}/edit`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ row.cells[column.name] ?? row.title }}
                                </Link>
                                <template v-else>
                                    {{ row.cells[column.name] ?? '—' }}
                                </template>
                            </td>
                            <td class="px-4 py-2.5">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        v-for="action in availableActions(row)"
                                        :key="action.key"
                                        type="button"
                                        class="rounded-md px-2 py-1 text-xs font-medium whitespace-nowrap text-muted-foreground hover:bg-accent hover:text-foreground"
                                        @click="runAction(row, action.key)"
                                    >
                                        {{ action.label }}
                                    </button>
                                    <a
                                        v-if="row.publicUrl"
                                        :href="row.publicUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-foreground"
                                        :aria-label="`View ${row.title} on site`"
                                        title="View on site"
                                    >
                                        <ExternalLink class="size-4" />
                                    </a>
                                    <Link
                                        v-if="resource.editable"
                                        :href="`${baseUrl}/${row.id}/edit`"
                                        class="rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-foreground"
                                        :aria-label="`Edit ${row.title}`"
                                        title="Edit"
                                    >
                                        <Pencil class="size-4" />
                                    </Link>
                                    <button
                                        v-if="row.canDelete"
                                        type="button"
                                        class="rounded-md p-2 text-destructive hover:bg-destructive/10"
                                        :aria-label="`Delete ${row.title}`"
                                        title="Delete"
                                        @click="destroy(row)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav
                v-if="records.links.length > 3"
                class="flex flex-wrap items-center justify-between gap-3 text-sm"
                aria-label="Pagination"
            >
                <p class="text-muted-foreground">
                    Showing {{ records.from }}–{{ records.to }} of
                    {{ records.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in records.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            :class="[
                                'rounded-md border px-3 py-1.5',
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input hover:bg-accent',
                            ]"
                            ><span v-html="link.label"
                        /></Link>
                        <span
                            v-else
                            class="rounded-md border border-input px-3 py-1.5 opacity-40"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
