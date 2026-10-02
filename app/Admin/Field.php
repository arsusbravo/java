<?php

namespace App\Admin;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * A form field on an admin resource.
 *
 * Types: text, email, url, password, slug, textarea, html, number, decimal,
 * boolean, select, datetime, list, image, gallery, belongsTo, belongsToMany, morphTo.
 */
class Field
{
    public bool $required = false;

    public bool $unique = false;

    public bool $wide = false;

    public array $rules = [];

    public array $options = [];

    public mixed $default = null;

    public ?string $help = null;

    public ?string $placeholder = null;

    /** Source field for slugs. */
    public ?string $from = null;

    /** Related model for belongsTo / belongsToMany. */
    public ?string $related = null;

    public string $relatedLabel = 'name';

    /** Relation method name for belongsToMany (defaults to the field name). */
    public ?string $relation = null;

    /** Allowed models for morphTo, keyed by alias. */
    public array $morphTypes = [];

    public function __construct(
        public string $type,
        public string $name,
        public string $label,
    ) {}

    public static function make(string $type, string $name, ?string $label = null): static
    {
        return new static($type, $name, $label ?? str($name)->replace('_id', '')->headline()->toString());
    }

    public static function text(string $name, ?string $label = null): static
    {
        return static::make('text', $name, $label);
    }

    public static function email(string $name = 'email', ?string $label = null): static
    {
        return static::make('email', $name, $label);
    }

    public static function url(string $name, ?string $label = null): static
    {
        return static::make('url', $name, $label);
    }

    /** Hashed by the model cast; left unchanged when empty on update. */
    public static function password(string $name = 'password', ?string $label = null): static
    {
        return static::make('password', $name, $label);
    }

    public static function textarea(string $name, ?string $label = null): static
    {
        return static::make('textarea', $name, $label)->wide();
    }

    public static function html(string $name, ?string $label = null): static
    {
        return static::make('html', $name, $label)->wide();
    }

    public static function number(string $name, ?string $label = null): static
    {
        return static::make('number', $name, $label);
    }

    public static function decimal(string $name, ?string $label = null): static
    {
        return static::make('decimal', $name, $label);
    }

    public static function boolean(string $name, ?string $label = null): static
    {
        return static::make('boolean', $name, $label)->default(false);
    }

    public static function select(string $name, array $options, ?string $label = null): static
    {
        return static::make('select', $name, $label)->options($options);
    }

    public static function datetime(string $name, ?string $label = null): static
    {
        return static::make('datetime', $name, $label);
    }

    /** A JSON array edited as one item per line. */
    public static function list(string $name, ?string $label = null): static
    {
        return static::make('list', $name, $label);
    }

    public static function slug(string $name = 'slug', string $from = 'name'): static
    {
        $field = static::make('slug', $name, 'Slug')->unique();
        $field->from = $from;
        $field->help = 'Leave empty to generate from ' . str($from)->headline()->lower() . '.';

        return $field;
    }

    /** A single image stored in a column (path on the public disk or public/). */
    public static function image(string $name, ?string $label = null): static
    {
        return static::make('image', $name, $label)->wide();
    }

    /** The model's morphMany `images` relation. */
    public static function gallery(string $name = 'images', ?string $label = 'Gallery'): static
    {
        return static::make('gallery', $name, $label)->wide();
    }

    public static function belongsTo(string $name, string $related, ?string $label = null, string $relatedLabel = 'name'): static
    {
        $field = static::make('belongsTo', $name, $label);
        $field->related = $related;
        $field->relatedLabel = $relatedLabel;

        return $field;
    }

    public static function belongsToMany(string $name, string $related, ?string $label = null, string $relatedLabel = 'name'): static
    {
        $field = static::make('belongsToMany', $name, $label)->wide();
        $field->related = $related;
        $field->relatedLabel = $relatedLabel;
        $field->default = [];

        return $field;
    }

    /** A polymorphic owner, e.g. `reviewable` (stores {name}_type and {name}_id). */
    public static function morphTo(string $name, array $types, ?string $label = null): static
    {
        $field = static::make('morphTo', $name, $label);
        $field->morphTypes = $types;

        return $field;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function unique(bool $unique = true): static
    {
        $this->unique = $unique;

        return $this;
    }

    public function wide(bool $wide = true): static
    {
        $this->wide = $wide;

        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function help(string $help): static
    {
        $this->help = $help;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Whether the field maps directly to a column on the model.
     */
    public function isAttribute(): bool
    {
        return ! in_array($this->type, ['image', 'gallery', 'belongsToMany', 'morphTo', 'password']);
    }

    /**
     * Validation rules for this field, keyed by input name.
     */
    public function validationRules(Model $model): array
    {
        $presence = $this->required ? 'required' : 'nullable';

        $rules = match ($this->type) {
            'text' => [$this->name => [$presence, 'string', 'max:255']],
            // Indexed columns are 191 characters (AppServiceProvider), for MariaDB 5.5's index limit
            'slug' => [$this->name => [$presence, 'string', 'max:191']],
            'email' => [$this->name => [$presence, 'email', 'max:191']],
            'url' => [$this->name => [$presence, 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail) {
                // Allow placeholders like {affiliate_id}, which are filled in when the link is used
                if (! filter_var(preg_replace('/\{\w+\}/', 'x', $value), FILTER_VALIDATE_URL)) {
                    $fail('The :attribute field must be a valid URL.');
                }
            }]],
            'password' => [$this->name => [$model->exists ? 'nullable' : 'required', 'string', 'min:8']],
            'textarea', 'html' => [$this->name => [$presence, 'string']],
            'number' => [$this->name => [$presence, 'integer']],
            'decimal' => [$this->name => [$presence, 'numeric']],
            'boolean' => [$this->name => ['boolean']],
            'select' => [$this->name => [$presence, Rule::in(array_keys($this->options))]],
            'datetime' => [$this->name => [$presence, 'date']],
            'list' => [
                $this->name => [$presence, 'array'],
                "{$this->name}.*" => ['string', 'max:255'],
            ],
            'image' => [
                "{$this->name}_upload" => [
                    $this->required && ! $model->getAttribute($this->name) ? 'required' : 'nullable',
                    'image',
                    'max:5120',
                ],
                "{$this->name}_remove" => ['boolean'],
            ],
            'gallery' => [
                "{$this->name}_new" => ['array'],
                "{$this->name}_new.*" => ['image', 'max:5120'],
                "{$this->name}_remove" => ['array'],
                "{$this->name}_remove.*" => ['integer'],
                "{$this->name}_featured" => ['nullable', 'integer'],
            ],
            'belongsTo' => [$this->name => [$presence, 'integer', Rule::exists((new $this->related)->getTable(), 'id')]],
            'belongsToMany' => [
                $this->name => ['array'],
                "{$this->name}.*" => ['integer', Rule::exists((new $this->related)->getTable(), 'id')],
            ],
            'morphTo' => [$this->name => [$presence, 'string', 'regex:/^(' . implode('|', array_keys($this->morphTypes)) . '):\d+$/']],
        };

        if ($this->unique) {
            $rules[$this->name][] = Rule::unique($model->getTable(), $this->name)->ignore($model->getKey());
        }

        if ($this->rules) {
            $rules[$this->name] = [...$rules[$this->name], ...$this->rules];
        }

        return $rules;
    }

    /**
     * The value shown in the form for this field.
     */
    public function formValue(Model $model): mixed
    {
        if (! $model->exists) {
            return match ($this->type) {
                'list' => implode("\n", $this->default ?? []),
                'image' => null,
                'gallery' => [],
                'password' => '',
                default => $this->default,
            };
        }

        return match ($this->type) {
            'datetime' => $model->getAttribute($this->name)?->format('Y-m-d\TH:i'),
            'list' => implode("\n", (array) $model->getAttribute($this->name)),
            // Older plain-text values open in the editor as paragraphs
            'html' => RichText::toHtml($model->getAttribute($this->name)) ?: null,
            'boolean' => (bool) $model->getAttribute($this->name),
            'password' => '',
            'image' => $model->getAttribute($this->name)
                ? ['path' => $model->getAttribute($this->name), 'url' => image_url($model->getAttribute($this->name))]
                : null,
            'gallery' => $model->{$this->name}()->orderBy('order')->get()->map(fn ($image) => [
                'id' => $image->id,
                'url' => image_url($image->path),
                'is_featured' => $image->is_featured,
            ])->all(),
            'belongsToMany' => $model->{$this->relationName()}()->pluck($model->{$this->relationName()}()->getRelated()->getQualifiedKeyName())->all(),
            'morphTo' => $model->getAttribute("{$this->name}_type")
                ? array_search($model->getAttribute("{$this->name}_type"), $this->morphTypes) . ':' . $model->getAttribute("{$this->name}_id")
                : null,
            default => $model->getAttribute($this->name),
        };
    }

    /** Relations with more rows than this are searched on the server instead of sent in full. */
    public const MAX_INLINE_CHOICES = 200;

    /**
     * Choices for select-like fields, as [{value, label, group?}].
     *
     * Large relations only include the model's current selection; the form
     * searches the rest through ResourceController::fieldOptions().
     */
    public function choices(?Model $model = null): array
    {
        if ($this->isRemote()) {
            return $model?->exists ? $this->selectedChoices($model) : [];
        }

        return match ($this->type) {
            'select' => collect($this->options)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all(),
            'belongsTo', 'belongsToMany', 'morphTo' => $this->search(null, PHP_INT_MAX),
            default => [],
        };
    }

    /**
     * Whether choices are too many to send with the form.
     */
    public function isRemote(): bool
    {
        return match ($this->type) {
            'belongsTo', 'belongsToMany' => $this->related::count() > self::MAX_INLINE_CHOICES,
            'morphTo' => collect($this->morphTypes)->sum(fn ($class) => $class::count()) > self::MAX_INLINE_CHOICES,
            default => false,
        };
    }

    /**
     * Related records matching a search term, as choices.
     */
    public function search(?string $term, int $limit = 50): array
    {
        $types = $this->type === 'morphTo' ? $this->morphTypes : [null => $this->related];

        return collect($types)->flatMap(function ($class, $alias) use ($term, $limit) {
            $label = $this->labelColumn($class);

            return $class::query()
                ->when(filled($term), fn ($query) => $query->where($label, 'like', '%' . $term . '%'))
                ->orderBy($label)
                ->limit($limit)
                ->get(['id', $label])
                ->map(fn ($related) => $this->choice($related, $alias ?: null, $label));
        })->take($limit)->values()->all();
    }

    /**
     * The model's currently selected records, so the form can show their labels.
     */
    protected function selectedChoices(Model $model): array
    {
        return match ($this->type) {
            'belongsTo' => array_filter([
                ($related = $this->related::find($model->getAttribute($this->name)))
                    ? $this->choice($related, null, $this->labelColumn($this->related))
                    : null,
            ]),
            'belongsToMany' => $model->{$this->relationName()}()
                ->get()
                ->map(fn ($related) => $this->choice($related, null, $this->labelColumn($this->related)))
                ->all(),
            'morphTo' => array_filter([
                ($related = $model->{$this->name})
                    ? $this->choice($related, array_search($related::class, $this->morphTypes), $this->labelColumn($related::class))
                    : null,
            ]),
            default => [],
        };
    }

    protected function choice(Model $related, ?string $alias, string $label): array
    {
        return $alias
            ? ['value' => "{$alias}:{$related->getKey()}", 'label' => $related->{$label}, 'group' => str($alias)->headline()->toString()]
            : ['value' => $related->getKey(), 'label' => $related->{$label}];
    }

    protected function labelColumn(string $class): string
    {
        if ($class === $this->related) {
            return $this->relatedLabel;
        }

        return in_array('name', (new $class)->getFillable()) ? 'name' : 'title';
    }

    public function relationName(): string
    {
        return $this->relation ?? $this->name;
    }

    /**
     * Serialize for the Vue form.
     */
    public function toArray(?Model $model = null): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'label' => $this->label,
            'required' => $this->required,
            'wide' => $this->wide,
            'help' => $this->help,
            'placeholder' => $this->placeholder,
            'from' => $this->from,
            'choices' => $this->choices($model),
            'remote' => $this->isRemote(),
        ];
    }
}
