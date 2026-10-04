<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    ChevronRight,
    ChevronUp,
    Edit,
    GripVertical,
    MapPin,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { defineAsyncComponent, ref, watch } from 'vue';

interface Region {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    meta_title: string | null;
    meta_description: string | null;
    destinations_count: number;
    destinations: Destination[];
}

interface Destination {
    id: number;
    region_id: number;
    name: string;
    slug: string;
    description: string | null;
    featured_image: string | null;
    latitude: number | null;
    longitude: number | null;
    best_time_to_visit: string | null;
    average_cost: string | null;
    is_featured: boolean;
    types: DestinationType[];
}

interface DestinationType {
    id: number;
    name: string;
}

const props = defineProps<{
    regions: Region[];
    destinationTypes: DestinationType[];
}>();

// Loaded only when the destination dialog opens (Tiptap is large)
const RichTextEditor = defineAsyncComponent(
    () => import('@/components/admin/editor/RichTextEditor.vue'),
);

// Local copy so reordering shows instantly; server data replaces it after each visit
const orderedRegions = ref<Region[]>([...props.regions]);
watch(
    () => props.regions,
    (regions) => (orderedRegions.value = [...regions]),
);

const draggingIndex = ref<number | null>(null);
// Only the grip starts a drag, so text and buttons in the card still work normally
const dragHandleId = ref<number | null>(null);

const moveRegion = (from: number, to: number) => {
    if (to < 0 || to >= orderedRegions.value.length || from === to) return;
    const regions = [...orderedRegions.value];
    const [moved] = regions.splice(from, 1);
    regions.splice(to, 0, moved);
    orderedRegions.value = regions;
};

const saveRegionOrder = () => {
    const ids = orderedRegions.value.map((region) => region.id);
    if (ids.every((id, index) => id === props.regions[index]?.id)) return;

    router.put(
        '/admin/places/regions/order',
        { ids },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => (orderedRegions.value = [...props.regions]),
        },
    );
};

const nudgeRegion = (index: number, step: -1 | 1) => {
    moveRegion(index, index + step);
    saveRegionOrder();
};

const onDragStart = (event: DragEvent, index: number) => {
    draggingIndex.value = index;
    event.dataTransfer?.setData('text/plain', String(index));
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
};

const onDragOver = (index: number) => {
    if (draggingIndex.value === null || draggingIndex.value === index) return;
    moveRegion(draggingIndex.value, index);
    draggingIndex.value = index;
};

const onDragEnd = () => {
    draggingIndex.value = null;
    dragHandleId.value = null;
    saveRegionOrder();
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Places',
        href: '/admin/places',
    },
];

const showRegionModal = ref(false);
const showDestinationModal = ref(false);
const editingRegion = ref<Region | null>(null);
const editingDestination = ref<Destination | null>(null);
const expandedRegions = ref<Set<number>>(new Set());

const regionForm = useForm({
    name: '',
    slug: '',
    description: '',
    image: '',
    meta_title: '',
    meta_description: '',
});

// Search engines cut meta descriptions off beyond this (App\Support\Seo)
const META_DESCRIPTION_LENGTH = 160;

const destinationForm = useForm({
    region_id: null as number | null,
    name: '',
    slug: '',
    description: '',
    featured_image: '',
    latitude: null as number | null,
    longitude: null as number | null,
    best_time_to_visit: '',
    average_cost: '',
    is_featured: false,
    types: [] as number[],
});

const toggleRegion = (regionId: number) => {
    if (expandedRegions.value.has(regionId)) {
        expandedRegions.value.delete(regionId);
    } else {
        expandedRegions.value.add(regionId);
    }
};

const openRegionModal = (region: Region | null = null) => {
    if (region) {
        editingRegion.value = region;
        regionForm.name = region.name;
        regionForm.slug = region.slug;
        regionForm.description = region.description || '';
        regionForm.image = region.image || '';
        regionForm.meta_title = region.meta_title || '';
        regionForm.meta_description = region.meta_description || '';
    } else {
        editingRegion.value = null;
        regionForm.reset();
    }
    showRegionModal.value = true;
};

const openDestinationModal = (
    destination: Destination | null = null,
    regionId: number | null = null,
) => {
    if (destination) {
        editingDestination.value = destination;
        destinationForm.region_id = destination.region_id;
        destinationForm.name = destination.name;
        destinationForm.slug = destination.slug;
        destinationForm.description = destination.description || '';
        destinationForm.featured_image = destination.featured_image || '';
        destinationForm.latitude = destination.latitude;
        destinationForm.longitude = destination.longitude;
        destinationForm.best_time_to_visit =
            destination.best_time_to_visit || '';
        destinationForm.average_cost = destination.average_cost || '';
        destinationForm.is_featured = destination.is_featured;
        destinationForm.types = destination.types.map((type) => type.id);
    } else {
        editingDestination.value = null;
        destinationForm.reset();
        if (regionId) {
            destinationForm.region_id = regionId;
        }
    }
    showDestinationModal.value = true;
};

const submitRegion = () => {
    if (editingRegion.value) {
        regionForm.put(`/admin/places/regions/${editingRegion.value.id}`, {
            onSuccess: () => {
                showRegionModal.value = false;
                regionForm.reset();
            },
        });
    } else {
        regionForm.post('/admin/places/regions', {
            onSuccess: () => {
                showRegionModal.value = false;
                regionForm.reset();
            },
        });
    }
};

const submitDestination = () => {
    if (editingDestination.value) {
        destinationForm.put(
            `/admin/places/destinations/${editingDestination.value.id}`,
            {
                onSuccess: () => {
                    showDestinationModal.value = false;
                    destinationForm.reset();
                },
            },
        );
    } else {
        destinationForm.post('/admin/places/destinations', {
            onSuccess: () => {
                showDestinationModal.value = false;
                destinationForm.reset();
            },
        });
    }
};

const deleteRegion = (region: Region) => {
    if (
        confirm(
            `Are you sure you want to delete "${region.name}"? This will also delete all destinations in this region.`,
        )
    ) {
        useForm({}).delete(`/admin/places/regions/${region.id}`);
    }
};

const deleteDestination = (destination: Destination) => {
    if (confirm(`Are you sure you want to delete "${destination.name}"?`)) {
        useForm({}).delete(`/admin/places/destinations/${destination.id}`);
    }
};

const generateSlug = (text: string) => {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
};
</script>

<template>
    <Head title="Places Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Places Management</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage regions and destinations
                    </p>
                </div>
                <button
                    @click="openRegionModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Add Region
                </button>
            </div>

            <!-- Regions List -->
            <div class="space-y-4">
                <div
                    v-for="(region, index) in orderedRegions"
                    :key="region.id"
                    :draggable="dragHandleId === region.id"
                    :class="[
                        'overflow-hidden rounded-lg border border-sidebar-border bg-card transition-opacity',
                        draggingIndex === index && 'opacity-50',
                    ]"
                    @dragstart="onDragStart($event, index)"
                    @dragover.prevent="onDragOver(index)"
                    @drop.prevent
                    @dragend="onDragEnd"
                >
                    <!-- Region Header -->
                    <div
                        class="flex items-center justify-between p-4 transition-colors hover:bg-accent/50"
                    >
                        <div class="mr-2 flex items-center gap-0.5">
                            <span
                                class="cursor-grab p-1 text-muted-foreground hover:text-foreground active:cursor-grabbing"
                                title="Drag to reorder"
                                aria-hidden="true"
                                @pointerdown="dragHandleId = region.id"
                                @pointerup="dragHandleId = null"
                            >
                                <GripVertical class="h-5 w-5" />
                            </span>
                            <div class="flex flex-col">
                                <button
                                    type="button"
                                    class="rounded text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-30"
                                    :disabled="index === 0"
                                    :aria-label="`Move ${region.name} up`"
                                    @click="nudgeRegion(index, -1)"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-30"
                                    :disabled="
                                        index === orderedRegions.length - 1
                                    "
                                    :aria-label="`Move ${region.name} down`"
                                    @click="nudgeRegion(index, 1)"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <div
                            class="flex flex-1 cursor-pointer items-center gap-3"
                            @click="toggleRegion(region.id)"
                        >
                            <button class="text-muted-foreground">
                                <ChevronRight
                                    v-if="!expandedRegions.has(region.id)"
                                    class="h-5 w-5"
                                />
                                <ChevronDown v-else class="h-5 w-5" />
                            </button>
                            <MapPin class="h-5 w-5 text-primary" />
                            <div>
                                <h3 class="text-lg font-semibold">
                                    {{ region.name }}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ region.destinations_count }}
                                    destination{{
                                        region.destinations_count !== 1
                                            ? 's'
                                            : ''
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="openDestinationModal(null, region.id)"
                                class="inline-flex items-center gap-1 rounded-md bg-primary/10 px-3 py-1.5 text-sm text-primary transition-colors hover:bg-primary/20"
                            >
                                <Plus class="h-3 w-3" />
                                Add Destination
                            </button>
                            <button
                                @click="openRegionModal(region)"
                                class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                <Edit class="h-4 w-4" />
                            </button>
                            <button
                                @click="deleteRegion(region)"
                                class="rounded-md p-2 text-destructive transition-colors hover:bg-destructive/10"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Destinations List -->
                    <div
                        v-if="expandedRegions.has(region.id)"
                        class="border-t border-sidebar-border bg-accent/20"
                    >
                        <div
                            v-if="region.destinations.length === 0"
                            class="p-8 text-center text-muted-foreground"
                        >
                            No destinations yet. Add one to get started.
                        </div>
                        <div v-else class="divide-y divide-sidebar-border">
                            <div
                                v-for="destination in region.destinations"
                                :key="destination.id"
                                class="flex items-center justify-between p-4 transition-colors hover:bg-accent/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-muted"
                                    >
                                        <MapPin
                                            class="h-5 w-5 text-muted-foreground"
                                        />
                                    </div>
                                    <div>
                                        <h4 class="font-medium">
                                            {{ destination.name }}
                                        </h4>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ destination.slug }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="destination.is_featured"
                                        class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary"
                                    >
                                        Featured
                                    </span>
                                    <span
                                        v-for="type in destination.types"
                                        :key="type.id"
                                        class="hidden rounded-full border border-input px-2 py-0.5 text-xs text-muted-foreground sm:inline"
                                    >
                                        {{ type.name }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="
                                            openDestinationModal(destination)
                                        "
                                        class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteDestination(destination)"
                                        class="rounded-md p-2 text-destructive transition-colors hover:bg-destructive/10"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Region Modal -->
            <div
                v-if="showRegionModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="showRegionModal = false"
            >
                <div
                    class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg bg-card p-6 shadow-lg"
                >
                    <h2 class="mb-4 text-2xl font-bold">
                        {{ editingRegion ? 'Edit Region' : 'Add Region' }}
                    </h2>
                    <form @submit.prevent="submitRegion" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Name</label
                            >
                            <input
                                v-model="regionForm.name"
                                @input="
                                    regionForm.slug = generateSlug(
                                        regionForm.name,
                                    )
                                "
                                type="text"
                                required
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Slug</label
                            >
                            <input
                                v-model="regionForm.slug"
                                type="text"
                                required
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Description</label
                            >
                            <textarea
                                v-model="regionForm.description"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            ></textarea>
                        </div>
                        <div>
                            <label
                                for="region-meta-title"
                                class="mb-1 block text-sm font-medium"
                                >Meta Title</label
                            >
                            <input
                                id="region-meta-title"
                                v-model="regionForm.meta_title"
                                type="text"
                                maxlength="255"
                                :placeholder="
                                    regionForm.name
                                        ? `${regionForm.name} Travel Guide | Java Sunrise`
                                        : ''
                                "
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Leave empty to generate from the name.
                            </p>
                            <InputError
                                :message="regionForm.errors.meta_title"
                            />
                        </div>
                        <div>
                            <div
                                class="mb-1 flex items-baseline justify-between"
                            >
                                <label
                                    for="region-meta-description"
                                    class="block text-sm font-medium"
                                    >Meta Description</label
                                >
                                <span
                                    :class="[
                                        'text-xs tabular-nums',
                                        regionForm.meta_description.length >
                                        META_DESCRIPTION_LENGTH
                                            ? 'text-destructive'
                                            : 'text-muted-foreground',
                                    ]"
                                    aria-live="polite"
                                >
                                    {{ regionForm.meta_description.length }} /
                                    {{ META_DESCRIPTION_LENGTH }}
                                </span>
                            </div>
                            <textarea
                                id="region-meta-description"
                                v-model="regionForm.meta_description"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2"
                            ></textarea>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Leave empty to use the start of the description,
                                cut to {{ META_DESCRIPTION_LENGTH }} characters.
                            </p>
                            <InputError
                                :message="regionForm.errors.meta_description"
                            />
                        </div>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showRegionModal = false"
                                class="rounded-md border border-input px-4 py-2 hover:bg-accent"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="regionForm.processing"
                                class="rounded-md bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90"
                            >
                                {{ editingRegion ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Destination Modal -->
            <div
                v-if="showDestinationModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="showDestinationModal = false"
            >
                <div
                    class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-card p-6 shadow-lg"
                >
                    <h2 class="mb-4 text-2xl font-bold">
                        {{
                            editingDestination
                                ? 'Edit Destination'
                                : 'Add Destination'
                        }}
                    </h2>
                    <form @submit.prevent="submitDestination" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="mb-1 block text-sm font-medium"
                                    >Region</label
                                >
                                <select
                                    v-model="destinationForm.region_id"
                                    required
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                >
                                    <option :value="null">
                                        Select a region
                                    </option>
                                    <option
                                        v-for="region in orderedRegions"
                                        :key="region.id"
                                        :value="region.id"
                                    >
                                        {{ region.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Name</label
                                >
                                <input
                                    v-model="destinationForm.name"
                                    @input="
                                        destinationForm.slug = generateSlug(
                                            destinationForm.name,
                                        )
                                    "
                                    type="text"
                                    required
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Slug</label
                                >
                                <input
                                    v-model="destinationForm.slug"
                                    type="text"
                                    required
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1 block text-sm font-medium"
                                    >Description</label
                                >
                                <RichTextEditor
                                    id="destination-description"
                                    v-model="destinationForm.description"
                                    label="Description"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Latitude</label
                                >
                                <input
                                    v-model.number="destinationForm.latitude"
                                    type="number"
                                    step="any"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Longitude</label
                                >
                                <input
                                    v-model.number="destinationForm.longitude"
                                    type="number"
                                    step="any"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Best Time to Visit</label
                                >
                                <input
                                    v-model="destinationForm.best_time_to_visit"
                                    type="text"
                                    placeholder="e.g., April - October"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Average Cost</label
                                >
                                <input
                                    v-model="destinationForm.average_cost"
                                    type="text"
                                    placeholder="e.g., $50-100/day"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2"
                                />
                            </div>
                            <fieldset class="col-span-2">
                                <legend class="mb-1 block text-sm font-medium">
                                    Types
                                    <span
                                        class="font-normal text-muted-foreground"
                                        >— what kind of trip it suits</span
                                    >
                                </legend>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="type in destinationTypes"
                                        :key="type.id"
                                        :class="[
                                            'inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors',
                                            destinationForm.types.includes(
                                                type.id,
                                            )
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input hover:bg-accent',
                                        ]"
                                    >
                                        <input
                                            v-model="destinationForm.types"
                                            type="checkbox"
                                            :value="type.id"
                                            class="sr-only"
                                        />
                                        <Check
                                            v-if="
                                                destinationForm.types.includes(
                                                    type.id,
                                                )
                                            "
                                            class="h-3.5 w-3.5"
                                            aria-hidden="true"
                                        />
                                        {{ type.name }}
                                    </label>
                                    <p
                                        v-if="!destinationTypes.length"
                                        class="text-sm text-muted-foreground"
                                    >
                                        No types yet. Add them under Destination
                                        Types.
                                    </p>
                                </div>
                                <InputError
                                    :message="destinationForm.errors.types"
                                />
                            </fieldset>
                            <div class="col-span-2 flex items-center gap-2">
                                <input
                                    v-model="destinationForm.is_featured"
                                    type="checkbox"
                                    id="is_featured"
                                    class="rounded border-input"
                                />
                                <label
                                    for="is_featured"
                                    class="text-sm font-medium"
                                    >Featured Destination</label
                                >
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button
                                type="button"
                                @click="showDestinationModal = false"
                                class="rounded-md border border-input px-4 py-2 hover:bg-accent"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="destinationForm.processing"
                                class="rounded-md bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90"
                            >
                                {{ editingDestination ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
