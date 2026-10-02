<script setup lang="ts">
import EditorButton from '@/components/admin/editor/EditorButton.vue';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import {
    Table,
    TableCell,
    TableHeader,
    TableRow,
} from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';
import { Color, TextStyle } from '@tiptap/extension-text-style';
import Youtube from '@tiptap/extension-youtube';
import { CharacterCount, Placeholder } from '@tiptap/extensions';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    Code2,
    Columns3,
    Eraser,
    FileCode2,
    Highlighter,
    ImagePlus,
    Italic,
    Link2,
    Link2Off,
    List,
    ListOrdered,
    Maximize2,
    Minimize2,
    Minus,
    Quote,
    Redo2,
    Rows3,
    Strikethrough,
    Subscript as SubscriptIcon,
    Superscript as SuperscriptIcon,
    TableCellsMerge,
    TableCellsSplit,
    Table as TableIcon,
    Trash2,
    Underline as UnderlineIcon,
    Undo2,
    Youtube as YoutubeIcon,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        id: string;
        label?: string;
        placeholder?: string;
        uploadUrl?: string;
    }>(),
    { placeholder: 'Start writing…', uploadUrl: '/admin/editor-images' },
);

const model = defineModel<string | null>({ default: null });

const sourceMode = ref(false);
const source = ref('');
const fullscreen = ref(false);
const uploading = ref(false);
const uploadError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement>();

// One small form under the toolbar for link, image-URL and video inputs
const prompt = ref<{
    kind: 'link' | 'image' | 'youtube';
    value: string;
} | null>(null);

const BRAND_COLORS = [
    { name: 'Default', value: null },
    { name: 'Java blue', value: '#2c3e50' },
    { name: 'Sunrise orange', value: '#e67e22' },
    { name: 'Green', value: '#16a34a' },
    { name: 'Red', value: '#dc2626' },
    { name: 'Grey', value: '#6b7280' },
];
const HIGHLIGHTS = ['#fef08a', '#fed7aa', '#bbf7d0', '#bfdbfe', '#fbcfe8'];

const editor = useEditor({
    content: model.value ?? '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3, 4] },
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
            },
        }),
        TextStyle,
        Color,
        Highlight.configure({ multicolor: true }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Subscript,
        Superscript,
        Image.configure({ HTMLAttributes: { loading: 'lazy' } }),
        Youtube.configure({ nocookie: true, width: 640, height: 360 }),
        Table.configure({ resizable: false }),
        TableRow,
        TableHeader,
        TableCell,
        Placeholder.configure({ placeholder: props.placeholder }),
        CharacterCount,
    ],
    editorProps: {
        attributes: {
            id: props.id,
            role: 'textbox',
            'aria-multiline': 'true',
            'aria-label': props.label ?? 'Rich text',
            class: 'prose prose-sm sm:prose-base dark:prose-invert max-w-none min-h-64 px-4 py-3 focus:outline-none',
        },
        // Pasted or dropped image files are uploaded instead of embedded as data
        handlePaste: (_view, event) => uploadFrom(event.clipboardData?.files),
        handleDrop: (_view, event) =>
            uploadFrom((event as DragEvent).dataTransfer?.files),
    },
    onUpdate: ({ editor }) => {
        model.value = editor.isEmpty ? null : editor.getHTML();
    },
});

// Follow outside changes, e.g. the form resetting after a save
watch(model, (value) => {
    const current = editor.value?.isEmpty ? null : editor.value?.getHTML();
    if (editor.value && value !== current) {
        editor.value.commands.setContent(value ?? '', { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const is = (name: string, attributes?: Record<string, unknown>) =>
    editor.value?.isActive(name, attributes) ?? false;
const chain = () => editor.value!.chain().focus();

const blockType = computed({
    get: () => {
        for (const level of [2, 3, 4]) {
            if (is('heading', { level })) return `h${level}`;
        }
        return 'p';
    },
    set: (value: string) => {
        if (value === 'p') chain().setParagraph().run();
        else
            chain()
                .toggleHeading({ level: Number(value.slice(1)) as 2 | 3 | 4 })
                .run();
    },
});

const currentColor = computed(
    () => editor.value?.getAttributes('textStyle').color ?? null,
);

const openPrompt = (kind: 'link' | 'image' | 'youtube') => {
    const value =
        kind === 'link' ? (editor.value?.getAttributes('link').href ?? '') : '';
    prompt.value = { kind, value };
};

const applyPrompt = () => {
    if (!prompt.value) return;
    const value = prompt.value.value.trim();

    if (prompt.value.kind === 'link') {
        if (value) {
            chain().extendMarkRange('link').setLink({ href: value }).run();
        } else {
            chain().extendMarkRange('link').unsetLink().run();
        }
    } else if (prompt.value.kind === 'image' && value) {
        chain().setImage({ src: value }).run();
    } else if (prompt.value.kind === 'youtube' && value) {
        chain().setYoutubeVideo({ src: value }).run();
    }
    prompt.value = null;
};

const xsrfToken = () =>
    decodeURIComponent(
        document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
    );

// Matches the server's validation in ResourceController::editorImage
const MAX_IMAGE_MB = 5;

const uploadImage = async (file: File) => {
    uploadError.value = null;
    if (file.size > MAX_IMAGE_MB * 1024 * 1024) {
        uploadError.value = `${file.name} is ${(file.size / 1024 / 1024).toFixed(1)} MB; images can be at most ${MAX_IMAGE_MB} MB.`;
        return;
    }

    uploading.value = true;
    try {
        const body = new FormData();
        body.append('image', file);
        const response = await fetch(props.uploadUrl, {
            method: 'POST',
            body,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
        });
        if (response.status === 413) {
            throw new Error(
                'the image is larger than the web server accepts. Raise the upload limit (in Herd: Settings → PHP → Max File Upload Size) or use a smaller image.',
            );
        }
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(
                data.errors?.image?.[0] ??
                    data.message ??
                    `the server responded with ${response.status}.`,
            );
        }
        chain()
            .setImage({ src: data.url, alt: file.name.replace(/\.[^.]+$/, '') })
            .run();
    } catch (error) {
        uploadError.value = `Image upload failed: ${(error as Error).message}`;
    } finally {
        uploading.value = false;
    }
};

const uploadFrom = (files?: FileList | null) => {
    const images = Array.from(files ?? []).filter((file) =>
        file.type.startsWith('image/'),
    );
    images.forEach(uploadImage);
    return images.length > 0;
};

const pickImage = (event: Event) => {
    uploadFrom((event.target as HTMLInputElement).files);
    (event.target as HTMLInputElement).value = '';
};

const toggleSource = () => {
    if (!editor.value) return;
    if (sourceMode.value) {
        editor.value.commands.setContent(source.value, { emitUpdate: true });
    } else {
        source.value = editor.value.getHTML();
    }
    sourceMode.value = !sourceMode.value;
};

const words = computed(() => editor.value?.storage.characterCount.words() ?? 0);
</script>

<template>
    <div
        :class="[
            'overflow-hidden rounded-md border border-input bg-background shadow-xs focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50',
            fullscreen && 'fixed inset-4 z-50 flex flex-col',
        ]"
    >
        <div
            v-if="editor"
            class="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 border-b border-input bg-muted/60 p-1.5 backdrop-blur"
            role="toolbar"
            aria-label="Formatting"
        >
            <template v-if="!sourceMode">
                <EditorButton
                    :icon="Undo2"
                    label="Undo"
                    :disabled="!editor.can().undo()"
                    @click="chain().undo().run()"
                />
                <EditorButton
                    :icon="Redo2"
                    label="Redo"
                    :disabled="!editor.can().redo()"
                    @click="chain().redo().run()"
                />
                <span class="mx-1 h-6 w-px bg-border" />

                <select
                    v-model="blockType"
                    aria-label="Text style"
                    class="h-8 rounded-md border border-input bg-background px-2 text-sm"
                >
                    <option value="p">Paragraph</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                </select>
                <span class="mx-1 h-6 w-px bg-border" />

                <EditorButton
                    :icon="Bold"
                    label="Bold"
                    :active="is('bold')"
                    @click="chain().toggleBold().run()"
                />
                <EditorButton
                    :icon="Italic"
                    label="Italic"
                    :active="is('italic')"
                    @click="chain().toggleItalic().run()"
                />
                <EditorButton
                    :icon="UnderlineIcon"
                    label="Underline"
                    :active="is('underline')"
                    @click="chain().toggleUnderline().run()"
                />
                <EditorButton
                    :icon="Strikethrough"
                    label="Strikethrough"
                    :active="is('strike')"
                    @click="chain().toggleStrike().run()"
                />
                <EditorButton
                    :icon="Code"
                    label="Inline code"
                    :active="is('code')"
                    @click="chain().toggleCode().run()"
                />
                <EditorButton
                    :icon="SubscriptIcon"
                    label="Subscript"
                    :active="is('subscript')"
                    @click="chain().toggleSubscript().run()"
                />
                <EditorButton
                    :icon="SuperscriptIcon"
                    label="Superscript"
                    :active="is('superscript')"
                    @click="chain().toggleSuperscript().run()"
                />
                <span class="mx-1 h-6 w-px bg-border" />

                <!-- Text colour -->
                <div
                    class="flex items-center gap-0.5"
                    role="group"
                    aria-label="Text colour"
                >
                    <button
                        v-for="color in BRAND_COLORS"
                        :key="color.name"
                        type="button"
                        :title="`Text colour: ${color.name}`"
                        :aria-label="`Text colour: ${color.name}`"
                        :aria-pressed="currentColor === color.value"
                        :class="[
                            'size-5 rounded-full border',
                            currentColor === color.value
                                ? 'ring-2 ring-ring ring-offset-1'
                                : 'border-input',
                        ]"
                        :style="{
                            background:
                                color.value ??
                                'linear-gradient(135deg, #fff 45%, #ef4444 45% 55%, #fff 55%)',
                        }"
                        @mousedown.prevent
                        @click="
                            color.value
                                ? chain().setColor(color.value).run()
                                : chain().unsetColor().run()
                        "
                    />
                </div>
                <span class="mx-1 h-6 w-px bg-border" />

                <!-- Highlight -->
                <EditorButton
                    :icon="Highlighter"
                    label="Highlight"
                    :active="is('highlight')"
                    @click="
                        chain().toggleHighlight({ color: HIGHLIGHTS[0] }).run()
                    "
                />
                <button
                    v-for="color in HIGHLIGHTS.slice(1)"
                    :key="color"
                    type="button"
                    :title="`Highlight ${color}`"
                    :aria-label="`Highlight colour ${color}`"
                    class="size-4 rounded border border-input"
                    :style="{ background: color }"
                    @mousedown.prevent
                    @click="chain().toggleHighlight({ color }).run()"
                />
                <span class="mx-1 h-6 w-px bg-border" />

                <EditorButton
                    :icon="AlignLeft"
                    label="Align left"
                    :active="editor.isActive({ textAlign: 'left' })"
                    @click="chain().setTextAlign('left').run()"
                />
                <EditorButton
                    :icon="AlignCenter"
                    label="Align centre"
                    :active="editor.isActive({ textAlign: 'center' })"
                    @click="chain().setTextAlign('center').run()"
                />
                <EditorButton
                    :icon="AlignRight"
                    label="Align right"
                    :active="editor.isActive({ textAlign: 'right' })"
                    @click="chain().setTextAlign('right').run()"
                />
                <EditorButton
                    :icon="AlignJustify"
                    label="Justify"
                    :active="editor.isActive({ textAlign: 'justify' })"
                    @click="chain().setTextAlign('justify').run()"
                />
                <span class="mx-1 h-6 w-px bg-border" />

                <EditorButton
                    :icon="List"
                    label="Bullet list"
                    :active="is('bulletList')"
                    @click="chain().toggleBulletList().run()"
                />
                <EditorButton
                    :icon="ListOrdered"
                    label="Numbered list"
                    :active="is('orderedList')"
                    @click="chain().toggleOrderedList().run()"
                />
                <EditorButton
                    :icon="Quote"
                    label="Quote"
                    :active="is('blockquote')"
                    @click="chain().toggleBlockquote().run()"
                />
                <EditorButton
                    :icon="Code2"
                    label="Code block"
                    :active="is('codeBlock')"
                    @click="chain().toggleCodeBlock().run()"
                />
                <EditorButton
                    :icon="Minus"
                    label="Divider"
                    @click="chain().setHorizontalRule().run()"
                />
                <span class="mx-1 h-6 w-px bg-border" />

                <EditorButton
                    :icon="Link2"
                    label="Link"
                    :active="is('link')"
                    @click="openPrompt('link')"
                />
                <EditorButton
                    v-if="is('link')"
                    :icon="Link2Off"
                    label="Remove link"
                    @click="chain().extendMarkRange('link').unsetLink().run()"
                />
                <EditorButton
                    :icon="ImagePlus"
                    :label="uploading ? 'Uploading image…' : 'Image'"
                    :disabled="uploading"
                    @click="openPrompt('image')"
                />
                <EditorButton
                    :icon="YoutubeIcon"
                    label="YouTube video"
                    @click="openPrompt('youtube')"
                />
                <EditorButton
                    :icon="TableIcon"
                    label="Insert table"
                    :disabled="is('table')"
                    @click="
                        chain()
                            .insertTable({
                                rows: 3,
                                cols: 3,
                                withHeaderRow: true,
                            })
                            .run()
                    "
                />
                <span class="mx-1 h-6 w-px bg-border" />

                <EditorButton
                    :icon="Eraser"
                    label="Clear formatting"
                    @click="chain().unsetAllMarks().clearNodes().run()"
                />
            </template>

            <div class="ml-auto flex items-center gap-0.5">
                <EditorButton
                    :icon="FileCode2"
                    :label="sourceMode ? 'Back to editor' : 'Edit HTML'"
                    :active="sourceMode"
                    @click="toggleSource"
                />
                <EditorButton
                    :icon="fullscreen ? Minimize2 : Maximize2"
                    :label="fullscreen ? 'Exit full screen' : 'Full screen'"
                    @click="fullscreen = !fullscreen"
                />
            </div>
        </div>

        <!-- Table tools, shown while the cursor is in a table -->
        <div
            v-if="editor && !sourceMode && is('table')"
            class="flex flex-wrap items-center gap-1 border-b border-input bg-muted/30 px-2 py-1 text-xs"
            role="toolbar"
            aria-label="Table"
        >
            <span class="mr-1 font-medium text-muted-foreground">Table:</span>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().addRowBefore().run()"
            >
                <Rows3 class="mr-1 inline size-3.5" />Row above
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().addRowAfter().run()"
            >
                <Rows3 class="mr-1 inline size-3.5" />Row below
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().deleteRow().run()"
            >
                Delete row
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().addColumnBefore().run()"
            >
                <Columns3 class="mr-1 inline size-3.5" />Column left
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().addColumnAfter().run()"
            >
                <Columns3 class="mr-1 inline size-3.5" />Column right
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().deleteColumn().run()"
            >
                Delete column
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent"
                @mousedown.prevent
                @click="chain().toggleHeaderRow().run()"
            >
                Header row
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent disabled:opacity-40"
                :disabled="!editor.can().mergeCells()"
                @mousedown.prevent
                @click="chain().mergeCells().run()"
            >
                <TableCellsMerge class="mr-1 inline size-3.5" />Merge
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 hover:bg-accent disabled:opacity-40"
                :disabled="!editor.can().splitCell()"
                @mousedown.prevent
                @click="chain().splitCell().run()"
            >
                <TableCellsSplit class="mr-1 inline size-3.5" />Split
            </button>
            <button
                type="button"
                class="ml-auto rounded px-2 py-1 text-destructive hover:bg-destructive/10"
                @mousedown.prevent
                @click="chain().deleteTable().run()"
            >
                <Trash2 class="mr-1 inline size-3.5" />Delete table
            </button>
        </div>

        <!-- Link / image / video input -->
        <form
            v-if="prompt"
            class="flex flex-wrap items-center gap-2 border-b border-input bg-muted/30 px-3 py-2"
            @submit.prevent="applyPrompt"
        >
            <label :for="`${id}-prompt`" class="text-sm font-medium">
                {{
                    {
                        link: 'Link URL',
                        image: 'Image URL',
                        youtube: 'YouTube URL',
                    }[prompt.kind]
                }}
            </label>
            <input
                :id="`${id}-prompt`"
                v-model="prompt.value"
                type="url"
                :placeholder="
                    prompt.kind === 'youtube'
                        ? 'https://www.youtube.com/watch?v=…'
                        : 'https://…'
                "
                class="h-8 min-w-48 flex-1 rounded-md border border-input bg-background px-2 text-sm"
                autofocus
                @keydown.escape.prevent="prompt = null"
            />
            <button
                type="submit"
                class="h-8 rounded-md bg-primary px-3 text-sm text-primary-foreground hover:bg-primary/90"
            >
                {{
                    prompt.kind === 'link' && !prompt.value
                        ? 'Remove link'
                        : 'Insert'
                }}
            </button>
            <template v-if="prompt.kind === 'image'">
                <span class="text-sm text-muted-foreground">or</span>
                <button
                    type="button"
                    class="h-8 rounded-md border border-input px-3 text-sm hover:bg-accent"
                    @click="
                        fileInput?.click();
                        prompt = null;
                    "
                >
                    Upload from computer
                </button>
            </template>
            <button
                type="button"
                class="h-8 rounded-md px-2 text-sm text-muted-foreground hover:text-foreground"
                @click="prompt = null"
            >
                Cancel
            </button>
        </form>
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            multiple
            class="hidden"
            @change="pickImage"
        />

        <p
            v-if="uploadError"
            class="border-b border-input bg-destructive/10 px-3 py-1.5 text-sm text-destructive"
            role="alert"
        >
            {{ uploadError }}
        </p>

        <div
            :class="['overflow-y-auto', fullscreen ? 'flex-1' : 'max-h-[70vh]']"
        >
            <textarea
                v-if="sourceMode"
                v-model="source"
                :aria-label="`HTML source`"
                spellcheck="false"
                class="block min-h-64 w-full resize-y bg-background px-4 py-3 font-mono text-xs leading-relaxed outline-none"
                :class="fullscreen && 'h-full'"
            />
            <EditorContent v-else :editor="editor" />
        </div>

        <div
            class="flex items-center justify-between border-t border-input bg-muted/30 px-3 py-1 text-xs text-muted-foreground"
        >
            <span>
                <template v-if="uploading">Uploading image…</template>
                <template v-else-if="sourceMode"
                    >Editing HTML: switch back to see the result.</template
                >
                <template v-else>Paste or drop images to upload them.</template>
            </span>
            <span class="tabular-nums">{{ words }} words</span>
        </div>
    </div>
</template>

<style>
/* Editor-only details that prose doesn't cover */
.tiptap p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
    color: var(--muted-foreground);
}
.tiptap table td,
.tiptap table th {
    border: 1px solid var(--border);
    padding: 0.35rem 0.5rem;
    vertical-align: top;
}
.tiptap table .selectedCell {
    background: color-mix(in oklab, var(--primary) 12%, transparent);
}
.tiptap img.ProseMirror-selectednode,
.tiptap div[data-youtube-video].ProseMirror-selectednode {
    outline: 3px solid var(--ring);
}
.tiptap div[data-youtube-video] iframe {
    aspect-ratio: 16 / 9;
    width: 100%;
    height: auto;
    border-radius: 0.5rem;
}
</style>
