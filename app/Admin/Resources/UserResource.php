<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    public static string $model = Models\User::class;

    public static string $key = 'users';

    public static string $label = 'Users';

    public static string $singular = 'User';

    public static string $icon = 'Users';

    public static string $group = 'System';

    public static array $search = ['name', 'email'];

    public static string $sort = 'name';

    public static string $direction = 'asc';

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::email()->required()->unique(),
            Field::password()->help('Every user has full admin access. Leave empty to keep the current password.'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('name')->sortable(),
            Column::make('email')->sortable(),
            Column::make('articles', 'Articles', 'number')->value(fn ($user) => Models\Article::where('author_id', $user->id)->count()),
            Column::make('created_at', 'Joined', 'date')->sortable(),
        ];
    }

    public function canDelete(Model $model): bool
    {
        return $model->id !== auth()->id();
    }

    public function saved(Model $model, bool $created): void
    {
        // Admin-created accounts are trusted, so skip email verification
        if ($created && ! $model->email_verified_at) {
            $model->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
