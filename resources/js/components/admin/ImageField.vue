<script setup lang="ts">
import { type ImageValue } from '@/types/admin';
import { ImagePlus, Trash2, Undo2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
    id: string;
    current: ImageValue | null;
    required: boolean;
}>();

const upload = defineModel<File | null>('upload', { default: null });
const remove = defineModel<boolean>('remove', { default: false });

const previewUrl = ref<string | null>(null);
const input = ref<HTMLInputElement>();

const shownUrl = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (remove.value) return null;
    return props.current?.url ?? null;
});

const pick = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = file ? URL.createObjectURL(file) : null;
    upload.value = file;
    remove.value = false;
};

const clear = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
    upload.value = null;
    if (input.value) input.value.value = '';
    remove.value = !!props.current;
};

onBeforeUnmount(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});
</script>

<template>
    <div class="flex flex-wrap items-start gap-4">
        <div
            class="flex h-32 w-48 items-center justify-center overflow-hidden rounded-lg border border-input bg-muted"
        >
            <img
                v-if="shownUrl"
                :src="shownUrl"
                alt=""
                class="h-full w-full object-cover"
            />
            <ImagePlus v-else class="size-8 text-muted-foreground" />
        </div>
        <div class="flex flex-col gap-2">
            <input
                :id="id"
                ref="input"
                type="file"
                accept="image/*"
                class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary"
                @change="pick"
            />
            <p v-if="current && !remove" class="text-xs text-muted-foreground">
                Current: {{ current.path }}
            </p>
            <button
                v-if="(current && !remove && !required) || upload"
                type="button"
                class="inline-flex w-fit items-center gap-1 text-sm text-destructive hover:underline"
                @click="clear"
            >
                <Trash2 class="size-3.5" />
                {{ upload ? 'Discard new image' : 'Remove image' }}
            </button>
            <button
                v-if="remove"
                type="button"
                class="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:underline"
                @click="remove = false"
            >
                <Undo2 class="size-3.5" /> Keep current image
            </button>
        </div>
    </div>
</template>
