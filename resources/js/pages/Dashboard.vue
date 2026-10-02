<script setup lang="ts">
import { resourceIcon } from '@/components/admin/icons';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    counts: { key: string; label: string; icon: string; count: number }[];
    stats: {
        pendingReviews: number;
        draftArticles: number;
        clicksLast30Days: number;
        articleViews: number;
    };
    pendingReviews: {
        id: number;
        user_name: string;
        rating: number;
        comment: string;
        item: string | null;
    }[];
    topArticles: { id: number; title: string; views_count: number }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const moderate = (id: number, action: 'approve' | 'reject') => {
    router.post(
        `/admin/reviews/${id}/actions/${action}`,
        {},
        { preserveScroll: true },
    );
};

const number = (value: number) => value.toLocaleString();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
            <h1 class="text-2xl font-bold sm:text-3xl">Dashboard</h1>

            <!-- Headline stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Link
                    href="/admin/reviews?status=pending"
                    class="rounded-xl border border-sidebar-border bg-card p-4 hover:bg-accent/40"
                >
                    <p class="text-sm text-muted-foreground">Pending reviews</p>
                    <p
                        :class="[
                            'mt-1 text-3xl font-semibold tabular-nums',
                            stats.pendingReviews && 'text-amber-600',
                        ]"
                    >
                        {{ number(stats.pendingReviews) }}
                    </p>
                </Link>
                <Link
                    href="/admin/articles?status=draft"
                    class="rounded-xl border border-sidebar-border bg-card p-4 hover:bg-accent/40"
                >
                    <p class="text-sm text-muted-foreground">Draft articles</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">
                        {{ number(stats.draftArticles) }}
                    </p>
                </Link>
                <Link
                    href="/admin/affiliate-clicks"
                    class="rounded-xl border border-sidebar-border bg-card p-4 hover:bg-accent/40"
                >
                    <p class="text-sm text-muted-foreground">
                        Affiliate clicks (30 days)
                    </p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">
                        {{ number(stats.clicksLast30Days) }}
                    </p>
                </Link>
                <div
                    class="rounded-xl border border-sidebar-border bg-card p-4"
                >
                    <p class="text-sm text-muted-foreground">Article views</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">
                        {{ number(stats.articleViews) }}
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Pending reviews -->
                <section
                    class="rounded-xl border border-sidebar-border bg-card"
                >
                    <header
                        class="flex items-center justify-between border-b border-sidebar-border px-4 py-3"
                    >
                        <h2 class="font-semibold">Reviews awaiting approval</h2>
                        <Link
                            href="/admin/reviews?status=pending"
                            class="text-sm text-muted-foreground hover:text-foreground"
                            >View all</Link
                        >
                    </header>
                    <p
                        v-if="!pendingReviews.length"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        All caught up.
                    </p>
                    <ul v-else class="divide-y divide-sidebar-border">
                        <li
                            v-for="review in pendingReviews"
                            :key="review.id"
                            class="flex gap-3 px-4 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">
                                    {{ review.user_name }}
                                    <span
                                        class="text-amber-500"
                                        :aria-label="`${review.rating} stars`"
                                        >{{ '★'.repeat(review.rating) }}</span
                                    >
                                    <span
                                        v-if="review.item"
                                        class="font-normal text-muted-foreground"
                                    >
                                        · {{ review.item }}</span
                                    >
                                </p>
                                <p
                                    class="mt-0.5 line-clamp-2 text-sm text-muted-foreground"
                                >
                                    {{ review.comment }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-start gap-1">
                                <button
                                    type="button"
                                    class="rounded-md bg-green-600/10 px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-600/20 dark:text-green-400"
                                    @click="moderate(review.id, 'approve')"
                                >
                                    Approve
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md px-2 py-1 text-xs font-medium text-destructive hover:bg-destructive/10"
                                    @click="moderate(review.id, 'reject')"
                                >
                                    Reject
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Top articles -->
                <section
                    class="rounded-xl border border-sidebar-border bg-card"
                >
                    <header
                        class="flex items-center justify-between border-b border-sidebar-border px-4 py-3"
                    >
                        <h2 class="font-semibold">Most read articles</h2>
                        <Link
                            href="/admin/articles?sort=views_count&direction=desc"
                            class="text-sm text-muted-foreground hover:text-foreground"
                            >View all</Link
                        >
                    </header>
                    <p
                        v-if="!topArticles.length"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        No published articles yet.
                    </p>
                    <ol v-else class="divide-y divide-sidebar-border">
                        <li
                            v-for="article in topArticles"
                            :key="article.id"
                            class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                        >
                            <Link
                                :href="`/admin/articles/${article.id}/edit`"
                                class="truncate hover:text-primary hover:underline"
                                >{{ article.title }}</Link
                            >
                            <span
                                class="shrink-0 text-muted-foreground tabular-nums"
                                >{{ number(article.views_count) }} views</span
                            >
                        </li>
                    </ol>
                </section>
            </div>

            <!-- Everything in the database -->
            <section>
                <h2 class="mb-3 font-semibold">Database</h2>
                <div
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6"
                >
                    <Link
                        v-for="item in counts"
                        :key="item.key"
                        :href="`/admin/${item.key}`"
                        class="flex items-center gap-3 rounded-xl border border-sidebar-border bg-card p-3 hover:bg-accent/40"
                    >
                        <component
                            :is="resourceIcon(item.icon)"
                            class="size-5 shrink-0 text-primary"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm text-muted-foreground">
                                {{ item.label }}
                            </p>
                            <p class="font-semibold tabular-nums">
                                {{ number(item.count) }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
