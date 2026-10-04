<script setup lang="ts">
import FieldInput from '@/components/admin/FieldInput.vue';
import { adminFormKey } from '@/components/admin/form';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type FieldDef, type ResourceMeta } from '@/types/admin';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Trash2 } from 'lucide-vue-next';
import { computed, provide } from 'vue';

const props = defineProps<{
    resource: ResourceMeta;
    record: {
        id: number;
        title: string;
        publicUrl: string | null;
        canDelete: boolean;
    } | null;
    fields: FieldDef[];
    values: Record<string, unknown>;
}>();

const baseUrl = computed(() => `/admin/${props.resource.key}`);
const title = computed(() =>
    props.record ? props.record.title : `New ${props.resource.singular}`,
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.resource.label, href: baseUrl.value },
    {
        title: props.record ? 'Edit' : 'New',
        href: props.record
            ? `${baseUrl.value}/${props.record.id}/edit`
            : `${baseUrl.value}/create`,
    },
]);

// Image and gallery fields submit uploads under their own keys instead of the value
const formData = () => {
    const data: Record<string, any> = {};
    for (const field of props.fields) {
        if (field.type === 'image') {
            data[`${field.name}_upload`] = null;
            data[`${field.name}_remove`] = false;
        } else if (field.type === 'gallery') {
            data[`${field.name}_new`] = [];
            data[`${field.name}_remove`] = [];
            data[`${field.name}_featured`] = null;
        } else {
            data[field.name] = props.values[field.name] ?? null;
        }
    }
    return data;
};

const form = useForm(formData());
provide(adminFormKey, form);

const slugify = (text: unknown) =>
    String(text ?? '')
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');

// Slug fields show the generated slug as a placeholder until typed over
const fieldsWithPlaceholders = computed(() =>
    props.fields.map((field) => ({
        ...field,
        optionsUrl: `${baseUrl.value}/fields/${field.name}/options`,
        placeholder:
            field.type === 'slug' && field.from
                ? slugify(form[field.from]) || field.placeholder
                : field.placeholder,
    })),
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: props.record ? 'put' : 'post',
    })).post(
        props.record ? `${baseUrl.value}/${props.record.id}` : baseUrl.value,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // Pick up saved values, e.g. new gallery images and generated slugs
                form.defaults(formData());
                form.reset();
            },
        },
    );
};

const destroy = () => {
    if (
        props.record &&
        confirm(`Delete "${props.record.title}"? This can't be undone.`)
    ) {
        router.delete(`${baseUrl.value}/${props.record.id}`);
    }
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form
            class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-5 p-4 sm:p-6"
            novalidate
            @submit.prevent="submit"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <Link
                        :href="baseUrl"
                        class="mb-1 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="size-4" />
                        {{ resource.label }}
                    </Link>
                    <h1 class="truncate text-2xl font-bold sm:text-3xl">
                        {{ title }}
                    </h1>
                </div>
                <a
                    v-if="record?.publicUrl"
                    :href="record.publicUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-input px-3 py-2 text-sm hover:bg-accent"
                >
                    <ExternalLink class="size-4" />
                    View on site
                </a>
            </div>

            <div
                class="grid gap-x-5 gap-y-5 rounded-lg border border-sidebar-border bg-card p-4 sm:grid-cols-2 sm:p-6"
            >
                <FieldInput
                    v-for="field in fieldsWithPlaceholders"
                    :key="field.name"
                    :field="field"
                    :initial="values[field.name]"
                />
            </div>

            <div
                class="sticky bottom-0 -mx-4 flex items-center justify-between gap-3 border-t border-sidebar-border bg-background/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6"
            >
                <button
                    v-if="record?.canDelete"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-destructive hover:bg-destructive/10"
                    @click="destroy"
                >
                    <Trash2 class="size-4" />
                    Delete
                </button>
                <span v-else />
                <div class="flex items-center gap-3">
                    <span
                        v-if="form.isDirty && !form.processing"
                        class="hidden text-sm text-muted-foreground sm:inline"
                    >
                        Unsaved changes
                    </span>
                    <span
                        v-if="form.hasErrors"
                        class="text-sm text-destructive"
                        role="alert"
                    >
                        Please fix the errors above.
                    </span>
                    <Link
                        :href="baseUrl"
                        class="rounded-lg border border-input px-4 py-2 text-sm hover:bg-accent"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Saving…'
                                : record
                                  ? 'Save changes'
                                  : `Create ${resource.singular}`
                        }}
                    </button>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
