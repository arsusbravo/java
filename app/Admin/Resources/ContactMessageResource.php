<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

class ContactMessageResource extends Resource
{
    public static string $model = Models\ContactMessage::class;

    public static string $key = 'contact-messages';

    public static string $label = 'Contact Messages';

    public static string $singular = 'Contact Message';

    public static string $icon = 'Mail';

    public static string $group = 'Media & Stats';

    public static array $search = ['name', 'email', 'subject', 'message'];

    public static string $sort = 'created_at';

    public static bool $creatable = false;

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::email('email')->required(),
            Field::text('subject'),
            Field::select('status', Models\ContactMessage::STATUSES)->required()->default('new'),
            Field::textarea('message')->required()->wide(),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('created_at', 'Received', 'date')->sortable(),
            Column::make('name')->sortable(),
            Column::make('email'),
            Column::make('message')->value(fn ($message) => str($message->subject ? "{$message->subject}: {$message->message}" : $message->message)->limit(80)->toString()),
            Column::make('status', 'Status', 'badge')->sortable(),
        ];
    }

    public function filters(): array
    {
        return ['status' => Models\ContactMessage::STATUSES];
    }

    public function actions(): array
    {
        return [
            'replied' => ['label' => 'Mark replied', 'attributes' => ['status' => 'replied']],
            'spam' => ['label' => 'Mark spam', 'attributes' => ['status' => 'spam']],
        ];
    }

    public function title(Model $model): string
    {
        return "Message from {$model->name}";
    }
}
