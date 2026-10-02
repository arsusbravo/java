<script setup lang="ts">
import { type GalleryImage } from '@/types/admin';
import { Star, Trash2, Undo2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
    id: string;
    images: GalleryImage[];
}>();

const added = defineModel<File[]>('added', { default: () => [] });
const removed = defineModel<number[]>('removed', { default: () => [] });
const featured = defineModel<number | null>('featured', { default: null });

const input = ref<HTMLInputElement>();
const previews = ref<string[]>([]);

const currentFeatured = computed(
    () =>
        featured.value ??
        props.images.find((image) => image.is_featured)?.id ??
        null,
);

const pick = (event: Event) => {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    added.value = [...added.value, ...files];
    previews.value = [
        ...previews.value,
        ...files.map((file) => URL.createObjectURL(file)),
    ];
    if (input.value) input.value.value = '';
};

const discard = (index: number) => {
    URL.revokeObjectURL(previews.value[index]);
    previews.value = previews.value.filter((_, i) => i !== index);
    added.value = added.value.filter((_, i) => i !== index);
};

const toggleRemove = (id: number) => {
    removed.value = removed.value.includes(id)
        ? removed.value.filter((item) => item !== id)
        : [...removed.value, id];
};

onBeforeUnmount(() => previews.value.forEach(URL.revokeObjectURL));
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="images.length || previews.length"
            class="grid grid-cols-2 gap-3 sm:grid-cols-4"
        >
            <div
                v-for="image in images"
                :key="image.id"
                :class="[
                    'group relative aspect-[4/3] overflow-hidden rounded-lg border',
                    currentFeatured === image.id
                        ? 'border-primary ring-2 ring-primary/40'
                        : 'border-input',
                ]"
            >
                <img
                    :src="image.url"
                    alt=""
                    :class="[
                        'h-full w-full object-cover',
                        removed.includes(image.id) && 'opacity-30 grayscale',
                    ]"
                />
                <div class="absolute inset-x-1 bottom-1 flex justify-between">
                    <button
                        type="button"
                        :disabled="removed.includes(image.id)"
                        :class="[
                            'rounded-md bg-background/90 p-1.5 disabled:opacity-40',
                            currentFeatured === image.id
                                ? 'text-amber-500'
                                : 'text-muted-foreground hover:text-amber-500',
                        ]"
                        :aria-label="
                            currentFeatured === image.id
                                ? 'Featured image'
                                : 'Make featured'
                        "
                        :title="
                            currentFeatured === image.id
                                ? 'Featured image'
                                : 'Make featured'
                        "
                        @click="featured = image.id"
                    >
                        <Star
                            class="size-4"
                            :fill="
                                currentFeatured === image.id
                                    ? 'currentColor'
                                    : 'none'
                            "
                        />
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-background/90 p-1.5 text-destructive"
                        :aria-label="
                            removed.includes(image.id)
                                ? 'Keep image'
                                : 'Remove image'
                        "
                        :title="
                            removed.includes(image.id)
                                ? 'Keep image'
                                : 'Remove image'
                        "
                        @click="toggleRemove(image.id)"
                    >
                        <Undo2
                            v-if="removed.includes(image.id)"
                            class="size-4"
                        />
                        <Trash2 v-else class="size-4" />
                    </button>
                </div>
            </div>
            <div
                v-for="(url, index) in previews"
                :key="url"
                class="relative aspect-[4/3] overflow-hidden rounded-lg border border-dashed border-primary"
            >
                <img :src="url" alt="" class="h-full w-full object-cover" />
                <span
                    class="absolute top-1 left-1 rounded bg-primary px-1.5 py-0.5 text-[10px] font-semibold text-primary-foreground uppercase"
                >
                    New
                </span>
                <button
                    type="button"
                    class="absolute right-1 bottom-1 rounded-md bg-background/90 p-1.5 text-destructive"
                    aria-label="Discard upload"
                    @click="discard(index)"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>
        <input
            :id="id"
            ref="input"
            type="file"
            accept="image/*"
            multiple
            class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary"
            @change="pick"
        />
        <p class="text-xs text-muted-foreground">
            Star an image to use it as the main picture. Changes apply when you
            save.
        </p>
    </div>
</template>
