export interface ResourceMeta {
    key: string;
    label: string;
    singular: string;
    icon: string;
    group: string;
    editable: boolean;
    creatable: boolean;
}

export interface NavGroup {
    label: string;
    items: (ResourceMeta & { href: string })[];
}

export interface Choice {
    value: string | number;
    label: string;
    group?: string;
}

export type FieldType =
    | 'text'
    | 'email'
    | 'url'
    | 'password'
    | 'slug'
    | 'textarea'
    | 'html'
    | 'number'
    | 'decimal'
    | 'boolean'
    | 'select'
    | 'datetime'
    | 'list'
    | 'image'
    | 'gallery'
    | 'belongsTo'
    | 'belongsToMany'
    | 'morphTo';

export interface FieldDef {
    type: FieldType;
    name: string;
    label: string;
    required: boolean;
    wide: boolean;
    help: string | null;
    placeholder: string | null;
    from: string | null;
    choices: Choice[];
    // Too many choices to send: search them at optionsUrl instead
    remote: boolean;
    optionsUrl?: string;
}

export interface ImageValue {
    path: string;
    url: string;
}

export interface GalleryImage {
    id: number;
    url: string;
    is_featured: boolean;
}

export interface Column {
    name: string;
    label: string;
    type: 'text' | 'badge' | 'boolean' | 'image' | 'date' | 'number';
    sortable: boolean;
}

export interface Filter {
    name: string;
    label: string;
    options: { value: string; label: string }[];
}

export interface RecordRow {
    id: number;
    title: string;
    cells: Record<string, unknown>;
    publicUrl: string | null;
    canDelete: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}
