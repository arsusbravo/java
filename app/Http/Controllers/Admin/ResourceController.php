<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Admin;
use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    /**
     * List records with search, filters, sorting and pagination.
     */
    public function index(Request $request, string $resource): Response
    {
        $resource = $this->resource($resource);
        $query = $resource->query();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request, $resource) {
                foreach ($resource::$search as $column) {
                    $query->orWhere($column, 'like', '%' . $request->search . '%');
                }
            });
        }

        $filters = $resource->filters();
        foreach (array_keys($filters) as $column) {
            if ($request->filled($column)) {
                $resource->applyFilter($query, $column, $request->input($column));
            }
        }

        $columns = $resource->columns();
        $sortable = collect($columns)->filter->sortable->pluck('name')->push($resource::$sort)->all();
        $sort = in_array($request->sort, $sortable) ? $request->sort : $resource::$sort;
        $direction = in_array($request->direction, ['asc', 'desc'])
            ? $request->direction
            : ($sort === $resource::$sort ? $resource::$direction : 'asc');

        $records = $query->orderBy($sort, $direction)
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Model $model) => [
                'id' => $model->getKey(),
                'title' => $resource->title($model),
                'cells' => collect($columns)->mapWithKeys(fn (Column $column) => [$column->name => $column->resolve($model)])->all(),
                'publicUrl' => $resource->publicUrl($model),
                'canDelete' => $resource->canDelete($model),
            ]);

        return Inertia::render('Admin/Resources/Index', [
            'resource' => $resource->meta(),
            'columns' => array_map(fn (Column $column) => $column->toArray(), $columns),
            'filters' => collect($filters)->map(fn ($options, $column) => [
                'name' => $column,
                'label' => str($column)->replace(['_id', '_type'], '')->headline()->toString(),
                'options' => collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all(),
            ])->values()->all(),
            'actions' => collect($resource->actions())->map(fn ($action, $key) => [
                'key' => $key,
                'label' => $action['label'],
                'attributes' => $action['attributes'],
            ])->values()->all(),
            'records' => $records,
            'query' => [
                'search' => $request->search,
                'sort' => $sort,
                'direction' => $direction,
                ...$request->only(array_keys($filters)),
            ],
        ]);
    }

    public function create(string $resource): Response
    {
        $resource = $this->editableResource($resource, creating: true);

        return $this->form($resource, $resource->newModel());
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $resource = $this->editableResource($resource, creating: true);
        $model = $this->save($request, $resource, $resource->newModel());

        return redirect("/admin/{$resource::$key}/{$model->getKey()}/edit")
            ->with('success', "{$resource::$singular} created.");
    }

    public function edit(string $resource, string $id): Response
    {
        $resource = $this->editableResource($resource);

        return $this->form($resource, $resource->query()->findOrFail($id));
    }

    public function update(Request $request, string $resource, string $id): RedirectResponse
    {
        $resource = $this->editableResource($resource);
        $this->save($request, $resource, $resource->query()->findOrFail($id));

        return back()->with('success', "{$resource::$singular} saved.");
    }

    public function destroy(string $resource, string $id): RedirectResponse
    {
        $resource = $this->resource($resource);
        $model = $resource->query()->findOrFail($id);

        if (! $resource->canDelete($model)) {
            return back()->with('error', "This {$resource::$singular} can't be deleted.");
        }

        DB::transaction(function () use ($resource, $model) {
            foreach ($resource->fields() as $field) {
                match ($field->type) {
                    'image' => $this->deleteUpload($model->getAttribute($field->name)),
                    'gallery' => $model->{$field->name}->each(fn (Image $image) => $this->deleteImage($image)),
                    default => null,
                };
            }

            if (method_exists($model, 'tags')) {
                $model->tags()->detach();
            }

            $model->delete();
        });

        return redirect("/admin/{$resource::$key}")->with('success', "{$resource::$singular} deleted.");
    }

    /**
     * Apply a one-click row action, e.g. approve a review.
     */
    public function action(string $resource, string $id, string $action): RedirectResponse
    {
        $resource = $this->editableResource($resource);
        $definition = $resource->actions()[$action] ?? abort(404);

        $model = $resource->query()->findOrFail($id);
        $model->update($definition['attributes']);
        $resource->saved($model, false);

        return back()->with('success', "{$resource->title($model)}: {$definition['label']} done.");
    }

    /**
     * Store an image inserted in the rich text editor and return its URL.
     */
    public function editorImage(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:5120']]);

        $path = $request->file('image')->store('uploads/editor', 'public');

        // Root-relative, so content keeps working if the site moves to another domain
        return response()->json(['url' => '/storage/' . $path]);
    }

    /**
     * Search a large relation's records for a form field, e.g. hotels on an article.
     */
    public function fieldOptions(Request $request, string $resource, string $field): JsonResponse
    {
        $definition = collect($this->editableResource($resource)->fields())->firstWhere('name', $field);

        abort_unless($definition && in_array($definition->type, ['belongsTo', 'belongsToMany', 'morphTo']), 404);

        return response()->json($definition->search($request->string('search')->trim()->toString()));
    }

    protected function form(Resource $resource, Model $model): Response
    {
        $fields = $resource->fields();

        return Inertia::render('Admin/Resources/Form', [
            'resource' => $resource->meta(),
            'record' => $model->exists ? [
                'id' => $model->getKey(),
                'title' => $resource->title($model),
                'publicUrl' => $resource->publicUrl($model),
                'canDelete' => $resource->canDelete($model),
            ] : null,
            'fields' => array_map(fn (Field $field) => $field->toArray($model), $fields),
            'values' => collect($fields)->mapWithKeys(fn (Field $field) => [$field->name => $field->formValue($model)])->all(),
        ]);
    }

    /**
     * Validate the request and persist attributes, relations and uploads.
     */
    protected function save(Request $request, Resource $resource, Model $model): Model
    {
        $fields = $resource->fields();
        $this->prepare($request, $fields);

        $rules = collect($fields)->flatMap(fn (Field $field) => $field->validationRules($model))->all();
        $data = $request->validate($rules);
        $creating = ! $model->exists;

        DB::transaction(function () use ($request, $resource, $model, $fields, $data, $creating) {
            foreach ($fields as $field) {
                $this->fill($request, $resource, $model, $field, $data);
            }

            $model->save();

            foreach ($fields as $field) {
                if ($field->type === 'belongsToMany') {
                    $model->{$field->relationName()}()->sync($data[$field->name] ?? []);
                }

                if ($field->type === 'gallery') {
                    $this->saveGallery($request, $resource, $model, $field);
                }
            }

            $resource->saved($model, $creating);
        });

        return $model;
    }

    /**
     * Normalize input before validation: generate slugs and split list fields.
     */
    protected function prepare(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if ($field->type === 'slug' && blank($request->input($field->name))) {
                $request->merge([$field->name => Str::slug((string) $request->input($field->from)) ?: null]);
            }

            if ($field->type === 'html') {
                $request->merge([$field->name => RichText::sanitize($request->input($field->name))]);
            }

            if ($field->type === 'list' && ! is_array($request->input($field->name))) {
                $lines = preg_split('/\r\n|\r|\n/', (string) $request->input($field->name));
                $request->merge([$field->name => array_values(array_filter(array_map('trim', $lines), 'strlen'))]);
            }
        }
    }

    protected function fill(Request $request, Resource $resource, Model $model, Field $field, array $data): void
    {
        match ($field->type) {
            'boolean' => $model->setAttribute($field->name, $request->boolean($field->name)),
            'password' => filled($data[$field->name] ?? null) ? $model->setAttribute($field->name, $data[$field->name]) : null,
            'image' => $this->fillImage($request, $resource, $model, $field),
            'morphTo' => $this->fillMorph($model, $field, $data[$field->name] ?? null),
            'gallery', 'belongsToMany' => null,
            // Empty input falls back to the default, e.g. NOT NULL columns like currency
            default => $model->setAttribute($field->name, $data[$field->name] ?? $field->default),
        };
    }

    protected function fillImage(Request $request, Resource $resource, Model $model, Field $field): void
    {
        $current = $model->getAttribute($field->name);

        if ($request->hasFile("{$field->name}_upload")) {
            $this->deleteUpload($current);
            $model->setAttribute($field->name, $this->storeUpload($request->file("{$field->name}_upload"), $resource));
        } elseif ($request->boolean("{$field->name}_remove") && ! $field->required) {
            $this->deleteUpload($current);
            $model->setAttribute($field->name, null);
        }
    }

    protected function fillMorph(Model $model, Field $field, ?string $value): void
    {
        [$alias, $id] = $value ? explode(':', $value) : [null, null];

        $model->setAttribute("{$field->name}_type", $alias ? $field->morphTypes[$alias] : null);
        $model->setAttribute("{$field->name}_id", $id);
    }

    protected function saveGallery(Request $request, Resource $resource, Model $model, Field $field): void
    {
        // A fresh relation query each time: constraints added to one would leak into the next
        $images = fn () => $model->{$field->name}();

        $images()->whereKey($request->input("{$field->name}_remove", []))
            ->get()
            ->each(fn (Image $image) => $this->deleteImage($image));

        $order = (int) $images()->max('order');
        foreach ($request->file("{$field->name}_new", []) as $file) {
            $images()->create([
                'path' => $this->storeUpload($file, $resource),
                'alt_text' => $resource->title($model),
                'order' => ++$order,
            ]);
        }

        if ($request->filled("{$field->name}_featured")) {
            $images()->update(['is_featured' => false]);
            $images()->whereKey($request->input("{$field->name}_featured"))->update(['is_featured' => true]);
        }
    }

    protected function storeUpload(UploadedFile $file, Resource $resource): string
    {
        return $file->store("uploads/{$resource::$key}", 'public');
    }

    /**
     * Delete a file uploaded through the admin. Seeded files under public/ are left alone.
     */
    protected function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/')) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function deleteImage(Image $image): void
    {
        $this->deleteUpload($image->path);
        $image->delete();
    }

    protected function resource(string $key): Resource
    {
        return Admin::find($key) ?? abort(404);
    }

    protected function editableResource(string $key, bool $creating = false): Resource
    {
        $resource = $this->resource($key);

        abort_unless($resource::$editable && (! $creating || $resource::$creatable), 404);

        return $resource;
    }
}
