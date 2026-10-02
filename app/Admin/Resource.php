<?php

namespace App\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Describes how one database table is managed in the admin.
 */
abstract class Resource
{
    /** @var class-string<Model> */
    public static string $model;

    /** URL segment, e.g. "articles". */
    public static string $key;

    public static string $label;

    public static string $singular;

    /** Lucide icon name for the navigation. */
    public static string $icon = 'Database';

    /** Navigation group. */
    public static string $group = 'Content';

    /** Columns searched by the list search box. */
    public static array $search = ['name'];

    /** Relations eager-loaded for the list. */
    public static array $with = [];

    public static string $sort = 'id';

    public static string $direction = 'desc';

    /** Whether records can be created and edited (false for logs). */
    public static bool $editable = true;

    public static bool $creatable = true;

    /** @return Field[] */
    abstract public function fields(): array;

    /** @return Column[] */
    abstract public function columns(): array;

    /**
     * Dropdown filters for the list, as [column => [value => label]].
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Apply one list filter; override for filters on relations.
     */
    public function applyFilter(Builder $query, string $name, mixed $value): void
    {
        $query->where($name, $value);
    }

    /**
     * One-click row actions, as [key => ['label' => ..., 'attributes' => [...]]].
     */
    public function actions(): array
    {
        return [];
    }

    public function query(): Builder
    {
        return static::$model::query()->with(static::$with);
    }

    public function newModel(): Model
    {
        return new static::$model;
    }

    public function title(Model $model): string
    {
        return $model->name ?? $model->title ?? static::$singular . ' #' . $model->getKey();
    }

    /**
     * Link to the record on the public site, if it has a page.
     */
    public function publicUrl(Model $model): ?string
    {
        return null;
    }

    public function canDelete(Model $model): bool
    {
        return true;
    }

    /**
     * Hook for side effects after a record is saved.
     */
    public function saved(Model $model, bool $created): void {}

    public function meta(): array
    {
        return [
            'key' => static::$key,
            'label' => static::$label,
            'singular' => static::$singular,
            'icon' => static::$icon,
            'group' => static::$group,
            'editable' => static::$editable,
            'creatable' => static::$creatable && static::$editable,
        ];
    }
}
