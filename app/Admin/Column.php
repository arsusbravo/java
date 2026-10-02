<?php

namespace App\Admin;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * A column in an admin resource's list table.
 *
 * Types: text, badge, boolean, image, date, number.
 */
class Column
{
    public bool $sortable = false;

    public ?Closure $value = null;

    public function __construct(
        public string $name,
        public string $label,
        public string $type = 'text',
    ) {}

    public static function make(string $name, ?string $label = null, string $type = 'text'): static
    {
        return new static($name, $label ?? str($name)->afterLast('.')->headline()->toString(), $type);
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    /** Compute the cell value instead of reading the attribute. */
    public function value(Closure $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function resolve(Model $model): mixed
    {
        $value = $this->value ? ($this->value)($model) : data_get($model, $this->name);

        return match ($this->type) {
            'date' => $value?->format('Y-m-d H:i'),
            'boolean' => (bool) $value,
            'image' => image_url($value),
            default => $value,
        };
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
        ];
    }
}
