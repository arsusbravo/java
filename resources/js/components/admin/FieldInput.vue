<script setup lang="ts">
import { useAdminForm } from '@/components/admin/form';
import GalleryField from '@/components/admin/GalleryField.vue';
import ImageField from '@/components/admin/ImageField.vue';
import MultiSelect from '@/components/admin/MultiSelect.vue';
import RemoteSelect from '@/components/admin/RemoteSelect.vue';
import InputError from '@/components/InputError.vue';
import { type Choice, type FieldDef } from '@/types/admin';
import { computed, defineAsyncComponent } from 'vue';

// Loaded only on forms that have a rich text field (Tiptap is large)
const RichTextEditor = defineAsyncComponent(
    () => import('@/components/admin/editor/RichTextEditor.vue'),
);

const props = defineProps<{
    field: FieldDef;
    initial: unknown;
}>();

const form = useAdminForm();

const id = computed(() => `field-${props.field.name}`);

const inputClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

// Errors for sub-keys (e.g. images_new.0) are shown under the field
const error = computed(() => {
    const name = props.field.name;
    return Object.entries(form.errors).find(
        ([key]) =>
            key === name ||
            key.startsWith(`${name}.`) ||
            key.startsWith(`${name}_`),
    )?.[1];
});

const groupedChoices = computed(() => {
    const groups = new Map<string, Choice[]>();
    for (const choice of props.field.choices) {
        const group = choice.group ?? '';
        groups.set(group, [...(groups.get(group) ?? []), choice]);
    }
    return groups;
});
</script>

<template>
    <div :class="field.wide ? 'sm:col-span-2' : ''">
        <label
            v-if="field.type !== 'boolean'"
            :for="id"
            class="mb-1.5 block text-sm font-medium"
        >
            {{ field.label }}
            <span v-if="field.required" class="text-destructive">*</span>
        </label>

        <textarea
            v-if="field.type === 'textarea' || field.type === 'list'"
            :id="id"
            v-model="form[field.name]"
            :rows="field.type === 'list' ? 5 : 3"
            :placeholder="field.placeholder ?? undefined"
            :class="inputClass"
        />

        <RichTextEditor
            v-else-if="field.type === 'html'"
            :id="id"
            :label="field.label"
            v-model="form[field.name]"
            :placeholder="field.placeholder ?? undefined"
        />

        <label
            v-else-if="field.type === 'boolean'"
            class="flex h-full cursor-pointer items-center gap-2 pt-6 text-sm font-medium"
        >
            <input
                :id="id"
                v-model="form[field.name]"
                type="checkbox"
                class="size-4 rounded border-input"
            />
            {{ field.label }}
        </label>

        <RemoteSelect
            v-else-if="
                field.remote &&
                field.optionsUrl &&
                field.type !== 'belongsToMany'
            "
            :id="id"
            v-model="form[field.name]"
            :choices="field.choices"
            :options-url="field.optionsUrl"
            :required="field.required"
        />

        <select
            v-else-if="
                field.type === 'select' ||
                field.type === 'belongsTo' ||
                field.type === 'morphTo'
            "
            :id="id"
            v-model="form[field.name]"
            :required="field.required"
            :class="inputClass"
        >
            <option :value="null">
                {{ field.required ? 'Choose…' : '— None —' }}
            </option>
            <template v-for="[group, choices] in groupedChoices" :key="group">
                <optgroup v-if="group" :label="group">
                    <option
                        v-for="choice in choices"
                        :key="choice.value"
                        :value="choice.value"
                    >
                        {{ choice.label }}
                    </option>
                </optgroup>
                <template v-else>
                    <option
                        v-for="choice in choices"
                        :key="choice.value"
                        :value="choice.value"
                    >
                        {{ choice.label }}
                    </option>
                </template>
            </template>
        </select>

        <MultiSelect
            v-else-if="field.type === 'belongsToMany'"
            :id="id"
            v-model="form[field.name]"
            :choices="field.choices"
            :options-url="field.remote ? field.optionsUrl : undefined"
        />

        <ImageField
            v-else-if="field.type === 'image'"
            :id="id"
            v-model:upload="form[`${field.name}_upload`]"
            v-model:remove="form[`${field.name}_remove`]"
            :current="initial as any"
            :required="field.required"
        />

        <GalleryField
            v-else-if="field.type === 'gallery'"
            :id="id"
            v-model:added="form[`${field.name}_new`]"
            v-model:removed="form[`${field.name}_remove`]"
            v-model:featured="form[`${field.name}_featured`]"
            :images="(initial as any) ?? []"
        />

        <input
            v-else
            :id="id"
            v-model="form[field.name]"
            :type="
                {
                    email: 'email',
                    url: 'url',
                    password: 'password',
                    number: 'number',
                    decimal: 'number',
                    datetime: 'datetime-local',
                }[field.type as string] ?? 'text'
            "
            :step="field.type === 'decimal' ? 'any' : undefined"
            :required="field.required && field.type !== 'password'"
            :placeholder="field.placeholder ?? undefined"
            :autocomplete="
                field.type === 'password' ? 'new-password' : undefined
            "
            :class="inputClass"
        />

        <p v-if="field.help" class="mt-1 text-xs text-muted-foreground">
            {{ field.help }}
        </p>
        <InputError :message="error" class="mt-1" />
    </div>
</template>
